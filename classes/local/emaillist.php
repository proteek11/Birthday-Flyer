<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_birthdayflyer\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Parse a list of email addresses typed by an administrator.
 *
 * Added in v1.2.0 (25 November 2026): the CC setting used to accept exactly one
 * address (PARAM_EMAIL). Administrators asked for several CC recipients, so the
 * setting now accepts a list. The same parser is used by the admin setting (to
 * validate on save) and by the scheduled task (to build the CC list at send time),
 * so what is accepted in the settings page is exactly what is used when sending.
 */
class emaillist {

    /** Upper limit of CC recipients. Every CC address is another recipient counted by the SMTP provider. */
    const MAX_ADDRESSES = 10;

    /**
     * Split and validate a raw address list.
     *
     * Accepted separators: comma, semicolon, whitespace and new lines.
     * Duplicates are removed case-insensitively, first spelling wins.
     *
     * @param string $raw Raw text from the setting.
     * @return array ['valid' => string[], 'invalid' => string[]]
     */
    public static function parse(string $raw): array {
        $valid = [];
        $invalid = [];
        $seen = [];
        foreach (preg_split('/[\s,;]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) as $address) {
            $key = \core_text::strtolower($address);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            if (validate_email($address)) {
                $valid[] = $address;
            } else {
                $invalid[] = $address;
            }
        }
        return ['valid' => $valid, 'invalid' => $invalid];
    }
}
