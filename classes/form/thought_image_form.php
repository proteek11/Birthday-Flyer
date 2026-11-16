<?php
namespace local_birthdayflyer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for the single shared Thought of the Day image.
 */
class thought_image_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('filepicker', 'imagefile', get_string('thoughtsharedimage', 'local_birthdayflyer'), null, [
            'maxbytes' => 20 * 1024 * 1024,
            'accepted_types' => ['.png', '.jpg', '.jpeg', '.gif', '.webp'],
        ]);
        $mform->addRule('imagefile', get_string('required'), 'required', null, 'client');
        $this->add_action_buttons(false, get_string('savethoughtimage', 'local_birthdayflyer'));
    }
}
