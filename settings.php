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
 * Admin settings are defined here.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ask4summary/locallib.php');

global $COURSE;

if ($ADMIN->fulltree) {
    // Allow teachers to control Ask4Summary functionality.
    $settings->add(new admin_setting_configcheckbox('block_ask4summary/grantteacher',
        get_string('grantteacher', 'block_ask4summary'),
        get_string('grantteacher_desc', 'block_ask4summary'), 1));

    // Allow Ask4Summary to begin responding to forums.
    $settings->add(new admin_setting_configcheckbox('block_ask4summary/enableresponse',
        get_string('enable', 'block_ask4summary'),
        get_string('enable_desc', 'block_ask4summary'), 1));

    // Change the default name of the Ask4Summary helper.
    $settings->add(new admin_setting_configtext('block_ask4summary/defaultname',
        get_string('blockstring', 'block_ask4summary'),
        get_string('blockstring_desc', 'block_ask4summary'),
        get_string('defaultname', 'block_ask4summary'),
        PARAM_ALPHA));

    // Change the method in which the Ask4Summary helper will respond to forums.
    $settings->add(new admin_setting_configselect('block_ask4summary/responsetype',
        get_string('responsetype', 'block_ask4summary'),
        get_string('reponsetype_desc', 'block_ask4summary'), 1,
        ['1' => get_string('allforums', 'block_ask4summary'), '2' => get_string('existingforum', 'block_ask4summary'),
            '3' => get_string('autoforum', 'block_ask4summary')]));

    // Change the default name of the automatically generated forum.
    $settings->add(new admin_setting_configtext('block_ask4summary/defaultforum',
        get_string('autoname', 'block_ask4summary'),
        get_string('autoname_desc', 'block_ask4summary'),
        get_string('defaultauto', 'block_ask4summary'),
        PARAM_TEXT));

    $settings->add(new admin_setting_configselect('block_ask4summary/crawldepth',
        get_string('crawldepth', 'block_ask4summary'),
        get_string('crawldepth_desc', 'block_ask4summary'), 1,
        ['1' => 1, '2' => 2, '3' => 3]));

    $settings->add(new admin_setting_configselect('block_ask4summary/topdoc',
        get_string('topdoc', 'block_ask4summary'),
        get_string('topdoc_desc', 'block_ask4summary'), 3,
        ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5]));

    $settings->add(new admin_setting_configselect('block_ask4summary/topsent',
        get_string('topsent', 'block_ask4summary'),
        get_string('topsent_desc', 'block_ask4summary'), 8,
        ['4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
             '10' => 10, '11' => 11, '12' => 12]));


}

