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
 * Privacy Subsystem implementation for Quiz Wizard.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider.
 *
 * The plugin stores draft quiz data associated with a user and course.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about stored user data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_quizwizard_session',
            [
                'course'    => 'privacy:metadata:session:course',
                'userid'    => 'privacy:metadata:session:userid',
                'title'     => 'privacy:metadata:session:title',
                'intro'     => 'privacy:metadata:session:intro',
                'status'    => 'privacy:metadata:session:status',
                'timecreated'   => 'privacy:metadata:session:timecreated',
                'timemodified'  => 'privacy:metadata:session:timemodified',
            ],
            'privacy:metadata:local_quizwizard_session'
        );

        $collection->add_database_table(
            'local_quizwizard_session_question',
            [
                'sessionid'     => 'privacy:metadata:question:sessionid',
                'questiontext'  => 'privacy:metadata:question:questiontext',
                'points'        => 'privacy:metadata:question:points',
                'difficulty'    => 'privacy:metadata:question:difficulty',
                'sortorder'     => 'privacy:metadata:question:sortorder',
            ],
            'privacy:metadata:local_quizwizard_session_question'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {local_quizwizard_session} s
                  JOIN {context} ctx ON ctx.instanceid = s.course AND ctx.contextlevel = :courselevel
                 WHERE s.userid = :userid";
        $contextlist->add_from_sql($sql, ['courselevel' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Export personal data for the user in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }
            $sessions = $DB->get_records('local_quizwizard_session', ['course' => $context->instanceid, 'userid' => $userid]);
            if (!$sessions) {
                continue;
            }
            $data = [];
            foreach ($sessions as $session) {
                $questions = $DB->get_records('local_quizwizard_session_question', ['sessionid' => $session->id], 'sortorder');
                foreach ($questions as $q) {
                    $q->answers = array_values($DB->get_records('local_quizwizard_session_answer', ['questionid' => $q->id], 'sortorder'));
                }
                $session->questions = array_values($questions);
                $data[] = $session;
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_quizwizard')], (object)['sessions' => $data]);
        }
    }

    /**
     * Delete personal data for all users in the given contexts.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $sessions = $DB->get_records('local_quizwizard_session', ['course' => $context->instanceid]);
        foreach ($sessions as $session) {
            self::delete_session($session->id);
        }
    }

    /**
     * Delete personal data for a user in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }
            $sessions = $DB->get_records('local_quizwizard_session', ['course' => $context->instanceid, 'userid' => $userid]);
            foreach ($sessions as $session) {
                self::delete_session($session->id);
            }
        }
    }

    /**
     * Delete a session and its questions/answers.
     *
     * @param int $sessionid
     */
    protected static function delete_session(int $sessionid) {
        global $DB;
        $questions = $DB->get_records('local_quizwizard_session_question', ['sessionid' => $sessionid]);
        foreach ($questions as $q) {
            $DB->delete_records('local_quizwizard_session_answer', ['questionid' => $q->id]);
        }
        $DB->delete_records('local_quizwizard_session_question', ['sessionid' => $sessionid]);
        $DB->delete_records('local_quizwizard_session', ['id' => $sessionid]);
    }
}
