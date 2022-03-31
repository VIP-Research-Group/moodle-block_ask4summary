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
 * Upgrade file for Ask4Summary.
 *
 * @package     block_ask4summary
 * @author      Mohammed Saleh
 * @copyright   2022 Athabasca University
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The upgrade function.
 *
 * @param int $oldversion - The current version of the plugin.
 */
function xmldb_block_ask4summary_upgrade($oldversion) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020010600) {
        // Introduced capability - view.

        upgrade_plugin_savepoint(true, 2020010600, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022011700) {
        // Introduced automatic task - scan forums.

        upgrade_plugin_savepoint(true, 2022011700, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022011701) {

        // Defines the block_ask4summary_settings table where each courses'
        // Ask4Summary settings will be saved.
        $table = new xmldb_table('block_ask4summary_settings');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('forumid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'forumid');
        $table->add_field('helpername', XMLDB_TYPE_TEXT, null, null, null, null, null, 'enabled');
        $table->add_field('responsetype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'helpername');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022011701, 'ask4summary');
    }

    if ($oldversion < 2022012000) {

        $table = new xmldb_table('block_ask4summary_table_pos');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('ngram_length', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'id');
        $table->add_field('ngram_pos', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null, 'ngram_length');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012000, 'ask4summary');
    }

    if ($oldversion < 2022012001) {

        $table = new xmldb_table('block_ask4summary_tablengram');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('word', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('pos_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'word');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('pos_ref', XMLDB_KEY_FOREIGN, ['pos_id'], 'block_ask4summary_table_pos', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012001, 'ask4summary');
    }

    if ($oldversion < 2022012100) {

        $table = new xmldb_table('block_ask4summary_sentences');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'courseid');
        $table->add_field('sentence', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'postid');
        $table->add_field('ngram_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'sentence');
        $table->add_field('timetaken', XMLDB_TYPE_NUMBER, '10, 9', null, XMLDB_NOTNULL, null, null, 'ngram_id');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('ngram_ref', XMLDB_KEY_FOREIGN, ['ngram_id'], 'block_ask4summary_tablengram', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012100, 'ask4summary');
    }

    if ($oldversion < 2022012400) {

        // Define field autoforumid to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('autoforumid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'responsetype');

        // Conditionally launch add field autoforumid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012400, 'ask4summary');
    }

    if ($oldversion < 2022012600) {

        // Define field id to be dropped from block_ask4summary_sentences.
        $table = new xmldb_table('block_ask4summary_sentences');
        $field = new xmldb_field('id');
        $field1 = new xmldb_field('ngram_id');
        $key = new xmldb_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $key1 = new xmldb_key('ngram_ref', XMLDB_KEY_FOREIGN, ['ngram_id'], 'block_ask4summary_tablengram', ['id']);

        $dbman->drop_key($table, $key1);

        // Conditionally launch drop field sentenceid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        if ($dbman->field_exists($table, $field1)) {
            $dbman->drop_field($table, $field1);
        }

        $table->add_field('sentenceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('sentenceid'));

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012600, 'ask4summary');
    }

    if ($oldversion < 2022012601) {

        // Define field sentenceid to be added to block_ask4summary_sentences.
        $table = new xmldb_table('block_ask4summary_sentences');
        $table->add_field('sentenceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('sentenceid'));

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012601, 'ask4summary');
    }

    if ($oldversion < 2022012602) {

        // Define fields to be added to block_ask4summary_sngrams.
        $table = new xmldb_table('block_ask4summary_sngrams');
        $table->add_field('sentenceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('ngram_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

        $table->add_key('ngram_ref', XMLDB_KEY_FOREIGN, ['ngram_id'], 'block_ask4summary_tablengram', ['id']);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022012602, 'ask4summary');
    }

    if ($oldversion < 2022012700) {
        // Introduced automatic task - answer questions.

        upgrade_plugin_savepoint(true, 2022012700, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022013100) {
        // Introduced automatic task - scan documents.

        upgrade_plugin_savepoint(true, 2022013100, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022020300) {

        // Define field id to be added to block_ask4summary_cmsentence.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'courseid');
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'cmid');
        $table->add_field('sentence', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'url');
        $table->add_field('timetaken', XMLDB_TYPE_NUMBER, '10, 9', null, XMLDB_NOTNULL, null, null, 'sentence');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020300, 'ask4summary');
    }

    if ($oldversion < 2022020301) {

        // Changing nullability of field cmid on table block_ask4summary_cmsentence to null.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');

        // Launch change of nullability for field cmid.
        $dbman->change_field_notnull($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020301, 'ask4summary');
    }

    if ($oldversion < 2022020302) {

        // Define field id to be added to block_ask4summary_cmngram.
        $table = new xmldb_table('block_ask4summary_cmngram');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('cmsentenceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('ngram_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'cmsentenceid');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('cmngram_ref', XMLDB_KEY_FOREIGN, ['ngram_id'], 'block_ask4summary_tablengram', ['id']);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020302, 'ask4summary');
    }

    if ($oldversion < 2022020400) {

        // Changing nullability of field sentence on table block_ask4summary_cmsentence to null.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $field = new xmldb_field('sentence', XMLDB_TYPE_TEXT, null, null, null, null, null, 'url');

        // Launch change of nullability for field sentence.
        $dbman->change_field_notnull($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020400, 'ask4summary');
    }

    if ($oldversion < 2022020401) {

        // Changing nullability of field timetaken on table block_ask4summary_cmsentence to null.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $field = new xmldb_field('timetaken', XMLDB_TYPE_NUMBER, '10, 9', null, null, null, null, 'sentence');

        // Launch change of nullability for field timetaken.
        $dbman->change_field_notnull($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020401, 'ask4summary');
    }

    if ($oldversion < 2022020402) {

        // Define field courseid to be dropped from block_ask4summary_cmsentence.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $field = new xmldb_field('courseid');

        // Conditionally launch drop field courseid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020402, 'ask4summary');
    }

    if ($oldversion < 2022020403) {

        // Rename field cmid on table block_ask4summary_cmsentence to obid.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Launch rename field cmid.
        $dbman->rename_field($table, $field, 'obid');

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020403, 'ask4summary');
    }

    if ($oldversion < 2022020404) {

        // Define field url to be dropped from block_ask4summary_cmsentence.
        $table = new xmldb_table('block_ask4summary_cmsentence');
        $field = new xmldb_field('url');

        // Conditionally launch drop field url.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020404, 'ask4summary');
    }

    if ($oldversion < 2022020405) {

        // Define field id to be added to block_ask4summary_cmngram.
        $table = new xmldb_table('block_ask4summary_cl_objects');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'cmid');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020405, 'ask4summary');
    }

    if ($oldversion < 2022020700) {

        // Define field depth to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('depth', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'enabled');

        // Conditionally launch add field depth.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020700, 'ask4summary');
    }

    if ($oldversion < 2022020900) {

        // Define table block_ask4summary_table_pos to be renamed to block_ask4summary_tablepos.
        $table = new xmldb_table('block_ask4summary_table_pos');

        // Launch rename table for block_ask4summary_table_pos.
        $dbman->rename_table($table, 'block_ask4summary_tablepos');

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020900, 'ask4summary');
    }

    if ($oldversion < 2022020901) {

        // Define table block_ask4summary_cl_objects to be renamed to block_ask4summary_clobjects.
        $table = new xmldb_table('block_ask4summary_cl_objects');

        $table2 = new xmldb_table('block_ask4summary_cmsentence');

        $table3 = new xmldb_table('block_ask4summary_cmngram');

        // Launch rename table for block_ask4summary_cl_objects.
        $dbman->rename_table($table, 'block_ask4summary_clobjects');

        $dbman->rename_table($table2, 'block_ask4summary_clsentence');

        $dbman->rename_table($table3, 'block_ask4summary_clngram');

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022020901, 'ask4summary');
    }

    if ($oldversion < 2022021600) {

        // Define field depth to be added to block_ask4summary_clobjects.
        $table = new xmldb_table('block_ask4summary_clobjects');
        $field = new xmldb_field('depth', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'url');

        // Conditionally launch add field depth.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021600, 'ask4summary');
    }

    if ($oldversion < 2022021700) {

        // Define field enableurl to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $table->add_field('enableurl', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'autoforumid');
        $table->add_field('enablepdf', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'enableurl');
        $table->add_field('enabledocx', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'enablepdf');
        $table->add_field('enablepptx', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'enabledocx');

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021700, 'ask4summary');
    }

    if ($oldversion < 2022021701) {

        // Define field enableurl to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('enableurl', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'autoforumid');

        // Conditionally launch add field enableurl.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021701, 'ask4summary');
    }

    if ($oldversion < 2022021702) {

        // Define field enablepdf to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('enablepdf', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enableurl');

        // Conditionally launch add field enablepdf.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021702, 'ask4summary');
    }

    if ($oldversion < 2022021703) {

        // Define field enabledocx to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('enabledocx', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enablepdf');

        // Conditionally launch add field enabledocx.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021703, 'ask4summary');
    }

    if ($oldversion < 2022021704) {

        // Define field enablepptx to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('enablepptx', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enabledocx');

        // Conditionally launch add field enablepptx.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021704, 'ask4summary');
    }

    if ($oldversion < 2022021800) {

        // Define field mimetype to be added to block_ask4summary_clobjects.
        $table = new xmldb_table('block_ask4summary_clobjects');
        $field = new xmldb_field('mimetype', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'depth');

        // Conditionally launch add field mimetype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022021800, 'ask4summary');
    }

    if ($oldversion < 2022022300) {

        // Define field parsed to be added to block_ask4summary_clobjects.
        $table = new xmldb_table('block_ask4summary_clobjects');
        $field = new xmldb_field('parsed', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'mimetype');

        // Conditionally launch add field parsed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022022300, 'ask4summary');
    }

    if ($oldversion < 2022022400) {

        // Changing precision of field timetaken on table block_ask4summary_clsentence to (12, 9).
        $table = new xmldb_table('block_ask4summary_clsentence');
        $field = new xmldb_field('timetaken', XMLDB_TYPE_NUMBER, '12, 9', null, null, null, null, 'sentence');

        // Launch change of precision for field timetaken.
        $dbman->change_field_precision($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022022400, 'ask4summary');
    }

    if ($oldversion < 2022030200) {

        // Define field enablepage to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('enablepage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enablepptx');

        // Conditionally launch add field enablepage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022030200, 'ask4summary');
    }

    if ($oldversion < 2022030400) {

        // Define field topdocs to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('topdocs', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '3', 'enablepage');

        // Conditionally launch add field topdocs.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022030400, 'ask4summary');
    }

    if ($oldversion < 2022030401) {

        // Define field topsentences to be added to block_ask4summary_settings.
        $table = new xmldb_table('block_ask4summary_settings');
        $field = new xmldb_field('topsentences', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '8', 'topdocs');

        // Conditionally launch add field topsentences.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022030401, 'ask4summary');
    }

    if ($oldversion < 2022030800) {

        // Changing precision of field timetaken on table block_ask4summary_sentence to (12, 9).
        $table = new xmldb_table('block_ask4summary_sentence');
        $field = new xmldb_field('timetaken', XMLDB_TYPE_NUMBER, '12, 9', null, XMLDB_NOTNULL, null, null, 'sentence');

        // Launch change of precision for field timetaken.
        $dbman->change_field_precision($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022030800, 'ask4summary');
    }

    if ($oldversion < 2022031001) {

        // Define table block_ask4summary_response to be created.
        $table = new xmldb_table('block_ask4summary_response');

        // Adding fields to table block_ask4summary_response.
        $table->add_field('responseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('postid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('ngramlist', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timetaken', XMLDB_TYPE_NUMBER, '12, 9', null, null, null, null);

        // Adding keys to table block_ask4summary_response.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['responseid']);

        // Conditionally launch create table for block_ask4summary_response.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031001, 'ask4summary');
    }

    if ($oldversion < 2022031002) {

        // Changing nullability of field sentence on table block_ask4summary_sentence to null.
        $table = new xmldb_table('block_ask4summary_sentence');
        $field = new xmldb_field('sentence', XMLDB_TYPE_TEXT, null, null, null, null, null, 'postid');

        // Launch change of nullability for field sentence.
        $dbman->change_field_notnull($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031002, 'ask4summary');
    }

    if ($oldversion < 2022031003) {

        // Define field replypostid to be added to block_ask4summary_response.
        $table = new xmldb_table('block_ask4summary_response');
        $field = new xmldb_field('replypostid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'postid');

        // Conditionally launch add field replypostid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031003, 'ask4summary');
    }

    if ($oldversion < 2022031400) {
        // Introduced new accessibility - studentview.

        upgrade_plugin_savepoint(true, 2022031400, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022031401) {

        // Changing nullability of field obid on table block_ask4summary_clsentence to not null.
        $table = new xmldb_table('block_ask4summary_clsentence');
        $field = new xmldb_field('obid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'clsentenceid');

        // Launch change of nullability for field obid.
        $dbman->change_field_notnull($table, $field);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031401, 'ask4summary');
    }

    if ($oldversion < 2022031402) {

        // Define key clobj_ref (foreign) to be added to block_ask4summary_clsentence.
        $table = new xmldb_table('block_ask4summary_clsentence');
        $key = new xmldb_key('clobj_ref', XMLDB_KEY_FOREIGN, ['obid'], 'block_ask4summary_clobjects', ['obid']);

        // Launch add key clobj_ref.
        $dbman->add_key($table, $key);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031402, 'ask4summary');
    }

    if ($oldversion < 2022031403) {

        // Define key clsent_ref (foreign) to be added to block_ask4summary_clngram.
        $table = new xmldb_table('block_ask4summary_clngram');
        $key = new xmldb_key('clsent_ref', XMLDB_KEY_FOREIGN, ['clsentenceid'], 'block_ask4summary_clsentence', ['clsentenceid']);

        // Launch add key clsent_ref.
        $dbman->add_key($table, $key);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031403, 'ask4summary');
    }

    if ($oldversion < 2022031404) {

        // Define key sent_ref (foreign) to be added to block_ask4summary_sngram.
        $table = new xmldb_table('block_ask4summary_sngram');
        $key = new xmldb_key('sent_ref', XMLDB_KEY_FOREIGN, ['sentenceid'], 'block_ask4summary_sentence', ['sentenceid']);

        // Launch add key sent_ref.
        $dbman->add_key($table, $key);

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022031404, 'ask4summary');
    }

    if ($oldversion < 2022031700) {
        // Some misc changes to logistics and question answering.

        upgrade_plugin_savepoint(true, 2022031700, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022031800) {
        // Bugfixes to answer questions and autoforum swapping.

        upgrade_plugin_savepoint(true, 2022031800, 'qtype', 'myqtype');
    }

    if ($oldversion < 2022032300) {

        // Define field answered to be added to block_ask4summary_sentence.
        $table = new xmldb_table('block_ask4summary_sentence');
        $field = new xmldb_field('answered', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'timetaken');

        // Conditionally launch add field answered.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ask4summary savepoint reached.
        upgrade_block_savepoint(true, 2022032300, 'ask4summary');
    }

    return true;

}
