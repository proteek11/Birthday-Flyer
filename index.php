<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
require('../../config.php');

use local_birthdayflyer\form\employee_form;
use local_birthdayflyer\form\employee_csv_import_form;
use local_birthdayflyer\form\thought_form;

require_login();
$context = context_system::instance();
require_capability('local/birthdayflyer:view', $context);

$editid = optional_param('edit', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);
$confirmdelete = optional_param('confirmdelete', 0, PARAM_BOOL);
$search = trim(optional_param('search', '', PARAM_RAW_TRIMMED));
$pageparams = [];
if ($editid) {
    $pageparams['edit'] = $editid;
} else if ($deleteid) {
    $pageparams['delete'] = $deleteid;
}
if ($search !== '') {
    $pageparams['search'] = $search;
}
$PAGE->set_url(new moodle_url('/local/birthdayflyer/index.php', $pageparams));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('employeemgmt', 'local_birthdayflyer'));
$PAGE->set_heading(get_string('employeemgmt', 'local_birthdayflyer'));
$PAGE->requires->css(new moodle_url('/local/birthdayflyer/styles.css'));
// Keep the dashboard in Moodle's standard navigation trail.
$PAGE->navbar->add(get_string('employeemgmt', 'local_birthdayflyer'), new moodle_url('/local/birthdayflyer/index.php'));

$canmanage = has_capability('local/birthdayflyer:manage', $context);
// Delete employee records only after a Moodle confirmation and sesskey check.
if ($deleteid) {
    require_capability('local/birthdayflyer:manage', $context);
    $deleterecord = $DB->get_record('local_birthdayflyer', ['id' => $deleteid], '*', MUST_EXIST);
    if ($confirmdelete) {
        require_sesskey();
        $DB->delete_records('local_birthdayflyer_sent', ['employeeid' => $deleterecord->id]);
        $DB->delete_records('local_birthdayflyer', ['id' => $deleterecord->id]);
        redirect(new moodle_url('/local/birthdayflyer/index.php'), get_string('employeedeleted', 'local_birthdayflyer'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo html_writer::start_div('employeemgmt-dashboard employeemgmt-subpage');
    echo $OUTPUT->confirm(get_string('confirmdeleteemployee', 'local_birthdayflyer', format_string($deleterecord->empfullname ?: $deleterecord->empid)),
        new moodle_url('/local/birthdayflyer/index.php', ['delete' => $deleteid, 'confirmdelete' => 1, 'sesskey' => sesskey()]),
        new moodle_url('/local/birthdayflyer/index.php'));
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}
$record = null;

if ($editid) {
    require_capability('local/birthdayflyer:manage', $context);
    $record = $DB->get_record('local_birthdayflyer', ['id' => $editid], '*', MUST_EXIST);
}

if ($canmanage) {
    $mform = new employee_form(null, ['recordid' => $editid]);
    if ($record) {
        $formdata = clone $record;
        $formdata->recordid = (int)$record->id;
        // Populate date selectors from the canonical timestamp columns.
        $datemap = [
            'dob' => 'date_of_birth_stamp',
            'dateofjoining' => 'date_of_joining_stamp',
            'marriagedate' => 'marriage_date_stamp',
            'fatherdob' => 'father_dob_stamp',
            'motherdob' => 'mother_dob_stamp',
            'wifedob' => 'wife_dob_stamp',
            'sondob' => 'son_dob_stamp',
            'daughterdob' => 'daughter_dob_stamp',
            // Added 18 November 2026: edit selectors for the new family dates.
            'husbanddob' => 'husband_dob_stamp',
            'daughter2dob' => 'daughter2_dob_stamp',
            'son2dob' => 'son2_dob_stamp',
        ];
        foreach ($datemap as $formfield => $dbfield) {
            $formdata->{$formfield} = empty($record->{$dbfield}) ? 0 : (int)$record->{$dbfield};
        }
        $mform->set_data($formdata);
    }

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/birthdayflyer/index.php'));
    } else if ($data = $mform->get_data()) {
        $empid = trim($data->empid);

        // Resolve the record being edited from submitted form data. This remains
        // reliable even if the browser does not preserve the ?edit=... URL.
        $submittedid = (int)($data->recordid ?? 0);
        $editrecord = null;
        if ($submittedid > 0) {
            $editrecord = $DB->get_record('local_birthdayflyer', ['id' => $submittedid], '*', MUST_EXIST);
        }

        // Employee IDs are unique, but the record currently being edited is
        // not a duplicate of itself.
        $duplicateparams = ['empid' => $empid];
        $duplicateselect = 'empid = :empid';
        if ($editrecord) {
            $duplicateselect .= ' AND id <> :currentid';
            $duplicateparams['currentid'] = $editrecord->id;
        }
        $duplicate = $DB->record_exists_select('local_birthdayflyer', $duplicateselect, $duplicateparams);

        if ($duplicate) {
            \core\notification::error(get_string('employeeidexists', 'local_birthdayflyer'));
        } else {
            $now = time();
            $save = new stdClass();
            $save->empid = $empid;
            $save->empfullname = trim($data->empfullname);
            $save->empemail = trim($data->empemail);
            $servertimezone = \core_date::get_server_timezone();
            // Store each date only twice: readable text plus its Unix timestamp.
            $datemap = [
                'dob' => ['date_of_birth', 'date_of_birth_stamp'],
                'dateofjoining' => ['date_of_joining', 'date_of_joining_stamp'],
                'marriagedate' => ['marriage_date', 'marriage_date_stamp'],
                'fatherdob' => ['father_dob', 'father_dob_stamp'],
                'motherdob' => ['mother_dob', 'mother_dob_stamp'],
                'wifedob' => ['wife_dob', 'wife_dob_stamp'],
                'sondob' => ['son_dob', 'son_dob_stamp'],
                'daughterdob' => ['daughter_dob', 'daughter_dob_stamp'],
                // Added 18 November 2026: save new family date pairs like existing dates.
                'husbanddob' => ['husband_dob', 'husband_dob_stamp'],
                'daughter2dob' => ['daughter2_dob', 'daughter2_dob_stamp'],
                'son2dob' => ['son2_dob', 'son2_dob_stamp'],
            ];
            foreach ($datemap as $formfield => [$readablefield, $stampfield]) {
                if (empty($data->{$formfield})) {
                    $save->{$readablefield} = null;
                    $save->{$stampfield} = null;
                    continue;
                }
                $dateparts = usergetdate((int)$data->{$formfield});
                $stamp = make_timestamp((int)$dateparts['year'], (int)$dateparts['mon'], (int)$dateparts['mday'], 0, 0, 0, $servertimezone);
                $save->{$readablefield} = userdate($stamp, '%d-%B-%Y', $servertimezone);
                $save->{$stampfield} = $stamp;
            }
            $save->timemodified = $now;
            $save->usermodified = $USER->id;

            if ($editrecord) {
                $save->id = $editrecord->id;
                $DB->update_record('local_birthdayflyer', $save);
                redirect(new moodle_url('/local/birthdayflyer/index.php'), get_string('recordupdated', 'local_birthdayflyer'), null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                $save->timecreated = $now;
                $DB->insert_record('local_birthdayflyer', $save);
                redirect(new moodle_url('/local/birthdayflyer/index.php'), get_string('recordsaved', 'local_birthdayflyer'), null, \core\output\notification::NOTIFY_SUCCESS);
            }
        }
    }
}


/**
 * Normalize a CSV column name so common header variations are accepted.
 */
function local_birthdayflyer_normalize_csv_header(string $header): string {
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
    $header = core_text::strtolower(trim($header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header);
    return trim($header, '_');
}

/**
 * Convert an imported date value into a Moodle timestamp.
 */
function local_birthdayflyer_csv_date_to_stamp(string $value): ?int {
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    // Excel serial dates are occasionally exported to CSV by spreadsheet tools.
    if (is_numeric($value) && (float)$value > 1 && (float)$value < 100000) {
        $serial = (float)$value;
        $days = (int)floor($serial);
        $seconds = (int)round(($serial - $days) * 86400);
        $base = new DateTimeImmutable('1899-12-30', new DateTimeZone(date_default_timezone_get()));
        $date = $base->modify('+' . $days . ' days');
        $date = $date->setTime(0, 0, 0);
        return $date->getTimestamp() + $seconds;
    }

    // Distinguish two-digit years from four-digit years explicitly.
    // PHP's Y parser accepts a short year such as "15" as year 0015;
    // the y parser applies the conventional 00-69 => 2000-2069 and
    // 70-99 => 1970-1999 mapping instead.
    $has2digityear = !preg_match('/^\\d{4}[-\\/.]/', $value)
        && (bool)preg_match('/(?:^|[-\\/ .])\\d{2}$/', $value);
    if ($has2digityear) {
        $formats = [
            '!d-m-y', '!d/m/y', '!d.m.y', '!d-M-y', '!d F y', '!j F y',
            '!m-d-y', '!m/d/y', '!m.d.y',
        ];
    } else {
        $formats = [
            '!d-m-Y', '!d/m/Y', '!d.m.Y', '!Y-m-d', '!Y/m/d', '!Y.m.d',
            '!m-d-Y', '!m/d/Y', '!m.d.Y', '!d-M-Y', '!d F Y', '!j F Y',
        ];
    }
    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->getTimestamp();
        }
    }

    return null;
}

/**
 * Store one imported employee row using the same canonical paired date fields
 * used by the manual Employee form.
 */
function local_birthdayflyer_import_employee_row(array $row, bool $updateexisting): array {
    global $DB, $USER;

    $empid = trim((string)($row['empid'] ?? ''));
    $fullname = trim((string)($row['empfullname'] ?? ''));
    $email = trim((string)($row['empemail'] ?? ''));

    if ($empid === '' || $fullname === '' || $email === '') {
        throw new moodle_exception('employeecsvrequiredfields', 'local_birthdayflyer');
    }
    if (!validate_email($email)) {
        throw new moodle_exception('employeecsvinvalidemail', 'local_birthdayflyer', '', $empid);
    }

    $existing = $DB->get_record('local_birthdayflyer', ['empid' => $empid], '*', IGNORE_MISSING);
    if ($existing && !$updateexisting) {
        return ['status' => 'skipped', 'id' => (int)$existing->id];
    }

    $save = new stdClass();
    $save->empid = $empid;
    $save->empfullname = $fullname;
    $save->empemail = $email;
    $servertimezone = \core_date::get_server_timezone();

    $fields = [
        'date_of_birth' => 'dob',
        'date_of_joining' => 'dateofjoining',
        'marriage_date' => 'marriagedate',
        'father_dob' => 'fatherdob',
        'mother_dob' => 'motherdob',
        'wife_dob' => 'wifedob',
        'son_dob' => 'sondob',
        'daughter_dob' => 'daughterdob',
        // Added 18 November 2026: CSV date mappings for additional family members.
        'husband_dob' => 'husbanddob',
        'daughter2_dob' => 'daughter2dob',
        'son2_dob' => 'son2dob',
    ];

    foreach ($fields as $readable => $csvkey) {
        $stampfield = $readable . '_stamp';
        $value = trim((string)($row[$csvkey] ?? ''));

        // On an update, an empty CSV date leaves the existing date untouched.
        if ($value === '' && $existing) {
            continue;
        }
        if ($value === '') {
            $save->{$readable} = null;
            $save->{$stampfield} = null;
            continue;
        }

        $stamp = local_birthdayflyer_csv_date_to_stamp($value);
        if ($stamp === null) {
            throw new moodle_exception('employeecsvinvaliddate', 'local_birthdayflyer', '', $csvkey . ': ' . $value);
        }

        // Normalize to midnight in Moodle's server timezone, matching the manual form.
        $parts = usergetdate($stamp);
        $stamp = make_timestamp((int)$parts['year'], (int)$parts['mon'], (int)$parts['mday'], 0, 0, 0, $servertimezone);
        $save->{$readable} = userdate($stamp, '%d-%B-%Y', $servertimezone);
        $save->{$stampfield} = $stamp;
    }

    $save->timemodified = time();
    $save->usermodified = $USER->id;

    if ($existing) {
        $save->id = $existing->id;
        $DB->update_record('local_birthdayflyer', $save);
        return ['status' => 'updated', 'id' => (int)$existing->id];
    }

    $save->timecreated = time();
    $id = $DB->insert_record('local_birthdayflyer', $save);
    return ['status' => 'inserted', 'id' => (int)$id];
}


$employeecsvform = null;
if ($canmanage) {
    $employeecsvform = new employee_csv_import_form();
    if ($employeecsvform->is_cancelled()) {
        redirect(new moodle_url('/local/birthdayflyer/index.php'));
    } else if ($csvdata = $employeecsvform->get_data()) {
        require_sesskey();
        try {
            $content = $employeecsvform->get_file_content('csvfile');
            if ($content === false || trim($content) === '') {
                throw new moodle_exception('employeecsvempty', 'local_birthdayflyer');
            }
            // Strip UTF-8 BOM and normalize line endings before parsing.
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $lines = preg_split('/\n/', $content);
            $lines = array_values(array_filter($lines, static function($line) { return trim($line) !== ''; }));
            if (!$lines) {
                throw new moodle_exception('employeecsvempty', 'local_birthdayflyer');
            }

            // Detect comma, semicolon or tab separated CSV exports.
            $headerline = $lines[0];
            $counts = [',' => substr_count($headerline, ','), ';' => substr_count($headerline, ';'), "\t" => substr_count($headerline, "\t")];
            arsort($counts);
            $delimiter = (string)array_key_first($counts);
            if ($counts[$delimiter] === 0) {
                throw new moodle_exception('employeecsvbadheaders', 'local_birthdayflyer');
            }

            $headers = str_getcsv($headerline, $delimiter);
            $headers = array_map('local_birthdayflyer_normalize_csv_header', $headers);
            $aliases = [
                // Canonical database-style CSV headers.
                'empid' => 'empid', 'empfullname' => 'empfullname', 'empemail' => 'empemail',
                'date_of_birth' => 'dob', 'date_of_joining' => 'dateofjoining',
                'marriage_date' => 'marriagedate', 'father_dob' => 'fatherdob', 'mother_dob' => 'motherdob',
                'wife_dob' => 'wifedob', 'son_dob' => 'sondob', 'daughter_dob' => 'daughterdob',
                // Added 18 November 2026: canonical CSV headers for new family dates.
                'husband_dob' => 'husbanddob', 'daughter2_dob' => 'daughter2dob', 'son2_dob' => 'son2dob',
                // Friendly/legacy aliases remain supported for compatibility.
                'employee_id' => 'empid', 'employeeid' => 'empid', 'emp_id' => 'empid', 'id' => 'empid',
                'employee_name' => 'empfullname', 'employee_full_name' => 'empfullname', 'full_name' => 'empfullname', 'fullname' => 'empfullname', 'name' => 'empfullname',
                'employee_email' => 'empemail', 'email' => 'empemail', 'email_address' => 'empemail',
                'dateofbirth' => 'dob', 'birth_date' => 'dob', 'dob' => 'dob',
                'dateofjoining' => 'dateofjoining', 'joining_date' => 'dateofjoining', 'doj' => 'dateofjoining',
                'marriagedate' => 'marriagedate', 'marriage_anniversary' => 'marriagedate',
                'father_date_of_birth' => 'fatherdob', 'fatherdob' => 'fatherdob',
                'mother_date_of_birth' => 'motherdob', 'motherdob' => 'motherdob',
                'wife_date_of_birth' => 'wifedob', 'wifedob' => 'wifedob',
                'son_date_of_birth' => 'sondob', 'sondob' => 'sondob',
                'daughter_date_of_birth' => 'daughterdob', 'daughterdob' => 'daughterdob',
                'husband_date_of_birth' => 'husbanddob', 'husbanddob' => 'husbanddob',
                'second_daughter_dob' => 'daughter2dob', 'daughter_2_dob' => 'daughter2dob', 'daughter2dob' => 'daughter2dob',
                'second_son_dob' => 'son2dob', 'son_2_dob' => 'son2dob', 'son2dob' => 'son2dob',
            ];
            $mapped = [];
            foreach ($headers as $index => $header) {
                if (isset($aliases[$header])) {
                    $mapped[$index] = $aliases[$header];
                }
            }
            $required = ['empid', 'empfullname', 'empemail'];
            if (count(array_intersect($required, array_values($mapped))) !== count($required)) {
                throw new moodle_exception('employeecsvbadheaders', 'local_birthdayflyer');
            }

            $inserted = $updated = $skipped = $failed = 0;
            $errors = [];
            foreach (array_slice($lines, 1) as $lineNumber => $line) {
                $csvrow = str_getcsv($line, $delimiter);
                $row = [];
                foreach ($mapped as $index => $key) {
                    $row[$key] = isset($csvrow[$index]) ? trim((string)$csvrow[$index]) : '';
                }
                if (implode('', $row) === '') {
                    continue;
                }
                try {
                    $result = local_birthdayflyer_import_employee_row($row, !empty($csvdata->updateexisting));
                    if ($result['status'] === 'inserted') {
                        $inserted++;
                    } else if ($result['status'] === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = get_string('employeecsvrowerror', 'local_birthdayflyer', [
                        'row' => $lineNumber + 2,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = get_string('employeecsvsummary', 'local_birthdayflyer', [
                'inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped, 'failed' => $failed,
            ]);
            if ($errors) {
                $message .= html_writer::empty_tag('br') . implode(html_writer::empty_tag('br'), array_map('s', array_slice($errors, 0, 10)));
                if (count($errors) > 10) {
                    $message .= html_writer::empty_tag('br') . get_string('employeecsvmoreerrors', 'local_birthdayflyer', count($errors) - 10);
                }
            }
            \core\notification::add($message, $failed ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS);
        } catch (\Throwable $e) {
            \core\notification::error(get_string('employeecsvimportfailed', 'local_birthdayflyer', $e->getMessage()));
        }

        // Post/Redirect/Get: clear the submitted filepicker draft after processing.
        // This prevents the selected CSV from remaining visible on refresh and
        // prevents the browser from offering to resubmit the import POST.
        redirect(new moodle_url('/local/birthdayflyer/index.php'));
    }
}

// Search across all employee fields represented in the employee table, including
// family date fields, so administrators can find a record using any keyword.
if ($search !== '') {
    $searchfields = [
        'empid', 'empfullname', 'empemail',
        'date_of_birth', 'date_of_joining', 'marriage_date',
        'father_dob', 'mother_dob', 'wife_dob', 'son_dob', 'daughter_dob', 'husband_dob', 'daughter2_dob', 'son2_dob',
    ];
    $searchparts = [];
    $searchparams = [];
    foreach ($searchfields as $fieldindex => $fieldname) {
        // sql_like() requires the SQL placeholder as its second argument.
        // Give each field a unique named placeholder for portable Moodle DML.
        $paramname = 'employeesearch' . $fieldindex;
        $searchparts[] = $DB->sql_like($fieldname, ':' . $paramname, false, false);
        $searchparams[$paramname] = '%' . $DB->sql_like_escape($search) . '%';
    }
    $employees = $DB->get_records_select(
        'local_birthdayflyer',
        '(' . implode(' OR ', $searchparts) . ')',
        $searchparams,
        'empid ASC'
    );
} else {
    $employees = $DB->get_records('local_birthdayflyer', null, 'empid ASC');
}
echo $OUTPUT->header();

echo html_writer::start_div('employeemgmt-dashboard');

// Primary navigation shortcuts keep the main plugin areas easy to discover.
if ($canmanage) {
    $navigation = html_writer::link(new moodle_url('/local/birthdayflyer/index.php'), get_string('employees', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link is-active']) .
        html_writer::link(new moodle_url('/local/birthdayflyer/import.php'), get_string('importthoughts', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
        html_writer::link(new moodle_url('/local/birthdayflyer/templates.php'), get_string('managetemplates', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
        html_writer::link(new moodle_url('/local/birthdayflyer/reports.php'), get_string('reports', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']);
    echo html_writer::div($navigation, 'employeemgmt-navigation', ['aria-label' => get_string('pluginareanavigation', 'local_birthdayflyer')]);
}

echo html_writer::start_div('employeemgmt-hero');
echo html_writer::start_div('employeemgmt-hero-copy');
echo html_writer::tag('div', get_string('pluginname', 'local_birthdayflyer'), ['class' => 'employeemgmt-eyebrow']);
echo html_writer::tag('h2', get_string('dashboardtitle', 'local_birthdayflyer'), ['class' => 'employeemgmt-title']);
echo html_writer::tag('p', get_string('dashboardintro', 'local_birthdayflyer'), ['class' => 'employeemgmt-subtitle']);
// Navigation tabs above provide access to the other sections; avoid duplicate buttons here.
echo html_writer::end_div();
echo html_writer::tag('div', html_writer::tag('span', get_string('plugininitials', 'local_birthdayflyer'), ['class' => 'employeemgmt-mark']), ['class' => 'employeemgmt-hero-mark']);
echo html_writer::end_div();

echo html_writer::start_div('employeemgmt-stats');
$stats = [
    ['label' => get_string('totalemployees', 'local_birthdayflyer'), 'value' => count($employees), 'icon' => 'fa-users'],
    ['label' => get_string('recordsupdatedlabel', 'local_birthdayflyer'), 'value' => $employees ? userdate(max(array_map(static function($e) { return (int)$e->timemodified; }, $employees)), get_string('strftimedate')) : '—', 'icon' => 'fa-clock-o'],
];
foreach ($stats as $stat) {
    echo html_writer::start_div('employeemgmt-stat-card');
    echo html_writer::tag('span', '', ['class' => 'employeemgmt-stat-icon ' . $stat['icon'], 'aria-hidden' => 'true']);
    echo html_writer::tag('div', s((string)$stat['value']), ['class' => 'employeemgmt-stat-value']);
    echo html_writer::tag('div', s($stat['label']), ['class' => 'employeemgmt-stat-label']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

if ($canmanage) {
    echo html_writer::start_div('employeemgmt-panel employeemgmt-form-panel');
    echo html_writer::start_div('employeemgmt-panel-heading');
    echo html_writer::tag('div', html_writer::tag('h3', $record ? get_string('editemployee', 'local_birthdayflyer') : get_string('addemployee', 'local_birthdayflyer')) .
        html_writer::tag('p', get_string('formintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
    if ($record) {
        echo html_writer::link(new moodle_url('/local/birthdayflyer/index.php'), get_string('cancel', 'local_birthdayflyer'), ['class' => 'btn btn-outline-secondary employeemgmt-cancel']);
    }
    echo html_writer::end_div();
    $mform->display();
    echo html_writer::end_div();

    echo html_writer::start_div('employeemgmt-panel employeemgmt-form-panel employeemgmt-csv-panel');
    echo html_writer::start_div('employeemgmt-panel-heading');
    echo html_writer::tag('div', html_writer::tag('h3', get_string('importemployees', 'local_birthdayflyer')) .
        html_writer::tag('p', get_string('employeecsvintro', 'local_birthdayflyer')) .
        html_writer::link(new moodle_url('/local/birthdayflyer/sample_employee_import.csv', ['v' => 2026111803]), get_string('downloademployeecsvsample', 'local_birthdayflyer'), ['class' => 'employeemgmt-csv-sample-link']), ['class' => 'employeemgmt-heading-copy']);
    echo html_writer::end_div();
    $employeecsvform->display();
    echo html_writer::end_div();
}

echo html_writer::start_div('employeemgmt-panel employeemgmt-list-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', get_string('employees', 'local_birthdayflyer')) .
    html_writer::tag('p', get_string('employeesintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::tag('span', count($employees) . ' ' . get_string('recordslabel', 'local_birthdayflyer'), ['class' => 'employeemgmt-record-count']);
echo html_writer::end_div();

// Keyword search for any employee-table field.
$searchform = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => (new moodle_url('/local/birthdayflyer/index.php'))->out(false),
    'class' => 'employeemgmt-search-form mb-3',
]);
$searchform .= html_writer::empty_tag('input', [
    'type' => 'search',
    'name' => 'search',
    'value' => $search,
    'placeholder' => get_string('employeesearchplaceholder', 'local_birthdayflyer'),
    'aria-label' => get_string('employeesearchplaceholder', 'local_birthdayflyer'),
    'class' => 'form-control',
]);
$searchform .= html_writer::tag('button', get_string('search'), ['type' => 'submit', 'class' => 'btn btn-primary']);
if ($search !== '') {
    $searchform .= html_writer::link(
        new moodle_url('/local/birthdayflyer/index.php'),
        get_string('clearsearch', 'local_birthdayflyer'),
        ['class' => 'btn btn-outline-secondary']
    );
}
$searchform .= html_writer::end_tag('form');
echo $searchform;

if (!$employees) {
    echo html_writer::start_div('employeemgmt-empty-state');
    echo html_writer::tag('div', '＋', ['class' => 'employeemgmt-empty-icon', 'aria-hidden' => 'true']);
    echo html_writer::tag('h4', get_string('noemployees', 'local_birthdayflyer'));
    echo html_writer::tag('p', get_string('emptyintro', 'local_birthdayflyer'));
    echo html_writer::end_div();
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable employeemgmt-table';
    $table->head = [
        get_string('employee', 'local_birthdayflyer'),
        get_string('employeefullname', 'local_birthdayflyer'),
        get_string('employeeemail', 'local_birthdayflyer'),
        get_string('dob', 'local_birthdayflyer'),
        get_string('dateofjoining', 'local_birthdayflyer'),
        get_string('marriagedate', 'local_birthdayflyer'),
    ];
    if ($canmanage) {
        $table->head[] = get_string('actions', 'local_birthdayflyer');
    }

    $rownumber = 0;
    foreach ($employees as $employee) {
        $rownumber++;
        $employeeidentity = html_writer::start_div('employeemgmt-identity');
        $employeeidentity .= html_writer::tag('span', (string)$rownumber, ['class' => 'employeemgmt-avatar']);
        $employeeidentity .= html_writer::start_div();
        $employeeidentity .= html_writer::tag('strong', format_string($employee->empid), ['class' => 'employeemgmt-employee-name']);
        $employeeidentity .= html_writer::tag('span', get_string('employeeid', 'local_birthdayflyer'), ['class' => 'employeemgmt-muted']);
        $employeeidentity .= html_writer::end_div();
        $employeeidentity .= html_writer::end_div();

        $row = [
            $employeeidentity,
            html_writer::tag('span', s($employee->empfullname ?? ''), ['class' => 'employeemgmt-email']),
            html_writer::tag('span', s($employee->empemail), ['class' => 'employeemgmt-email']),
            empty($employee->date_of_birth_stamp) ? '—' : userdate($employee->date_of_birth_stamp, get_string('strftimedate')),
            empty($employee->date_of_joining_stamp) ? '—' : userdate($employee->date_of_joining_stamp, get_string('strftimedate')),
            empty($employee->marriage_date_stamp) ? '—' : userdate($employee->marriage_date_stamp, get_string('strftimedate')),
        ];
        if ($canmanage) {
            $row[] = html_writer::link(
                new moodle_url('/local/birthdayflyer/index.php', ['edit' => $employee->id]),
                html_writer::tag('span', '✎', ['aria-hidden' => 'true']) . ' ' . get_string('edit', 'local_birthdayflyer'),
                ['class' => 'btn btn-sm btn-outline-primary employeemgmt-edit-button']
            ) . ' ' . html_writer::link(
                new moodle_url('/local/birthdayflyer/index.php', ['delete' => $employee->id, 'sesskey' => sesskey()]),
                html_writer::tag('span', '×', ['aria-hidden' => 'true']) . ' ' . get_string('delete'),
                ['class' => 'btn btn-sm btn-outline-danger employeemgmt-delete-button']
            );
        }
        $table->data[] = $row;
    }
    echo html_writer::start_div('employeemgmt-table-wrap');
    echo html_writer::table($table);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
