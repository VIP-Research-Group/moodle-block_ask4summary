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
 * Block ask4summary is defined here.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ask4summary/locallib.php');

/**
 * The block itself.
 *
 * @package block_ask4summary
 * @author Mohammed Saleh
 * @copyright Athabasca University 2022
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ask4summary extends block_base {

    /**
     * Initialize the block by settings the title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ask4summary');
    }

    /**
     * Ensure global settings are available.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Allow block only in courses, not in main page, etc.
     *
     * @return boolean
     */
    public function applicable_formats() {
        return array('all' => false, 'course-view' => true);
    }

    /**
     * Make sure there can only be one block per course.
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Delete the settings from the database when the block is removed.
     *
     * @return boolean
     */
    public function instance_delete() {
        global $COURSE, $DB;

        $DB->delete_records('block_ask4summary_settings',
            ['courseid' => $COURSE->id]);

        return true;

    }

    /**
     * Create block content. The block itself will handle when
     * settings are changed, and if there are new resource course
     * modules whose mimetypes need to be added to the course learning
     * object database.
     *
     * @return stdClass
     */
    public function get_content() {
        global $COURSE, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);

        $this->content = new stdClass();

        $this->content->text = '';

        if (has_capability('block/ask4summary:view', $context)) {

            // Check to see if the block configuration settings were input.
            $check = !empty($this->config->text)
                && !empty($this->config->response)
                && isset($this->config->enablea4s)
                && isset($this->config->depth)
                && isset($this->config->enablepage)
                && isset($this->config->enableurl)
                && isset($this->config->enablepdf)
                && isset($this->config->enabledocx)
                && isset($this->config->enablepptx)
                && isset($this->config->topdoc)
                && isset($this->config->topsent);

            if ($check) {

                $helpername = $this->config->text;
                $responsetype = $this->config->response;
                $enable = $this->config->enablea4s;
                $depth = $this->config->depth;
                $url = $this->config->enableurl;
                $pdf = $this->config->enablepdf;
                $docx = $this->config->enabledocx;
                $pptx = $this->config->enablepptx;
                $page = $this->config->enablepage;
                $topdoc = $this->config->topdoc;
                $topsent = $this->config->topsent;

                // If the settings have been stored into the database previously.
                if ($cursettings = $DB->get_record('block_ask4summary_settings',
                                                    ['courseid' => $COURSE->id])) {

                    $forumid = $cursettings->forumid;
                    $autoforumid = $cursettings->autoforumid;

                    // If the response type was changed...
                    if ($responsetype !== $cursettings->responsetype) {

                        // If we have set it to all forums or a specific forum
                        // (1 or 2 respectively) we need to check if the autoforum
                        // exists. If it does it needs become invisible.

                        // If we have set it to 3, we need to check if the autoforum exists.
                        // If it doesn't we need to create it, and then make it visible.
                        switch ($responsetype) {
                            case 1:
                                $forumid = null;
                                if ($cursettings->autoforumid !== null) {
                                    block_ask4summary_display_autoforum($COURSE->id,
                                                                        $cursettings->autoforumid,
                                                                        0);
                                }
                                break;
                            case 2:
                                $forumid = $this->config->forumselect;
                                if ($cursettings->autoforumid !== null) {
                                    block_ask4summary_display_autoforum($COURSE->id,
                                                                        $cursettings->autoforumid,
                                                                        0);
                                }
                                break;
                            case 3:
                                if ($autoforumid === null) {
                                    $autoforumname = $this->config->autotext;
                                    $autoobj = block_ask4summary_create_forum($COURSE->id, $autoforumname);
                                    $autoforumid = $autoobj->id;
                                } else {
                                    block_ask4summary_display_autoforum($COURSE->id, $autoforumid, 1);
                                }

                                $forumid = $autoforumid;

                                break;

                        }

                    } else {
                        // Otherwise there was no change in the response type.
                        if ($responsetype == 2) {
                            // Now verify whether the existing forum changed.
                            $forumid = $this->config->forumselect;

                        } else if ($responsetype == 3) {
                            // Now verify whether the autoforum name was changed.
                            $autoforumname = $this->config->autotext;
                            block_ask4summary_update_autoforum($COURSE->id,
                                                               $autoforumname,
                                                               $autoforumid);
                            // A failsafe, was running into problems.
                            $forumid = $autoforumid;
                        }
                    }

                    $cursettings->helpername = $helpername;
                    $cursettings->responsetype = $responsetype;
                    $cursettings->enabled = $enable;
                    $cursettings->forumid = $forumid;
                    $cursettings->autoforumid = $autoforumid;
                    $cursettings->depth = $depth;
                    $cursettings->enableurl = $url;
                    $cursettings->enablepdf = $pdf;
                    $cursettings->enabledocx = $docx;
                    $cursettings->enablepptx = $pptx;
                    $cursettings->enablepage = $page;
                    $cursettings->topdocs = $topdoc;
                    $cursettings->topsentences = $topsent;

                    // Update the record with the new configuration properties.
                    $DB->update_record('block_ask4summary_settings', $cursettings);

                } else {
                    // Otherwise plugin settings were just initialized.
                    $autoforumid = null;
                    $forumid = null;

                    switch ($responsetype) {
                        case 1:
                            break;
                        case 2:
                            $forumid = $this->config->forumselect;
                            break;
                        case 3:
                            if (!empty($this->config->autotext)) {
                                $autoforumname = $this->config->autotext;
                                $autoforumid = block_ask4summary_create_forum($COURSE->id, $autoforumname);
                            }
                            $forumid = $autoforumid;
                            break;
                    }

                    $settings = (object) [
                        'enabled' => $enable,
                        'helpername' => $helpername,
                        'responsetype' => $responsetype,
                        'courseid' => $COURSE->id,
                        'forumid' => $forumid,
                        'autoforumid' => $autoforumid,
                        'depth' => $depth,
                        'enableurl' => $url,
                        'enablepdf' => $pdf,
                        'enabledocx' => $docx,
                        'enablepptx' => $pptx,
                        'enablepage' => $page,
                        'topdocs' => $topdoc,
                        'topsentences' => $topsent];

                    $DB->insert_record('block_ask4summary_settings', $settings);
                }

            } else {
                // Otherwise, assume pure defaults.
                $this->content->text .= get_string('namenote', 'block_ask4summary');
                $this->content->text .= html_writer::empty_tag('br');
                $settings = (object) [
                    'courseid' => $COURSE->id,
                    'enabled' => 0,
                    'responsetype' => 1];

                if (!($DB->get_record('block_ask4summary_settings', ['courseid' => $COURSE->id]))) {
                    $DB->insert_record('block_ask4summary_settings', $settings);
                }

            }
            // This is for seeing new file course modules that have not been scanned.
            $unscanned = block_ask4summary_get_unscanned_cms($COURSE->id);

            // Link to Ask4Summary logistics.
            $this->content->text .= html_writer::tag('a', get_string("loganchor", "block_ask4summary"),
                array('href' => new moodle_url('/blocks/ask4summary/logistics.php', array(
                    'id' => $COURSE->id
                ))));
            $this->content->text .= html_writer::empty_tag('br');

            // Link to the documentation.
            $this->content->text .= html_writer::tag('a', get_string("docsanchor", "block_ask4summary"),
                array('href' => new moodle_url('/blocks/ask4summary/documentation.php', array(
                    'id' => $COURSE->id
                ))));
            $this->content->text .= html_writer::empty_tag('br');

            // If there was unscanned file course modules, return how many were scanned.
            if (count($unscanned) !== 0) {

                $insert = block_ask4summary_insert_cms($unscanned);

                $this->content->text .= html_writer::tag('p', $insert);

            }

        }

        if (has_capability('block/ask4summary:studentview', $context)) {

            // Link to Ask4Summary Student Guide.
            $this->content->text .= html_writer::tag('a',
                get_string('guideanchor', 'block_ask4summary'),
                array('href' => new moodle_url('/blocks/ask4summary/guide.php',
                array('id' => $COURSE->id))));

            $this->content->text .= html_writer::empty_tag('br');
            $this->content->text .= html_writer::empty_tag('br');

            if ($on = $DB->get_field('block_ask4summary_settings', 'enabled',
                ['courseid' => $COURSE->id])) {

                $this->content->text .= html_writer::tag('p',
                    get_string('currenton', 'block_ask4summary',
                    get_string('enabled', 'block_ask4summary')));

                if ($hn = $DB->get_field('block_ask4summary_settings',
                    'helpername', ['courseid' => $COURSE->id])) {

                    $this->content->text .= html_writer::tag('p',
                        get_string('blocka4shelper', 'block_ask4summary',
                        $hn));

                } else {

                    $this->content->text .= html_writer::tag('p',
                        get_string('noname', 'block_ask4summary'));

                }

                if ($rt = $DB->get_field('block_ask4summary_settings',
                    'responsetype', ['courseid' => $COURSE->id])) {

                    switch ($rt) {
                        case 1:
                            $this->content->text .= html_writer::tag('p',
                                get_string('currentresponse', 'block_ask4summary',
                                get_string('allforums', 'block_ask4summary')));

                            break;
                        case 2:
                            $this->content->text .= html_writer::tag('p',
                                get_string('currentresponse', 'block_ask4summary',
                                get_string('existingforum', 'block_ask4summary')));

                            if ($fid = $DB->get_field('block_ask4summary_settings',
                                'forumid', ['courseid' => $COURSE->id])) {

                                $fname = $DB->get_field('forum',
                                    'name', ['id' => $fid]);

                                $this->content->text .= html_writer::tag('p',
                                    get_string('selectedforum',
                                    'block_ask4summary', $fname));

                            }

                            break;

                        case 3:
                            $this->content->text .= html_writer::tag('p',
                                get_string('currentresponse', 'block_ask4summary',
                                get_string('autoforum', 'block_ask4summary')));

                            if ($fid = $DB->get_field('block_ask4summary_settings',
                                'autoforumid', ['courseid' => $COURSE->id])) {

                                $fname = $DB->get_field('forum',
                                    'name', ['id' => $fid]);

                                $this->content->text .= html_writer::tag('p',
                                    get_string('selectedforum',
                                    'block_ask4summary', $fname));

                            }

                            break;
                    }

                }

            } else {
                $this->content->text .= html_writer::tag('p',
                    get_string('currenton', 'block_ask4summary',
                    get_string('disabled', 'block_ask4summary')));

            }
            $this->content->footer = "";
            return $this->content;

        }

    }
}

