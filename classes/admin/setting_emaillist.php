<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_birthdayflyer\admin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

use local_birthdayflyer\local\emaillist;

/**
 * Admin setting holding one or more CC email addresses.
 *
 * Added in v1.2.0 (25 November 2026). It replaces the old single-address
 * PARAM_EMAIL text box but keeps the same config name
 * (local_birthdayflyer/email_cc_address), so an existing single address keeps
 * working after the upgrade without the administrator touching anything.
 *
 * Why validate here: with PARAM_EMAIL, typing "a@x.com, b@x.com" made Moodle
 * silently store an empty value and CC stopped without any visible error.
 * Now each wrong address is named in the error message and nothing is saved
 * until the list is correct.
 */
class setting_emaillist extends \admin_setting_configtextarea {

    /**
     * Validate the list. Empty is allowed (CC simply has no recipients).
     *
     * @param string $data
     * @return true|string True when valid, otherwise the error to show.
     */
    public function validate($data) {
        $parsed = emaillist::parse((string)$data);
        if ($parsed['invalid']) {
            return get_string('emailccaddressinvalid', 'local_birthdayflyer', implode(', ', $parsed['invalid']));
        }
        if (count($parsed['valid']) > emaillist::MAX_ADDRESSES) {
            return get_string('emailccaddresstoomany', 'local_birthdayflyer', emaillist::MAX_ADDRESSES);
        }
        return true;
    }

    /**
     * Store the list in one normalised form ("a@x.com, b@y.com") after validation.
     *
     * @param string $data
     * @return string Empty string on success, error otherwise (Moodle convention).
     */
    public function write_setting($data) {
        $validated = $this->validate($data);
        if ($validated !== true) {
            return $validated;
        }
        $parsed = emaillist::parse((string)$data);
        return parent::write_setting(implode(', ', $parsed['valid']));
    }
}
