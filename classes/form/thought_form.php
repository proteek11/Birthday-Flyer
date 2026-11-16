<?php
namespace local_birthdayflyer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to create or update a dated Thought of the Day quote.
 */
class thought_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'thoughtheader', get_string('thoughtformtitle', 'local_birthdayflyer'));
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', (int)($this->_customdata['id'] ?? 0));

        $mform->addElement('date_selector', 'thoughtdate', get_string('thoughtdate', 'local_birthdayflyer'));
        $mform->addRule('thoughtdate', get_string('required'), 'required', null, 'client');

        $mform->addElement('textarea', 'thought', get_string('thoughtoftheday', 'local_birthdayflyer'), [
            'rows' => 8,
            'cols' => 100,
            'wrap' => 'soft',
            'spellcheck' => 'true',
            'style' => 'width:100%; min-height:180px;',
            'placeholder' => 'Enter the Thought of the day here',
        ]);
        $mform->setType('thought', PARAM_TEXT);
        $mform->addRule('thought', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('thought', 'thoughtoftheday', 'local_birthdayflyer');

        $this->add_action_buttons(true, get_string('savethought', 'local_birthdayflyer'));
    }
}
