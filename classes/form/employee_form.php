<?php
namespace local_birthdayflyer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing employee records.
 */
class employee_form extends \moodleform {
    /**
     * Define form fields.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'employeeheader', get_string('employeemgmt', 'local_birthdayflyer'));

        // Preserve the record being edited when the form is submitted.
        $mform->addElement('hidden', 'recordid', 0);
        $mform->setType('recordid', PARAM_INT);
        $mform->setDefault('recordid', (int)($this->_customdata['recordid'] ?? 0));

        $mform->addElement('text', 'empid', get_string('employeeid', 'local_birthdayflyer'), ['maxlength' => 100]);
        $mform->setType('empid', PARAM_TEXT);
        $mform->addRule('empid', get_string('employeeidrequired', 'local_birthdayflyer'), 'required', null, 'client');

        $mform->addElement('text', 'empfullname', get_string('employeefullname', 'local_birthdayflyer'), ['maxlength' => 255]);
        $mform->setType('empfullname', PARAM_TEXT);
        $mform->addRule('empfullname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'empemail', get_string('employeeemail', 'local_birthdayflyer'), ['maxlength' => 255]);
        $mform->setType('empemail', PARAM_EMAIL);
        $mform->addRule('empemail', get_string('required'), 'required', null, 'client');
        $mform->addRule('empemail', get_string('email'), 'email', null, 'client');

        foreach ([
            'dob' => 'dob',
            'dateofjoining' => 'dateofjoining',
            'marriagedate' => 'marriagedate',
            'fatherdob' => 'fatherdob',
            'motherdob' => 'motherdob',
            'wifedob' => 'wifedob',
            'sondob' => 'sondob',
            'daughterdob' => 'daughterdob',
            // Added 18 November 2026: additional family birthday date selectors.
            'husbanddob' => 'husbanddob',
            'daughter2dob' => 'daughter2dob',
            'son2dob' => 'son2dob',
        ] as $field => $stringkey) {
            $mform->addElement('date_selector', $field, get_string($stringkey, 'local_birthdayflyer'), ['optional' => true]);
        }

        $this->add_action_buttons(true, get_string('saveemployee', 'local_birthdayflyer'));
    }
}
