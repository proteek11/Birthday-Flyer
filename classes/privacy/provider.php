<?php
namespace local_birthdayflyer\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy metadata for the Employee Management plugin.
 */
class provider implements metadata_provider {
    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_birthdayflyer',
            [
                'empid' => 'privacy:metadata:local_birthdayflyer:empid',
                'empfullname' => 'privacy:metadata:local_birthdayflyer:empfullname',
                'empemail' => 'privacy:metadata:local_birthdayflyer:empemail',
                'date_of_birth' => 'privacy:metadata:local_birthdayflyer:date_of_birth',
                'date_of_birth_stamp' => 'privacy:metadata:local_birthdayflyer:date_of_birth_stamp',
                'date_of_joining' => 'privacy:metadata:local_birthdayflyer:date_of_joining',
                'date_of_joining_stamp' => 'privacy:metadata:local_birthdayflyer:date_of_joining_stamp',
                'marriage_date' => 'privacy:metadata:local_birthdayflyer:marriage_date',
                'marriage_date_stamp' => 'privacy:metadata:local_birthdayflyer:marriage_date_stamp',
                'father_dob' => 'privacy:metadata:local_birthdayflyer:father_dob',
                'father_dob_stamp' => 'privacy:metadata:local_birthdayflyer:father_dob_stamp',
                'mother_dob' => 'privacy:metadata:local_birthdayflyer:mother_dob',
                'mother_dob_stamp' => 'privacy:metadata:local_birthdayflyer:mother_dob_stamp',
                'wife_dob' => 'privacy:metadata:local_birthdayflyer:wife_dob',
                'wife_dob_stamp' => 'privacy:metadata:local_birthdayflyer:wife_dob_stamp',
                'son_dob' => 'privacy:metadata:local_birthdayflyer:son_dob',
                'son_dob_stamp' => 'privacy:metadata:local_birthdayflyer:son_dob_stamp',
                'daughter_dob' => 'privacy:metadata:local_birthdayflyer:daughter_dob',
                'daughter_dob_stamp' => 'privacy:metadata:local_birthdayflyer:daughter_dob_stamp',
                // v1.2.0: the 18 November 2026 family dates were missing from the metadata.
                'husband_dob' => 'privacy:metadata:local_birthdayflyer:husband_dob',
                'daughter2_dob' => 'privacy:metadata:local_birthdayflyer:daughter2_dob',
                'son2_dob' => 'privacy:metadata:local_birthdayflyer:son2_dob',
                'usermodified' => 'privacy:metadata:local_birthdayflyer:usermodified',
            ],
            'privacy:metadata:local_birthdayflyer'
        );
        $collection->add_database_table(
            'local_birthdayflyer_thought',
            [
                'thoughtdate' => 'privacy:metadata:local_birthdayflyer_thought:thoughtdate',
                'thought' => 'privacy:metadata:local_birthdayflyer_thought:thought',
                'imagekey' => 'privacy:metadata:local_birthdayflyer_thought:imagekey',
                'usermodified' => 'privacy:metadata:local_birthdayflyer_thought:usermodified',
            ],
            'privacy:metadata:local_birthdayflyer_thought'
        );
        $collection->add_database_table(
            'local_birthdayflyer_template',
            [
                'name' => 'privacy:metadata:local_birthdayflyer_template:name',
                'body' => 'privacy:metadata:local_birthdayflyer_template:body',
                'usermodified' => 'privacy:metadata:local_birthdayflyer:usermodified',
            ],
            'privacy:metadata:local_birthdayflyer_template'
        );
        $collection->add_database_table(
            'local_birthdayflyer_sent',
            [
                'employeeid' => 'privacy:metadata:local_birthdayflyer_sent:employeeid',
                'senddate' => 'privacy:metadata:local_birthdayflyer_sent:senddate',
            ],
            'privacy:metadata:local_birthdayflyer_sent'
        );
        // v1.2.0 (25 November 2026): per-email delivery log.
        $collection->add_database_table(
            'local_birthdayflyer_log',
            [
                'employeeid' => 'privacy:metadata:local_birthdayflyer_log:employeeid',
                'senddate' => 'privacy:metadata:local_birthdayflyer_log:senddate',
                'messagetype' => 'privacy:metadata:local_birthdayflyer_log:messagetype',
                'status' => 'privacy:metadata:local_birthdayflyer_log:status',
                'reason' => 'privacy:metadata:local_birthdayflyer_log:reason',
                'ccaddresses' => 'privacy:metadata:local_birthdayflyer_log:ccaddresses',
            ],
            'privacy:metadata:local_birthdayflyer_log'
        );
        return $collection;
    }
}
