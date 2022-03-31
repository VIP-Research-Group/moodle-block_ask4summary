<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file displays the student guide for Ask4Summary.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

defined('MOODLE_INTERNAL') || die();

$id = required_param('id', PARAM_INT);

$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);
require_capability('block/ask4summary:studentview', $context);

// Was script called with course id where plugin is not installed?
if (!block_ask4summary_is_installed($course->id)) {

    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
    die();
}

// Set up the page.
$PAGE->set_url('/blocks/ask4summary/guide.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_ask4summary'));

// Finish setting up page.
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_ask4summary') . ' '
    . get_string('guideanchor', 'block_ask4summary'));

// Output page.
echo $OUTPUT->header();

$cid = array('id' => $COURSE->id);

echo html_writer::tag('a', get_string('return', 'block_ask4summary'), array(
    'href' => new moodle_url('/course/view.php', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::div('<hr/>', '');

$moodleurl = new moodle_url('https://www.youtube.com/watch?v=-1hA-rPQK68');
$title = 'Student Guide';
$mediamanager = core_media_manager::instance();
$embedoptions = array(
    core_media_manager::OPTION_TRUSTED => true, // Only add if user has respective capability with RISK_XSS mask.
    core_media_manager::OPTION_BLOCK => true,
);
if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
    $code = $mediamanager->embed_url($moodleurl, $title, 750, 750, $embedoptions);
    echo html_writer::div($code);
} else {
    echo html_writer::div($moodleurl);
}

echo html_writer::div('<hr/>', '');

// What is Ask4Summary?
echo html_writer::div(get_string('docswhatis', 'block_ask4summary'), 'bigger', array('id' => 'whatis'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsdescription1', 'block_ask4summary'));
echo html_writer::empty_tag('br');

// How do I ask a question? How long will it take?

echo html_writer::div(get_string('docsquery', 'block_ask4summary'), 'bigger', array('id' => 'query'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsquerydesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsquerydesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsquerydesc3', 'block_ask4summary'));
echo html_writer::empty_tag('br');

// Where are the answers located?

echo html_writer::div(get_string('location', 'block_ask4summary'), 'bigger', array('id' => 'loc'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('locationdesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('locationdesc2', 'block_ask4summary'));

echo $OUTPUT->footer();
