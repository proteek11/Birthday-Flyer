<?php
namespace local_birthdayflyer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Form to upload a dated Excel workbook. */
class thought_import_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'importheader', get_string('importthoughts', 'local_birthdayflyer'));
        $mform->addElement('filepicker', 'workbook', get_string('excelworkbook', 'local_birthdayflyer'), null,
            ['maxbytes' => 10 * 1024 * 1024, 'accepted_types' => ['.xlsx', '.xls', '.csv']]);
        $mform->addRule('workbook', get_string('required'), 'required', null, 'client');
        $mform->addElement('advcheckbox', 'updateexisting', get_string('updateexistingthoughts', 'local_birthdayflyer'));
        $mform->setDefault('updateexisting', 1);
        $mform->addElement('static', 'importhelp', get_string('excelimporthelp', 'local_birthdayflyer'));
        $this->add_action_buttons(true, get_string('importfile', 'local_birthdayflyer'));
    }
}
