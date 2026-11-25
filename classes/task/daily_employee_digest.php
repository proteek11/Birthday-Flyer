<?php
namespace local_birthdayflyer\task;

defined('MOODLE_INTERNAL') || die();

use local_birthdayflyer\local\emaillist;

/**
 * Send the daily Thought of the Day and optional birthday template to employees.
 *
 * v1.2.0 (25 November 2026) — summary of what changed in this file and why.
 * Every change below is also marked inline with "v1.2.0".
 *
 *  1. CC per email type. Thought of the Day goes to every employee, so CCing it
 *     flooded the CC mailbox (one copy per employee) and used up SMTP quota.
 *     Each email type now has its own "CC this type" switch; Thought is off by default.
 *  2. Several CC addresses. The CC setting accepts a list (see \local_birthdayflyer\local\emaillist).
 *  3. CC follows Moodle's "divert all emails" rule, so a test/staging site never
 *     sends a CC copy to a real mailbox.
 *  4. DKIM signing and a Moodle Message-ID, exactly as core email_to_user() does.
 *     This task builds the mail itself (for real CC + inline images), so it had lost
 *     core's DKIM signature, which hurts delivery to external mailboxes like Gmail.
 *  5. Per-email delivery log (table local_birthdayflyer_log). The old log was one row
 *     per employee per day, written only when every email for that employee
 *     succeeded. A partial failure therefore meant: no retry, and if the task was
 *     run again the emails that DID go out were sent a second time. Now each email
 *     is logged on its own, a re-run sends only what has not been sent, and the
 *     reason for every skip/failure is visible on the Reports page.
 *     The original day-level table (local_birthdayflyer_sent) is still written the
 *     same way, so existing reports and the "already processed today" check are unchanged.
 *  6. Work done once per run instead of once per email: language strings, template
 *     lookups, image file reads and the Thought of the Day HTML.
 *  7. One SMTP connection can be reused for many emails (Moodle's get_mailer('buffer')),
 *     plus an optional pause between emails for providers with per-minute limits.
 *  8. Inline images: images pasted as data: URIs are now embedded (Gmail blocks data: images),
 *     data-src is no longer mistaken for src, and an image used twice is embedded once.
 *  9. New {{image_url}} placeholder so the uploaded template image can be used as a background.
 * 10. Optional "email-safe" Thought of the Day layout (off by default; the current layout is unchanged).
 *
 * Recipient eligibility is deliberately UNCHANGED: suspended accounts, "No login"
 * accounts (an admin-set authentication method that blocks sign-in — not the same
 * as "never logged in"), accounts over the bounce threshold and domains blocked by
 * Moodle are still skipped. Employees who have never logged in still receive email.
 */
class daily_employee_digest extends \core\task\scheduled_task {

    /** Image types that can be served by this plugin and embedded in an email. */
    const IMAGE_MIMETYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    /**
     * v1.2.0: stable message-type keys. They are used in the delivery log and the CC
     * settings. The old code identified employee birthdays by comparing a translated
     * string; these keys do not depend on language packs.
     */
    const TYPE_THOUGHT = 'thought';
    const TYPE_BIRTHDAY_EMPLOYEE = 'birthdayemployee';
    const TYPE_BIRTHDAY_RELATIONSHIP = 'birthdayrelationship';
    const TYPE_WORK_ANNIVERSARY = 'workanniversary';
    const TYPE_MARRIAGE_ANNIVERSARY = 'marriageanniversary';
    /** Used only in the log, when an employee record has no usable email address. */
    const TYPE_RECIPIENT = 'recipient';

    /** Default CC switch per type when the setting has never been saved. Thought is off (agreed v1.2.0 behaviour). */
    const CC_DEFAULTS = [
        self::TYPE_THOUGHT => 0,
        self::TYPE_BIRTHDAY_EMPLOYEE => 1,
        self::TYPE_BIRTHDAY_RELATIONSHIP => 1,
        self::TYPE_WORK_ANNIVERSARY => 1,
        self::TYPE_MARRIAGE_ANNIVERSARY => 1,
    ];

    /** @var array v1.2.0 per-run cache: active template per event type (null = none). */
    private $templatecache = [];
    /** @var array v1.2.0 per-run cache: stored image per "area:itemid" (null = none/unusable). */
    private $imagecache = [];
    /** @var string[]|null v1.2.0 per-run cache: configured CC addresses. */
    private $ccaddresses = null;
    /** @var array v1.2.0 "employeeid:type" => true for emails already logged as sent today. */
    private $alreadysent = [];
    /** @var array v1.2.0 "employeeid:type" => log row id for today's existing log rows (saves a lookup per email). */
    private $logids = [];
    /** @var string v1.2.0 reason code of the last send_message() call ('' when sent normally). */
    private $lastreason = '';
    /** @var bool v1.2.0 true when the last send_message() call skipped an ineligible recipient (not a mail error). */
    private $lastskipped = false;
    /** @var string v1.2.0 CC addresses actually added to the last email. */
    private $lastcc = '';
    /** @var int|null v1.2.0 server midnight of the processing day; used by the log (null = do not log, e.g. CLI test). */
    private $todaykey = null;

    public function get_name(): string {
        return get_string('taskdailydigest', 'local_birthdayflyer');
    }

    public function execute(): void {
        global $DB;
        // v1.2.0: clear per-run caches. A scheduled task object can be reused inside
        // one long-running cron process, so cached templates/images must not leak
        // into the next day's run.
        $this->reset_run_state();

        // Email categories are enabled by default to preserve existing behaviour
        // on upgrades where the new settings have not yet been saved.
        $flags = [
            self::TYPE_THOUGHT => $this->is_email_type_enabled('send_thought_of_day'),
            self::TYPE_BIRTHDAY_EMPLOYEE => $this->is_email_type_enabled('send_employee_birthday'),
            self::TYPE_BIRTHDAY_RELATIONSHIP => $this->is_email_type_enabled('send_family_birthday'),
            self::TYPE_WORK_ANNIVERSARY => $this->is_email_type_enabled('send_work_anniversary'),
            self::TYPE_MARRIAGE_ANNIVERSARY => $this->is_email_type_enabled('send_marriage_anniversary'),
        ];
        $now = time();
        $timezone = \core_date::get_server_timezone();
        $today = usergetdate($now, $timezone);
        $todaykey = make_timestamp((int)$today['year'], (int)$today['mon'], (int)$today['mday'], 0, 0, 0, $timezone);
        $this->todaykey = $todaykey;
        $thought = $DB->get_record('local_birthdayflyer_thought', ['thoughtdate' => $todaykey]);
        $thoughttext = $thought ? trim((string)$thought->thought) : '';
        $employees = $DB->get_records('local_birthdayflyer', null, 'id ASC');
        mtrace(get_string('taskstarted', 'local_birthdayflyer', userdate($now)));
        if (!$employees) { mtrace(get_string('tasknoemployees', 'local_birthdayflyer')); return; }
        if ($thoughttext === '') { mtrace(get_string('taskmissingthought', 'local_birthdayflyer', userdate($todaykey, '%Y-%m-%d'))); }

        // v1.2.0: the Thought of the Day email is identical for every employee
        // (it has no personal placeholders), so build it once instead of per employee.
        $thoughthtml = ($flags[self::TYPE_THOUGHT] && $thoughttext !== '') ? $this->render_thought_html($thought, $thoughttext) : '';

        // v1.2.0: one query for today's already-sent emails instead of one per email.
        $this->load_already_sent($todaykey);

        // v1.2.0: optional pause between emails (Settings > "Pause between emails").
        // Default 0 keeps the old behaviour (no pause).
        $delayms = max(0, (int)get_config('local_birthdayflyer', 'send_delay_ms'));

        $sentcount = 0; $skippedcount = 0; $failedcount = 0;
        $mailssent = 0;

        // v1.2.0: keep the SMTP connection open between emails, as core's forum
        // cron does. With Moodle's default "SMTP session limit" (smtpmaxbulk = 1)
        // this behaves exactly like before; raising that limit lets one connection
        // carry many emails. get_mailer('close') in "finally" always closes it, even
        // when an exception escapes, so no SMTP connection is left open.
        get_mailer('buffer');
        try {
            foreach ($employees as $employee) {
                // This daily log is checked once per employee; all applicable messages are sent
                // during this execution before the day is marked as processed.
                if ($DB->record_exists('local_birthdayflyer_sent', ['employeeid' => $employee->id, 'senddate' => $todaykey])) {
                    $skippedcount++; continue;
                }
                $recipient = $this->get_recipient($employee);
                if (!$recipient) {
                    mtrace(get_string('taskinvalidemail', 'local_birthdayflyer', $employee->empid));
                    // v1.2.0: record why, so the Reports page can show it.
                    $this->log_result((int)$employee->id, self::TYPE_RECIPIENT, 'skipped', 'invalidemail', '');
                    $failedcount++; continue;
                }

                // v1.2.0: message building moved to build_messages() so the CLI test
                // script produces exactly the same emails. Order and content are unchanged:
                // Thought, employee birthday, family birthday, work anniversary, marriage anniversary.
                $messages = $this->build_messages($employee, $flags, $today, $timezone, $thoughttext, $thoughthtml);
                if (!$messages) { $skippedcount++; continue; }

                $allok = true;
                foreach ($messages as $message) {
                    // v1.2.0: never send the same email twice on the same day. This only
                    // matters on a re-run after a partial failure: emails that already went
                    // out are skipped and only the failed ones are retried.
                    $sentkey = $employee->id . ':' . $message['type'];
                    if (isset($this->alreadysent[$sentkey])) {
                        mtrace(get_string('taskalreadysent', 'local_birthdayflyer',
                            ['empid' => $employee->empid, 'type' => $message['type']]));
                        continue;
                    }
                    if ($delayms > 0 && $mailssent > 0) {
                        usleep($delayms * 1000);
                    }
                    $ok = $this->send_message($recipient, $message['subject'], $message['plain'], $message['html'],
                        $message['template'], $message['type']);
                    $mailssent++;
                    $status = $ok ? 'sent' : ($this->lastskipped ? 'skipped' : 'failed');
                    $this->log_result((int)$employee->id, $message['type'], $status, $this->lastreason, $this->lastcc);
                    if ($ok) {
                        $this->alreadysent[$sentkey] = true;
                        if (strpos($this->lastreason, 'ccrejected:') === 0) {
                            // v1.2.0: employee got the email, but the mail server refused a CC address.
                            mtrace(get_string('taskccrejected', 'local_birthdayflyer',
                                ['empid' => $employee->empid, 'type' => $message['type'], 'reason' => $this->lastreason]));
                        }
                    } else {
                        mtrace(get_string('taskemailfailedtype', 'local_birthdayflyer',
                            ['empid' => $employee->empid, 'type' => $message['type'], 'reason' => $this->lastreason]));
                    }
                    $allok = $ok && $allok;
                }

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
        } finally {
            get_mailer('close');
        }
        mtrace(get_string('taskfinished', 'local_birthdayflyer', ['sent' => $sentcount, 'skipped' => $skippedcount, 'failed' => $failedcount]));
    }

    /**
     * v1.2.0: build every email one employee should receive on the given day.
     *
     * This is the original message-building code from execute(), moved unchanged
     * into its own method (only the per-employee get_string() calls were hoisted
     * into get_birthday_map()). It is shared by the scheduled task and the CLI
     * test script, so a test email is the same email the task would send.
     *
     * @param \stdClass $employee Employee record.
     * @param array $flags Enabled email types (TYPE_* => bool).
     * @param array $today usergetdate() of the processing day in server time.
     * @param string $timezone Server timezone.
     * @param string $thoughttext Today's thought ('' when none).
     * @param string $thoughthtml Pre-rendered Thought of the Day HTML ('' when not sent).
     * @return array List of ['type', 'subject', 'plain', 'html', 'template'].
     */
    public function build_messages(\stdClass $employee, array $flags, array $today, string $timezone,
            string $thoughttext, string $thoughthtml): array {
        $messages = [];
        $fullname = trim((string)($employee->empfullname ?? '')) ?: trim((string)$employee->empid);
        $birthdaymap = $this->get_birthday_map();
        $birthdays = [];
        foreach ($birthdaymap as $field => $details) {
            if (empty($employee->{$field})) { continue; }
            $bd = usergetdate((int)$employee->{$field}, $timezone);
            if ((int)$bd['mon'] !== (int)$today['mon'] || (int)$bd['mday'] !== (int)$today['mday']) {
                continue;
            }

            // Added 18 November 2026: avoid duplicate family labels when the
            // first and second child of the same relationship share a birthday.
            // In that case, keep the standard "Son" or "Daughter" label only.
            $primaryfield = null;
            if ($field === 'daughter2_dob_stamp') {
                $primaryfield = 'daughter_dob_stamp';
            } else if ($field === 'son2_dob_stamp') {
                $primaryfield = 'son_dob_stamp';
            }

            // Added 18 November 2026: use the standard Son/Daughter label
            // for second-child birthdays too, even when the first child has
            // a different birthday or no birthday date is recorded.
            if ($primaryfield && isset($birthdaymap[$primaryfield])) {
                $details = $birthdaymap[$primaryfield];
            }

            if ($primaryfield && !empty($employee->{$primaryfield})) {
                $primarydate = usergetdate((int)$employee->{$primaryfield}, $timezone);
                if ((int)$primarydate['mon'] === (int)$today['mon'] &&
                        (int)$primarydate['mday'] === (int)$today['mday']) {
                    continue;
                }
            }

            $birthdays[] = $details;
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
        if (!empty($flags[self::TYPE_THOUGHT]) && $thoughttext !== '' && $thoughthtml !== '') {
            $messages[] = [
                'type' => self::TYPE_THOUGHT,
                'subject' => get_string('thoughtmailtitle', 'local_birthdayflyer'),
                'plain' => get_string('thoughtmailtitle', 'local_birthdayflyer') . "\n\n" . $thoughttext,
                'html' => $thoughthtml,
                'template' => null,
            ];
        }

        // Send employee and relationship birthdays through separate template types.
        // If both occur on the same day, they are sent as separate emails so each
        // category can use its own design and subject.
        if ($birthdays) {
            $employeebirthdays = [];
            $relationshipbirthdays = [];
            foreach ($birthdays as $birthday) {
                // v1.2.0: use the stable 'isemployee' flag instead of comparing the
                // translated relationship label, so a language customisation cannot
                // move an employee birthday into the family group.
                if (!empty($birthday['isemployee'])) {
                    $employeebirthdays[] = $birthday;
                } else {
                    $relationshipbirthdays[] = $birthday;
                }
            }

            $birthdaygroups = [];
            if (!empty($flags[self::TYPE_BIRTHDAY_EMPLOYEE])) {
                $birthdaygroups[] = ['type' => self::TYPE_BIRTHDAY_EMPLOYEE, 'items' => $employeebirthdays];
            }
            if (!empty($flags[self::TYPE_BIRTHDAY_RELATIONSHIP])) {
                $birthdaygroups[] = ['type' => self::TYPE_BIRTHDAY_RELATIONSHIP, 'items' => $relationshipbirthdays];
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
                $messages[] = ['type' => $birthdaygroup['type'], 'subject' => $subject, 'plain' => html_to_text($html),
                    'html' => $html, 'template' => $template];
            }
        }
        // Send work and marriage anniversary emails independently, even when birthdays also occur.
        $anniversaryevents = [];
        if (!empty($flags[self::TYPE_WORK_ANNIVERSARY])) {
            $anniversaryevents[] = ['type' => self::TYPE_WORK_ANNIVERSARY, 'years' => $workyears, 'placeholder' => '{{work_anniversary_years}}', 'label' => get_string('eventworkanniversary', 'local_birthdayflyer')];
        }
        if (!empty($flags[self::TYPE_MARRIAGE_ANNIVERSARY])) {
            $anniversaryevents[] = ['type' => self::TYPE_MARRIAGE_ANNIVERSARY, 'years' => $marriageyears, 'placeholder' => '{{marriage_anniversary_years}}', 'label' => get_string('eventmarriageanniversary', 'local_birthdayflyer')];
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
            $messages[] = ['type' => $event['type'], 'subject' => $subject, 'plain' => html_to_text($html),
                'html' => $html, 'template' => $template];
        }
        return $messages;
    }

    /**
     * v1.2.0: send one employee's emails to a test address only (used by cli/send_test.php).
     *
     * Nothing is written to either delivery log, so a test never stops or changes
     * the real morning email. All email types are considered (the "send" switches
     * are ignored so any template can be previewed). CC is added only when
     * $withcc is true, and then the per-type CC switches apply as in the real run.
     *
     * @param \stdClass $employee Employee record.
     * @param string $toemail Test address that receives the emails.
     * @param int $daystamp Any timestamp on the day to simulate (server time).
     * @param bool $withcc Also send to the configured CC addresses.
     * @return array List of [type, subject, ok, reason, cc].
     */
    public function send_test(\stdClass $employee, string $toemail, int $daystamp, bool $withcc): array {
        global $DB;
        $this->reset_run_state();
        $this->todaykey = null; // Never log test emails.
        $timezone = \core_date::get_server_timezone();
        $today = usergetdate($daystamp, $timezone);
        $daykey = make_timestamp((int)$today['year'], (int)$today['mon'], (int)$today['mday'], 0, 0, 0, $timezone);
        $thought = $DB->get_record('local_birthdayflyer_thought', ['thoughtdate' => $daykey]);
        $thoughttext = $thought ? trim((string)$thought->thought) : '';
        $flags = array_fill_keys(array_keys(self::CC_DEFAULTS), true);
        $thoughthtml = $thoughttext !== '' ? $this->render_thought_html($thought, $thoughttext) : '';
        if (!$withcc) {
            $this->ccaddresses = [];
        }
        $recipient = (object)[
            'id' => -1, 'auth' => 'manual', 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0,
            'email' => $toemail, 'firstname' => trim((string)$employee->empfullname), 'lastname' => '', 'emailstop' => 0,
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => '',
            'mailformat' => FORMAT_HTML,
        ];
        $results = [];
        get_mailer('buffer');
        try {
            foreach ($this->build_messages($employee, $flags, $today, $timezone, $thoughttext, $thoughthtml) as $message) {
                $ok = $this->send_message($recipient, '[TEST] ' . $message['subject'], $message['plain'], $message['html'],
                    $message['template'], $message['type']);
                $results[] = [$message['type'], $message['subject'], $ok, $this->lastreason, $this->lastcc];
            }
        } finally {
            get_mailer('close');
        }
        return $results;
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
     * v1.2.0: should this email type carry the CC recipients?
     *
     * Settings cc_<type>. When a setting was never saved, CC_DEFAULTS applies
     * (Thought of the Day off, everything else on). The upgrade step also writes
     * these defaults so behaviour is explicit from the moment v1.2.0 is installed.
     */
    private function is_cc_enabled_for(string $messagetype): bool {
        if (!array_key_exists($messagetype, self::CC_DEFAULTS)) {
            return false;
        }
        $value = get_config('local_birthdayflyer', 'cc_' . $messagetype);
        if ($value === false || $value === null) {
            return (bool)self::CC_DEFAULTS[$messagetype];
        }
        return (bool)$value;
    }

    /**
     * v1.2.0: configured CC addresses (validated, de-duplicated), read once per run.
     *
     * @return string[]
     */
    private function get_cc_addresses(): array {
        if ($this->ccaddresses !== null) {
            return $this->ccaddresses;
        }
        $this->ccaddresses = [];
        if (!get_config('local_birthdayflyer', 'enable_email_cc')) {
            return $this->ccaddresses;
        }
        $parsed = emaillist::parse((string)get_config('local_birthdayflyer', 'email_cc_address'));
        if ($parsed['invalid']) {
            // The settings page refuses invalid addresses, so this only happens if the
            // value was written another way (e.g. CLI). Skip the bad ones, keep the rest.
            mtrace(get_string('taskccinvalid', 'local_birthdayflyer', implode(', ', $parsed['invalid'])));
        }
        $this->ccaddresses = array_slice($parsed['valid'], 0, emaillist::MAX_ADDRESSES);
        return $this->ccaddresses;
    }

    /** v1.2.0: reset everything cached for one run. */
    private function reset_run_state(): void {
        $this->templatecache = [];
        $this->imagecache = [];
        $this->ccaddresses = null;
        $this->alreadysent = [];
        $this->logids = [];
        $this->lastreason = '';
        $this->lastskipped = false;
        $this->lastcc = '';
        $this->todaykey = null;
    }

    /**
     * v1.2.0: relationship labels, built once instead of 18 get_string() calls per employee.
     * 'isemployee' replaces the old comparison against the translated "Employee" label.
     */
    private function get_birthday_map(): array {
        static $map = null;
        if ($map === null) {
            $s = function(string $key): string {
                return get_string($key, 'local_birthdayflyer');
            };
            $map = [
                'date_of_birth_stamp' => ['person' => $s('birthdaypersonemployee'), 'relationship' => $s('relationshipemployee'), 'isemployee' => true],
                'father_dob_stamp' => ['person' => $s('birthdaypersonfather'), 'relationship' => $s('relationshipfather')],
                'mother_dob_stamp' => ['person' => $s('birthdaypersonmother'), 'relationship' => $s('relationshipmother')],
                'wife_dob_stamp' => ['person' => $s('birthdaypersonwife'), 'relationship' => $s('relationshipwife')],
                'son_dob_stamp' => ['person' => $s('birthdaypersonson'), 'relationship' => $s('relationshipson')],
                'daughter_dob_stamp' => ['person' => $s('birthdaypersondaughter'), 'relationship' => $s('relationshipdaughter')],
                // Added 18 November 2026: additional family birthdays use the existing family-birthday control.
                'husband_dob_stamp' => ['person' => $s('birthdaypersonhusband'), 'relationship' => $s('relationshiphusband')],
                'daughter2_dob_stamp' => ['person' => $s('birthdaypersondaughter2'), 'relationship' => $s('relationshipdaughter2')],
                'son2_dob_stamp' => ['person' => $s('birthdaypersonson2'), 'relationship' => $s('relationshipson2')],
            ];
        }
        return $map;
    }

    /**
     * v1.2.0: load today's log rows once: which emails were already sent (re-run safety)
     * and the row ids, so log_result() can update or insert without an extra lookup.
     */
    private function load_already_sent(int $daykey): void {
        global $DB;
        $this->alreadysent = [];
        $this->logids = [];
        $rows = $DB->get_records('local_birthdayflyer_log', ['senddate' => $daykey], '', 'id, employeeid, messagetype, status');
        foreach ($rows as $row) {
            $key = $row->employeeid . ':' . $row->messagetype;
            $this->logids[$key] = (int)$row->id;
            if ($row->status === 'sent') {
                $this->alreadysent[$key] = true;
            }
        }
    }

    /**
     * v1.2.0: write one row per email to local_birthdayflyer_log (insert or update).
     *
     * Logging must never stop email delivery, so any database problem here is
     * reported with mtrace() and otherwise ignored.
     */
    private function log_result(int $employeeid, string $type, string $status, string $reason, string $cc): void {
        global $DB;
        if ($this->todaykey === null) {
            return;
        }
        try {
            $now = time();
            $key = ['employeeid' => $employeeid, 'senddate' => $this->todaykey, 'messagetype' => $type];
            $record = (object)array_merge($key, [
                'status' => $status,
                'reason' => \core_text::substr($reason, 0, 255),
                'ccaddresses' => \core_text::substr($cc, 0, 1333),
                'timemodified' => $now,
            ]);
            $mapkey = $employeeid . ':' . $type;
            if (!empty($this->logids[$mapkey])) {
                $record->id = $this->logids[$mapkey];
                $DB->update_record('local_birthdayflyer_log', $record);
            } else {
                $record->timecreated = $now;
                $this->logids[$mapkey] = (int)$DB->insert_record('local_birthdayflyer_log', $record);
            }
        } catch (\Throwable $e) {
            mtrace('Birthday Flyer: could not write delivery log: ' . $e->getMessage());
        }
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

        // v1.2.0: optional email-safe layout (Settings > "Thought of the Day layout").
        // The default 'overlay' layout below is the original one and is unchanged.
        if ($imageurl !== '' && get_config('local_birthdayflyer', 'thought_layout') === 'emailsafe') {
            $safe = $this->render_thought_html_emailsafe($imageurl, $quote);
            if ($safe !== '') {
                return $safe;
            }
        }

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

    /**
     * v1.2.0: Thought of the Day as a 600px table with the artwork as background.
     *
     * Why: the default layout positions the quote with CSS position:absolute,
     * which Gmail and Outlook remove, so in those clients the quote drops below
     * the picture. Table + background is the pattern those clients support:
     *  - Gmail/Apple Mail/Outlook.com read background-image / background="".
     *  - Outlook for Windows reads the VML block inside the [if gte mso 9] comment.
     * The background must be a public URL (not cid:), because Gmail does not
     * display Content-ID images as backgrounds. The height is taken from the image
     * itself so the whole artwork is shown at 600px wide, never cropped.
     *
     * @param string $imageurl Public image URL (not escaped).
     * @param string $quote Escaped quote HTML.
     * @return string '' when the image size cannot be read (caller falls back to the default layout).
     */
    private function render_thought_html_emailsafe(string $imageurl, string $quote): string {
        $image = $this->get_stored_image('thoughtimage', 0);
        $info = $image ? $image['file']->get_imageinfo() : false;
        if (!$info || empty($info['width']) || empty($info['height'])) {
            return '';
        }
        $width = 600;
        $height = (int)round($width * $info['height'] / $info['width']);
        $top = (int)round($height * 0.43);
        $url = s($imageurl);
        $vmlwidth = (int)round($width * 0.75);
        $vmlheight = (int)round($height * 0.75);
        return '<!DOCTYPE html><html xmlns:v="urn:schemas-microsoft-com:vml"><body style="margin:0;padding:0;background:#ffffff;">' .
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#ffffff;"><tr><td align="center">' .
            '<table role="presentation" width="' . $width . '" cellpadding="0" cellspacing="0" border="0" style="width:' . $width . 'px;">' .
            '<tr><td width="' . $width . '" height="' . $height . '" valign="top" background="' . $url . '" ' .
                'style="width:' . $width . 'px;height:' . $height . 'px;background-image:url(\'' . $url . '\');background-repeat:no-repeat;' .
                'background-position:top left;background-size:' . $width . 'px ' . $height . 'px;font-family:Arial,Helvetica,sans-serif;">' .
            '<!--[if gte mso 9]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:' . $vmlwidth . 'pt;height:' . $vmlheight . 'pt;">' .
                '<v:fill type="frame" src="' . $url . '" /><v:textbox inset="0,0,0,0"><![endif]-->' .
            '<table role="presentation" width="' . $width . '" cellpadding="0" cellspacing="0" border="0">' .
                '<tr><td height="' . $top . '" style="height:' . $top . 'px;font-size:0;line-height:0;">&nbsp;</td></tr>' .
                '<tr><td align="right" style="padding:0 24px 0 0;">' .
                    '<table role="presentation" width="252" cellpadding="0" cellspacing="0" border="0" align="right"><tr>' .
                    '<td style="color:#254083;font-size:21px;line-height:1.45;font-style:italic;font-weight:600;text-align:right;">&ldquo;' . $quote . '&rdquo;</td>' .
                    '</tr></table>' .
                '</td></tr>' .
            '</table>' .
            '<!--[if gte mso 9]></v:textbox></v:rect><![endif]-->' .
            '</td></tr></table>' .
            '</td></tr></table>' .
            '</body></html>';
    }

    /**
     * GitHub change (25 November 2026): Embed Birthday Flyer images in the
     * outgoing email using Content-ID (CID), rather than relying on HTTP URLs.
     *
     * v1.2.0 additions:
     *  - images written as data: URIs (e.g. pasted into the HTML) are embedded too,
     *    because Gmail refuses to display data: images;
     *  - the attribute match no longer treats data-src="..." as src="...";
     *  - an image used more than once in the same email is attached only once;
     *  - stored images are read once per run (cache) instead of once per email.
     * Backgrounds (CSS url(...) and background="...") are intentionally NOT turned
     * into cid: references: Gmail does not show cid: images as backgrounds, so a
     * public URL is the most compatible choice there. See {{image_url}}.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer Configured Moodle mailer.
     * @param string $html Email HTML.
     * @return string HTML with local Birthday Flyer image URLs replaced by CID URLs.
     */
    private function embed_email_images($mailer, string $html): string {
        global $DB;

        $embedded = []; // v1.2.0: cid => true, per message.
        // Only process image URLs generated by this plugin (and data: URIs). Leave all
        // other external or template-provided images unchanged.
        // v1.2.0: (?<![\w-]) stops "data-src" from matching as "src".
        $pattern = '/(<img\b[^>]*?(?<![\w-])src\s*=\s*)(["\'])(.*?)\2/is';
        $result = preg_replace_callback($pattern, function($matches) use ($mailer, $DB, &$embedded) {
            $src = html_entity_decode(trim($matches[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // v1.2.0: inline data: image -> real inline attachment.
            if (preg_match('#^data:(image/(?:png|jpe?g|gif|webp));base64,(.+)$#is', $src, $dm)) {
                $content = base64_decode(preg_replace('/\s+/', '', $dm[2]), true);
                if ($content === false || $content === '') {
                    return $matches[0];
                }
                $mimetype = strtolower($dm[1]) === 'image/jpg' ? 'image/jpeg' : strtolower($dm[1]);
                $hash = sha1($content);
                $cid = 'birthdayflyer-data-' . $hash . '@moodle';
                if (!isset($embedded[$cid])) {
                    $extension = substr($mimetype, strpos($mimetype, '/') + 1);
                    if (!$mailer->addStringEmbeddedImage($content, $cid, 'image-' . substr($hash, 0, 8) . '.' . $extension,
                            'base64', $mimetype, 'inline')) {
                        return $matches[0];
                    }
                    $embedded[$cid] = true;
                }
                return $matches[1] . $matches[2] . 'cid:' . $cid . $matches[2];
            }

            $parts = parse_url($src);
            if (empty($parts['path']) || empty($parts['query'])) {
                return $matches[0];
            }

            $path = $parts['path'];
            if (strpos($path, '/local/birthdayflyer/template_image.php') === false &&
                    strpos($path, '/local/birthdayflyer/thought_image.php') === false) {
                return $matches[0];
            }

            $params = [];
            parse_str($parts['query'], $params);
            $id = isset($params['id']) ? (int)$params['id'] : -1;
            $key = isset($params['key']) ? (string)$params['key'] : '';
            if ($id < 0 || $key === '') {
                return $matches[0];
            }

            $area = '';
            if (strpos($path, '/template_image.php') !== false) {
                $record = $DB->get_record('local_birthdayflyer_template',
                    ['id' => $id], 'id,imagekey', IGNORE_MISSING);
                if (!$record || empty($record->imagekey) ||
                        !hash_equals((string)$record->imagekey, $key)) {
                    return $matches[0];
                }
                $area = 'templateimage';
            } else {
                if ($id === 0) {
                    $configuredkey = (string)get_config('local_birthdayflyer', 'thoughtimagekey');
                    if ($configuredkey === '' || !hash_equals($configuredkey, $key)) {
                        return $matches[0];
                    }
                } else {
                    $record = $DB->get_record('local_birthdayflyer_thought',
                        ['id' => $id], 'id,imagekey', IGNORE_MISSING);
                    if (!$record || empty($record->imagekey) ||
                            !hash_equals((string)$record->imagekey, $key)) {
                        return $matches[0];
                    }
                }
                $area = 'thoughtimage';
            }

            // v1.2.0: cached read (was: get_area_files + get_content for every email).
            $image = $this->get_stored_image($area, $id);
            if (!$image) {
                return $matches[0];
            }

            // Use a stable, unique Content-ID for each stored image.
            $cid = 'birthdayflyer-' . sha1($area . ':' . $id . ':' . $image['fileid']) . '@moodle';
            if (!isset($embedded[$cid])) {
                $ok = $mailer->addStringEmbeddedImage(
                    $image['content'],
                    $cid,
                    $image['filename'],
                    'base64',
                    $image['mimetype'],
                    'inline'
                );
                if (!$ok) {
                    debugging('Birthday Flyer: could not embed image ' . $image['filename'], DEBUG_DEVELOPER);
                    return $matches[0];
                }
                $embedded[$cid] = true;
            }

            return $matches[1] . $matches[2] . 'cid:' . $cid . $matches[2];
        }, $html);
        // v1.2.0: preg_replace_callback() returns null on a regex error (e.g. a huge
        // pasted data: image hitting the PCRE limit). Never send an empty body because
        // of that: fall back to the original HTML.
        return $result === null ? $html : $result;
    }

    /**
     * v1.2.0: read a stored plugin image once per run.
     *
     * @param string $area 'templateimage' or 'thoughtimage'.
     * @param int $itemid Template id, thought id, or 0 for the shared thought artwork.
     * @return array|null ['file', 'fileid', 'content', 'mimetype', 'filename'] or null when missing/unsupported.
     */
    private function get_stored_image(string $area, int $itemid): ?array {
        $cachekey = $area . ':' . $itemid;
        if (!array_key_exists($cachekey, $this->imagecache)) {
            $this->imagecache[$cachekey] = null;
            $files = get_file_storage()->get_area_files(\context_system::instance()->id, 'local_birthdayflyer', $area, $itemid, 'id ASC', false);
            if ($files) {
                $file = reset($files);
                if (in_array($file->get_mimetype(), self::IMAGE_MIMETYPES, true)) {
                    $this->imagecache[$cachekey] = [
                        'file' => $file,
                        'fileid' => (int)$file->get_id(),
                        'content' => $file->get_content(),
                        'mimetype' => $file->get_mimetype(),
                        'filename' => clean_filename($file->get_filename()),
                    ];
                }
            }
        }
        return $this->imagecache[$cachekey];
    }

    private function get_thought_image_url(): string {
        $key = (string)get_config('local_birthdayflyer', 'thoughtimagekey');
        if ($key === '') {
            return '';
        }
        // v1.2.0: cached image lookup (same checks as before: file exists and is a supported image).
        $image = $this->get_stored_image('thoughtimage', 0);
        if (!$image) {
            return '';
        }
        return (new \moodle_url('/local/birthdayflyer/thought_image.php', [
            'id' => 0,
            'key' => $key,
            'v' => $image['fileid'],
        ]))->out(false);
    }

    /** Find the newest active template for a specific event type. */
    private function get_active_template(string $eventtype): ?\stdClass {
        global $DB;
        // v1.2.0: cached per run (was one query per email).
        if (!array_key_exists($eventtype, $this->templatecache)) {
            $records = $DB->get_records('local_birthdayflyer_template', ['active' => 1, 'eventtype' => $eventtype], 'timemodified DESC', '*', 0, 1);
            $this->templatecache[$eventtype] = $records ? reset($records) : null;
        }
        return $this->templatecache[$eventtype];
    }

    /** Render an event template as HTML and replace its dynamic fields. */
    private function render_template(\stdClass $template, \stdClass $employee, string $fullname, string $thought, array $people, array $relationships, array $extra = []): string {
        $body = (string)$template->body;
        // Some editors save escaped HTML; decode repeated entity escaping before rendering.
        for ($i = 0; $i < 2; $i++) {
            $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $body) { break; }
            $body = $decoded;
        }
        $imagehtml = '';
        $imageurl = '';
        if (!empty($template->imagekey)) {
            // v1.2.0: cached image lookup (same checks as before).
            if ($this->get_stored_image('templateimage', (int)$template->id)) {
                $imageurl = (new \moodle_url('/local/birthdayflyer/template_image.php', ['id' => (int)$template->id, 'key' => (string)$template->imagekey]))->out(false);
                $imagehtml = '<img src="' . s($imageurl) . '" alt="' . s(get_string('birthdayimagealt', 'local_birthdayflyer')) . '" style="max-width:100%;height:auto;display:block;margin:0 auto 20px;">';
            }
        }
        // v1.2.0: {{image_url}} = public URL of the uploaded image, for use as a
        // background (background="{{image_url}}" or style="background-image:url('{{image_url}}')").
        // It stays a public URL on purpose: Gmail does not display cid: backgrounds.
        $extra = array_merge(['{{image_url}}' => s($imageurl)], $extra);
        // Replace tokens before Moodle processes HTML; otherwise editor/format filters
        // may escape or alter the braces and leave placeholders visible in the email.
        $body = $this->replace_placeholders($body, $employee, $fullname, $thought, $people, $relationships, $template, $imagehtml, $extra);
        // The uploaded image is added at the top when the template does not place it itself.
        // v1.2.0: (a) also not when the template uses it as a background through
        // {{image_url}}, otherwise the picture would appear twice; (b) the check is
        // case-insensitive like the placeholder replacement itself, so "{{IMAGE}}"
        // no longer produces the image twice.
        $templatebody = (string)$template->body;
        if ($imagehtml && stripos($templatebody, '{{image}}') === false && stripos($templatebody, '{{image_url}}') === false) {
            $body = $imagehtml . $body;
        }
        return $body;
    }

    /**
     * Send a message and attach the template's optional .eml file, if present.
     *
     * v1.2.0: new $messagetype argument (decides CC), and the outcome is kept in
     * $this->lastreason / $this->lastskipped / $this->lastcc for the delivery log.
     * The recipient eligibility checks themselves are unchanged.
     */
    private function send_message(\stdClass $recipient, string $subject, string $plain, string $html, ?\stdClass $template, string $messagetype = ''): bool {
        global $CFG;
        $this->lastreason = '';
        $this->lastskipped = false;
        $this->lastcc = '';
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

        // GitHub change (25 November 2026): Use Moodle's configured mailer
        // directly so PHPMailer can add a real CC recipient with addCC().
        // get_mailer() inherits Moodle's SMTP/transport configuration.
        $sent = false;
        $from = \core_user::get_noreply_user();

        // Keep the core email safety checks that are relevant to this scheduled
        // task before handing the message to Moodle's configured mail transport.
        // v1.2.0: unchanged rules; each branch now records a reason code for the log.
        // When the employee is skipped the CC copy is skipped too (agreed: a suspended
        // or "No login" employee should not trigger copies either).
        if (empty($recipient->email) || !validate_email((string)$recipient->email)) {
            debugging('Birthday Flyer: employee email address is empty or invalid; email not sent.', DEBUG_DEVELOPER);
            $this->lastreason = 'invalidemail';
            $this->lastskipped = true;
        } else if (!empty($recipient->deleted) || (!empty($recipient->suspended)) ||
                (isset($recipient->auth) && $recipient->auth === 'nologin')) {
            debugging('Birthday Flyer: recipient is deleted, suspended, or cannot log in; email not sent.', DEBUG_DEVELOPER);
            $this->lastreason = !empty($recipient->suspended) ? 'suspended' : (!empty($recipient->deleted) ? 'deleted' : 'nologin');
            $this->lastskipped = true;
        } else if (over_bounce_threshold($recipient)) {
            debugging('Birthday Flyer: recipient is over the email bounce threshold; email not sent.', DEBUG_DEVELOPER);
            $this->lastreason = 'bounce';
            $this->lastskipped = true;
        } else if (!empty($CFG->noemailever)) {
            // Match Moodle's email_to_user() behaviour on development sites.
            debugging('Birthday Flyer: not sending because $CFG->noemailever is enabled.', DEBUG_DEVELOPER);
            $this->lastreason = 'noemailever';
            $sent = true;
        } else {
            // Respect Moodle's global email diversion setting, as email_to_user() does.
            $toemail = (string)$recipient->email;
            if (email_should_be_diverted($toemail)) {
                $toemail = (string)$CFG->divertallemailsto;
                $subject = "[DIVERTED {$recipient->email}] " . $subject;
            }

            if (email_is_not_allowed($toemail)) {
                debugging('Birthday Flyer: recipient email is not allowed by Moodle email domain restrictions.', DEBUG_DEVELOPER);
                $this->lastreason = 'notallowed';
                $this->lastskipped = true;
            } else {
                // v1.2.0: CC list for this email type (may be several addresses).
                // CC addresses are NOT checked against Moodle's allowed email domains on
                // purpose: the CC may be an external mailbox (e.g. Gmail) while employees
                // are on the company domain. They DO follow "divert all emails": on a test
                // site the To is already diverted, so a CC to a real person is dropped.
                $cclist = [];
                if ($messagetype === '' || $this->is_cc_enabled_for($messagetype)) {
                    foreach ($this->get_cc_addresses() as $ccaddress) {
                        // Avoid duplicate recipients if the CC address equals the To address.
                        if (strcasecmp($ccaddress, $toemail) === 0 || strcasecmp($ccaddress, (string)$recipient->email) === 0) {
                            continue;
                        }
                        if (email_should_be_diverted($ccaddress)) {
                            debugging('Birthday Flyer: CC ' . $ccaddress . ' dropped because all email is diverted on this site.', DEBUG_DEVELOPER);
                            continue;
                        }
                        $cclist[] = $ccaddress;
                    }
                }
                try {
                    $mailer = get_mailer();

                    // The employee is the primary recipient (To).
                    $mailer->addAddress($toemail, fullname($recipient));

                    // GitHub change (25 November 2026): add the configured address
                    // as a real CC recipient, not as a second email or custom header.
                    // v1.2.0: one addCC() per configured address.
                    foreach ($cclist as $ccaddress) {
                        $mailer->addCC($ccaddress);
                    }

                    $fromemail = !empty($from->email) ? $from->email : $CFG->noreplyaddress;
                    $fromname = fullname($from);
                    $mailer->setFrom($fromemail, $fromname);
                    $mailer->Subject = $subject;
                    $mailer->WordWrap = 79;
                    $mailer->CharSet = 'UTF-8';

                    // v1.2.0: a Moodle-style Message-ID, as email_to_user() sets.
                    // get_mailer() does not reset MessageID on a reused mailer, so it is set every time.
                    $mailer->MessageID = function_exists('generate_email_messageid') ? generate_email_messageid() : '';

                    // v1.2.0: DKIM signing, copied from core email_to_user(). Without it, mail
                    // built here was unsigned even when the site has DKIM configured
                    // (Site admin > Server > Email > Outgoing mail > DKIM selector), which
                    // makes external mailboxes such as Gmail more likely to spam or drop it.
                    // The fields are cleared first because get_mailer() may reuse the object.
                    $mailer->DKIM_domain = '';
                    $mailer->DKIM_private = '';
                    $mailer->DKIM_selector = '';
                    $mailer->DKIM_identity = '';
                    if (!empty($CFG->emaildkimselector)) {
                        $domain = substr(strrchr($mailer->From, '@'), 1);
                        $pempath = "{$CFG->dataroot}/dkim/{$domain}/{$CFG->emaildkimselector}.private";
                        if (file_exists($pempath)) {
                            $mailer->DKIM_domain = $domain;
                            $mailer->DKIM_private = $pempath;
                            $mailer->DKIM_selector = $CFG->emaildkimselector;
                            $mailer->DKIM_identity = $mailer->From;
                        } else {
                            debugging("Birthday Flyer: DKIM selector set but no key found at $pempath", DEBUG_DEVELOPER);
                        }
                    }

                    if ($html !== '') {
                        $mailer->isHTML(true);
                        // v1.2.0: quoted-printable, as core email_to_user() uses for HTML.
                        // The reset mailer defaults to 8bit, which some relays without
                        // 8BITMIME support can damage.
                        $mailer->Encoding = 'quoted-printable';

                        // GitHub change (25 November 2026): Embed Birthday Flyer
                        // template and Thought of the Day images as CID resources.
                        // Email clients cannot reliably load Moodle-relative URLs,
                        // and a local development server may not be publicly reachable.
                        // CID images travel inside the email, so Gmail on mobile can
                        // display them without requesting the image from the Moodle site.
                        $html = $this->embed_email_images($mailer, $html);

                        $mailer->Body = $html;
                        $mailer->AltBody = $plain;
                    } else {
                        $mailer->isHTML(false);
                        $mailer->Body = $plain;
                    }

                    // Preserve Moodle's no-reply address as Reply-To.
                    // v1.2.0: get_string('noreply') does not exist in core 'moodle' strings, so
                    // the Reply-To name showed as "[[noreply]]" in mail clients. 'noreplyname'
                    // ("Do not reply to this email") is the core string meant for this.
                    if (!empty($CFG->noreplyaddress) && validate_email($CFG->noreplyaddress)) {
                        $mailer->addReplyTo($CFG->noreplyaddress, get_string('noreplyname'));
                    }

                    // Keep the template's optional .eml attachment.
                    if ($attachmentpath !== '' && is_readable($attachmentpath) && $attachmentname !== '') {
                        $mailer->addAttachment($attachmentpath, $attachmentname);
                    }

                    // Retain any custom headers configured on Moodle's no-reply user.
                    if (!empty($from->customheaders)) {
                        $headers = is_array($from->customheaders) ? $from->customheaders : preg_split('/\r\n|\r|\n/', $from->customheaders);
                        foreach ($headers as $header) {
                            if (is_string($header) && strpos($header, ':') !== false) {
                                [$headername, $headervalue] = explode(':', $header, 2);
                                $mailer->addCustomHeader(trim($headername), trim($headervalue));
                            }
                        }
                    }

                    // v1.2.0: find out, per address, which recipients the mail server accepted.
                    // Why: when the server refuses only the CC address (common when a company
                    // relay does not allow external recipients such as Gmail), PHPMailer still
                    // delivers to the employee but send() returns false. Before v1.2.0 that
                    // was logged as "failed" for the employee and the missing CC was invisible;
                    // with retries it would even re-send to the employee. PHPMailer reports each
                    // recipient through action_function after the message data was accepted,
                    // so this does not depend on the wording of the error text.
                    // PHPMailer older than 6.5 is not trusted for this (old behaviour is kept).
                    $accepted = [];
                    $trustcallback = defined('\PHPMailer\PHPMailer\PHPMailer::VERSION') &&
                        version_compare(\PHPMailer\PHPMailer\PHPMailer::VERSION, '6.5.0', '>=');
                    $mailer->action_function = function($issent, $to, $cc = []) use (&$accepted) {
                        if (!$issent) {
                            return;
                        }
                        foreach (array_merge((array)$to, (array)$cc) as $address) {
                            $address = is_array($address) ? (string)reset($address) : (string)$address;
                            $accepted[\core_text::strtolower($address)] = true;
                        }
                    };
                    try {
                        $sent = (bool)$mailer->send();
                    } finally {
                        // get_mailer() may reuse this object for other Moodle email; never leave our callback on it.
                        $mailer->action_function = '';
                    }
                    if ($sent) {
                        $this->lastcc = implode(', ', $cclist);
                    } else if ($trustcallback && $cclist && isset($accepted[\core_text::strtolower($toemail)])) {
                        // The employee's copy was accepted; only (some) CC addresses were refused.
                        $sent = true;
                        $acceptedcc = array_filter($cclist, function($cc) use ($accepted) {
                            return isset($accepted[\core_text::strtolower($cc)]);
                        });
                        $this->lastcc = implode(', ', $acceptedcc);
                        $this->lastreason = 'ccrejected: ' . trim((string)$mailer->ErrorInfo);
                        debugging('Birthday Flyer: employee email sent but CC refused: ' . $mailer->ErrorInfo, DEBUG_DEVELOPER);
                    } else {
                        debugging('Birthday Flyer: Moodle mailer failed: ' . $mailer->ErrorInfo, DEBUG_DEVELOPER);
                        $this->lastreason = 'mailerror: ' . $mailer->ErrorInfo;
                    }
                } catch (\Throwable $e) {
                    debugging('Birthday Flyer: exception while sending email: ' . $e->getMessage(), DEBUG_DEVELOPER);
                    $this->lastreason = 'mailerror: ' . $e->getMessage();
                    $sent = false;
                }
            }
        }

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
            // v1.2.0: all name fields present, so fullname() does not raise a developer warning.
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => '',
            'lang' => current_language(), 'timezone' => \core_date::get_server_timezone(), 'maildigest' => 0,
            'maildisplay' => 0, 'mailformat' => FORMAT_HTML, 'mnethostid' => $CFG->mnet_localhost_id,
        ];
    }
}
