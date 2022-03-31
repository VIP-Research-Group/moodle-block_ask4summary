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
 * This file displays the logistics of Ask4Summary's processes.
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
$PAGE->set_url('/blocks/ask4summary/logistics.php', array('id' => $course->id));
$PAGE->set_title(get_string('pluginname', 'block_ask4summary'));

// Finish setting up page.
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'block_ask4summary') . ' ' . get_string('loganchor', 'block_ask4summary'));

echo $OUTPUT->header();

// Make the hyperlink menu.
$cid = array('id' => $COURSE->id);

echo html_writer::div(block_ask4summary_get_nav_links(), '');
echo html_writer::div('<hr/>', '');

// What is the logistics page about?
echo html_writer::div(get_string('logdesc1', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('logdesc2', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('logdesc3', 'block_ask4summary'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('logdesc4', 'block_ask4summary'));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('coursemodules', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/logistics.php#cm', $cid), 'class' => 'biggernormal'));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('forumposts', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/logistics.php#fp', $cid), 'class' => 'biggernormal'));
echo html_writer::empty_tag('br');

echo html_writer::div('<hr/>', '');
echo html_writer::div(get_string('coursemodules', 'block_ask4summary'), 'secheader', array('id' => 'cm'));
echo html_writer::div('<hr/>', '');

// Get this course's Ask4Summary settings from the database.
$settings = block_ask4summary_get_settings($course->id);

// Display the settings in a single line, color coded for enabling/disabling.
echo html_writer::div(block_ask4summary_display_settings($settings), 'centerline');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// Get the parsed and unparsed course modules,
// based on whether they are enabled or not.
$parsed = block_ask4summary_get_all_parsed($course->id, $settings);
$unparsed = block_ask4summary_get_all_unparsed($course->id, $settings);

$colheaderclass = array('class' => 'colheader');
$tableclass = array('class' => 'centertable');

// Output the table with each row being a module type or file type
// and with each column being parsed, unparsed and percentage.
echo html_writer::start_tag('table', $tableclass);
echo html_writer::start_tag('tr');
echo html_writer::tag('th', '', $colheaderclass) .
    html_writer::tag('th', get_string('parsed', 'block_ask4summary'), $colheaderclass) .
    html_writer::tag('th', get_string('unparsed', 'block_ask4summary'), $colheaderclass) .
    html_writer::tag('th', get_string('percent', 'block_ask4summary'), $colheaderclass);
echo html_writer::end_tag('tr');
foreach ($parsed as $type => $num) {
    if ($num !== false) {
        echo block_ask4summary_build_row(get_string($type, 'block_ask4summary'),
            $parsed[$type], $unparsed[$type]);
    }
}
echo block_ask4summary_build_row(get_string('total', 'block_ask4summary'),
    array_sum($parsed), array_sum($unparsed));
echo html_writer::end_tag('table');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

// Get all the names of parsed and unparsed course modules.
$query = block_ask4summary_display_names($course->id);

// Output them in tile card format.
echo html_writer::div($query, 'container', array('id' => 'names'));

echo html_writer::div('<hr/>', '');
echo html_writer::div(get_string('forumposts', 'block_ask4summary'), 'secheader', array('id' => 'fp'));
echo html_writer::div('<hr/>', '');

echo html_writer::tag('a', get_string('answered', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/logistics.php#answered', $cid), 'class' => 'biggernormal'));
echo html_writer::empty_tag('br');

echo html_writer::tag('a', get_string('unanswered', 'block_ask4summary'), array(
    'href' => new moodle_url('/blocks/ask4summary/logistics.php#unanswered', $cid), 'class' => 'biggernormal'));
echo html_writer::empty_tag('br');

echo html_writer::div('<hr>', 'smallerhr');

// Get the messages that have been answered.
$messages = block_ask4summary_get_answered_forum_posts($course->id);
// Get the average time taken for posts to have a response generated.
$avg = round(block_ask4summary_avg_answer_time($course->id), 2);
// Get how many posts could not have a summary generated.
$unanswered = block_ask4summary_get_unanswered_forum_posts($course->id);

echo html_writer::div(get_string('postanswered', 'block_ask4summary', count($messages)), 'biggernormal', array('id' => 'answered'));
echo html_writer::empty_tag('br');
echo html_writer::div(get_string('avgtime', 'block_ask4summary', $avg), 'biggernormal');
// If there are answered messages, show the post, who asked, and when asked.
if ($messages) {
    echo html_writer::div('<hr>', 'smallerhr');

    echo html_writer::div(get_string('answeredposts', 'block_ask4summary'), 'biggernormal');
    echo html_writer::empty_tag('br');

    $check = 'check';
    $prevsummary = '';
    $nobullets = array('class' => 'nobullets');

    // NOTE: Recursion could probably clean this up a bit
    // Perhaps that will be a task to do in the future.
    echo html_writer::start_tag('ul');
    foreach ($messages as $id => $obj) {

        $date = date("F d, Y", $obj->created);

        // Check to see if the summary is the first one.
        if ($id === array_key_first($messages)) {

            // Since we would like to show duplicates, we will need to check
            // the previous summary.
            $check = $obj->ngramlist;
            $prevsummary = $obj->summary;

            echo html_writer::tag('li', html_writer::div(get_string('post', 'block_ask4summary', strip_tags($obj->message))
                . html_writer::tag('b', get_string('timeasked', 'block_ask4summary', $date) .
                get_string('userasked', 'block_ask4summary', $obj->firstname . ' ' . $obj->lastname))), $nobullets);

            // If the first is the last, just print out it's summary.
            if ($id === array_key_last($messages)) {

                echo html_writer::empty_tag('br');

                echo html_writer::start_tag('ul');

                echo html_writer::tag('li', html_writer::div(get_string('summary',
                    'block_ask4summary', strip_tags($obj->summary))), $nobullets);

                echo html_writer::end_tag('ul');

            }

            continue;
        }

        // If the question has the same summary as the previous summary
        // just print out the question and not the summary.
        if (!(strcmp($obj->ngramlist, $check))) {

            echo html_writer::tag('li', html_writer::div(get_string('post', 'block_ask4summary', strip_tags($obj->message))
                . html_writer::tag('b', get_string('timeasked', 'block_ask4summary', $date) .
                get_string('userasked', 'block_ask4summary', $obj->firstname . ' ' . $obj->lastname))), $nobullets);

            // If the duplicates end up being the last one then just print it.
            if ($id === array_key_last($messages)) {

                echo html_writer::start_tag('ul');

                echo html_writer::tag('li', html_writer::div(get_string('summary',
                    'block_ask4summary', strip_tags($obj->summary))), $nobullets);
                echo html_writer::end_tag('ul');

            }

            continue;

        } else {

            // Otherwise the question has a different summary and we will
            // need to print out the previous messages summary.

            echo html_writer::start_tag('ul');

            echo html_writer::empty_tag('br');

            // Print out previous summary.
            echo html_writer::tag('li', html_writer::div(get_string('summary',
                'block_ask4summary', strip_tags($prevsummary))), $nobullets);

            echo html_writer::end_tag('ul');

            // Now our previous summary and question is this question.
            $check = $obj->ngramlist;
            $prevsummary = $obj->summary;

            echo html_writer::div('<hr/>', '');

            // Print out its bullet.
            echo html_writer::tag('li', html_writer::div(get_string('post', 'block_ask4summary', strip_tags($obj->message))
                . html_writer::tag('b', get_string('timeasked', 'block_ask4summary', $date) .
                get_string('userasked', 'block_ask4summary', $obj->firstname . ' ' . $obj->lastname))), $nobullets);

            // If it is the last question then print out the summary.
            if ($id === array_key_last($messages)) {

                echo html_writer::empty_tag('br');

                echo html_writer::start_tag('ul');

                echo html_writer::tag('li', html_writer::div(get_string('summary',
                    'block_ask4summary', strip_tags($obj->summary))), $nobullets);
                echo html_writer::end_tag('ul');

            }

        }

    }
    echo html_writer::end_tag('ul');
    echo html_writer::empty_tag('br');
} else {
    echo html_writer::div('<hr>', 'smallerhr');

    echo html_writer::div(get_string('noanswered', 'block_ask4summary'), 'biggernormal');
    echo html_writer::empty_tag('br');
}

echo html_writer::div('<hr/>', '');

$num = count($unanswered);

echo html_writer::div(get_string('postunanswered', 'block_ask4summary', $num), 'biggernormal', array('id' => 'unanswered'));

if ($unanswered) {

    echo html_writer::div('<hr>', 'smallerhr');

    echo html_writer::div(get_string('unansweredposts', 'block_ask4summary'), 'biggernormal');
    echo html_writer::empty_tag('br');

    $nobullets = array('class' => 'nobullets');

    echo html_writer::start_tag('ul');

    foreach ($unanswered as $id => $obj) {

        $date = date("F d, Y", $obj->created);

        echo html_writer::tag('li', html_writer::div(get_string('post', 'block_ask4summary', strip_tags($obj->message))
            . html_writer::tag('b', get_string('timeasked', 'block_ask4summary', $date) .
            get_string('userasked', 'block_ask4summary', $obj->firstname . ' ' . $obj->lastname))), $nobullets);

    }

    echo html_writer::end_tag('ul');
}

echo $OUTPUT->footer();
