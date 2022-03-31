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
 * This file displays the documentation for Ask4Summary.
 *
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
require_capability('block/ask4summary:view', $context);

// Was script called with course id where plugin is not installed?
if (!block_ask4summary_is_installed($course->id)) {

    redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
    die();
}

// Set up the page.
$PAGE->set_url('/blocks/ask4summary/documentation.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_ask4summary'));

// Finish setting up page.
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_ask4summary') . ' ' . get_string('docsanchor', 'block_ask4summary'));

// Output page.
echo $OUTPUT->header();

// Make the hyperlink menu.
$cid = array('id' => $COURSE->id);

echo html_writer::div(block_ask4summary_get_nav_links(), '');
echo html_writer::div('<hr/>', '');

$moodleurl = new moodle_url('https://www.youtube.com/watch?v=7be6xexguwI');
$title = get_string('configvid', 'block_ask4summary');
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

$moodleurl = new moodle_url('https://www.youtube.com/watch?v=1LTJqx_O-k4');
$title = get_string('teachervid', 'block_ask4summary');
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

echo html_writer::tag('a', get_string('termsofuse', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#tos', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docswhatis', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#whatis', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docsalgo', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#algo', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docssettings', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#settings', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docscm', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#cm', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docsquery', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#query', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docstime', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#time', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docsprog', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#prog', $cid)));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('docsabi', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#abi', $cid)));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// Terms Of Use.
echo html_writer::div(get_string('termsofuse', 'block_ask4summary'), 'bigger', array('id' => 'tos'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('termsofuse1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('termsofuse2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('termsofuse3', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');


// What is Ask4Summary?
echo html_writer::div(get_string('docswhatis', 'block_ask4summary'), 'bigger', array('id' => 'whatis'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsdescription1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsdescription2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsdescription3', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// How does the algorithm work?
echo html_writer::div(get_string('docsalgo', 'block_ask4summary'), 'bigger', array('id' => 'algo'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsalgodesc', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsalgodesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsmoreinfo', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docsvipngram', 'block_ask4summary'), array(
    'href' => 'https://ngrampos.vipresearch.ca/'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docsvipacq', 'block_ask4summary'), array(
    'href' => 'https://askcovidq.vipresearch.ca/'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docsngram', 'block_ask4summary'), array(
    'href' => 'https://en.wikipedia.org/wiki/N-gram'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docscossim', 'block_ask4summary'), array(
    'href' => 'https://en.wikipedia.org/wiki/Cosine_similarity'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// Where are the settings changed?
echo html_writer::div(get_string('docssettings', 'block_ask4summary'), 'bigger', array('id' => 'settings'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docssettingsdesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docssettingsdesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('docshelpername', 'block_ask4summary'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('docshelperdesc', 'block_ask4summary'));
echo html_writer::end_tag('ul');
echo html_writer::tag('li', get_string('docsenable', 'block_ask4summary'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('docsenabledesc', 'block_ask4summary'));
echo html_writer::end_tag('ul');
echo html_writer::tag('li', get_string('docsforum', 'block_ask4summary'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('docsallforum', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsexistingforum', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsautoforum', 'block_ask4summary'));
echo html_writer::end_tag('ul');
echo html_writer::end_tag('ul');
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docssettingsdesc4', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docssettingsdesc3', 'block_ask4summary'));
echo html_writer::empty_tag('br');

// What are course modules and what does Ask4Summary use them for?

echo html_writer::div(get_string('docscm', 'block_ask4summary'), 'bigger', array('id' => 'cm'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc7', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc3', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('docsurl', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docspdf', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsdocx', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docspptx', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docspage', 'block_ask4summary'));
echo html_writer::end_tag('ul');
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc4', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc5', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('docsurlon', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsurldepth', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docspdfon', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsdocxon', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docspptxon', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docspageon', 'block_ask4summary'));
echo html_writer::end_tag('ul');
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc6', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docsabi', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/documentation.php#abi', $cid)));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc8', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docscmdesc9', 'block_ask4summary'));
echo html_writer::empty_tag('br');
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

// Can I control how long it will take?

echo html_writer::div(get_string('docstime', 'block_ask4summary'), 'bigger', array('id' => 'time'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docstimedesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docstimedesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');

// How can I see the forum post scanning progress? The course module progress?

echo html_writer::div(get_string('docsprog', 'block_ask4summary'), 'bigger', array('id' => 'prog'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsprogdesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsprogdesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('loganchor', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/logistics.php', $cid)));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// PDF: AbiWord.

echo html_writer::div(get_string('docsabi', 'block_ask4summary'), 'bigger', array('id' => 'abi'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabidesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabidesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabidesc3', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabidesc4', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabidesc5', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::start_tag('ol');
echo html_writer::tag('li', get_string('docsabiinstall1', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsabiinstall2', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsabiinstall8', 'block_ask4summary'));
echo html_writer::end_tag('ol');
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabiinstall3', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::start_tag('ol');
echo html_writer::tag('li', get_string('docsabiinstall4', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsabiinstall5', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsabiinstall6', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsabiinstall7', 'block_ask4summary'));
echo html_writer::tag('li', get_string('docsabiinstall8', 'block_ask4summary'));
echo html_writer::end_tag('ol');
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('docsabiinstall9', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docsabilink1', 'block_ask4summary'), array(
    'href' => 'https://www.abisource.com/'));
echo html_writer::empty_tag('br');
echo html_writer::tag('a', get_string('docsabilink2', 'block_ask4summary'), array(
    'href' => 'https://www.addictivetips.com/ubuntu-linux-tips/install-abiword-word-processor-on-linux/'));
echo html_writer::empty_tag('br');



echo $OUTPUT->footer();
