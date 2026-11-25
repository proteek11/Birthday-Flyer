<?php
// v1.2.0 (25 November 2026): these images are opened by mail clients and image
// proxies (e.g. Gmail), never by a logged-in browser session. NO_MOODLE_COOKIES
// stops Moodle starting a session for every image request. Access is still
// protected by the secret key checked below.
define('NO_MOODLE_COOKIES', true);
require('../../config.php');

$id = required_param('id', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUMEXT);

if ($id === 0) {
    $configuredkey = (string)get_config('local_birthdayflyer', 'thoughtimagekey');
    if ($configuredkey === '' || !hash_equals($configuredkey, (string)$key)) {
        http_response_code(404);
        exit;
    }
} else {
    $thought = $DB->get_record('local_birthdayflyer_thought', ['id' => $id], 'id,imagekey', IGNORE_MISSING);
    if (!$thought || empty($thought->imagekey) || !hash_equals((string)$thought->imagekey, (string)$key)) {
        http_response_code(404);
        exit;
    }
}

$files = get_file_storage()->get_area_files(
    context_system::instance()->id,
    'local_birthdayflyer',
    'thoughtimage',
    $id,
    'id ASC',
    false
);
if (!$files) {
    http_response_code(404);
    exit;
}

$file = reset($files);
$allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
if (!in_array($file->get_mimetype(), $allowed, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $file->get_mimetype());
header('Content-Length: ' . $file->get_filesize());
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $file->get_content();
exit;
