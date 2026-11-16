<?php
namespace local_birthdayflyer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Form for creating and editing an HTML birthday email template. */
class birthday_template_form extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $custom = $this->_customdata ?? [];
        $mform->addElement('header', 'templateheader', get_string('templateeditor', 'local_birthdayflyer'));
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', (int)($custom['id'] ?? 0));
        $mform->addElement('text', 'name', get_string('templatename', 'local_birthdayflyer'), ['maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $eventtypes = [
            'birthdayemployee' => get_string('eventtypebirthdayemployee', 'local_birthdayflyer'),
            'birthdayrelationship' => get_string('eventtypebirthdayrelationship', 'local_birthdayflyer'),
            'workanniversary' => get_string('eventtypeworkanniversary', 'local_birthdayflyer'),
            'marriageanniversary' => get_string('eventtypemarriageanniversary', 'local_birthdayflyer'),
        ];
        $mform->addElement('select', 'eventtype', get_string('templateeventtype', 'local_birthdayflyer'), $eventtypes);
        $mform->setDefault('eventtype', 'birthdayemployee');
        $mform->addElement('text', 'subject', get_string('templatesubject', 'local_birthdayflyer'), ['maxlength' => 255]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');
        $editoroptions = ['maxfiles' => 0, 'maxbytes' => 0, 'context' => \context_system::instance(), 'trusttext' => false, 'subdirs' => false];
        // Use a plain textarea so administrator-supplied HTML is stored exactly as entered.
        // The rich-text editor can transform raw email HTML/CSS into visible source text.
        $mform->addElement('textarea', 'body_html', get_string('templatetext', 'local_birthdayflyer'), [
            'rows' => 24,
            'cols' => 100,
            'wrap' => 'off',
            'spellcheck' => 'false',
            'style' => 'font-family:monospace; width:100%; min-height:420px;',
        ]);
        $mform->setType('body_html', PARAM_RAW);
        $mform->addRule('body_html', get_string('required'), 'required', null, 'client');
        $mform->addElement('filepicker', 'imagefile', get_string('templateimage', 'local_birthdayflyer'), null,
            ['maxbytes' => 20 * 1024 * 1024, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.gif', '.webp']]);
        $mform->addElement('filepicker', 'attachmentfile', get_string('templateattachment', 'local_birthdayflyer'), null,
            ['maxbytes' => 20 * 1024 * 1024, 'accepted_types' => ['.eml']]);
        $mform->addElement('static', 'placeholderhelp', get_string('templatevariables', 'local_birthdayflyer'));
        $mform->addElement('advcheckbox', 'active', get_string('templateactive', 'local_birthdayflyer'));
        $mform->setDefault('active', 1);
        $this->add_action_buttons(true, get_string('savetemplate', 'local_birthdayflyer'));
    }
}
