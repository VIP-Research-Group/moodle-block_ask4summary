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
 * The scheduled task: Scan Forums is defined here.
 *
 * This task takes the teacher/administrator settings into account and either
 * scans all forums within a course, a specific forum within a course, or an
 * automatically generated forum within the course. It will then check through
 * forum postings and subjects to see if any of them contain 'Hi' plus
 * the set Ask4Summary helper name. It will then generate the N-grams and
 * Parts of Speech (POS) from these sentences, and put these into the database
 * if they have not been read already.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ask4summary\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ask4summary/locallib.php');

/**
 * Scan questions for N-Gram content with the helpername.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 *
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scan_forums extends \core\task\scheduled_task {

    /**
     * Return the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('scan', 'block_ask4summary');
    }

    /**
     * Execute the forum parsing and N-Gram generation.
     * Queues answer_question adhoc task if valid questions
     * parsed.
     *
     * @return true
     */
    public function execute() {
        global $DB;

        // Only select the courses that have set the functionality on.
        $enabledcourses = $DB->get_records('block_ask4summary_settings',
                                            ['enabled' => 1]);

        mtrace(get_string('retrieved', 'block_ask4summary'));

        // Go through each course...
        foreach ($enabledcourses as $course) {

            $cid = $course->courseid;
            $cfid = $course->forumid;
            $ctype = $course->responsetype;
            $cname = $course->helpername;

            $cdoc = $course->topdocs;
            $csent = $course->topsentences;

            $answer = array();
            $answerqueue = array();

            $answer['topdoc'] = $cdoc;
            $answer['topsent'] = $csent;
            $answer['courseid'] = $cid;

            mtrace(get_string('currentcourse', 'block_ask4summary', $cid));

            // Confirm that the selected forum was not deleted.
            if (($ctype !== 1) && (is_null($cfid))) {
                mtrace(get_string('deletedforum', 'block_ask4summary'));
                continue;
            }

            // Get the messages that include the helper name.
            $messages = $this->get_messages($cid, $cname, $ctype, $cfid);

            foreach ($messages as $postid => $message) {
                // Get the N-Grams for the specific sentence.
                $ngrams = $this->return_ngram_pos($message, $cname);

                $ngramlist = array();

                $answerngramlist = $this->get_answer_ngram_list($cid);

                // Put into the database each N-Gram, POS, and sentence.
                foreach ($ngrams as $ngram) {
                    $posid = block_ask4summary_get_table_pos($ngram);

                    $ngramid = block_ask4summary_get_table_ngram($ngram, $posid);

                    $ngramlist[] = $ngramid;

                    $sentenceid = $this->get_sentence($ngram, $cid, $postid);

                    $sngramid = $this->get_sngram($sentenceid, $ngramid);

                }
                // Sort the N-Gram list, and turn into a string.
                asort($ngramlist);

                $ngramlist = implode('-', $ngramlist);

                mtrace($ngramlist);
                // If the N-Gram list was found in the summary table...
                if (in_array($ngramlist, $answerngramlist, true)) {
                    // Use the previous response and do not compute again.
                    $responseid = array_search($ngramlist, $answerngramlist);

                    $this->existing_answer($cid, $postid, $responseid);

                    continue;

                }

                // If parsing was successful, add to answering queue.
                if ($DB->get_records('block_ask4summary_sentence',
                    ['postid' => $postid])) {

                    $answerqueue[] = $postid;

                } else {
                    // Otherwise the parsing failed
                    // or no valid top POS ngrams were calculated.
                    mtrace(get_string('notanswerable', 'block_ask4summary'));

                    $obj = (object)
                        ['courseid' => $cid,
                         'postid' => $postid,
                         'timetaken' => 0,
                         'answered' => 0];

                    $DB->insert_record('block_ask4summary_sentence', $obj);

                    block_ask4summary_reply_to_question(
                        get_string('unable', 'block_ask4summary'), $postid);
                }
            }

            // Now get all the messages with subjects that are 'Hi (Helpername)'.
            $subjmessages = $this->get_messages_from_subject($cid, $cname, $ctype, $cfid);

            foreach ($subjmessages as $postid => $message) {
                // Get the N-Grams for each sentence of the message.
                $subjngrams = $this->return_ngram_pos($message, $cname);

                $ngramlist = array();

                $answerngramlist = $this->get_answer_ngram_list($cid);

                // Insert the N-Grams, POS, and sentences into the database tables.
                foreach ($subjngrams as $ngram) {
                    $posid = block_ask4summary_get_table_pos($ngram);

                    $ngramid = block_ask4summary_get_table_ngram($ngram, $posid);

                    $ngramlist[] = $ngramid;

                    $sentenceid = $this->get_sentence($ngram, $cid, $postid);

                    $sngramid = $this->get_sngram($sentenceid, $ngramid);
                }
                // Sort N-Gram list and turn into a string.
                asort($ngramlist);

                $ngramlist = implode('-', $ngramlist);

                mtrace($ngramlist);
                // If the N-Gram list was found in the response table...
                if (in_array($ngramlist, $answerngramlist, true)) {
                    // Do not compute again and use the previous summary.
                    $responseid = array_search($ngramlist, $answerngramlist);

                    $this->existing_answer($cid, $postid, $responseid);

                    continue;

                }

                // If parsing was successful.
                if ($DB->get_records('block_ask4summary_sentence',
                    ['postid' => $postid])) {

                    $answerqueue[] = $postid;

                } else {
                    // Otherwise the parsing failed
                    // or no valid top POS ngrams were calculated.
                    mtrace('Unable to answer question.');

                    $obj = (object)
                        ['courseid' => $cid,
                         'postid' => $postid,
                         'timetaken' => 0,
                         'answered' => 0];

                    $DB->insert_record('block_ask4summary_sentence', $obj);

                    $replypostid = block_ask4summary_reply_to_question(
                        get_string('unable', 'block_ask4summary'), $postid);

                    $robj = (object)
                        ['courseid' => $cid,
                         'postid' => $replypostid,
                         'timetaken' => 0];

                    $DB->insert_record('block_ask4summary_sentence', $robj);
                }

            }

            $answer['posts'] = $answerqueue;

            // If any posts were parsed, queue the answering service.
            if ($answer['posts']) {

                mtrace(get_string('adhocqueue', 'block_ask4summary'));

                $answerservice = new answer_questions();

                $answerservice->set_custom_data($answer);

                \core\task\manager::queue_adhoc_task($answerservice);
            }

        }

        return true;
    }

    /**
     * Inserts the sentence, and its related parameters into the
     * database table 'block_ask4summary_sentence' or returns it if it already exists.
     *
     * @param object $query - the object containing the N-Gram, N, POS, Sentence
     *                        and timetaken
     * @param int $cid - the course ID number
     * @param int $pid - the post ID number
     *
     * @return int - the ID in the database table
     */
    private function get_sentence($query, $cid, $pid) {
        global $DB;

        // See if the sentence exists in the database.
        $sql = "SELECT s.sentenceid
                  FROM {block_ask4summary_sentence} s
                 WHERE s.sentence = :sentence
                       AND s.postid = :postid";

        // If yes, return the ID. Otherwise insert the course id, fourm post id, the sentence
        // and the time taken for the N-Gram generation.
        if ($result = $DB->get_record_sql($sql,
            array('sentence' => $query->sentence, 'postid' => $pid))) {
            return $result->sentenceid;
        } else {
            $sentence = (object)
                ['courseid' => $cid,
                'postid' => $pid,
                'sentence' => $query->sentence,
                'timetaken' => $query->timetaken,
                'answered' => 0];
            return $DB->insert_record('block_ask4summary_sentence', $sentence);
        }
    }

    /**
     * Inserts the sentence id and ngram id into the database table
     * 'block_ask4summary_sngrams'
     *
     * The reason this function does not check to return an ID is because
     * the same sentence could contain the same N-Gram multiple times.
     *
     * @param int $sentenceid - the sentence ID number
     * @param int $ngramid - the ngram ID number
     *
     * @return int - the ID in the database table
     */
    private function get_sngram($sentenceid, $ngramid) {
        global $DB;

        $sentencengram = (object) ['sentenceid' => $sentenceid,
                                        'ngramid' => $ngramid];
        return $DB->insert_record('block_ask4summary_sngram', $sentencengram);
    }

    /**
     * Verifies that a query has the 'Hi XXXX' indicator within it.
     *
     * @param string $query - the string to check
     * @param string $helpername - the Ask4Summary helpername
     *
     * @return boolean
     */
    private function check_query($query, $helpername) {
        $str = strtolower(strip_tags($query));

        if (strpos($str, strtolower('Hi ' . $helpername)) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get messages from all forums in a course, or a specific one.
     *
     * @param int $courseid - the course id
     * @param string $helpername - the helpername of the course
     * @param int $responsetype - 1 for all forum, 2 for specific, 3 for auto
     * @param int $forumid - the forum id number
     *
     * @return array
     */
    private function get_messages($courseid, $helpername, $responsetype, $forumid) {
        global $DB;

        // If the all forum response option is selected, only get the forum
        // postings from the course, given they have not been read before.
        if ($responsetype == 1) {
            $sql = "SELECT m.message, m.id
                      FROM {forum_posts} m
                      JOIN {forum_discussions} d ON d.id = m.discussion
                      JOIN {forum} f ON f.id = d.forum
                     WHERE f.course = :course
                           AND NOT EXISTS (SELECT null
                                           FROM {block_ask4summary_sentence} s
                                           WHERE s.postid = m.id)";

            $results = $DB->get_records_sql($sql, array('course' => $courseid));

        } else {
            // This scenario is the same, but also requires the forum id to also match.
            $sql = "SELECT m.message, m.id
                      FROM {forum_posts} m
                      JOIN {forum_discussions} d ON d.id = m.discussion
                      JOIN {forum} f ON f.id = d.forum
                     WHERE f.course = :course
                           AND f.id = :forum
                           AND NOT EXISTS (SELECT null
                                           FROM {block_ask4summary_sentence} s
                                           WHERE s.postid = m.id)";

            $results = $DB->get_records_sql($sql, array('course' => $courseid, 'forum' => $forumid));
        }

        // Check each message to see if they contain the helpername, then
        // insert to an associative array with the forum post id and the
        // message itself.
        $resultarr = array();
        foreach ($results as $result) {
            if ($this->check_query($result->message, $helpername)) {
                $resultarr[$result->id] = $result->message;
            }
        }

        return $resultarr;

    }

    /**
     * Get messages from discussion subjects that are the helper name.
     *
     * @param int $courseid - the course id
     * @param string $helpername - the Ask4Summary helper name
     * @param int $responsetype - 1 for all, 2 for specific, 3 for auto
     * @param int $forumid - the specific forum id
     *
     * @return array
     */
    private function get_messages_from_subject($courseid, $helpername, $responsetype, $forumid) {
        global $DB;

        $helper = strtolower('Hi ' . $helpername);

        // If the all forum response option is selected, only get the forum
        // postings from the course with the subject title being 'Hi (Helpername)'
        // given they have not been read before.
        if ($responsetype == 1) {
            $sql = "SELECT m.message, m.id
                      FROM {forum_posts} m
                      JOIN {forum_discussions} d ON d.id = m.discussion
                      JOIN {forum} f ON f.id = d.forum
                     WHERE f.course = :course
                           AND d.name = :helpername
                           AND NOT EXISTS (SELECT null
                                          FROM {block_ask4summary_sentence} s
                                          WHERE s.postid = m.id)";

            $results = $DB->get_records_sql($sql, array('course' => $courseid, 'helpername' => $helper));

        } else {
            // This scenario is the same, but also requires the forum id to also match.
            $sql = "SELECT m.message, m.id
                      FROM {forum_posts} m
                      JOIN {forum_discussions} d ON d.id = m.discussion
                      JOIN {forum} f ON f.id = d.forum
                     WHERE f.course = :course
                           AND d.name = :helpername
                           AND f.id = :forumname
                           AND NOT EXISTS (SELECT null
                                          FROM {block_ask4summary_sentence} s
                                          WHERE s.postid = m.id)";

            $results = $DB->get_records_sql($sql, array('course' => $courseid, 'helpername' => $helper, 'forumname' => $forumid));
        }

        // Check each message to see if they contain the helpername, then
        // insert to an associative array with the forum post id and the
        // message itself.
        $resultarr = array();
        foreach ($results as $result) {
            $resultarr[$result->id] = $result->message;
        }

        return $resultarr;

    }

    /**
     * Generate the Valid N-Grams and POS for the message.
     *
     * @param string $message - The entire forum posting
     * @param string $cname - The Ask4Summary helpername
     *
     * @return array
     */
    private function return_ngram_pos($message, $cname) {

        $replace = array("'", ".", ":", ";", "?", "!", '"', ',', "hi ". strtolower($cname) . " ");

        mtrace(get_string('prepmsg', 'block_ask4summary'));

        mtrace($message);

        // Remove HTML tags.
        $cleanedmessage = strip_tags($message);

        mtrace($cleanedmessage);

        // Break the message into sentences.
        $sentences = block_ask4summary_break_content($cleanedmessage);

        $sentarr = array();

        mtrace(get_string('prepsent', 'block_ask4summary'));

        // For each sentence, replace the helpername and puncutation. Then calculate
        // their respective ngrams. Return an array of N-Gram properties, given that
        // any exist for that sentence.
        foreach ($sentences as $sentence) {

            mtrace($sentence);

            $cleanedsentence = str_replace($replace, '', strtolower($sentence));

            mtrace($cleanedsentence);

            $msgnram = block_ask4summary_generate_ngram_pos($cleanedsentence);

            mtrace(get_string('ngramscalc', 'block_ask4summary'));

            if ($msgnram !== false) {

                foreach ($msgnram as $ngram) {
                    $sentarr[] = $ngram;
                }

            } else {
                mtrace(get_string('novalid', 'block_ask4summary'));
            }
        }

        return $sentarr;

    }

    /**
     * Get the N-Gram strings from the response table to see
     * if a question has already been asked.
     *
     * @param int $courseid - The course id number
     *
     * @return array
     */
    private function get_answer_ngram_list($courseid) {

        global $DB;

        $sql = "SELECT r.responseid, r.ngramlist
                  FROM {block_ask4summary_response} r
                 WHERE r.courseid = :courseid";

        return $DB->get_records_sql_menu($sql, ['courseid' => $courseid]);

    }

    /**
     * Called when a question has already been asked. Finds the previously
     * generated response and adds that to the table.
     *
     * @param int $courseid - The course id number
     * @param int $postid - The forum post id number
     * @param int $responseid - The forum post id number of the response
     *
     */
    private function existing_answer($courseid, $postid, $responseid) {

        global $DB;

        mtrace(get_string('existinganswer', 'block_ask4summary'));

        $response = $DB->get_record('block_ask4summary_response',
            ['responseid' => $responseid]);

        $replypostid = block_ask4summary_reply_to_question($response->summary,
            $postid);

        $sql = "SELECT s.sentence
                  FROM {block_ask4summary_sentence} s
                 WHERE s.postid = :postid";

        $questionsentences = $DB->get_fieldset_sql($sql, ['postid' => $postid]);

        $question = '';

        foreach ($questionsentences as $sentence) {
            $question .= ucfirst($sentence) . '. ';
        }

        $newsummary = (object)
            ['courseid' => $courseid,
             'postid' => $postid,
             'question' => $question,
             'replypostid' => $replypostid,
             'summary' => $response->summary,
             'ngramlist' => $response->ngramlist,
             'timetaken' => $response->timetaken];

        $DB->insert_record('block_ask4summary_response', $newsummary);

        $DB->set_field('block_ask4summary_sentence', 'answered', 1,
            ['postid' => $postid]);

        $robj = (object)
            ['courseid' => $courseid,
             'postid' => $replypostid,
             'timetaken' => 0];

        $DB->insert_record('block_ask4summary_sentence', $robj);

    }
}
