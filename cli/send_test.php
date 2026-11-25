<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Send one employee's Birthday Flyer emails to a test address.
 *
 * Added in v1.2.0 (25 November 2026) so CC, background images and layouts can be
 * checked in real mailboxes (Gmail, Outlook) on the live site without waiting for
 * 07:00 and without emailing the employee. The emails are built by the same code
 * as the scheduled task. Nothing is written to the delivery logs, so the real
 * morning email is not affected.
 *
 * Usage: php local/birthdayflyer/cli/send_test.php --empid=E1001 --to=me@example.com [--date=YYYY-MM-DD] [--withcc]
 *
 * @package    local_birthdayflyer
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    ['empid' => '', 'to' => '', 'date' => '', 'withcc' => false, 'help' => false],
    ['h' => 'help']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help'] || $options['empid'] === '' || $options['to'] === '') {
    cli_writeln(get_string('clisendtest_help', 'local_birthdayflyer'));
    exit($options['help'] ? 0 : 1);
}
if (!validate_email($options['to'])) {
    cli_error('Invalid --to address: ' . $options['to']);
}

$employee = $DB->get_record('local_birthdayflyer', ['empid' => $options['empid']]);
if (!$employee) {
    cli_error('No employee with empid ' . $options['empid']);
}

$timezone = core_date::get_server_timezone();
$daystamp = time();
if ($options['date'] !== '') {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $options['date'], $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        cli_error('--date must be YYYY-MM-DD');
    }
    $daystamp = make_timestamp((int)$m[1], (int)$m[2], (int)$m[3], 12, 0, 0, $timezone);
}

cli_writeln('Simulating ' . userdate($daystamp, '%Y-%m-%d', $timezone) . ' for ' . $employee->empfullname .
    ' (' . $employee->empid . '), sending to ' . $options['to'] . ($options['withcc'] ? ' + CC' : ''));

$task = new \local_birthdayflyer\task\daily_employee_digest();
$results = $task->send_test($employee, $options['to'], $daystamp, (bool)$options['withcc']);
if (!$results) {
    cli_writeln('Nothing to send for this employee on that day (no thought, birthday or anniversary).');
    exit(0);
}
$failed = false;
foreach ($results as [$type, $subject, $ok, $reason, $cc]) {
    cli_writeln(sprintf('  %-22s %-6s %s%s%s', $type, $ok ? 'SENT' : 'FAILED', $subject,
        $cc !== '' ? '  [CC: ' . $cc . ']' : '', $reason !== '' ? '  (' . $reason . ')' : ''));
    $failed = $failed || !$ok;
}
exit($failed ? 1 : 0);
