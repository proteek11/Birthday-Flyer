<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_birthdayflyer',
        get_string('settings', 'local_birthdayflyer')
    );
    $ADMIN->add('localplugins', $settings);

    // Direct navigation link to the Birthday Flyer management interface.
    $pluginurl = new moodle_url('/local/birthdayflyer/index.php');
    $settings->add(new admin_setting_description(
        'local_birthdayflyer/pluginlink',
        get_string('pluginlinktitle', 'local_birthdayflyer'),
        html_writer::link(
            $pluginurl,
            get_string('openplugin', 'local_birthdayflyer'),
            ['class' => 'btn btn-primary']
        )
    ));

    $settings->add(new admin_setting_heading(
        'local_birthdayflyer/emailcontrols',
        get_string('emailcontrolsheading', 'local_birthdayflyer'),
        get_string('emailcontrolsintro', 'local_birthdayflyer')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_birthdayflyer/send_thought_of_day',
        get_string('settingthought', 'local_birthdayflyer'),
        get_string('settingthought_desc', 'local_birthdayflyer'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_birthdayflyer/send_employee_birthday',
        get_string('settingemployeebirthday', 'local_birthdayflyer'),
        get_string('settingemployeebirthday_desc', 'local_birthdayflyer'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_birthdayflyer/send_family_birthday',
        get_string('settingfamilybirthday', 'local_birthdayflyer'),
        get_string('settingfamilybirthday_desc', 'local_birthdayflyer'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_birthdayflyer/send_work_anniversary',
        get_string('settingworkanniversary', 'local_birthdayflyer'),
        get_string('settingworkanniversary_desc', 'local_birthdayflyer'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_birthdayflyer/send_marriage_anniversary',
        get_string('settingmarriageanniversary', 'local_birthdayflyer'),
        get_string('settingmarriageanniversary_desc', 'local_birthdayflyer'),
        1
    ));

    // Added 16 November 2026: configure an optional global CC recipient for all
    // Birthday Flyer scheduled emails. CC is disabled by default to preserve
    // the existing delivery behaviour until an administrator enables it.
    $settings->add(new admin_setting_heading(
        'local_birthdayflyer/ccsettingsheading',
        get_string('ccsettingsheading', 'local_birthdayflyer'),
        get_string('ccsettingsintro', 'local_birthdayflyer')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_birthdayflyer/enable_email_cc',
        get_string('enableemailcc', 'local_birthdayflyer'),
        get_string('enableemailcc_desc', 'local_birthdayflyer'),
        0
    ));

    // v1.2.0 (25 November 2026): the CC address box now accepts several addresses
    // (comma, semicolon or one per line). Same config name as before, so the
    // address already saved keeps working. Invalid addresses are named in an
    // error and nothing is saved, instead of the old silent empty value.
    $settings->add(new \local_birthdayflyer\admin\setting_emaillist(
        'local_birthdayflyer/email_cc_address',
        get_string('emailccaddress', 'local_birthdayflyer'),
        get_string('emailccaddress_desc', 'local_birthdayflyer'),
        '',
        PARAM_RAW_TRIMMED,
        '60',
        '3'
    ));

    // v1.2.0 (25 November 2026): choose which email types carry the CC.
    // Thought of the Day goes to every employee, so CCing it put one copy per
    // employee in the CC mailbox every morning and used SMTP quota; it is off by
    // default. Birthdays and anniversaries stay on (same as before).
    $cctypes = [
        'thought' => 0,
        'birthdayemployee' => 1,
        'birthdayrelationship' => 1,
        'workanniversary' => 1,
        'marriageanniversary' => 1,
    ];
    foreach ($cctypes as $cctype => $ccdefault) {
        $settings->add(new admin_setting_configcheckbox(
            'local_birthdayflyer/cc_' . $cctype,
            get_string('cctype_' . $cctype, 'local_birthdayflyer'),
            get_string('cctype_desc', 'local_birthdayflyer'),
            $ccdefault
        ));
    }

    // v1.2.0 (25 November 2026): delivery and layout options. Defaults keep the
    // existing behaviour exactly (original overlay layout, no pause).
    $settings->add(new admin_setting_heading(
        'local_birthdayflyer/deliveryheading',
        get_string('deliveryheading', 'local_birthdayflyer'),
        get_string('deliveryheading_desc', 'local_birthdayflyer')
    ));

    $settings->add(new admin_setting_configselect(
        'local_birthdayflyer/thought_layout',
        get_string('thoughtlayout', 'local_birthdayflyer'),
        get_string('thoughtlayout_desc', 'local_birthdayflyer'),
        'overlay',
        [
            'overlay' => get_string('thoughtlayout_overlay', 'local_birthdayflyer'),
            'emailsafe' => get_string('thoughtlayout_emailsafe', 'local_birthdayflyer'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'local_birthdayflyer/send_delay_ms',
        get_string('senddelayms', 'local_birthdayflyer'),
        get_string('senddelayms_desc', 'local_birthdayflyer'),
        0,
        PARAM_INT
    ));
}
