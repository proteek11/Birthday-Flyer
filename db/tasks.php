<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_birthdayflyer\task\daily_employee_digest',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '7',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
