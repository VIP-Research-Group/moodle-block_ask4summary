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
 * Library of Ask4Summary functions.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Gets forum names available within the course, given they are not the autoforum.
 *
 * @param int $courseid - the course id
 * @return array
 */
function block_ask4summary_get_forums($courseid) {
    global $DB;

    // If the autoforum exists, do not include that in the return list.
    if ($autoid = $DB->get_field('block_ask4summary_settings', 'autoforumid', ['courseid' => $courseid])) {
        $sql = "SELECT *
               FROM {forum} f
               WHERE f.course = :courseid
                     AND f.id <> :autoid";

        $forums = $DB->get_records_sql($sql, array('courseid' => $courseid, 'autoid' => $autoid));
    } else {
        $sql = "SELECT *
               FROM {forum} f
               WHERE f.course = :courseid";

        $forums = $DB->get_records_sql($sql, array('courseid' => $courseid));
    }

    $forumarray = array();

    foreach ($forums as $forum) {
        $forumarray[$forum->id] = $forum->name;
    }

    return $forumarray;
}

/**
 * Alternate between hiding/showing the autoforum, given that it exists.
 *
 * @param int $courseid - the course id
 * @param int $autoforumid - the autoforum id
 * @param int $visible - 0/1
 *
 * @return none|false
 */
function block_ask4summary_display_autoforum($courseid, $autoforumid, $visible) {
    global $DB, $CFG;

    // If the autoforum exists...
    if ($forum = $DB->get_record_select("forum", "course = ? AND id = ?", array($courseid, $autoforumid))) {

        include_once("$CFG->dirroot/course/lib.php");
        include_once("$CFG->dirroot/lib/datalib.php");

        $sql = "SELECT c.id
                  FROM {course_modules} c
                  JOIN {modules} m on m.id = c.module
                 WHERE m.name = 'forum'
                       AND c.instance = :forumid";

        // Get the course module for the autoforum.
        $cmid = $DB->get_field_sql($sql, ['forumid' => $forum->id]);

        // Set to either visible or invisible and update the course module.
        set_coursemodule_visible($cmid, $visible);
        \core\event\course_module_updated::create_from_cm(get_coursemodule_from_id('forum', $cmid));
    } else {
        return false;
    }

}

/**
 * Helper function for block_ask4summary_create_forum(). Initializes most of the properties.
 *
 * @param int $courseid - the course id
 * @param string $forumname - the autoforum name
 *
 * @return object
 */
function block_ask4summary_autoforum_object($courseid, $forumname) {
    $autoforum = new stdClass();

    $autoforum->course = $courseid;
    $autoforum->type = 'general';
    $autoforum->name = $forumname;
    $autoforum->intro = get_string('identifier', 'block_ask4summary');
    $autoforum->introformat = 1;
    $autoforum->duedate = 0;
    $autoforum->cutoffdate = 0;
    $autoforum->scale = 100;
    $autoforum->grade_forum = 0;
    $autoforum->grade_forum_notify = 0;
    $autoforum->maxbytes = 512000;
    $autoforum->maxattachments = 9;
    $autoforum->forcesubscribe = 0;
    $autoforum->trackingtype = 1;
    $autoforum->rsstype = 0;
    $autoforum->rssarticles = 0;
    $autoforum->warnafter = 0;
    $autoforum->blockafter = 0;
    $autoforum->blockperiod = 0;
    $autoforum->completiondiscussions = 0;
    $autoforum->completionreplies = 0;
    $autoforum->completionposts = 0;
    $autoforum->displaywordcount = 0;
    $autoforum->lockdiscussionafter = 0;

    return $autoforum;
}

/**
 * Build a forum object with default settings for the Ask4Summary Autoforum.
 *
 * Heavily derived from the forum_get_course_forum() function in /mod/forum/lib.php
 *
 * @param int $courseid - the course id number
 * @param string $forumname - the autoforum name
 *
 * @return object
 */
function block_ask4summary_create_forum($courseid, $forumname) {
    global $CFG, $DB, $OUTPUT, $USER;

    // Initialize an object for the forum with many default properties set.
    $autoforum = block_ask4summary_autoforum_object($courseid, $forumname);

    // Set the time modified to whenever ran now.
    $autoforum->timemodified = time();
    // Create the forum in the database.
    $autoforum->id = $DB->insert_record("forum", $autoforum);

    if (! $module = $DB->get_record("modules", array("name" => "forum"))) {
        echo $OUTPUT->notification("Could not find forum module!!");
        return false;
    }
    // Create the course module object.
    $mod = new stdClass();
    $mod->course = $courseid;
    $mod->module = $module->id;
    $mod->instance = $autoforum->id;
    $mod->section = 0;
    include_once("$CFG->dirroot/course/lib.php");
    if (! $mod->coursemodule = add_course_module($mod) ) {
        echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
        return false;
    }
    // Add the course module for the newly created forum.
    $sectionid = course_add_cm_to_section($courseid, $mod->coursemodule, 0);
    return $DB->get_record("forum", array("id" => "$autoforum->id"), 'id');

}

/**
 * Update the autoforum name.
 *
 * @param int $courseid - the ID for the course
 * @param string $forumname - the autoforum name
 * @param int $autoforumid - the autoforums database ID
 *
 */
function block_ask4summary_update_autoforum($courseid, $forumname, $autoforumid) {
    global $DB;

    // If the autoforum exists and the configuration settings have the name changed, update the name.
    if ($forum = $DB->get_record_select("forum", "course = ? AND id = ?", array($courseid, $autoforumid))) {
        if ($forumname != $forum->name) {
            $forum->name = $forumname;
            $DB->update_record('forum', $forum);
        }
    }
}

/**
 * Breaks a long string of sentences into an array, with each element being a sentence.
 *
 * @param string $query - the sentences/paragraph to be broken down
 * @return array
 */
function block_ask4summary_break_content($query) {
    return preg_split('/(?<=[.?!])(\s|)+(?=[a-z, 0-9])/i', $query);
}

/**
 * Generate the Valid N-Grams and POS for an individual sentence.
 *
 * @param string $query - The sentence
 * @return object|false
 */
function block_ask4summary_generate_ngram_pos($query) {

    // Generate the N-Grams from N = 1,2,3,4 for the given sentence.
    // Verify those N-Grams for the Top 15 POS.
    $inputarr = array(
        'content' => $query,
        'content_type' => 'ngram',
        'ngram_n' => 1,
        'ngram_n_max' => 4,
        'delimeter' => ";",
        'verify_for' => 'pos');

    $json = json_encode($inputarr);
    $context = array('http' =>
            array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/json',
            'content' => $json
            )
          );

    $msgstart = microtime(true);

    $context  = stream_context_create($context);

    $url = "https://ngrampos.vipresearch.ca/ngram_pos/service/word_service/";
    $contents = file_get_contents($url, false, $context);

    $ngrams = json_decode($contents);

    $msgend = microtime(true);
    $msgtime = $msgend - $msgstart;
    mtrace($query);
    mtrace(get_string('timetaken', 'block_ask4summary', $msgtime));

    if (!is_object($ngrams)) {
        return false;
    }

    // If valid n-grams exist for the sentence...
    if (property_exists($ngrams->pos_asked, 'valid')) {
        // Gather those valid n-grams.
        $validngrams = $ngrams->pos_asked->valid;
    } else {
        // Otherwise break out of the function.
        return false;
    }

    $resultarr = array();
    // Save every N-Gram, it's POS, and N into an array of objects.
    foreach ($validngrams as $ngram => $ngramp) {

        $ngrampos = (object) [
            'ngram' => $ngram,
            'pos' => $ngramp[0]->pos,
            'n' => $ngramp[0]->ngram,
            'sentence' => $query,
            'timetaken' => $msgtime];

        $resultarr[] = $ngrampos;

    }
    return $resultarr;

}

/**
 * Inserts the POS into the database table 'block_ask4summary_table_pos'
 * or returns it if it already exists.
 *
 * @param object $query - the object containing the N-Gram, N, POS, Sentence
 *                        and timetaken
 * @return int - the ID in the database table
 */
function block_ask4summary_get_table_pos($query) {
    global $DB;

    // See if the POS has already been inserted.
    $sql = "SELECT p.posid
            FROM {block_ask4summary_tablepos} p
            WHERE p.ngram_pos = :pos";

    // If yes, return that result. Otherwise insert the POS and N.
    if ($result = $DB->get_record_sql($sql, array('pos' => $query->pos))) {
        return $result->posid;
    } else {
        $pos = (object) ['ngram_pos' => $query->pos,
                         'ngram_length' => $query->n];
        return $DB->insert_record('block_ask4summary_tablepos', $pos);
    }
}

/**
 * Inserts the N-Gram into the database table 'block_ask4summary_tablengram'
 * or returns it if it already exists.
 *
 * @param object $query - the object containing the N-Gram, N, POS, Sentence
 *                        and timetaken
 * @param int $posid - the ID number of the POS in 'block_ask4summary_table_pos'
 *
 * @return int - the ID in the database table
 */
function block_ask4summary_get_table_ngram($query, $posid) {
    global $DB;

    // See if a particular N-Gram has been inserted.
    $sql = "SELECT n.ngramid
            FROM {block_ask4summary_tablengram} n
            WHERE n.word = :word";

    // If yes, return that N-Gram id, otherwise insert the N-Gram and its POS.
    if ($result = $DB->get_record_sql($sql, array('word' => $query->ngram))) {
        return $result->ngramid;
    } else {
        $ngram = (object) ['word' => $query->ngram,
                           'posid' => $posid];
        return $DB->insert_record('block_ask4summary_tablengram', $ngram);
    }
}

/**
 * Get the Ask4Summary settings of a particular course.
 *
 * @param int $courseid - The course ID
 *
 * @return object - the database object
 */
function block_ask4summary_get_settings($courseid) {
    global $DB;

    return $DB->get_record('block_ask4summary_settings', ['courseid' => $courseid]);
}

/**
 * Get how many unscanned course modules exist in the course.
 *
 * @param int $courseid - The course ID
 *
 * @return object - the database object
 */
function block_ask4summary_get_unscanned_cms($courseid) {
    global $DB;

    // If the course module is of file type and is not in clobjects.
    $sql = "SELECT c.id, c.course, c.instance
            FROM {course_modules} c
            JOIN {modules} m ON m.id = c.module
            WHERE m.name = 'resource'
                  AND c.course = :courseid
                  AND NOT EXISTS (SELECT NULL
                                  FROM {block_ask4summary_clobjects} l
                                  WHERE c.id = l.cmid)";

    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}

/**
 * Get the mimetype of a particular file type course module.
 *
 * @param object $cm - The course module
 *
 * @return string - the mimetype of the file
 */
function block_ask4summary_get_mimetype($cm) {
    global $DB;

    $resource = $DB->get_record('resource', array('id' => $cm->instance));
    $intro = array('name' => strip_tags($resource->name),
                   'intro' => strip_tags($resource->intro));

    // Retrieve the file from storage.
    $context = \context_module::instance($cm->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

    if (count($files) < 1) {
        return $intro;

    } else {
        $file = reset($files);
        unset($files);
    }

    // Get the file from file storage.
    $file = $fs->get_file($context->id, 'mod_resource', 'content', 0, $file->get_filepath(), $file->get_filename());

    return $file->get_mimetype();

}

/**
 * Insert the unscanned course modules into clobjects table
 * with their mimetype.
 *
 * @param array $cmarr - an array of unscanned file course modules
 *
 * @return string - how many were scanned
 */
function block_ask4summary_insert_cms($cmarr) {

    global $DB;

    // For every course module...
    foreach ($cmarr as $cm) {

        $mimetype = block_ask4summary_get_mimetype($cm);

        $clobj = (object) [
            'courseid' => $cm->course,
            'cmid' => $cm->id,
            'mimetype' => $mimetype];

        // Insert into object table.
        $DB->insert_record('block_ask4summary_clobjects', $clobj);

    }

    return 'Scanned ' . count($cmarr) . ' course modules.';

}

/**
 * Get how many unparsed URLs exist in the course.
 *
 * @param int $courseid - The course ID
 * @param int $depth - The depth that URL was recursively parsed with
 *
 * @return array - an array of URL objects
 */
function block_ask4summary_get_unparsed_urls($courseid, $depth) {
    global $DB;

    // If is URL module type, and that URL does not exist in clobjects with
    // that depth.
    $sql = "SELECT c.id, u.name
              FROM {course_modules} c
              JOIN {url} u ON c.instance = u.id
              JOIN {modules} m ON m.id = c.module
             WHERE m.name = 'url'
                   AND c.course = :courseid
                   AND NOT EXISTS (SELECT NULL
                                   FROM {block_ask4summary_clobjects} l
                                   WHERE c.id = l.cmid
                                         AND l.depth = :depth)";

    return $DB->get_records_sql($sql, ['courseid' => $courseid,
     'depth' => $depth]);
}

/**
 * Get how many parsed URLs exist in the course.
 *
 * @param int $courseid - The course ID
 * @param int $depth - The depth that URL was recursively parsed with
 *
 * @return array - an array of URL objects
 */
function block_ask4summary_get_parsed_urls($courseid, $depth) {
    global $DB;

    // If the URL exists with that depth...
    $sql = "SELECT *
              FROM {block_ask4summary_clobjects} l
             WHERE l.courseid = :courseid
                   AND l.depth = :depth
                   AND l.url IS NOT NULL";

    return $DB->get_records_sql($sql, ['courseid' => $courseid,
     'depth' => $depth]);
}

/**
 * Get how many unparsed pages exist in the course.
 *
 * @param int $courseid - The course ID
 *
 * @return array - an array of page objects
 */
function block_ask4summary_get_unparsed_pages($courseid) {
    global $DB;

    // If the course module is of Page type and does not exist in clobjects...
    $sql = "SELECT c.id, p.name
              FROM {course_modules} c
              JOIN {page} p ON c.instance = p.id
              JOIN {modules} m ON m.id = c.module
             WHERE m.name = 'page'
                   AND c.course = :courseid
                   AND NOT EXISTS (SELECT NULL
                                   FROM {block_ask4summary_clobjects} l
                                   WHERE c.id = l.cmid)";

    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}

/**
 * Get how many parsed pages exist in the course.
 *
 * @param int $courseid - The course ID
 *
 * @return array - an array of page objects
 */
function block_ask4summary_get_parsed_pages($courseid) {
    global $DB;

    $sql = "SELECT *
              FROM {block_ask4summary_clobjects} l
             WHERE l.courseid = :courseid
                   AND l.url IS NULL
                   AND l.mimetype IS NULL";

    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}

/**
 * Get the course modules of a particular type and parsed state
 * in the clobjects table.
 *
 * @param int $courseid - The course ID
 * @param string $mimetype - The file mimetype
 * @param int $parsed - Whether the course module has been parsed
 *
 * @return array - an array of page objects
 */
function block_ask4summary_get_cms($courseid, $mimetype, $parsed) {
    global $DB;

    return $DB->get_records('block_ask4summary_clobjects',
        ['courseid' => $courseid,
         'mimetype' => $mimetype,
         'parsed' => $parsed]);
}

/**
 * Get every parsed course module in the course.
 *
 * @param int $courseid - The course ID
 * @param object $settings - The database record with Ask4Summary settings
 *
 * @return array - an array with each type and the amount parsed, or false
 *                 if none
 */
function block_ask4summary_get_all_parsed($courseid, $settings) {

    $parsed = array('url' => false,
        'pdf' => false, 'docx' => false, 'pptx' => false, 'page' => false);

    // If the settings are enabled, count how many records exist.
    if ($settings->enableurl) {
        $parsed['url'] = count(block_ask4summary_get_parsed_urls($courseid, $settings->depth));
    }

    if ($settings->enablepdf) {
        $parsed['pdf'] = count(block_ask4summary_get_cms($courseid,
            get_string('mimetype_pdf', 'block_ask4summary'), 1));
    }

    if ($settings->enabledocx) {
        $parsed['docx'] = count(block_ask4summary_get_cms($courseid,
            get_string('mimetype_docx', 'block_ask4summary'), 1));
    }

    if ($settings->enablepptx) {
        $parsed['pptx'] = count(block_ask4summary_get_cms($courseid,
            get_string('mimetype_pptx', 'block_ask4summary'), 1));
    }

    if ($settings->enablepage) {
        $parsed['page'] = count(block_ask4summary_get_parsed_pages($courseid));
    }

    return $parsed;

}

/**
 * Get every unparsed course module in the course.
 *
 * @param int $courseid - The course ID
 * @param object $settings - The database record with Ask4Summary settings
 *
 * @return array - an array with each type and the amount parsed, or false
 *                 if none
 */
function block_ask4summary_get_all_unparsed($courseid, $settings) {
    $unparsed = array('url' => false,
        'pdf' => false, 'docx' => false, 'pptx' => false, 'page' => false);

    // If the settings enabled, count how many records exist.
    if ($settings->enableurl) {
        $unparsed['url'] = count(block_ask4summary_get_unparsed_urls($courseid, $settings->depth));
    }

    if ($settings->enablepdf) {
        $unparsed['pdf'] = count(block_ask4summary_get_cms($courseid,
            get_string('mimetype_pdf', 'block_ask4summary'), 0));
    }

    if ($settings->enabledocx) {
        $unparsed['docx'] = count(block_ask4summary_get_cms($courseid,
            get_string('mimetype_docx', 'block_ask4summary'), 0));
    }

    if ($settings->enablepptx) {
        $unparsed['pptx'] = count(block_ask4summary_get_cms($courseid,
            get_string('mimetype_pptx', 'block_ask4summary'), 0));
    }

    if ($settings->enablepage) {
        $unparsed['page'] = count(block_ask4summary_get_unparsed_pages($courseid));
    }

    return $unparsed;

}

/**
 * Called to get the courses that have this plugin installed.
 *
 * Taken from Ted Krahn's Behaviour Analytics.
 *
 * @return stdClass
 */
function block_ask4summary_get_courses() {
    global $DB;

    // Get the courses for which the plugin is installed.
    $sql = "SELECT c.id, c.shortname FROM {course} c
            JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextcourse
            WHERE ctx.id in (SELECT distinct parentcontextid FROM {block_instances}
                               WHERE blockname = 'ask4summary')
            ORDER BY c.sortorder";
    return $DB->get_records_sql($sql, array('contextcourse' => CONTEXT_COURSE));
}

/**
 * Called to determine whether or not the block is installed in a course.
 *
 * Taken from Ted Krahn's Behaviour Analytics.
 *
 * @param int $courseid The course ID.
 * @return boolean
 */
function block_ask4summary_is_installed($courseid) {
    global $DB;

    $courses = block_ask4summary_get_courses();

    foreach ($courses as $c) {
        if ($c->id === $courseid) {
            return true;
        }
    }
    return false;
}

/**
 * Called to get the navigation links.
 *
 * Taken from Ted Krahn's Behaviour Analytics.
 *
 * @return string
 */
function block_ask4summary_get_nav_links() {
    global $COURSE, $USER;

    $params = array(
        'id' => $COURSE->id,
    );

    $log = new moodle_url('/blocks/ask4summary/logistics.php', $params);
    $docs = new moodle_url('/blocks/ask4summary/documentation.php', $params);
    $return = new moodle_url('/course/view.php', $params);

    $links = html_writer::link($log, get_string('loganchor', 'block_ask4summary')) . '&nbsp&nbsp&nbsp' .
        html_writer::link($docs, get_string('docsanchor', 'block_ask4summary')) . '&nbsp&nbsp&nbsp' .
        html_writer::link($return, get_string('return', 'block_ask4summary')) . '&nbsp&nbsp&nbsp';

    return $links;
}

/**
 * Build a table row for the logistics page.
 *
 * @param string $type - The course module type
 * @param int $parsed - How many were parsed for this type
 * @param int $unparsed - How many were unparsed for this type
 *
 * @return string - HTML writer for table row
 */
function block_ask4summary_build_row($type, $parsed, $unparsed) {

    $class = array('class' => 'logrows');

    $total = $parsed + $unparsed;

    // If there are records, get the percentage to two decimal places.
    if ($total !== 0) {
        $percent = round(($parsed / $total), 2) * 100 . '%';
    } else {
        $percent = '0%';
    }

    // Build the table row with the type, how many parsed, how many unparsed,
    // and the percentage.
    $row = html_writer::start_tag('tr') .
            html_writer::tag('th', $type, $class) .
            html_writer::tag('td', $parsed, $class) .
            html_writer::tag('td', $unparsed, $class) .
            html_writer::tag('td', $percent, $class) .
            html_writer::end_tag('tr');

    return $row;
}

/**
 * Build a line containing the color coded Ask4Summary settings
 *
 * @param object $settings - The database record with Ask4Summary settings
 *
 * @return string - HTML writer for the settings
 */
function block_ask4summary_display_settings($settings) {

    $options = array('url' => get_string('disabled', 'block_ask4summary'),
                      'pdf' => get_string('disabled', 'block_ask4summary'),
                      'docx' => get_string('disabled', 'block_ask4summary'),
                      'pptx' => get_string('disabled', 'block_ask4summary'),
                      'page' => get_string('disabled', 'block_ask4summary'));

    $color = array('url' => 'disabled',
                   'pdf' => 'disabled',
                   'docx' => 'disabled',
                   'pptx' => 'disabled',
                   'page' => 'disabled');

    $disp = 'displayinline';
    $space = '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';

    // If the settings enabled, set the option to enabled and the color
    // to the enabled class.
    if ($settings->enableurl) {
        $options['url'] = get_string('enabled', 'block_ask4summary');
        $color['url'] = 'enabled';
    }

    if ($settings->enablepdf) {
        $options['pdf'] = get_string('enabled', 'block_ask4summary');
        $color['pdf'] = 'enabled';
    }

    if ($settings->enabledocx) {
        $options['docx'] = get_string('enabled', 'block_ask4summary');
        $color['docx'] = 'enabled';
    }

    if ($settings->enablepptx) {
        $options['pptx'] = get_string('enabled', 'block_ask4summary');
        $color['pptx'] = 'enabled';
    }

    if ($settings->enablepage) {
        $options['page'] = get_string('enabled', 'block_ask4summary');
        $color['page'] = 'enabled';
    }

    // Build the string in the same line with color coded settings.
    $text = html_writer::div(get_string('url', 'block_ask4summary') . ': ' . $options['url'], $color['url']) . $space .
        html_writer::div(get_string('pdf', 'block_ask4summary') . ': ' . $options['pdf'], $color['pdf']) . $space .
        html_writer::div(get_string('docx', 'block_ask4summary') . ': ' . $options['docx'], $color['docx']) . $space .
        html_writer::div(get_string('pptx', 'block_ask4summary') . ': ' . $options['pptx'], $color['pptx']) . $space .
         html_writer::div(get_string('page', 'block_ask4summary') . ': ' . $options['page'], $color['page']);

    return $text;

}

/**
 * Get the course module names for a particular type
 *
 * @param int $courseid - The course id number
 * @param int $parsed - Whether they were parsed or not
 * @param string $type - url, pdf, docx, pptx, page
 *
 * @return array - the records of this type
 */
function block_ask4summary_get_cm_names($courseid, $parsed, $type) {
    global $DB;

    $sql1 = "SELECT o.obid, u.name
               FROM {block_ask4summary_clobjects} o
               JOIN {course_modules} c ON o.cmid = c.id
               JOIN {url} u ON c.instance = u.id
              WHERE o.courseid = :courseid
                    AND o.url IS NOT NULL";

    $sql2 = "SELECT o.obid, p.name
               FROM {block_ask4summary_clobjects} o
               JOIN {course_modules} c ON o.cmid = c.id
               JOIN {page} p ON c.instance = p.id
               WHERE o.courseid = :courseid
                     AND o.mimetype IS NULL";

    $sql3 = "SELECT o.obid, r.name
               FROM {block_ask4summary_clobjects} o
               JOIN {course_modules} c ON o.cmid = c.id
               JOIN {resource} r ON c.instance = r.id
               WHERE o.courseid = :courseid
                     AND o.parsed = :parsed
                     AND o.mimetype = :mimetype
                     AND o.mimetype IS NOT NULL";

    // If the URL or Page type is selected, you do not need to clarify mimetype.
    if ($type === 'url') {
        return $DB->get_records_sql($sql1, ['courseid' => $courseid]);
    } else if ($type === 'page') {
        return $DB->get_records_sql($sql2, ['courseid' => $courseid]);
    } else {
        return $DB->get_records_sql($sql3, ['courseid' => $courseid,
            'parsed' => $parsed, 'mimetype' => $type]);
    }

}

/**
 * Get all the course module names in the course
 *
 * @param int $courseid - The course id number
 *
 * @return array - the names for each type
 */
function block_ask4summary_get_all_cm_names($courseid) {

    global $DB;

    // Array initially containing each types settings for getting their names.
    $types = array('parsedurl' => array(1, 'url'),
                   'parsedpdf' => array(1, get_string('mimetype_pdf', 'block_ask4summary')),
                   'parseddocx' => array(1, get_string('mimetype_docx', 'block_ask4summary')),
                   'parsedpptx' => array(1, get_string('mimetype_pptx', 'block_ask4summary')),
                   'parsedpage' => array(1, 'page'),
                   'unparsedurl' => array(0, 'url'),
                   'unparsedpdf' => array(0, get_string('mimetype_pdf', 'block_ask4summary')),
                   'unparseddocx' => array(0, get_string('mimetype_docx', 'block_ask4summary')),
                   'unparsedpptx' => array(0, get_string('mimetype_pptx', 'block_ask4summary')),
                   'unparsedpage' => array(0, 'page'));

    // Change the names component to an array containing all the course modules.
    foreach ($types as $type => &$names) {
        if ($type === 'unparsedurl') {
            // Get the depth if a URL.
            $depth = $DB->get_field('block_ask4summary_settings', 'depth', ['courseid' => $courseid]);
            $names = block_ask4summary_get_unparsed_urls($courseid, $depth);
        } else if ($type === 'unparsedpage') {
            $names = block_ask4summary_get_unparsed_pages($courseid);
        } else {
            $parsed = $names[0];
            $type = $names[1];
            $names = block_ask4summary_get_cm_names($courseid, $parsed, $type);
        }
    }

    return $types;
}

/**
 * Build a HTML writer variable for displaying the course module names.
 *
 * @param int $courseid - The course id number
 *
 * @return string - the HTML writer portrayal
 */
function block_ask4summary_display_names($courseid) {

    $types = block_ask4summary_get_all_cm_names($courseid);

    $div = '';

    // Build an unordered HTML list for every types course module name.
    foreach ($types as $type => $names) {

        $namestr = html_writer::start_tag('ul');

        foreach ($names as $name) {
            $namestr .= html_writer::tag('li', $name->name);
        }

        $namestr .= html_writer::end_tag('ul');

        $div .= html_writer::div(html_writer::div(
            get_string($type, 'block_ask4summary'), 'itemheader') . html_writer::div($namestr, 'item'), 'itemcontainer');

    }

    return $div;

}

/**
 * Get all the answered forum posts in the course.
 *
 * @param int $courseid - The course id number
 *
 * @return array - the answered forum post objects in the course
 */
function block_ask4summary_get_answered_forum_posts($courseid) {
    global $DB;

    $sql = "SELECT DISTINCT p.id, p.message, p.created, u.firstname,
                            u.lastname, r.summary, r.ngramlist
              FROM {forum_posts} p
              JOIN {block_ask4summary_response} r ON p.id = r.postid
              JOIN {user} u ON p.userid = u.id
             WHERE r.courseid = :courseid
          ORDER BY r.summary ASC";

    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}

/**
 * Get all the unanswered forum posts in the course.
 *
 * @param int $courseid - The course id number
 *
 * @return array - the unanswered forum post objects in the course
 */
function block_ask4summary_get_unanswered_forum_posts($courseid) {
    global $DB;

    $sql = "SELECT DISTINCT p.id, p.message, p.created, u.firstname,
                            u.lastname
              FROM {forum_posts} p
              JOIN {block_ask4summary_sentence} s ON p.id = s.postid
              JOIN {user} u ON p.userid = u.id
             WHERE s.courseid = :courseid
                   AND s.answered = 0
          ORDER BY p.id ASC";

    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}

/**
 * Replies to a post with the given summary.
 *
 * Derived from /mod/forum/lib.php::forum_add_new_post, but not
 * reused as there is no mform object being created, and some
 * other functions were not necessary.
 *
 * @param string $summary - the generated Ask4Summary response
 * @param int $postid - the parent post which contained the question
 *
 * @return int - the ID of the new post in the database table
 */
function block_ask4summary_reply_to_question($summary, $postid) {
    global $USER, $DB;

    $parent = $DB->get_record('forum_posts', array('id' => $postid));

    $discussion = $DB->get_record('forum_discussions', array('id' => $parent->discussion));
    $forum      = $DB->get_record('forum', array('id' => $discussion->forum));
    $cm         = get_coursemodule_from_instance('forum', $forum->id);
    $privatereplyto = 0;

    if (!empty($parent->privatereplyto)) {
        throw new \coding_exception('It should not be possible to reply to a private reply');
    }

    $post = new \stdClass();

    $post->discussion = $discussion->id;
    $post->parent     = $postid;
    $post->subject    = 'Re: ' . $parent->subject;
    $post->message    = $summary;
    $post->created    = $post->modified = time();
    $post->mailed     = $parent->mailed;
    $post->userid     = $USER->id;
    $post->privatereplyto = $privatereplyto;
    $post->attachment = "";
    $post->messageformat = 1;
    $post->messagetrust = 0;
    $post->deleted = 0;

    $post->wordcount = str_word_count($summary);
    $post->charcount = strlen($summary) - substr_count($summary, ' ');

    if (!isset($post->totalscore)) {
        $post->totalscore = 0;
    }
    if (!isset($post->mailnow)) {
        $post->mailnow    = 0;
    }

    \mod_forum\local\entities\post::add_message_counts($post);
    $post->id = $DB->insert_record("forum_posts", $post);

    // Update discussion modified date.
    $DB->set_field("forum_discussions", "timemodified", $post->modified, array("id" => $post->discussion));
    $DB->set_field("forum_discussions", "usermodified", $post->userid, array("id" => $post->discussion));

    return $post->id;
}

/**
 * Compute the average answer time for questions to be answered.
 *
 * @param int $courseid - The course id number
 *
 * @return float|int
 */
function block_ask4summary_avg_answer_time($courseid) {
    global $DB;

    $sql = "SELECT r.timetaken
              FROM {block_ask4summary_response} r
              WHERE r.courseid = :courseid";

    if ($times = $DB->get_fieldset_sql($sql, ['courseid' => $courseid])) {
        $total = 0;

        foreach ($times as $time) {
            $total += $time;
        }

        $avg = $total / count($times);

        return $avg;

    } else {
        return 0;
    }

}
