<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Migrate data from the former local_employeemgmt plugin when installing
 * local_birthdayflyer. This keeps the rename transparent for existing sites.
 */
function xmldb_local_birthdayflyer_install() {
    global $DB;

    $oldtables = [
        'local_employeemgmt',
        'local_employeemgmt_thought',
        'local_employeemgmt_template',
        'local_employeemgmt_sent',
    ];

    // Nothing to migrate on a completely new installation.
    $manager = $DB->get_manager();
    if (!$manager->table_exists(new xmldb_table('local_employeemgmt'))) {
        return true;
    }

    $employeemap = [];
    $thoughtmap = [];
    $templatemap = [];

    // Employees.
    if ($manager->table_exists(new xmldb_table('local_employeemgmt')) &&
            $manager->table_exists(new xmldb_table('local_birthdayflyer'))) {
        foreach ($DB->get_records('local_employeemgmt', null, 'id ASC') as $old) {
            $copy = clone $old;
            $oldid = (int)$copy->id;
            unset($copy->id);
            $newid = $DB->insert_record('local_birthdayflyer', $copy);
            $employeemap[$oldid] = (int)$newid;
        }
    }

    // Thoughts.
    if ($manager->table_exists(new xmldb_table('local_employeemgmt_thought')) &&
            $manager->table_exists(new xmldb_table('local_birthdayflyer_thought'))) {
        foreach ($DB->get_records('local_employeemgmt_thought', null, 'id ASC') as $old) {
            $copy = clone $old;
            $oldid = (int)$copy->id;
            unset($copy->id);
            if (!isset($copy->imagekey)) {
                $copy->imagekey = '';
            }
            $newid = $DB->insert_record('local_birthdayflyer_thought', $copy);
            $thoughtmap[$oldid] = (int)$newid;
        }
    }

    // Birthday/anniversary templates.
    if ($manager->table_exists(new xmldb_table('local_employeemgmt_template')) &&
            $manager->table_exists(new xmldb_table('local_birthdayflyer_template'))) {
        foreach ($DB->get_records('local_employeemgmt_template', null, 'id ASC') as $old) {
            $copy = clone $old;
            $oldid = (int)$copy->id;
            unset($copy->id);
            $newid = $DB->insert_record('local_birthdayflyer_template', $copy);
            $templatemap[$oldid] = (int)$newid;
        }
    }

    // Delivery log, remapping employee IDs to the new employee table.
    if ($manager->table_exists(new xmldb_table('local_employeemgmt_sent')) &&
            $manager->table_exists(new xmldb_table('local_birthdayflyer_sent'))) {
        foreach ($DB->get_records('local_employeemgmt_sent', null, 'id ASC') as $old) {
            $employeeid = $employeemap[(int)$old->employeeid] ?? 0;
            if (!$employeeid) {
                continue;
            }
            $copy = clone $old;
            unset($copy->id);
            $copy->employeeid = $employeeid;
            try {
                $DB->insert_record('local_birthdayflyer_sent', $copy);
            } catch (\dml_exception $e) {
                // Preserve the unique employee/date rule if the old log contains duplicates.
            }
        }
    }

    // Copy plugin configuration, including the shared Thought of the Day image key.
    $oldconfig = get_config('local_employeemgmt');
    if ($oldconfig) {
        foreach (get_object_vars($oldconfig) as $name => $value) {
            set_config($name, $value, 'local_birthdayflyer');
        }
    }

    // Copy stored files into the new component. The item IDs are remapped where necessary.
    $fs = get_file_storage();
    $context = \context_system::instance();

    $copyarea = static function(string $filearea, array $itemmap = []) use ($fs, $context) {
        $files = $fs->get_area_files($context->id, 'local_employeemgmt', $filearea, false, 'id ASC', false);
        foreach ($files as $file) {
            $olditemid = (int)$file->get_itemid();
            $newitemid = array_key_exists($olditemid, $itemmap) ? $itemmap[$olditemid] : $olditemid;
            $record = [
                'contextid' => $context->id,
                'component' => 'local_birthdayflyer',
                'filearea' => $filearea,
                'itemid' => $newitemid,
                'filepath' => $file->get_filepath(),
                'filename' => $file->get_filename(),
                'userid' => $file->get_userid(),
                'author' => $file->get_author(),
                'license' => $file->get_license(),
                'source' => $file->get_source(),
            ];
            try {
                $fs->create_file_from_storedfile($record, $file);
            } catch (\file_exception $e) {
                // Do not abort installation because an individual legacy file cannot be copied.
            }
        }
    };

    // Shared Thought image lives at itemid 0. Legacy per-thought images are also copied,
    // using the thought ID map so old files remain available during the transition.
    $copyarea('thoughtimage', [0 => 0] + $thoughtmap);
    $copyarea('templateimage', $templatemap);
    $copyarea('templateattachment', $templatemap);

    return true;
}
