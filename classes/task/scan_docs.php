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
 * The scheduled task: Document Scanning is defined here.
 *
 * This task will take one course module from the course and parse its
 * information for its sentences. It will then calculate the N-Grams and
 * POS from this document.
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
 * Scan course modules for sentence and N-Gram content.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 *
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scan_docs extends \core\task\scheduled_task {

    /**
     * Return the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('scan_docs', 'block_ask4summary');
    }

    /**
     * Execute the course module parsing and N-Gram generation.
     *
     * @return true
     */
    public function execute() {

        global $DB;

        $cmodules = $this->get_enabled_course_modules();

        // Keep seeking through course modules until one is enabled.
        foreach ($cmodules as $cm) {

            switch ($cm->name) {
                // Moodle Page CM...
                case 'page':

                    // Create a temporary record...
                    $init = (object) ['courseid' => $cm->course,
                        'cmid' => $cm->id,
                        'parsed' => 1];

                    $initid = $DB->insert_record('block_ask4summary_clobjects', $init);

                    // If pages are enabled, insert the page. Otherwise remove
                    // the temp record.
                    if ($cm->enablepage) {
                        $this->insert_page($cm);

                    } else {
                        mtrace(get_string('cannotparse', 'block_ask4summary', $cm->id));
                        $DB->delete_records('block_ask4summary_clobjects', ['obid' => $initid]);
                        continue 2;
                    }

                    break;
                // File CM...
                case 'resource':

                    // If the file was already scanned...
                    if ($clobj = $DB->get_record('block_ask4summary_clobjects', ['cmid' => $cm->id], 'obid')) {
                        $DB->set_field('block_ask4summary_clobjects', 'parsed', 1,
                                   ['obid' => $clobj->obid]);

                        $cm->{"obid"} = $clobj->obid;

                    } else {
                        // Otherwise have a failsafe case and just create the record.
                        $mimetype = block_ask4summary_get_mimetype($cm);

                        $obj = (object) ['cmid' => $cm->id,
                                         'courseid' => $cm->course,
                                         'mimetype' => $mimetype,
                                         'parsed' => 1];

                        $obid = $DB->insert_record('block_ask4summary_clobjects', $obj);

                        $cm->{"obid"} = $obid;
                    }

                    $doc = $this->insert_doc($cm);

                    // If document parsing is disabled, turn it to unparsed.
                    if (($doc) === false) {
                        mtrace(get_string('cannotparse', 'block_ask4summary', $cm->id));
                        $DB->set_field('block_ask4summary_clobjects', 'parsed', 0,
                                       ['cmid' => $cm->id]);
                        continue 2;
                    }

                    break;
                // URL CM...
                case 'url':

                    // Create temp record.
                    $init = (object) ['courseid' => $cm->course,
                                      'cmid' => $cm->id,
                                      'parsed' => 1];

                    $initid = $DB->insert_record('block_ask4summary_clobjects', $init);

                    // If URL parsing is enabled, get the URL and the depth.
                    if ($cm->enableurl) {
                        $url = $DB->get_record('url', ['id' => $cm->instance], 'externalurl');

                        $DB->set_field('block_ask4summary_clobjects', 'url',
                                       $url->externalurl, ['obid' => $initid]);
                        $DB->set_field('block_ask4summary_clobjects', 'depth',
                                       $cm->depth, ['obid' => $initid]);

                        $this->insert_url($cm, $url->externalurl);
                        break;

                    } else {
                        // Otherwise remove from objects table.

                        mtrace(get_string('cannotparse', 'block_ask4summary', $cm->id));
                        $DB->delete_records('block_ask4summary_clobjects', ['obid' => $initid]);
                        continue 2;
                    }

                default:
                    mtrace(get_string('unknowncm', 'block_ask4summary'));
            }

            break;

        }

        mtrace(get_string('finished', 'block_ask4summary'));

        return true;
    }


    /**
     * Function to get the sentences from a Moodle page
     *
     *
     * @param object $cm The course module
     *
     * @return array
     */
    private function do_page(&$cm) {
        global $DB;

        $page = $DB->get_record('page', ['id' => $cm->instance], 'content');

        $content = $this->parse_html($page->content, true);

        return block_ask4summary_break_content($content);

    }

    /**
     * Function to extract heading and paragraph elements from an HTML document.
     *
     * Taken from Ted Krahn's LORD plugin.
     *
     * @param string $text The document as a string.
     * @param boolean $asstring Return a single string or an array of strings?
     * @return array|string
     */
    private function parse_html(&$text, $asstring) {

        $dom = new \DOMDocument();
        @$dom->loadHTML($text);

        $str = '';
        $headings = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
        foreach ($headings as $heading) {
            foreach ($dom->getElementsByTagName($heading) as $head) {
                $str .= $head->textContent.'. ';
            }
        }

        $paras = $str == '' ? [] : [$str];
        foreach ($dom->getElementsByTagName('p') as $para) {
            $paras[] = $para->textContent;
            $str .= $para->textContent.' ';
        }

        if ($asstring) {
            return $str;
        } else {
            return array('paragraphs' => $paras);
        }
    }

    /**
     * Function to insert the page sentence content into the applicable tables
     *
     *
     * @param object $cm The course module
     *
     */
    private function insert_page(&$cm) {
        $cmid = $cm->id;
        $cmcourseid = $cm->course;

        mtrace(get_string('coursemoduleid', 'block_ask4summary', $cmid));
        mtrace(get_string('courseid', 'block_ask4summary', $cmcourseid));

        if ($sentences = $this->do_page($cm)) {

            foreach ($sentences as $sentence) {

                // Calculate N-Grams...
                $ngrams = $this->return_ngram_pos($sentence);

                foreach ($ngrams as $ngram) {

                    // Insert into POS.
                    $posid = block_ask4summary_get_table_pos($ngram);

                    // Insert into N-Grams.
                    $ngramid = block_ask4summary_get_table_ngram($ngram, $posid);

                    // Insert into course learning object table.
                    $obid = $this->get_cl_object($cmcourseid,
                                                 null,
                                                 $cmid,
                                                 null);

                    // Insert into sentence table.
                    $sentenceid = $this->get_sentence($ngram, $obid);

                    // Insert into sentence N-Gram table.
                    $clngramid = $this->get_clngram($sentenceid, $ngramid);

                }

            }

        }
    }


    /**
     * Function to do a file type learning activity.
     *
     * Derived from Ted Krahn's LORD plugin.
     *
     * @param stdClass $cm The course module.
     * @return array|false
     */
    private function do_file(&$cm) {
        global $DB;

        $resource = $DB->get_record('resource', array('id' => $cm->instance), '*', MUST_EXIST);
        $intro = array('name' => strip_tags($resource->name),
                       'intro' => strip_tags($resource->intro));

        // Retrieve the file from storage and convert to text.
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return $intro;

        } else {
            $file = reset($files);
            unset($files);
        }

        $file = $fs->get_file($context->id, 'mod_resource', 'content', 0, $file->get_filepath(), $file->get_filename());

        if ($sentences = $this->convert_file($file, $cm)) {
            return $sentences;
        } else {
            return false;
        }

    }

    /**
     * Function to parse a file name from a file object.
     *
     * Taken from Ted Krahn's LORD plugin.
     *
     * @param stored_file $file The file object to parse.
     * @return string
     */
    private function get_filename(&$file) {
        global $CFG;

        $dir1 = substr($file->get_contenthash(), 0, 2);
        $dir2 = substr($file->get_contenthash(), 2, 2);
        $fn = $CFG->dataroot.'/filedir/'.$dir1.'/'.$dir2.'/'.$file->get_contenthash();

        return $fn;
    }


    /**
     * Function to extract text data from another file type.
     *
     * Derived from Ted Krahn's LORD plugin.
     *
     * @param stored_file $file The file object to convert.
     * @param object $cm - The course module
     *
     * @return string|false
     */
    private function convert_file(&$file, &$cm) {

        global $DB;

        if (!$file) {
            return '';
        }

        // Get file name and MIME type.
        $fn = $this->get_filename($file);
        $mimetype = $file->get_mimetype();

        $docx = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $pptx = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

        // If the file is a PDF and it is enabled...
        if ($mimetype == 'application/pdf' && $cm->enablepdf) {
            // Use AbiWord to convert the file into text.

            $DB->set_field('block_ask4summary_clobjects',
                           'mimetype', $mimetype, ['obid' => $cm->obid]);

            $text = shell_exec('abiword --to=txt --to-name=fd://1 "' . $fn . '"');
            if (strlen($text) == 0) {
                return false;
            }
            return block_ask4summary_break_content(strip_tags($text));

        } else if ($mimetype == $docx && $cm->enabledocx) {
            // If the file is a word document and docx parsing is on
            // open the zip archive and get the XML file content.
            $zip = new \ZipArchive();
            if ($zip->open($fn) === true) {
                // Another failsafe.
                $DB->set_field('block_ask4summary_clobjects',
                               'mimetype', $mimetype, ['obid' => $cm->obid]);

                $contents = $zip->getFromName('word/document.xml');
                $zip->close();
                return block_ask4summary_break_content(strip_tags($contents));

            } else {
                return false;
            }

        } else if ($mimetype == $pptx && $cm->enablepptx) {
            // If the file is a powerpoint presentation and pptx parsing is on
            // open the zip file.
            $zip = new \ZipArchive();
            if ($zip->open($fn) === true) {
                // Another failsafe.
                $DB->set_field('block_ask4summary_clobjects',
                               'mimetype', $mimetype, ['obid' => $cm->obid]);

                $slides = array();
                // For every file in the Zip archive...
                for ($index = 0; $index < $zip->numFiles; $index++) {

                    // Get the file name.
                    $entry = $zip->getNameIndex($index);

                    // If the slide matches /ppt/slides/slide(number).xml...
                    if (preg_match('/ppt\/slides\/slide[0-9]+.xml/', $entry)) {

                        $slide = $zip->getFromIndex($index);
                        $substr = '<\/a:t>';
                        $sent = '.';

                        // Give every text element a sentence so it can be
                        // broken down more accurately.
                        $slide = json_encode($slide);
                        $slide = str_replace($substr, $sent . $substr, $slide);
                        $slide = strip_tags($slide);
                        $slide = json_decode($slide);

                        // If the slide is not empty...
                        if ($slide !== "\r") {
                            // Get rid of the return carry.
                            $slide = ltrim($slide);
                            // If the slide is not an added period...
                            if ($slide !== ".") {
                                // Merge previous slides sentence with this.
                                $sentences = block_ask4summary_break_content($slide);
                                $slides = array_merge($slides, $sentences);
                            }
                        }

                    }

                }
                $zip->close();

                return $slides;

            } else {
                // Otherwise could not open.
                return false;
            }

        } else {
            // Otherwise the parsing was disabled.
            return false;
        }
    }

    /**
     * Function to insert the document sentences into applicable tables
     *
     * @param object $cm The course module
     *
     * @return nothing|false - False if the parsing was disabled or failed
     */
    private function insert_doc(&$cm) {
        $cmid = $cm->id;
        $cmcourseid = $cm->course;

        mtrace(get_string('coursemoduleid', 'block_ask4summary', $cmid));
        mtrace(get_string('courseid', 'block_ask4summary', $cmcourseid));

        if ($sentences = $this->do_file($cm)) {

            foreach ($sentences as $sentence) {

                // Calculate N-Grams.
                $ngrams = $this->return_ngram_pos($sentence);

                foreach ($ngrams as $ngram) {

                    // Insert into POS.
                    $posid = block_ask4summary_get_table_pos($ngram);

                    // Insert into N-Grams.
                    $ngramid = block_ask4summary_get_table_ngram($ngram, $posid);

                    // Insert into course learning object table.
                    $obid = $this->get_cl_object($cmcourseid,
                        null, $cmid, null, true);

                    // Insert into sentence table.
                    $sentenceid = $this->get_sentence($ngram, $obid);

                    // Insert into sentence N-Gram table.
                    $clngramid = $this->get_clngram($sentenceid, $ngramid);

                }

            }

        } else {
            return false;
        }

    }

    /**
     * Crawls through a URL, and any other URLs contained within it, getting
     * their paragraph content.
     *
     * Derived from:
     * https://stackoverflow.com/questions/2313107/how-do-i-make-a-simple-crawler-in-php
     *
     * @param string $url - The URL in question
     * @param int $depth - How many URLs should be recursed through
     * @param array $paragraphs - The paragraphs and sentences
     *
     * @return array - Content within
     */
    private function crawl_page($url, $depth = 1, &$paragraphs) {

        // Holds any URL that has been parsed.
        static $seen = array();

        // If the URL has been arrived at or we have parsed enough webpages,
        // break.
        if (isset($seen[$url]) || $depth === 0) {
            return;
        }

        $seen[$url] = true;

        $dom = new \DOMDocument('1.0');
        @$dom->loadHTMLFile($url);
        $paras = $dom->getElementsByTagName('p');
        $bullets = $dom->getElementsByTagName('ul');

        $urlsent = array();

        foreach ($bullets as $bullet) {
            $text = $bullet->textContent;

            // If the bullets have a sentence, and don't have tabbing
            // (this seemed to be a reoccuring problem in many URLs).
            if (!empty($text)) {
                $check = (strpos($text, '.')
                    || strpos($text, '!')
                    || strpos($text, '?'))
                    && !(preg_match('/\t+/', $text, $match));

                if ($check) {

                    // Break the bullet down into sentences and store.
                    $sentences = block_ask4summary_break_content(trim($text));
                    $urlsent[] = $sentences;

                }
            }
        }

        // For every paragraph in the parsed HTML content...
        foreach ($paras as $para) {
            $text = $para->textContent;

            // If the paragraph has content and has at least one sentence...
            if (!empty($text)) {
                $check = strpos($text, '.') || strpos($text, '!')
                         || strpos($text, '?');

                if ($check) {
                    // Then we break the paragraph into sentences.
                    $sentences = block_ask4summary_break_content($text);
                    $urlsent[] = $sentences;
                }
            }
        }

        $paragraphs[$url] = (object) ['paragraphs' => $urlsent,
                                      'depth' => $depth];

        // Check for additional links in the page.
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $element) {

            // Get the HREF and build the URL of the original page.
            $href = $element->getAttribute('href');
            if (0 !== strpos($href, 'http')) {
                $path = '/' . ltrim($href, '/');
                if (extension_loaded('http')) {
                    $href = http_build_url($url, array('path' => $path));
                } else {
                    $parts = parse_url($url);
                    $href = $parts['scheme'] . '://';
                    if (isset($parts['user']) && isset($parts['pass'])) {
                        $href .= $parts['user'] . ':' . $parts['pass'] . '@';
                    }
                    $href .= $parts['host'];
                    if (isset($parts['port'])) {
                        $href .= ':' . $parts['port'];
                    }
                    if (isset($parts['path'])) {
                        $href .= dirname($parts['path'], 1).$path;
                    } else {
                        $href .= $path;
                    }
                }
            }
            $this->crawl_page($href, $depth - 1, $paragraphs);
        }

    }

    /**
     * Function to insert the URL into the applicable tables.
     *
     *
     * @param object $cm - The course module
     * @param string $cmurl - The course module's URL
     *
     */
    private function insert_url(&$cm, $cmurl) {

        $cmid = $cm->id;
        $cmcourseid = $cm->course;
        $depth = $cm->depth;

        mtrace(get_string('coursemoduleid', 'block_ask4summary', $cmid));
        mtrace(get_string('courseid', 'block_ask4summary', $cmcourseid));
        mtrace(get_string('urlname', 'block_ask4summary', $cmurl));

        // Flag for whether this URL was the original (hence a course module).
        $cmflag = true;

        // Where the parsed paragraphs will be stored.
        $urls = array();

        $this->crawl_page($cmurl, $depth, $urls);

        foreach ($urls as $url => $paraobj) {

            foreach ($paraobj->paragraphs as $paragraph) {

                foreach ($paragraph as $sentence) {

                    // Calculate N-Grams.
                    $ngrams = $this->return_ngram_pos($sentence);

                    foreach ($ngrams as $ngram) {

                        // Insert into POS.
                        $posid = block_ask4summary_get_table_pos($ngram);

                        // Insert into N-Grams.
                        $ngramid = block_ask4summary_get_table_ngram($ngram, $posid);

                        // If this URL is a course module...
                        if ($cmflag) {
                            // Insert into course learning object table.
                            $obid = $this->get_cl_object($cmcourseid,
                                                         $url,
                                                         $cmid,
                                                         $paraobj->depth);

                        } else {
                            // Otherwise it was a recursed URL.

                            $obid = $this->get_cl_object($cmcourseid,
                                                         $url,
                                                         null,
                                                         $paraobj->depth);
                        }

                        // Insert into sentence table.
                        $sentenceid = $this->get_sentence($ngram, $obid);

                        // Insert into sentence N-Gram table.
                        $clngramid = $this->get_clngram($sentenceid, $ngramid);

                    }

                }

            }

            // After the 1st iteration, if there are any more iterations, these
            // are recursed URLs. Thus they are not course modules.
            $cmflag = false;

        }
    }

    /**
     * Inserts the learning object and its related properties into the
     * database table 'block_ask4summary_clobjects' or returns it if it already exists.
     *
     * It will first check if the course module has been added. Then, it will
     * check if another URL parsed from another URL was added.
     *
     * @param int $courseid - The course ID number
     * @param string $url - the URL of the webpage
     * @param int $cmid - The course module ID number
     * @param int $depth - How many URLs were crawled into from the webpage
     * @param boolean $isdoc - Is the course module a document
     *
     * @return int - the ID in the database table
     */
    private function get_cl_object($courseid, $url, $cmid, $depth, $isdoc = false) {
        global $DB;

        // To see if the course module exists, and is NULL
        // or has a specific module.
        $sql1 = "SELECT l.obid
                 FROM {block_ask4summary_clobjects} l
                 WHERE l.cmid = :cmid
                       AND (l.depth IS NULL OR l.depth = :depth)";

        // To see if the recursed URL has been added.
        $sql2 = "SELECT l.obid
                 FROM {block_ask4summary_clobjects} l
                 WHERE l.url = :url
                       AND l.url IS NOT NULL
                       AND l.courseid = :courseid
                       AND l.depth = :depth";

        if ($result = $DB->get_record_sql($sql1, array('cmid' => $cmid, 'depth' => $depth))) {
            return $result->obid;

        } else if ($result = $DB->get_record_sql(
                   $sql2, array('url' => $url,
                                'depth' => $depth,
                                'courseid' => $courseid))) {

            return $result->obid;

        } else {
            $obj = (object) ['courseid' => $courseid,
                                  'cmid' => $cmid,
                                  'url' => $url,
                                  'depth' => $depth,
                                  'parsed' => 1];

            if ($isdoc) {
                $cm = $DB->get_record('course_modules', ['id' => $cmid]);

                $obj->{"mimetype"} = block_ask4summary_get_mimetype($cm);
            }

            return $DB->insert_record('block_ask4summary_clobjects', $obj);
        }
    }

    /**
     * Inserts the sentence, and its related parameters into the
     * database table 'block_ask4summary_clsentence' or returns it if it already exists.
     *
     * @param object $query - the object containing the N-Gram, N, POS, Sentence
     *                        and timetaken
     * @param int $obid - The learning object ID
     *
     * @return int - the ID in the database table
     */
    private function get_sentence($query, $obid) {
        global $DB;

        // See if the sentence exists in the database.
        $sql = "SELECT s.clsentenceid
                FROM {block_ask4summary_clsentence} s
                WHERE s.sentence = :sentence
                      AND s.obid = :obid";

        // If yes, return the ID. Otherwise insert the object id, the sentence
        // and the time taken for the N-Gram generation.
        if ($result = $DB->get_record_sql($sql, array('sentence' => $query->sentence,
                                                      'obid' => $obid))) {
            return $result->clsentenceid;
        } else {
            $sentence = (object) ['obid' => $obid,
                                  'sentence' => $query->sentence,
                                  'timetaken' => $query->timetaken];
            return $DB->insert_record('block_ask4summary_clsentence', $sentence);
        }
    }

    /**
     * Inserts the course module sentence id and ngram id
     * into the database table 'block_ask4summary_clngram'.
     *
     * The reason this function does not check to return an ID is because
     * the same sentence could contain the same N-Gram multiple times.
     *
     * @param int $clsentenceid - the course learning sentence ID number
     * @param int $ngramid - the ngram ID number
     *
     * @return int - the ID in the database table
     */
    private function get_clngram($clsentenceid, $ngramid) {
        global $DB;

        $sentencengram = (object) ['clsentenceid' => $clsentenceid,
                                        'ngramid' => $ngramid];
        return $DB->insert_record('block_ask4summary_clngram', $sentencengram);

    }

    /**
     * Gets every course module, of the correct type which has A4S enabled.
     *
     * @return object - the IDs in the database table
     */
    private function get_enabled_course_modules() {
        global $DB;

        $sql = "SELECT c.id, m.name, c.instance, c.course, s.depth, s.enableurl,
                       s.enablepdf, s.enabledocx, s.enablepptx, s.enablepage
                FROM {course_modules} c
                JOIN {block_ask4summary_settings} s ON s.courseid = c.course
                JOIN {modules} m ON m.id = c.module
                WHERE m.name IN ('page', 'resource', 'url')
                      AND NOT EXISTS (SELECT null
                                      FROM {block_ask4summary_clobjects} o
                                      WHERE (o.cmid = c.id
                                            AND o.depth IS NULL
                                            AND o.parsed = 1)
                                            OR (o.cmid = c.id
                                            AND o.depth = s.depth))";

        return $DB->get_records_sql($sql);
    }

    /**
     * Gets the top valid N-Grams and POS for a particular sentence.
     *
     * @param string $sentence - the sentence
     *
     * @return array - the calculated N-Grams, POS, N, the sentence, and time taken
     */
    private function return_ngram_pos($sentence) {

        // Get rid of unneccesary characters.
        $replace = array("'", ".", ":", ";", "?", "!",
            "(", ")", ",", "‘", "’", '"');

        $sentarr = array();

        $cleanedsentence = str_replace($replace, '', strtolower($sentence));

        $msgngram = block_ask4summary_generate_ngram_pos($cleanedsentence);

        mtrace(get_string('ngramscalc', 'block_ask4summary'));

        // If N-Grams exist.
        if ($msgngram !== false) {
            foreach ($msgngram as $ngram) {

                $sentarr[] = $ngram;
            }

        } else {
            mtrace(get_string('novalid', 'block_ask4summary'));
        }

        return $sentarr;
    }

}
