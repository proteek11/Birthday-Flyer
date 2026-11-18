<?php
require('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/birthdayflyer:manage', $context);

$PAGE->set_url(new moodle_url('/local/birthdayflyer/reports.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('reports', 'local_birthdayflyer'));
$PAGE->set_heading(get_string('reports', 'local_birthdayflyer'));
$PAGE->requires->css(new moodle_url('/local/birthdayflyer/styles.css'));
$PAGE->navbar->add(get_string('employees', 'local_birthdayflyer'), new moodle_url('/local/birthdayflyer/index.php'));
$PAGE->navbar->add(get_string('reports', 'local_birthdayflyer'));

$now = time();
$timezone = \core_date::get_server_timezone();
$today = usergetdate($now, $timezone);
$todaykey = make_timestamp((int)$today['year'], (int)$today['mon'], (int)$today['mday'], 0, 0, 0, $timezone);
$enddate = $todaykey + (30 * DAYSECS);
$employees = $DB->get_records('local_birthdayflyer', null, 'empfullname ASC');
$thoughts = $DB->get_records_select('local_birthdayflyer_thought', 'thoughtdate >= :start AND thoughtdate <= :end', ['start' => $todaykey, 'end' => $enddate], 'thoughtdate ASC', '*', 0, 10);
$templates = $DB->get_records('local_birthdayflyer_template', null, 'eventtype ASC, timemodified DESC');
$recentcutoff = $todaykey - (29 * DAYSECS);
$deliverypage = max(0, optional_param('deliverypage', 0, PARAM_INT));
$deliverypagesize = 10;
$deliveryparams = ['cutoff' => $recentcutoff, 'today' => $todaykey];
$recentdeliverytotal = $DB->count_records_select('local_birthdayflyer_sent', 'senddate >= :cutoff AND senddate <= :today', $deliveryparams);
$recentdelivery = $DB->get_records_select('local_birthdayflyer_sent', 'senddate >= :cutoff AND senddate <= :today', $deliveryparams, 'senddate DESC, id DESC', '*', $deliverypage * $deliverypagesize, $deliverypagesize);

$activecount = 0;
foreach ($templates as $template) { if (!empty($template->active)) { $activecount++; } }

$eventmap = [
    'date_of_birth_stamp' => 'reportbirthdayemployee',
    'father_dob_stamp' => 'reportbirthdayfather',
    'mother_dob_stamp' => 'reportbirthdaymother',
    'wife_dob_stamp' => 'reportbirthdaywife',
    'son_dob_stamp' => 'reportbirthdayson',
    'daughter_dob_stamp' => 'reportbirthdaydaughter',
    // Added 18 November 2026: include additional family birthdays in reports.
    'husband_dob_stamp' => 'reportbirthdayhusband',
    'daughter2_dob_stamp' => 'reportbirthdaydaughter2',
    'son2_dob_stamp' => 'reportbirthdayson2',
];
$events = [];
foreach ($employees as $employee) {
    foreach ($eventmap as $field => $langkey) {
        if (empty($employee->{$field})) { continue; }
        $source = usergetdate((int)$employee->{$field}, $timezone);
        $year = (int)$today['year'];
        $eventstamp = make_timestamp($year, (int)$source['mon'], (int)$source['mday'], 0, 0, 0, $timezone);
        if ($eventstamp < $todaykey) { $eventstamp = make_timestamp($year + 1, (int)$source['mon'], (int)$source['mday'], 0, 0, 0, $timezone); }
        if ($eventstamp <= $enddate) {
            $age = max(0, (int)usergetdate($eventstamp, $timezone)['year'] - (int)$source['year']);
            $events[] = ['date' => $eventstamp, 'employee' => $employee->empfullname, 'event' => get_string($langkey, 'local_birthdayflyer'), 'years' => $age . ' ' . get_string('years', 'local_birthdayflyer')];
        }
    }
    foreach ([
        'date_of_joining_stamp' => 'reportworkanniversary',
        'marriage_date_stamp' => 'reportmarriageanniversary',
    ] as $field => $langkey) {
        if (empty($employee->{$field})) { continue; }
        $source = usergetdate((int)$employee->{$field}, $timezone);
        $year = (int)$today['year'];
        $eventstamp = make_timestamp($year, (int)$source['mon'], (int)$source['mday'], 0, 0, 0, $timezone);
        if ($eventstamp < $todaykey) { $eventstamp = make_timestamp($year + 1, (int)$source['mon'], (int)$source['mday'], 0, 0, 0, $timezone); }
        if ($eventstamp <= $enddate) {
            $years = max(0, (int)usergetdate($eventstamp, $timezone)['year'] - (int)$source['year']);
            $events[] = ['date' => $eventstamp, 'employee' => $employee->empfullname, 'event' => get_string($langkey, 'local_birthdayflyer'), 'years' => $years . ' ' . get_string('years', 'local_birthdayflyer')];
        }
    }
}
usort($events, static function($a, $b) { return $a['date'] <=> $b['date']; });

$employeeById = [];
foreach ($employees as $employee) { $employeeById[(int)$employee->id] = $employee; }

echo $OUTPUT->header();
echo html_writer::start_div('employeemgmt-dashboard employeemgmt-subpage');
$navigation = html_writer::link(new moodle_url('/local/birthdayflyer/index.php'), get_string('employees', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
    html_writer::link(new moodle_url('/local/birthdayflyer/import.php'), get_string('importthoughts', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
    html_writer::link(new moodle_url('/local/birthdayflyer/templates.php'), get_string('managetemplates', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
    html_writer::link(new moodle_url('/local/birthdayflyer/reports.php'), get_string('reports', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link is-active']);
echo html_writer::div($navigation, 'employeemgmt-navigation', ['aria-label' => get_string('pluginareanavigation', 'local_birthdayflyer')]);

echo html_writer::start_div('employeemgmt-hero employeemgmt-subpage-hero');
echo html_writer::start_div('employeemgmt-hero-copy');
echo html_writer::tag('div', get_string('pluginname', 'local_birthdayflyer'), ['class' => 'employeemgmt-eyebrow']);
echo html_writer::tag('h2', get_string('reports', 'local_birthdayflyer'), ['class' => 'employeemgmt-title']);
echo html_writer::tag('p', get_string('reportspageintro', 'local_birthdayflyer'), ['class' => 'employeemgmt-subtitle']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('employeemgmt-report-grid');
foreach ([
    [count($employees), 'reporttotalemployees'],
    [count($thoughts), 'reportthoughts'],
    [$activecount, 'reportactivetemplates'],
    [$recentdeliverytotal, 'reportdelivery30days'],
] as [$value, $label]) {
    echo html_writer::start_div('employeemgmt-report-card');
    echo html_writer::tag('div', (string)$value, ['class' => 'employeemgmt-report-card-value']);
    echo html_writer::tag('div', get_string($label, 'local_birthdayflyer'), ['class' => 'employeemgmt-report-card-label']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Upcoming celebrations.
echo html_writer::start_div('employeemgmt-panel employeemgmt-list-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', get_string('reportupcomingevents', 'local_birthdayflyer')) . html_writer::tag('p', get_string('reportupcomingeventsintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::end_div();
if (!$events) {
    echo $OUTPUT->notification(get_string('reportnoevents', 'local_birthdayflyer'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable employeemgmt-table employeemgmt-report-table';
    $table->head = [get_string('reportdate', 'local_birthdayflyer'), get_string('reportevent', 'local_birthdayflyer'), get_string('reportemployee', 'local_birthdayflyer'), get_string('reportyears', 'local_birthdayflyer')];
    foreach (array_slice($events, 0, 50) as $event) {
        $table->data[] = [userdate($event['date'], get_string('strftimedate')), html_writer::tag('span', s($event['event']), ['class' => 'report-event']), s($event['employee']), $event['years'] !== '' ? s($event['years']) : '—'];
    }
    echo html_writer::table($table);
}
echo html_writer::end_div();

// Recent delivery log.
echo html_writer::start_div('employeemgmt-panel employeemgmt-list-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', get_string('reportrecentdelivery', 'local_birthdayflyer')) . html_writer::tag('p', get_string('reportrecentdeliveryintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::end_div();
if (!$recentdelivery) {
    echo $OUTPUT->notification(get_string('reportnodelivery', 'local_birthdayflyer'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable employeemgmt-table employeemgmt-report-table';
    $table->head = [get_string('reportdeliverydate', 'local_birthdayflyer'), get_string('reportemployee', 'local_birthdayflyer'), get_string('reportstatus', 'local_birthdayflyer')];
    foreach ($recentdelivery as $delivery) {
        $employee = $employeeById[(int)$delivery->employeeid] ?? null;
        $table->data[] = [userdate((int)$delivery->senddate, get_string('strftimedate')), $employee ? s($employee->empfullname) . ' <span class="report-muted">(' . s($employee->empid) . ')</span>' : '—', get_string('reportactive', 'local_birthdayflyer')];
    }
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($recentdeliverytotal, $deliverypage, $deliverypagesize,
        new moodle_url('/local/birthdayflyer/reports.php'), 'deliverypage');
}
echo html_writer::end_div();

// Thought schedule.
echo html_writer::start_div('employeemgmt-panel employeemgmt-list-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', get_string('reportthoughtschedule', 'local_birthdayflyer')) . html_writer::tag('p', get_string('reportthoughtscheduleintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::end_div();
if (!$thoughts) {
    echo $OUTPUT->notification(get_string('reportnothoughts', 'local_birthdayflyer'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable employeemgmt-table employeemgmt-report-table';
    $table->head = [get_string('reportdate', 'local_birthdayflyer'), get_string('thoughttextcolumn', 'local_birthdayflyer')];
    foreach ($thoughts as $thought) {
        $table->data[] = [userdate((int)$thought->thoughtdate, get_string('strftimedate')), s($thought->thought)];
    }
    echo html_writer::table($table);
}
echo html_writer::end_div();

// Template status.
echo html_writer::start_div('employeemgmt-panel employeemgmt-list-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', get_string('reporttemplates', 'local_birthdayflyer')) . html_writer::tag('p', get_string('reporttemplatesintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::end_div();
if (!$templates) {
    echo $OUTPUT->notification(get_string('reportnotemplates', 'local_birthdayflyer'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable employeemgmt-table employeemgmt-report-table';
    $table->head = [get_string('templatename', 'local_birthdayflyer'), get_string('templateeventtype', 'local_birthdayflyer'), get_string('reportstatus', 'local_birthdayflyer')];
    foreach ($templates as $template) {
        $eventkey = 'eventtype' . ($template->eventtype ?? 'birthday');
        $eventlabel = get_string($eventkey, 'local_birthdayflyer');
        $table->data[] = [format_string($template->name), s($eventlabel), $template->active ? get_string('reportactive', 'local_birthdayflyer') : get_string('reportinactive', 'local_birthdayflyer')];
    }
    echo html_writer::table($table);
}
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
