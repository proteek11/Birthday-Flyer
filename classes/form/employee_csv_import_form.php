<?php
namespace local_birthdayflyer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for importing employee records from CSV.
 */
class employee_csv_import_form extends \moodleform {
    /**
     * Define form fields.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'csvfile', get_string('employeecsvfile', 'local_birthdayflyer'), null, [
            'accepted_types' => ['.csv', 'text/csv'],
            'maxbytes' => 5 * 1024 * 1024,
        ]);
        $mform->addRule('csvfile', get_string('employeecsvfilerequired', 'local_birthdayflyer'), 'required', null, 'client');
        $mform->addHelpButton('csvfile', 'employeecsvfile', 'local_birthdayflyer');

        $mform->addElement('checkbox', 'updateexisting', get_string('updateexistingemployees', 'local_birthdayflyer'));
        $mform->setDefault('updateexisting', 1);

        $mform->addElement('static', 'csvformat', '', get_string('employeecsvformat', 'local_birthdayflyer'));

        $this->add_action_buttons(false, get_string('importemployees', 'local_birthdayflyer'));
    }
}
