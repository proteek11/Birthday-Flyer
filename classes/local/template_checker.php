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
 * Warn administrators about image references that email clients cannot show.
 *
 * Added in v1.2.0 (25 November 2026) while investigating "background image is
 * missing in some mailboxes". The plugin cannot rewrite every possible HTML
 * construct safely, so instead the template page tells the administrator, at
 * save time, when the template contains a reference that will break in common
 * mail clients. The checks are warnings only: saving is never blocked, so
 * existing templates keep working exactly as before.
 */
class template_checker {

    /**
     * Inspect template HTML.
     *
     * @param string $html Template body as stored.
     * @return string[] Lang string keys (component local_birthdayflyer) describing each problem found.
     */
    public static function check(string $html): array {
        global $CFG;
        $warnings = [];

        // Backgrounds: CSS url(...) or the HTML background="..." attribute.
        $backgrounds = [];
        if (preg_match_all('/url\(\s*([\'"]?)(.*?)\1\s*\)/is', $html, $m)) {
            $backgrounds = array_merge($backgrounds, $m[2]);
        }
        if (preg_match_all('/(?<![\w-])background\s*=\s*([\'"])(.*?)\1/is', $html, $m)) {
            $backgrounds = array_merge($backgrounds, $m[2]);
        }
        foreach ($backgrounds as $url) {
            $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (stripos($url, 'data:') === 0) {
                // Gmail and Outlook do not show inline data images used as backgrounds.
                $warnings['templatewarnbackgrounddata'] = true;
            } else if (stripos($url, 'cid:') === 0) {
                // Gmail does not support Content-ID images in CSS backgrounds.
                $warnings['templatewarnbackgroundcid'] = true;
            }
        }

        // Any image reference that needs a Moodle login or points to the admin's own machine.
        if (preg_match('#/draftfile\.php/#i', $html)) {
            $warnings['templatewarndraftfile'] = true;
        }
        if (preg_match('#/pluginfile\.php/#i', $html)) {
            $warnings['templatewarnpluginfile'] = true;
        }
        if (preg_match('#https?://(localhost|127\.0\.0\.1)[:/]#i', $html)
                && stripos($CFG->wwwroot, '://localhost') === false && stripos($CFG->wwwroot, '://127.0.0.1') === false) {
            $warnings['templatewarnlocalhost'] = true;
        }
        return array_keys($warnings);
    }
}
