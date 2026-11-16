<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
require('../../config.php');

use local_birthdayflyer\form\birthday_template_form;

require_login();
$context = context_system::instance();
require_capability('local/birthdayflyer:manage', $context);
$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$PAGE->set_url(new moodle_url('/local/birthdayflyer/templates.php', $id ? ['id' => $id] : []));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('managetemplates', 'local_birthdayflyer'));
$PAGE->set_heading(get_string('managetemplates', 'local_birthdayflyer'));
$PAGE->requires->css(new moodle_url('/local/birthdayflyer/styles.css'));
// Keep the template manager connected to the main plugin dashboard in the breadcrumb trail.
$PAGE->navbar->add(get_string('employeemgmt', 'local_birthdayflyer'), new moodle_url('/local/birthdayflyer/index.php'));
$PAGE->navbar->add(get_string('managetemplates', 'local_birthdayflyer'));

if ($delete) {
    require_sesskey();
    $template = $DB->get_record('local_birthdayflyer_template', ['id' => $delete], '*', MUST_EXIST);
    if ($confirm) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_birthdayflyer', 'templateimage', $template->id);
        $fs->delete_area_files($context->id, 'local_birthdayflyer', 'templateattachment', $template->id);
        $DB->delete_records('local_birthdayflyer_template', ['id' => $template->id]);
        redirect(new moodle_url('/local/birthdayflyer/templates.php'), get_string('templatedeleted', 'local_birthdayflyer'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo html_writer::start_div('employeemgmt-dashboard employeemgmt-subpage');
    echo $OUTPUT->confirm(get_string('confirmdeletetemplate', 'local_birthdayflyer', format_string($template->name)),
        new moodle_url('/local/birthdayflyer/templates.php', ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
        new moodle_url('/local/birthdayflyer/templates.php'));
    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

$template = $id ? $DB->get_record('local_birthdayflyer_template', ['id' => $id], '*', MUST_EXIST) : null;
$form = new birthday_template_form(null, ['id' => $id]);
if ($template) {
    $draftid = file_get_unused_draft_itemid();
    $fs = get_file_storage();
    $existing = $fs->get_area_files($context->id, 'local_birthdayflyer', 'templateimage', $template->id, 'id ASC', false);
    foreach ($existing as $file) {
        $filerecord = ['contextid' => context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftid, 'filepath' => '/', 'filename' => $file->get_filename(), 'userid' => $USER->id,
            'license' => $file->get_license(), 'author' => $file->get_author(), 'source' => $file->get_source()];
        $fs->create_file_from_storedfile($filerecord, $file);
        break;
    }
    $attachmentdraftid = file_get_unused_draft_itemid();
    $attachmentfiles = $fs->get_area_files($context->id, 'local_birthdayflyer', 'templateattachment', $template->id, 'id ASC', false);
    foreach ($attachmentfiles as $attachmentfile) {
        $attachmentrecord = ['contextid' => context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $attachmentdraftid, 'filepath' => '/', 'filename' => $attachmentfile->get_filename(), 'userid' => $USER->id,
            'license' => $attachmentfile->get_license(), 'author' => $attachmentfile->get_author(), 'source' => $attachmentfile->get_source()];
        $fs->create_file_from_storedfile($attachmentrecord, $attachmentfile);
        break;
    }
    $form->set_data((object)[
        'id' => $template->id,
        'name' => $template->name,
        'subject' => $template->subject,
        'eventtype' => $template->eventtype ?? 'birthdayemployee',
        'body_html' => (string)$template->body,
        'imagefile' => $draftid,
        'attachmentfile' => $attachmentdraftid,
        'active' => (int)$template->active,
    ]);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/birthdayflyer/templates.php'));
} else if ($data = $form->get_data()) {
    $now = time();
    $eventtype = clean_param($data->eventtype ?? 'birthdayemployee', PARAM_ALPHANUMEXT);
    $validtypes = ['birthdayemployee', 'birthdayrelationship', 'birthday', 'workanniversary', 'marriageanniversary'];
    if (!in_array($eventtype, $validtypes, true)) {
        $eventtype = 'birthday';
    }
    if (!empty($data->active)) {
        // Allow one active template per event type, not just one across all types.
        $DB->set_field('local_birthdayflyer_template', 'active', 0, ['eventtype' => $eventtype]);
    }
    $save = (object)[
        'name' => trim($data->name), 'subject' => trim($data->subject), 'eventtype' => $eventtype,
        'body' => (string)$data->body_html, 'bodyformat' => FORMAT_HTML,
        'active' => empty($data->active) ? 0 : 1, 'timemodified' => $now, 'usermodified' => $USER->id,
    ];
    if ($id && $template) {
        $save->id = $template->id;
        if (empty($template->imagekey)) {
            $save->imagekey = bin2hex(random_bytes(24));
        }
        $DB->update_record('local_birthdayflyer_template', $save);
        $templateid = $template->id;
    } else {
        $save->timecreated = $now;
        $save->imagekey = bin2hex(random_bytes(24));
        $templateid = $DB->insert_record('local_birthdayflyer_template', $save);
    }
    $draftitemid = (int)($data->imagefile ?? 0);
    if ($draftitemid) {
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftitemid, 'id ASC', false);
        if ($draftfiles) {
            file_save_draft_area_files($draftitemid, $context->id, 'local_birthdayflyer', 'templateimage', $templateid,
                ['subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => 20 * 1024 * 1024]);
        }
    }
    $attachmentdraftitemid = (int)($data->attachmentfile ?? 0);
    if ($attachmentdraftitemid) {
        $fs = get_file_storage();
        $attachmentdraftfiles = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $attachmentdraftitemid, 'id ASC', false);
        foreach ($attachmentdraftfiles as $attachmentdraftfile) {
            if (strtolower(pathinfo($attachmentdraftfile->get_filename(), PATHINFO_EXTENSION)) !== 'eml') {
                continue;
            }
            file_save_draft_area_files($attachmentdraftitemid, $context->id, 'local_birthdayflyer', 'templateattachment', $templateid,
                ['subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => 20 * 1024 * 1024]);
            break;
        }
    }
    redirect(new moodle_url('/local/birthdayflyer/templates.php'), get_string('templatesaved', 'local_birthdayflyer'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$templates = $DB->get_records('local_birthdayflyer_template', null, 'timemodified DESC');
echo $OUTPUT->header();
echo html_writer::start_div('employeemgmt-dashboard employeemgmt-subpage');
// Keep the primary plugin workflows available from every management screen.
echo html_writer::div(
    html_writer::link(new moodle_url('/local/birthdayflyer/index.php'), get_string('employees', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
    html_writer::link(new moodle_url('/local/birthdayflyer/import.php'), get_string('importthoughts', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']) .
    html_writer::link(new moodle_url('/local/birthdayflyer/templates.php'), get_string('managetemplates', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link is-active']) .
    html_writer::link(new moodle_url('/local/birthdayflyer/reports.php'), get_string('reports', 'local_birthdayflyer'), ['class' => 'employeemgmt-nav-link']),
    'employeemgmt-navigation', ['aria-label' => get_string('pluginareanavigation', 'local_birthdayflyer')]);
echo html_writer::start_div('employeemgmt-hero employeemgmt-subpage-hero');
echo html_writer::start_div('employeemgmt-hero-copy');
echo html_writer::tag('div', get_string('pluginname', 'local_birthdayflyer'), ['class' => 'employeemgmt-eyebrow']);
echo html_writer::tag('h2', get_string('managetemplates', 'local_birthdayflyer'), ['class' => 'employeemgmt-title']);
echo html_writer::tag('p', get_string('templatespageintro', 'local_birthdayflyer'), ['class' => 'employeemgmt-subtitle']);
echo html_writer::end_div();
echo html_writer::div(html_writer::link(new moodle_url('/local/birthdayflyer/index.php'), get_string('dashboard', 'local_birthdayflyer'), ['class' => 'btn btn-outline-primary']), 'employeemgmt-subpage-action');
echo html_writer::end_div();
echo html_writer::start_div('employeemgmt-panel employeemgmt-form-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', $template ? get_string('editemailtemplate', 'local_birthdayflyer') : get_string('templateeditor', 'local_birthdayflyer')) . html_writer::tag('p', get_string('templateformintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::end_div();
$form->display();
echo html_writer::end_div();
echo html_writer::start_div('employeemgmt-panel employeemgmt-list-panel');
echo html_writer::start_div('employeemgmt-panel-heading');
echo html_writer::tag('div', html_writer::tag('h3', get_string('savedtemplates', 'local_birthdayflyer')) . html_writer::tag('p', get_string('savedtemplatesintro', 'local_birthdayflyer')), ['class' => 'employeemgmt-heading-copy']);
echo html_writer::end_div();
if (!$templates) {
    echo $OUTPUT->notification(get_string('notemplatesyet', 'local_birthdayflyer'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable employeemgmt-table';
    $table->head = [get_string('templatename', 'local_birthdayflyer'), get_string('templateeventtype', 'local_birthdayflyer'), get_string('templatesubject', 'local_birthdayflyer'), get_string('templateactive', 'local_birthdayflyer'), get_string('actions')];
    foreach ($templates as $item) {
        // Use Moodle URLs and sesskey protection for state-changing template actions.
        $actions = html_writer::link(
            new moodle_url('/local/birthdayflyer/templates.php', ['id' => $item->id]),
            get_string('edit'),
            ['class' => 'btn btn-sm btn-outline-primary']
        ) . ' ' . html_writer::link(
            new moodle_url('/local/birthdayflyer/templates.php', ['delete' => $item->id, 'sesskey' => sesskey()]),
            get_string('delete'),
            ['class' => 'btn btn-sm btn-outline-secondary']
        );
        $activevalue = $item->active ? get_string('yes') : get_string('no');
        $eventlabel = get_string('eventtype' . ($item->eventtype ?? 'birthday'), 'local_birthdayflyer');
        $table->data[] = [format_string($item->name), $eventlabel, s($item->subject), $activevalue, $actions];
    }
    echo html_writer::start_div('employeemgmt-table-wrap');
    echo html_writer::table($table);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
