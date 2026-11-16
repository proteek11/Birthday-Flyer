<?php
namespace local_birthdayflyer\task;

defined('MOODLE_INTERNAL') || die();

/** Send the daily Thought of the Day and optional birthday template to employees. */
class daily_employee_digest extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('taskdailydigest', 'local_birthdayflyer');
    }

    public function execute(): void {
        global $DB;
        // Email categories are enabled by default to preserve existing behaviour
        // on upgrades where the new settings have not yet been saved.
        $sendthought = $this->is_email_type_enabled('send_thought_of_day');
        $sendemployeebirthday = $this->is_email_type_enabled('send_employee_birthday');
        $sendfamilybirthday = $this->is_email_type_enabled('send_family_birthday');
        $sendworkanniversary = $this->is_email_type_enabled('send_work_anniversary');
        $sendmarriageanniversary = $this->is_email_type_enabled('send_marriage_anniversary');
        $now = time();
        $timezone = \core_date::get_server_timezone();
        $today = usergetdate($now, $timezone);
        $todaykey = make_timestamp((int)$today['year'], (int)$today['mon'], (int)$today['mday'], 0, 0, 0, $timezone);
        $thought = $DB->get_record('local_birthdayflyer_thought', ['thoughtdate' => $todaykey]);
        $thoughttext = $thought ? trim((string)$thought->thought) : '';
        $employees = $DB->get_records('local_birthdayflyer', null, 'id ASC');
        mtrace(get_string('taskstarted', 'local_birthdayflyer', userdate($now)));
        if (!$employees) { mtrace(get_string('tasknoemployees', 'local_birthdayflyer')); return; }
        if ($thoughttext === '') { mtrace(get_string('taskmissingthought', 'local_birthdayflyer', userdate($todaykey, '%Y-%m-%d'))); }

        $sentcount = 0; $skippedcount = 0; $failedcount = 0;
        foreach ($employees as $employee) {
            // This daily log is checked once per employee; all applicable messages are sent
            // during this execution before the day is marked as processed.
            if ($DB->record_exists('local_birthdayflyer_sent', ['employeeid' => $employee->id, 'senddate' => $todaykey])) {
                $skippedcount++; continue;
            }
            $recipient = $this->get_recipient($employee);
            if (!$recipient) {
                mtrace(get_string('taskinvalidemail', 'local_birthdayflyer', $employee->empid));
                $failedcount++; continue;
            }
            $fullname = trim((string)($employee->empfullname ?? '')) ?: trim((string)$employee->empid);
            $birthdaymap = [
                'date_of_birth_stamp' => ['person' => get_string('birthdaypersonemployee', 'local_birthdayflyer'), 'relationship' => get_string('relationshipemployee', 'local_birthdayflyer')],
                'father_dob_stamp' => ['person' => get_string('birthdaypersonfather', 'local_birthdayflyer'), 'relationship' => get_string('relationshipfather', 'local_birthdayflyer')],
                'mother_dob_stamp' => ['person' => get_string('birthdaypersonmother', 'local_birthdayflyer'), 'relationship' => get_string('relationshipmother', 'local_birthdayflyer')],
                'wife_dob_stamp' => ['person' => get_string('birthdaypersonwife', 'local_birthdayflyer'), 'relationship' => get_string('relationshipwife', 'local_birthdayflyer')],
                'son_dob_stamp' => ['person' => get_string('birthdaypersonson', 'local_birthdayflyer'), 'relationship' => get_string('relationshipson', 'local_birthdayflyer')],
                'daughter_dob_stamp' => ['person' => get_string('birthdaypersondaughter', 'local_birthdayflyer'), 'relationship' => get_string('relationshipdaughter', 'local_birthdayflyer')],
            ];
            $birthdays = [];
            foreach ($birthdaymap as $field => $details) {
                if (empty($employee->{$field})) { continue; }
                $bd = usergetdate((int)$employee->{$field}, $timezone);
                if ((int)$bd['mon'] === (int)$today['mon'] && (int)$bd['mday'] === (int)$today['mday']) { $birthdays[] = $details; }
            }
            $workyears = 0;
            if (!empty($employee->date_of_joining_stamp)) {
                $jd = usergetdate((int)$employee->date_of_joining_stamp, $timezone);
                if ((int)$jd['mon'] === (int)$today['mon'] && (int)$jd['mday'] === (int)$today['mday']) { $workyears = max(0, (int)$today['year'] - (int)$jd['year']); }
            }
            $marriageyears = 0;
            if (!empty($employee->marriage_date_stamp)) {
                $md = usergetdate((int)$employee->marriage_date_stamp, $timezone);
                if ((int)$md['mon'] === (int)$today['mon'] && (int)$md['mday'] === (int)$today['mday']) { $marriageyears = max(0, (int)$today['year'] - (int)$md['year']); }
            }
            // Send the daily thought separately so an event template can never suppress it.
            $allok = true;
            if ($sendthought && $thoughttext !== '') {
                $subject = get_string('thoughtmailtitle', 'local_birthdayflyer');
                $html = $this->render_thought_html($thought, $thoughttext);
                $plain = get_string('thoughtmailtitle', 'local_birthdayflyer') . "\n\n" . $thoughttext;
                $allok = $this->send_message($recipient, $subject, $plain, $html, null) && $allok;
            }

            // Send employee and relationship birthdays through separate template types.
            // If both occur on the same day, they are sent as separate emails so each
            // category can use its own design and subject.
            $employeeBirthdays = [];
            $relationshipBirthdays = [];
            if ($birthdays) {
                $employeeBirthdays = [];
                $relationshipBirthdays = [];
                foreach ($birthdays as $birthday) {
                    if ($birthday['relationship'] === get_string('relationshipemployee', 'local_birthdayflyer')) {
                        $employeeBirthdays[] = $birthday;
                    } else {
                        $relationshipBirthdays[] = $birthday;
                    }
                }

                $birthdaygroups = [];
                if ($sendemployeebirthday) {
                    $birthdaygroups[] = ['type' => 'birthdayemployee', 'items' => $employeeBirthdays];
                }
                if ($sendfamilybirthday) {
                    $birthdaygroups[] = ['type' => 'birthdayrelationship', 'items' => $relationshipBirthdays];
                }
                foreach ($birthdaygroups as $birthdaygroup) {
                    if (!$birthdaygroup['items']) {
                        continue;
                    }
                    // Legacy 'birthday' remains a fallback so existing installations
                    // continue to work until the administrator creates the new templates.
                    $template = $this->get_active_template($birthdaygroup['type']);
                    if (!$template) {
                        $template = $this->get_active_template('birthday');
                    }
                    $people = array_column($birthdaygroup['items'], 'person');
                    $relationships = array_column($birthdaygroup['items'], 'relationship');
                    $subject = $template ? (string)$template->subject : get_string('birthdaymailtitle', 'local_birthdayflyer');
                    $subject = html_entity_decode($this->replace_placeholders($subject, $employee, $fullname, $thoughttext, $people, $relationships, $template), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if ($template) {
                        $html = $this->render_template($template, $employee, $fullname, $thoughttext, $people, $relationships);
                    } else {
                        $html = \html_writer::tag('p', s(get_string('emailgreeting', 'local_birthdayflyer', $fullname))) .
                            \html_writer::tag('p', s(get_string('birthdaysectiontitle', 'local_birthdayflyer') . ': ' . implode(', ', $people))) .
                            \html_writer::tag('p', s(get_string('emailfooter', 'local_birthdayflyer')));
                    }
                    $allok = $this->send_message($recipient, $subject, html_to_text($html), $html, $template) && $allok;
                }
            }
            // Send work and marriage anniversary emails independently, even when birthdays also occur.
            $anniversaryevents = [];
            if ($sendworkanniversary) {
                $anniversaryevents[] = ['type' => 'workanniversary', 'years' => $workyears, 'placeholder' => '{{work_anniversary_years}}', 'label' => get_string('eventworkanniversary', 'local_birthdayflyer')];
            }
            if ($sendmarriageanniversary) {
                $anniversaryevents[] = ['type' => 'marriageanniversary', 'years' => $marriageyears, 'placeholder' => '{{marriage_anniversary_years}}', 'label' => get_string('eventmarriageanniversary', 'local_birthdayflyer')];
            }
            foreach ($anniversaryevents as $event) {
                if ($event['years'] < 1) { continue; }
                $template = $this->get_active_template($event['type']);
                $extra = [$event['placeholder'] => (string)$event['years'], '{{years}}' => (string)$event['years']];
                $subject = $template ? (string)$template->subject : $event['label'];
                $subject = html_entity_decode($this->replace_placeholders($subject, $employee, $fullname, $thoughttext, [], [], $template, '', $extra), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($template) {
                    $html = $this->render_template($template, $employee, $fullname, $thoughttext, [], [], $extra);
                } else {
                    $html = \html_writer::tag('p', s(get_string('emailgreeting', 'local_birthdayflyer', $fullname))) .
                        \html_writer::tag('p', s($event['label'] . ': ' . $event['years'] . ' ' . get_string('years', 'local_birthdayflyer'))) .
                        \html_writer::tag('p', s(get_string('emailfooter', 'local_birthdayflyer')));
                }
                $allok = $this->send_message($recipient, $subject, html_to_text($html), $html, $template) && $allok;
            }
            $hasenabledmessage = ($sendthought && $thoughttext !== '') ||
                ($sendemployeebirthday && !empty($employeeBirthdays)) ||
                ($sendfamilybirthday && !empty($relationshipBirthdays)) ||
                ($sendworkanniversary && $workyears > 0) ||
                ($sendmarriageanniversary && $marriageyears > 0);
            if (!$hasenabledmessage) { $skippedcount++; continue; }
            if ($allok) {
                try {
                    $DB->insert_record('local_birthdayflyer_sent', (object)['employeeid' => $employee->id, 'senddate' => $todaykey, 'timecreated' => time()]);
                    $sentcount++;
                    mtrace(get_string('tasksentemployee', 'local_birthdayflyer', $employee->empid));
                } catch (\dml_write_exception $e) {
                    $skippedcount++;
                    mtrace(get_string('taskduplicate', 'local_birthdayflyer', $employee->empid));
                }
            } else {
                $failedcount++;
                mtrace(get_string('taskemailfailed', 'local_birthdayflyer', $employee->empid));
            }
        }
        mtrace(get_string('taskfinished', 'local_birthdayflyer', ['sent' => $sentcount, 'skipped' => $skippedcount, 'failed' => $failedcount]));
    }

    /**
     * Read an email-type setting. Missing settings default to enabled so an
     * upgrade does not unexpectedly stop any existing email category.
     */
    private function is_email_type_enabled(string $setting): bool {
        $value = get_config('local_birthdayflyer', $setting);
        return ($value === false || $value === null) ? true : (bool)$value;
    }

    /**
     * Render the Thought of the Day with the quote visually embedded inside
     * the shared artwork. The quote is HTML text layered over the image,
     * which keeps the source artwork reusable and avoids requiring admins to
     * create HTML for every scheduled thought.
     */
    private function render_thought_html(?\stdClass $thought, string $thoughttext): string {
        $imageurl = $this->get_thought_image_url();
        $quote = nl2br(s(trim(strip_tags($thoughttext))), false);

        if ($imageurl !== '') {
            // Use the artwork itself as a normal responsive image so its natural
            // aspect ratio is always preserved. The quote is layered over the
            // image without a background or shadow. Unlike a fixed-height
            // background, this never crops the uploaded artwork.
            $imageurl = s($imageurl);
            return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">' .
                '<div style="width:100%;margin:0;padding:0;background:#ffffff;">' .
                    '<div style="position:relative;width:100%;margin:0;padding:0;line-height:0;overflow:hidden;">' .
                        '<img src="' . $imageurl . '" alt="Thought of the Day" width="100%" style="display:block;width:100%;height:auto;margin:0;padding:0;border:0;outline:none;text-decoration:none;line-height:0;">' .
                        '<div style="position:absolute;top:43%;right:4%;width:42%;margin:0;padding:0;color:#254083;font-size:23px;line-height:1.45;font-style:italic;font-weight:600;text-align:right;text-shadow:none;">&ldquo;' . $quote . '&rdquo;</div>' .
                    '</div>' .
                '</div>' .
                '</body></html>';
        }

        // Graceful fallback if the shared image has not yet been configured.
        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">' .
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0;padding:0;background:#ffffff;"><tr><td align="center" style="padding:40px 24px;color:#254083;font-size:22px;line-height:1.6;font-style:italic;text-align:center;background:#ffffff;">&ldquo;' . $quote . '&rdquo;</td></tr></table>' .
            '</body></html>';
    }

    private function get_thought_image_url(): string {
        $key = (string)get_config('local_birthdayflyer', 'thoughtimagekey');
        if ($key === '') {
            return '';
        }
        $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'local_birthdayflyer', 'thoughtimage', 0, 'id ASC', false);
        if (!$files) {
            return '';
        }
        $file = reset($files);
        if (!in_array($file->get_mimetype(), ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return '';
        }
        return (new \moodle_url('/local/birthdayflyer/thought_image.php', [
            'id' => 0,
            'key' => $key,
            'v' => (int)$file->get_id(),
        ]))->out(false);
    }

    /** Find the newest active template for a specific event type. */
    private function get_active_template(string $eventtype): ?\stdClass {
        global $DB;
        $records = $DB->get_records('local_birthdayflyer_template', ['active' => 1, 'eventtype' => $eventtype], 'timemodified DESC', '*', 0, 1);
        return $records ? reset($records) : null;
    }

    /** Render an event template as HTML and replace its dynamic fields. */
    private function render_template(\stdClass $template, \stdClass $employee, string $fullname, string $thought, array $people, array $relationships, array $extra = []): string {
        global $CFG;
        $body = (string)$template->body;
        // Some editors save escaped HTML; decode repeated entity escaping before rendering.
        for ($i = 0; $i < 2; $i++) {
            $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $body) { break; }
            $body = $decoded;
        }
        $imagehtml = '';
        if (!empty($template->imagekey)) {
            $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'local_birthdayflyer', 'templateimage', (int)$template->id, 'id ASC', false);
            if ($files) {
                $file = reset($files);
                if (in_array($file->get_mimetype(), ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
                    $url = (new \moodle_url('/local/birthdayflyer/template_image.php', ['id' => (int)$template->id, 'key' => (string)$template->imagekey]))->out(false);
                    $imagehtml = '<img src="' . s($url) . '" alt="' . s(get_string('birthdayimagealt', 'local_birthdayflyer')) . '" style="max-width:100%;height:auto;display:block;margin:0 auto 20px;">';
                }
            }
        }
        // Replace tokens before Moodle processes HTML; otherwise editor/format filters
        // may escape or alter the braces and leave placeholders visible in the email.
        $body = $this->replace_placeholders($body, $employee, $fullname, $thought, $people, $relationships, $template, $imagehtml, $extra);
        if ($imagehtml && strpos((string)$template->body, '{{image}}') === false) { $body = $imagehtml . $body; }
        return $body;
    }

    /** Send a message and attach the template's optional .eml file, if present. */
    private function send_message(\stdClass $recipient, string $subject, string $plain, string $html, ?\stdClass $template): bool {
        global $CFG;
        $attachmentpath = '';
        $attachmentname = '';
        $tempfile = '';
        if ($template) {
            $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'local_birthdayflyer', 'templateattachment', (int)$template->id, 'id ASC', false);
            foreach ($files as $storedfile) {
                if (strtolower(pathinfo($storedfile->get_filename(), PATHINFO_EXTENSION)) === 'eml') {
                    $tempfile = tempnam($CFG->tempdir, 'employeemgmt_');
                    if ($tempfile !== false) {
                        file_put_contents($tempfile, $storedfile->get_content());
                        $attachmentpath = $tempfile;
                        $attachmentname = clean_filename($storedfile->get_filename());
                    }
                    break;
                }
            }
        }
        $sent = email_to_user($recipient, \core_user::get_noreply_user(), $subject, $plain, $html, $attachmentpath, $attachmentname);
        if ($tempfile && is_file($tempfile)) { @unlink($tempfile); }
        return (bool)$sent;
    }

    /** Replace supported template tokens. */
    private function replace_placeholders(string $text, \stdClass $employee, string $fullname, string $thought, array $birthdaypeople, array $relationships, ?\stdClass $template = null, string $imagehtml = '', array $extra = []): string {
        $birthdayperson = implode(', ', $birthdaypeople);
        $relationship = implode(', ', $relationships);
        $values = [
            '{{username}}' => s($fullname), '{{user fullname}}' => s($fullname), '{{user fullname }}' => s($fullname), '{{user_fullname}}' => s($fullname),
            '{{employee_fullname}}' => s($fullname), '{{employee full name}}' => s($fullname), '{{employeeid}}' => s((string)$employee->empid),
            '{{employeeemail}}' => s((string)$employee->empemail), '{{date}}' => s(userdate(time(), get_string('strftimedate'))),
            '{{thought_of_the_day}}' => nl2br(s($thought)), '{{thoughtoftheday}}' => nl2br(s($thought)), '{{thought of the day}}' => nl2br(s($thought)),
            '{{birthday_person}}' => s($birthdayperson), '{{birthdayperson}}' => s($birthdayperson), '{{birthday person}}' => s($birthdayperson),
            '{{relationship}}' => s($relationship), '{{image}}' => $imagehtml,
            '{{years}}' => (string)max(0, $employee->date_of_joining_stamp ? (int)userdate(time(), '%Y') - (int)userdate((int)$employee->date_of_joining_stamp, '%Y') : 0),
            '{{work_anniversary_years}}' => (string)max(0, $employee->date_of_joining_stamp ? (int)userdate(time(), '%Y') - (int)userdate((int)$employee->date_of_joining_stamp, '%Y') : 0),
            '{{marriage_anniversary_years}}' => (string)max(0, $employee->marriage_date_stamp ? (int)userdate(time(), '%Y') - (int)userdate((int)$employee->marriage_date_stamp, '%Y') : 0),
        ];
        $values = array_merge($values, $extra);
        return str_ireplace(array_keys($values), array_values($values), $text);
    }

    /** Resolve an employee's email address to a Moodle user or external recipient object. */
    private function get_recipient(\stdClass $employee) {
        global $DB, $CFG;
        $email = trim((string)$employee->empemail);
        if (!validate_email($email)) { return false; }
        $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MULTIPLE);
        if ($user) { return $user; }
        $name = trim((string)($employee->empfullname ?? '')) ?: trim((string)$employee->empid);
        return (object)[
            'id' => -1, 'auth' => 'manual', 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0,
            'email' => $email, 'firstname' => $name, 'lastname' => '', 'emailstop' => 0,
            'lang' => current_language(), 'timezone' => \core_date::get_server_timezone(), 'maildigest' => 0,
            'maildisplay' => 0, 'mailformat' => FORMAT_HTML, 'mnethostid' => $CFG->mnet_localhost_id,
        ];
    }
}
