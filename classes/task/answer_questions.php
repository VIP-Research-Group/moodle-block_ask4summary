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
 * The adhoc task which answers a parsed forum post.
 *
 * This adhoc task manages question answering of Ask4Summary questions.
 * It is triggered whenever a course's questions are complete, and it
 * handles all of the questions in the same course.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 *
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ask4summary\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ask4summary/locallib.php');

/**
 * Answer questions from a course that have been parsed by scan_forums.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 *
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answer_questions extends \core\task\adhoc_task {

    /**
     * Execute the task. Called when scan_forums queues the task.
     */
    public function execute() {

        global $DB, $CFG;

        // Gets the questions, the number of documents for every
        // response, and the number of sentences to be returned.
        $answerqueue = $this->get_custom_data();

        $topdoc = $answerqueue->topdoc;
        $topsent = $answerqueue->topsent;
        $courseid = $answerqueue->courseid;

        // For every parsed question.
        foreach ($answerqueue->posts as $postid) {

            $msgstart = microtime(true);

            // Get the frequency of N-Grams in the question
            // This is an array of ngramid => # in sngram table.
            $ngramcount = $this->get_ngram_count($postid);

            // Build a string of the N-grams so it can be stored
            // for duplicate question answering.
            $ngramlist = '';
            foreach ($ngramcount as $ngram => $count) {

                for ($i = 0; $i < $count; $i++) {
                    $ngramlist .= $ngram . '-';
                }

            }

            // Remove the final '-'.
            $ngramlist = substr($ngramlist, 0, -1);

            // If there are course modules to get content from...
            if ($DB->get_records('block_ask4summary_clobjects',
                ['courseid' => $courseid, 'parsed' => 1])) {

                // Get the top document object ids based on cosine similarity.
                $doccos = $this->best_docs($ngramcount, $topdoc, $courseid);

                // Get the top sentence ids based on cosine similarity.
                $sentcos = $this->best_sentences($ngramcount, $doccos, $topsent);

                // Get the sentences of the top ids.
                $summary = $this->get_sentences($sentcos);

                // If there were no sentences able to be generated...
                if (empty($summary)) {

                    mtrace('Could not answer the question.');

                    $summary = get_string('unable', 'block_ask4summary');

                    $msgend = microtime(true);
                    $msgtime = $msgend - $msgstart;

                    // Reply to the post and add to the sentence table
                    // so it is not considered later on.
                    $replypostid =
                        block_ask4summary_reply_to_question($summary, $postid);

                    $robj = (object)
                        ['courseid' => $courseid,
                         'postid' => $replypostid,
                         'timetaken' => 0];

                    $DB->insert_record('block_ask4summary_sentence', $robj);

                    continue;

                }

            } else {
                // Otherwise there were no available course objects.
                // Reply to the post and insert that into the sentence table.

                mtrace('No available course objects!');

                $summary = get_string('unable', 'block_ask4summary');

                $msgend = microtime(true);
                $msgtime = $msgend - $msgstart;

                $replypostid =
                    block_ask4summary_reply_to_question($summary, $postid);

                $robj = (object)
                    ['courseid' => $courseid,
                     'postid' => $replypostid,
                     'timetaken' => 0];

                $DB->insert_record('block_ask4summary_sentence', $robj);

                continue;

            }

            mtrace('');

            mtrace($summary);

            $DB->set_field('block_ask4summary_sentence', 'answered', 1,
                ['postid' => $postid]);

            // Now get the actual sentence content.
            $sql = "SELECT s.sentence
                      FROM {block_ask4summary_sentence} s
                     WHERE s.postid = :postid";

            $questionsentences = $DB->get_fieldset_sql($sql, ['postid' => $postid]);

            // Build the actual question...
            $question = '';

            foreach ($questionsentences as $sentence) {
                $question .= ucfirst($sentence) . '. ';
            }

            $msgend = microtime(true);
            $msgtime = $msgend - $msgstart;

            // Respond to the forum post and insert the response into
            // the response table and insert the reply post into
            // the sentence table.
            $replypostid =
                block_ask4summary_reply_to_question($summary, $postid);

            $obj = (object)
                ['postid' => $postid,
                 'courseid' => $courseid,
                 'replypostid' => $replypostid,
                 'question' => $question,
                 'summary' => $summary,
                 'ngramlist' => $ngramlist,
                 'timetaken' => $msgtime];

            $DB->insert_record('block_ask4summary_response', $obj);

            $robj = (object)
                ['courseid' => $courseid,
                 'postid' => $replypostid,
                 'timetaken' => 0];

            $DB->insert_record('block_ask4summary_sentence', $robj);

        }

    }

    /**
     * Get the frequency of N-Grams for a specific parsed post.
     *
     * @param int $postid - the post id
     * @return array
     */
    private function get_ngram_count($postid) {

        global $DB;

        $sql = "SELECT n.ngramid, count(n.ngramid) AS count
                  FROM {block_ask4summary_sngram} n
                  JOIN {block_ask4summary_sentence} s ON n.sentenceid = s.sentenceid
                 WHERE s.postid = :postid
              GROUP BY n.ngramid
              ORDER BY n.ngramid ASC";

        $sngrams = $DB->get_records_sql_menu($sql, ['postid' => $postid]);

        return $sngrams;

    }

    /**
     * Get the cosine similarity of the top course learning object ids
     *
     * @param array $ngramcount - the output of get_ngram_count()
     * @param int $numdocs - how many documents should be returned
     * @param int $courseid - the courseid
     *
     * @return array
     */
    private function best_docs($ngramcount, $numdocs, $courseid) {

        global $DB;

        // Get the course learning objects of this course.
        $clobjects = $DB->get_records('block_ask4summary_clobjects',
            ['courseid' => $courseid]);

        $clcosine = array();

        foreach ($clobjects as $clobject) {

            $obid = $clobject->obid;

            $obngramcount = array();

            // Get the frequency of every N-Gram the original question had
            // for each course learning object.
            foreach ($ngramcount as $ngramid => $count) {

                $sql = "SELECT count(n.ngramid) AS count
                          FROM {block_ask4summary_clngram} n
                          JOIN {block_ask4summary_clsentence} s ON n.clsentenceid = s.clsentenceid
                          JOIN {block_ask4summary_clobjects} o ON s.obid = o.obid
                         WHERE o.obid = :obid
                               AND n.ngramid = :ngramid";

                $clcount = $DB->get_field_sql($sql,
                    ['obid' => $obid, 'ngramid' => $ngramid]);

                $obngramcount += array($ngramid => $clcount);

            }

            // Get the cosine similarity of the question to the object.
            $obcossim = $this->cosine_similarity($ngramcount, $obngramcount);

            $clcosine[$obid] = $obcossim;

        }

        // Sort from highest to lowest.
        arsort($clcosine);

        // Only return the top $numdocs documents.
        return array_slice($clcosine, 0, $numdocs, true);

    }

    /**
     * Get the cosine similarity of the top course
     * learning object sentence ids
     *
     * @param array $ngramcount - the output of get_ngram_count()
     * @param array $doccos - the output of best_docs()
     * @param int $numsentence - how many sentences should be returned
     *
     * @return array
     */
    private function best_sentences($ngramcount, $doccos, $numsentence) {

        global $DB;

        $clcosine = array();

        // For every top learning object...
        foreach ($doccos as $obid => $cossim) {

            // Get the sentences of that object.
            $clsentences = $DB->get_records('block_ask4summary_clsentence',
                ['obid' => $obid]);

            // For every top learning object sentence...
            foreach ($clsentences as $clsentence) {

                $clsentenceid = $clsentence->clsentenceid;

                $clsentngramcount = array();

                // Get the frequency of the N-grams the original question
                // had for each sentence.
                foreach ($ngramcount as $ngramid => $count) {

                    $sql = "SELECT count(n.ngramid) AS count
                              FROM {block_ask4summary_clngram} n
                              JOIN {block_ask4summary_clsentence} s ON n.clsentenceid = s.clsentenceid
                             WHERE s.clsentenceid = :clsentenceid
                                   AND n.ngramid = :ngramid";

                    $clcount = $DB->get_field_sql($sql,
                        ['clsentenceid' => $clsentenceid, 'ngramid' => $ngramid]);

                    $clsentngramcount += array($ngramid => $clcount);

                }

                // Get the cosine similarity of each sentence to the original
                // question.
                $clsentcossim = $this->cosine_similarity($ngramcount,
                    $clsentngramcount);

                $clcosine[$clsentenceid] = $clsentcossim;

            }

        }

        // Sort highest to lowest cosine similarity.
        arsort($clcosine);

        // Only return the top $numsentence sentence ids.
        return array_slice($clcosine, 0, $numsentence, true);

    }

    /**
     * Gets the sentences of the top cosine similarity sentences
     *
     * @param array $sentcos - the output of best_sentences()
     *
     * @return array
     */
    private function get_sentences($sentcos) {

        global $DB;

        $summary = '';

        foreach ($sentcos as $clsentid => $cossim) {

            $sentence = $DB->get_field('block_ask4summary_clsentence',
                'sentence', ['clsentenceid' => $clsentid]);

            // If there was actually some similarity...
            if ($cossim !== 0) {
                $summary .= ucfirst($sentence) . ".\n";
            }

        }

        return $summary;
    }

    /**
     * Calculates the Cosine Similarity between two vectors
     *
     * @param array $v1 - The first vector
     * @param array $v2 - The second vector
     *
     * @return float - The cosine similarity which will be between 0-1
     */
    private function cosine_similarity($v1, $v2) {
        $sumxx = $sumyy = $sumxy = 0;

        foreach ($v1 as $obid => $count) {
            $x = $v1[$obid];
            $y = $v2[$obid];

            $sumxx += $x * $x;
            $sumyy += $y * $y;
            $sumxy += $x * $y;

        }

        if ($sumxx !== 0 && $sumyy !== 0) {
            return $sumxy / (sqrt($sumxx) * sqrt($sumyy));
        } else {
            return 0;
        }

    }

}
