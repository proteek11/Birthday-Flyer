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
}
