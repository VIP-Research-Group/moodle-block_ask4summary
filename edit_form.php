<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Certain teacher settings are defined here.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ask4summary/locallib.php');

/**
 * Form for editing block_ask4summary settings.
 *
 * @package     block_ask4summary
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ask4summary_edit_form extends block_edit_form {

    /**
     * The function to make the specific forum.
     *
     * @param MoodleQuickForum $mform
     */
    protected function specific_definition($mform) {
        global $CFG, $DB, $COURSE;

        $mform->addElement('hidden', 'allowchoice');
        $mform->setType('allowchoice', PARAM_INT);

        $mform->addElement('hidden', 'chooseforums');
        $mform->setType('chooseforums', PARAM_INT);
        $mform->setDefault('chooseforums', 1);

        // Controls permission given by an admin for teachers to access Ask4Summary functionality.
        if (get_config('block_ask4summary', 'grantteacher')) {
            $mform->setDefault('allowchoice', 1);
        } else {
            $mform->setDefault('allowchoice', 0);
        }

        // Section header title.
        $mform->addElement('header', 'config_header', get_string('forumsettings', 'block_ask4summary'));

        // Default name input for Ask4Summary.
        $mform->addElement('text', 'config_text', get_string('blockstring', 'block_ask4summary'));
        $mform->setDefault('config_text', get_config('block_ask4summary', 'defaultname'));
        $mform->setType('config_text', PARAM_RAW);

        // Checkbox for allowing Ask4Summary functionality. Teachers can control of this if enabled by an admin.
        $mform->addElement('advcheckbox', 'config_enablea4s', get_string('enable', 'block_ask4summary'),
        get_string('enable_desc', 'block_ask4summary'));
        $mform->setDefault('config_enablea4s', get_config('block_ask4summary', 'enableresponse'));
        $mform->disabledIf('config_enablea4s', 'allowchoice', 'eq', 0);

        // Dropdown for selecting the response method for which Ask4Summary will respond to.
        $mform->addElement('select', 'config_response', get_string('responsetype', 'block_ask4summary'),
        ['1' => get_string('allforums', 'block_ask4summary'), '2' => get_string('existingforum', 'block_ask4summary'),
            '3' => get_string('autoforum', 'block_ask4summary')]);
        $mform->setDefault('config_response', get_config('block_ask4summary', 'responsetype'));

        $forums = block_ask4summary_get_forums($COURSE->id);

        // Dropdown for selecting which specific forum Ask4Summary should get questions from,
        // disabled if another mode is selected.
        $mform->addElement('select', 'config_forumselect', get_string('forums', 'block_ask4summary'), $forums);
        $mform->disabledIf('config_forumselect', 'config_response', 'neq', 2);

        // Text box for choosing what the automatic forum's name should be,
        // disabled if another forum option is enabled.
        $mform->addElement('text', 'config_autotext', get_string('autoname', 'block_ask4summary'));
        $mform->setDefault('config_autotext', get_config('block_ask4summary', 'defaultforum'));
        $mform->setType('config_autotext', PARAM_TEXT);
        $mform->disabledIf('config_autotext', 'config_response', 'neq', 3);

        // Section header title.
        $mform->addElement('header', 'config_header', get_string('clobjsettings', 'block_ask4summary'));

        // Checkbox for whether URL parsing should be enabled.
        $mform->addElement('advcheckbox', 'config_enableurl', get_string('enableurl', 'block_ask4summary'),
        get_string('enableurl_desc', 'block_ask4summary'));
        $mform->setDefault('config_enableurl', 1);

        // Dropdown for selecting how many pages Ask4Summary should analyze additionally.
        $mform->addElement('select', 'config_depth', get_string('crawldepth', 'block_ask4summary'),
            ['1' => 1, '2' => 2, '3' => 3]);
        $mform->setDefault('config_depth', get_config('block_ask4summary', 'crawldepth'));

        $obj = (object) ['wwwroot' => $CFG->wwwroot,
            'id' => $COURSE->id];

        // Checkbox for whether PDF parsing should be enabled.
        $mform->addElement('advcheckbox', 'config_enablepdf', get_string('enablepdf', 'block_ask4summary'),
            get_string('enablepdf_desc', 'block_ask4summary'));
        $mform->setDefault('config_enablepdf', 0);
        $mform->addHelpButton('config_enablepdf', 'config_enablepdf', 'block_ask4summary', 'black');
        $mform->addElement('static', 'staticlink', '',
            get_string('docsabiurl', 'block_ask4summary', $obj));

        // Checkbox for whether DOCX parsing should be enabled.
        $mform->addElement('advcheckbox', 'config_enabledocx', get_string('enabledocx', 'block_ask4summary'),
            get_string('enabledocx_desc', 'block_ask4summary'));
        $mform->setDefault('config_enabledocx', 1);

        // Checkbox for whether PPTX parsing should be enabled.
        $mform->addElement('advcheckbox', 'config_enablepptx', get_string('enablepptx', 'block_ask4summary'),
            get_string('enablepptx_desc', 'block_ask4summary'));
        $mform->setDefault('config_enablepptx', 1);

        // Checkbox for whether Moodle Page parsing should be enabled.
        $mform->addElement('advcheckbox', 'config_enablepage', get_string('enablepage', 'block_ask4summary'),
            get_string('enablepage_desc', 'block_ask4summary'));
        $mform->setDefault('config_enablepage', 1);

        // Section header title.
        $mform->addElement('header', 'config_header', get_string('answersettings', 'block_ask4summary'));

        // Dropdown for selecting how many documents should be considered for summaries.
        $mform->addElement('select', 'config_topdoc', get_string('topdoc', 'block_ask4summary'),
            ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5]);
        $mform->setDefault('config_topdoc', get_config('block_ask4summary', 'topdoc'));

        // Dropdown for selecting how many sentences should be considered for summaries.
        $mform->addElement('select', 'config_topsent', get_string('topsent', 'block_ask4summary'),
            ['4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
             '10' => 10, '11' => 11, '12' => 12]);
        $mform->setDefault('config_topsent', get_config('block_ask4summary', 'topsent'));

    }
}
