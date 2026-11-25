<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_birthdayflyer.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_birthdayflyer_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026101401) {
        $table = new xmldb_table('local_birthdayflyer_thought');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('thoughtdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('thought', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_index('thoughtdate_uix', XMLDB_INDEX_UNIQUE, ['thoughtdate']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_birthdayflyer_sent');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('employeeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('senddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('employee_fk', XMLDB_KEY_FOREIGN, ['employeeid'], 'local_birthdayflyer', ['id']);
        $table->add_index('employee_senddate_uix', XMLDB_INDEX_UNIQUE, ['employeeid', 'senddate']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026101401, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101500) {
        $employeetable = new xmldb_table('local_birthdayflyer');
        $fullnamefield = new xmldb_field('empfullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'empid');
        if (!$dbman->field_exists($employeetable, $fullnamefield)) {
            $dbman->add_field($employeetable, $fullnamefield);
        }
        $marriagefield = new xmldb_field('marriagedate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'dateofjoining');
        if (!$dbman->field_exists($employeetable, $marriagefield)) {
            $dbman->add_field($employeetable, $marriagefield);
        }

        $table = new xmldb_table('local_birthdayflyer_template');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('bodyformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('imagekey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, '');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_index('active_ix', XMLDB_INDEX_NOTUNIQUE, ['active']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026101500, 'local', 'birthdayflyer');
    }
    if ($oldversion < 2026101512) {
        // Store the event category for each template. Existing templates default to birthday.
        $table = new xmldb_table('local_birthdayflyer_template');
        $field = new xmldb_field('eventtype', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'birthday', 'subject');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026101512, 'local', 'birthdayflyer');
    }


    if ($oldversion < 2026101514) {
        // Normalize each date to exactly two columns: readable date and timestamp.
        $table = new xmldb_table('local_birthdayflyer');
        $datemap = [
            'dob' => ['date_of_birth', 'date_of_birth_stamp'],
            'dateofjoining' => ['date_of_joining', 'date_of_joining_stamp'],
            'marriagedate' => ['marriage_date', 'marriage_date_stamp'],
            'fatherdob' => ['father_dob', 'father_dob_stamp'],
            'motherdob' => ['mother_dob', 'mother_dob_stamp'],
            'wifedob' => ['wife_dob', 'wife_dob_stamp'],
            'sondob' => ['son_dob', 'son_dob_stamp'],
            'daughterdob' => ['daughter_dob', 'daughter_dob_stamp'],
        ];
        foreach ($datemap as [$readable, $stamp]) {
            $readablefield = new xmldb_field($readable, XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $stampfield = new xmldb_field($stamp, XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            if (!$dbman->field_exists($table, $readablefield)) {
                $dbman->add_field($table, $readablefield);
            }
            if (!$dbman->field_exists($table, $stampfield)) {
                $dbman->add_field($table, $stampfield);
            }
        }

        // Copy legacy timestamps into the new pair without overwriting existing values.
        $records = $DB->get_records('local_birthdayflyer');
        foreach ($records as $record) {
            foreach ($datemap as $legacy => [$readable, $stamp]) {
                $value = !empty($record->$stamp) ? (int)$record->$stamp : (!empty($record->$legacy) ? (int)$record->$legacy : 0);
                if ($value > 0) {
                    $update = (object)['id' => $record->id];
                    if (empty($record->$stamp)) {
                        $update->$stamp = $value;
                    }
                    if (empty($record->$readable)) {
                        $update->$readable = userdate($value, '%d-%B-%Y', \core_date::get_server_timezone());
                    }
                    $DB->update_record('local_birthdayflyer', $update);
                }
            }
        }

        // Remove the old duplicate timestamp fields after data has been migrated.
        foreach (array_keys($datemap) as $legacy) {
            $field = new xmldb_field($legacy);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2026101514, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101600) {
        // Keep Thought of the Day in its dedicated dated table; it is not employee data.
        $employeetable = new xmldb_table('local_birthdayflyer');
        $thoughtfield = new xmldb_field('thoughtoftheday', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if ($dbman->field_exists($employeetable, $thoughtfield)) {
            $dbman->drop_field($employeetable, $thoughtfield);
        }
        upgrade_plugin_savepoint(true, 2026101600, 'local', 'birthdayflyer');
    }


    // v1.2.0 (25 November 2026): the 2026101601 step below used to come AFTER the
    // 2026101602 step. upgrade_plugin_savepoint() refuses to move the version
    // backwards, so a site upgrading from 2026101600 or older stopped with a
    // "downgrade" error. The two blocks are now in version order. Sites that are
    // already past 2026101602 (such as the live site) skip both blocks, so this
    // reordering changes nothing for them.
    if ($oldversion < 2026101601) {
        // Store a secure public image key for each Thought of the Day image.
        $table = new xmldb_table('local_birthdayflyer_thought');
        $field = new xmldb_field('imagekey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, '', 'thought');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $thoughts = $DB->get_records('local_birthdayflyer_thought', null, '', 'id');
            foreach ($thoughts as $thought) {
                $DB->set_field('local_birthdayflyer_thought', 'imagekey', bin2hex(random_bytes(24)), ['id' => $thought->id]);
            }
        }
        upgrade_plugin_savepoint(true, 2026101601, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101602) {
        // Allow the dated Thought of the Day field to contain raw HTML,
        // matching the raw-HTML editing behavior used by Birthday Wishes.
        // No database schema change is required.
        upgrade_plugin_savepoint(true, 2026101602, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101604) {
        // Keep the Thought of the Day editor as raw HTML, using the daily uploaded image.
        // No database schema change is required.
        upgrade_plugin_savepoint(true, 2026101604, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101605) {
        // Make the Thought of the Day field use the same raw HTML textarea format as Birthday Wishes.
        // No database schema change is required.
        upgrade_plugin_savepoint(true, 2026101605, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101606) {
        // Render {{image}} inside the raw Thought of the Day HTML field.
        upgrade_plugin_savepoint(true, 2026101606, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026101607) {
        // Add separate employee-birthday and relationship-birthday template types.
        // Existing 'birthday' templates remain as a backwards-compatible fallback.
        upgrade_plugin_savepoint(true, 2026101607, 'local', 'birthdayflyer');
    }


    if ($oldversion < 2026102100) {
        // Employee CSV import is application-level functionality; no schema change is required.
        upgrade_plugin_savepoint(true, 2026102100, 'local', 'birthdayflyer');
    }


    if ($oldversion < 2026102200) {
        // Use one shared Thought of the Day image for all dates. Keep the existing
        // per-thought image files intact for backwards compatibility, but copy the
        // most recently modified available image into the new shared file area.
        $sharedkey = (string)get_config('local_birthdayflyer', 'thoughtimagekey');
        if ($sharedkey === '') {
            $sharedkey = bin2hex(random_bytes(24));
            set_config('thoughtimagekey', $sharedkey, 'local_birthdayflyer');
        }

        $fs = get_file_storage();
        $systemcontext = \context_system::instance();
        $sharedfiles = $fs->get_area_files($systemcontext->id, 'local_birthdayflyer', 'thoughtimage', 0, 'id ASC', false);
        if (!$sharedfiles) {
            $thoughts = $DB->get_records('local_birthdayflyer_thought', null, 'timemodified DESC', 'id', 0, 0);
            foreach ($thoughts as $thought) {
                $files = $fs->get_area_files($systemcontext->id, 'local_birthdayflyer', 'thoughtimage', (int)$thought->id, 'id ASC', false);
                if ($files) {
                    $file = reset($files);
                    $filerecord = [
                        'contextid' => $systemcontext->id,
                        'component' => 'local_birthdayflyer',
                        'filearea' => 'thoughtimage',
                        'itemid' => 0,
                        'filepath' => $file->get_filepath(),
                        'filename' => $file->get_filename(),
                        'userid' => $file->get_userid(),
                        'author' => $file->get_author(),
                        'license' => $file->get_license(),
                        'source' => $file->get_source(),
                    ];
                    $fs->create_file_from_storedfile($filerecord, $file);
                    break;
                }
            }
        }

        // Existing dated entries may contain the old HTML email markup. Keep only
        // their readable quote text so the new fixed renderer owns all email HTML.
        $thoughts = $DB->get_records('local_birthdayflyer_thought', null, '', 'id,thought');
        foreach ($thoughts as $thought) {
            $clean = trim(strip_tags((string)$thought->thought));
            if ($clean !== (string)$thought->thought) {
                $DB->set_field('local_birthdayflyer_thought', 'thought', $clean, ['id' => $thought->id]);
            }
        }
        upgrade_plugin_savepoint(true, 2026102200, 'local', 'birthdayflyer');
    }
    if ($oldversion < 2026102201) {
        // Thought of the Day emails now composite the quote directly onto the shared artwork.
        // No database schema change is required.
        upgrade_plugin_savepoint(true, 2026102201, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026102202) {
        // The Thought of the Day email now places the quote visually inside the shared artwork.
        // No database schema change is required.
        upgrade_plugin_savepoint(true, 2026102202, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026102603) {
        // Correct the initial XMLDB definitions for the renamed Birthday Flyer plugin.
        // The final install.xml schema already contains these definitions for fresh installs.
        upgrade_plugin_savepoint(true, 2026102603, 'local', 'birthdayflyer');
    }


    if ($oldversion < 2026111800) {
        // Added 18 November 2026: add husband and second-child birthday dates.
        // Both the readable date and timestamp are nullable, matching existing
        // family date fields. Existing employee data is not modified.
        $table = new xmldb_table('local_birthdayflyer');
        foreach (['husband_dob', 'daughter2_dob', 'son2_dob'] as $datefield) {
            $readable = new xmldb_field($datefield, XMLDB_TYPE_CHAR, '30', null, null, null, null);
            $timestamp = new xmldb_field($datefield . '_stamp', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            if (!$dbman->field_exists($table, $readable)) {
                $dbman->add_field($table, $readable);
            }
            if (!$dbman->field_exists($table, $timestamp)) {
                $dbman->add_field($table, $timestamp);
            }
        }
        upgrade_plugin_savepoint(true, 2026111800, 'local', 'birthdayflyer');
    }

    if ($oldversion < 2026112600) {
        // v1.2.0 (25 November 2026): per-email delivery log.
        // Why: the day-level local_birthdayflyer_sent row is only written when every
        // email for an employee succeeded, so a partial failure could not be retried
        // without re-sending the emails that had already gone out, and the reason
        // for a failure was only visible in debug output. Existing tables and data
        // are not touched.
        $table = new xmldb_table('local_birthdayflyer_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('employeeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('senddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('messagetype', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ccaddresses', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('employee_fk', XMLDB_KEY_FOREIGN, ['employeeid'], 'local_birthdayflyer', ['id']);
        $table->add_index('employee_date_type_uix', XMLDB_INDEX_UNIQUE, ['employeeid', 'senddate', 'messagetype']);
        $table->add_index('senddate_status_ix', XMLDB_INDEX_NOTUNIQUE, ['senddate', 'status']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // v1.2.0: CC per email type. Written explicitly so the behaviour is defined the
        // moment the upgrade finishes, before anyone opens the settings page.
        // Agreed: Thought of the Day is no longer CC'd (it produced one CC copy per
        // employee every morning); birthdays and anniversaries keep their CC.
        // Values that already exist are never overwritten.
        $defaults = [
            'cc_thought' => 0,
            'cc_birthdayemployee' => 1,
            'cc_birthdayrelationship' => 1,
            'cc_workanniversary' => 1,
            'cc_marriageanniversary' => 1,
            'thought_layout' => 'overlay',
            'send_delay_ms' => 0,
        ];
        foreach ($defaults as $name => $value) {
            if (get_config('local_birthdayflyer', $name) === false) {
                set_config($name, $value, 'local_birthdayflyer');
            }
        }

        upgrade_plugin_savepoint(true, 2026112600, 'local', 'birthdayflyer');
    }

    return true;
}
