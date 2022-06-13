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
 * Privacy Subsystem implementation for block_ask4summary.
 *
 * Derived from Ted Krahn's LORD Plugin.
 *
 * @package    block_ask4summary
 * @author     Mohammed Saleh
 * @copyright  2022 Athabasca University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ask4summary\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy Subsystem implementation for block_ask4summary.
 *
 * @author     Mohammed Saleh
 * @copyright  2022 Athabasca University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns information about how block_ask4summary stores its data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'block_ask4summary_settings',
            [
                'courseid' => 'privacy:metadata:block_ask4summary:courseid',
                'forumid' => 'privacy:metadata:block_ask4summary:forumid',
                'autoforumid'   => 'privacy:metadata:block_ask4summary:autoforumid',
                'enabled' => 'privacy:metadata:block_ask4summary:enabled',
                'depth' => 'privacy:metadata:block_ask4summary:depth',
                'helpername' => 'privacy:metadata:block_ask4summary:helpername',
                'responsetype' => 'privacy:metadata:block_ask4summary:responsetype',
                'enableurl' => 'privacy:metadata:block_ask4summary:enableurl',
                'enablepdf' => 'privacy:metadata:block_ask4summary:enablepdf',
                'enabledocx' => 'privacy:metadata:block_ask4summary:enabledocx',
                'enablepptx' => 'privacy:metadata:block_ask4summary:enablepptx',
                'enablepage' => 'privacy:metadata:block_ask4summary:enablepage',
                'topdocs' => 'privacy:metadata:block_ask4summary:topdocs',
                'topsentences' => 'privacy:metadata:block_ask4summary:topsentences',
            ],
            'privacy:metadata:block_ask4summary_settings'
        );

        $collection->add_database_table(
            'block_ask4summary_sentence',
            [
                'courseid'  => 'privacy:metadata:block_ask4summary:courseid',
                'postid'    => 'privacy:metadata:block_ask4summary:postid',
                'sentence'  => 'privacy:metadata:block_ask4summary:sentence',
                'timetaken' => 'privacy:metadata:block_ask4summary:timetaken',
            ],
            'privacy:metadata:block_ask4summary_sentence'
        );

        $collection->add_database_table(
            'block_ask4summary_tablengram',
            [
                'word' => 'privacy:metadata:block_ask4summary:word',
            ],
            'privacy:metadata:block_ask4summary_tablengram'
        );

        $collection->add_database_table(
            'block_ask4summary_response',
            [
                'courseid'     => 'privacy:metadata:block_ask4summary:courseid',
                'postid'       => 'privacy:metadata:block_ask4summary:postid',
                'replypostid'     => 'privacy:metadata:block_ask4summary:replypostid',
                'question'    => 'privacy:metadata:block_ask4summary:question',
                'timetaken'    => 'privacy:metadata:block_ask4summary:timetaken',
            ],
            'privacy:metadata:block_ask4summary_response'
        );

        $collection->add_database_table(
            'block_ask4summary_clobjects',
            [
                'courseid'   => 'privacy:metadata:block_ask4summary:courseid',
                'cmid'     => 'privacy:metadata:block_ask4summary:cmid',
                'url'   => 'privacy:metadata:block_ask4summary:url',
                'depth'  => 'privacy:metadata:block_ask4summary:depth',
            ],
            'privacy:metadata:block_ask4summary_clobjects'
        );

        $collection->add_database_table(
            'block_ask4summary_clsentence',
            [
                'sentence' => 'privacy:metadata:block_ask4summary:sentence',
            ],
            'privacy:metadata:block_ask4summary_clsentence'
        );

        $collection->add_external_location_link('N-Gram POS Service', [
                'message' => 'privacy:metadata:block_ask4summary:sentence',
            ], 'privacy:metadata:ngramposservice');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new \core_privacy\local\request\contextlist();

        // The block_ask4summary data is associated at the course context level, so retrieve the user's context id.
        $sql = "SELECT id
                  FROM {context}
                 WHERE contextlevel = :context
                   AND instanceid = :userid
              GROUP BY id";

        $params = [
            'context' => CONTEXT_COURSE,
            'userid'  => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        // No userid in any table.
        return;
    }

    /**
     * Export all user data for the specified user using the Course context level.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // No userid in any table.
        return;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // No userid in any table.
        return;
    }

    /**
     * Delete all user data for the specified user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // No userid in any table.
        return;
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // No userid in any table.
        return;
    }
}


