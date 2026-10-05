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
 * Publishes wizard sessions as standard Moodle quizzes.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');

/**
 * Builds a standard mod_quiz activity from a wizard session.
 */
class quiz_builder {

    /** @var int Review option: during attempt. */
    const REVIEW_DURING = 0x10000;
    /** @var int Review option: immediately after attempt. */
    const REVIEW_IMMEDIATELY_AFTER = 0x01000;
    /** @var int Review option: later while open. */
    const REVIEW_LATER_WHILE_OPEN = 0x00100;
    /** @var int Review option: after close. */
    const REVIEW_AFTER_CLOSE = 0x00010;

    /**
     * Publish the session.
     *
     * @param int $sessionid
     * @return int Moodle cmid.
     */
    public static function publish(int $sessionid): int {
        global $DB;

        $session = wizard::get_session($sessionid);
        $course = get_course($session->course);
        $questions = wizard::get_questions($sessionid);

        if (empty($questions)) {
            throw new \moodle_exception('err_noquestions', 'local_quizwizard');
        }

        // Create question bank entries and collect question ids.
        $questionids = [];
        foreach ($questions as $q) {
            $questionids[] = question_builder::build($q, $course->id);
        }

        // Build mod_quiz module info.
        $module = $DB->get_record('modules', ['name' => 'quiz'], '*', MUST_EXIST);

        $quiz = new \stdClass();
        $quiz->course = $course->id;
        $quiz->name = $session->title;
        $quiz->intro = $session->intro;
        $quiz->introformat = $session->introformat;
        $quiz->section = $session->section;
        $quiz->module = $module->id;
        $quiz->modulename = 'quiz';
        $quiz->instance = 0;
        $quiz->add = 'mod';
        $quiz->update = 0;
        $quiz->return = 0;
        $quiz->cmidnumber = '';
        $quiz->groupmode = 0;
        $quiz->groupingid = 0;
        $quiz->visible = 1;
        $quiz->visibleoncoursepage = 1;

        // Timing and behaviour.
        $quiz->timeopen = 0;
        $quiz->timeclose = 0;
        $quiz->timelimit = $session->timelimit;
        $quiz->overduehandling = 'autosubmit';
        $quiz->graceperiod = 0;
        $quiz->preferredbehaviour = 'deferredfeedback';
        $quiz->attempts = $session->attempts;
        $quiz->grademethod = $session->grademethod;

        // Grading.
        $quiz->decimalpoints = 2;
        $quiz->questiondecimalpoints = -1;
        $quiz->grade = (float) array_sum(array_map(function($q) { return (float) $q->points; }, $questions));
        $quiz->sumgrades = 0;

        // Layout.
        $quiz->questionsperpage = 1;
        $quiz->shufflequestions = 0;
        $quiz->shuffleanswers = 1;
        $quiz->navmethod = 'free';

        // Review options.
        $reviewmask = self::REVIEW_DURING;
        if (!empty($session->reviewcorrectanswers)) {
            $reviewmask |= self::REVIEW_IMMEDIATELY_AFTER | self::REVIEW_LATER_WHILE_OPEN;
        }
        $quiz->reviewattempt = self::REVIEW_DURING;
        $quiz->reviewcorrectness = $reviewmask;
        $quiz->reviewmarks = $reviewmask;
        $quiz->reviewspecificfeedback = $reviewmask;
        $quiz->reviewgeneralfeedback = $reviewmask;
        $quiz->reviewrightanswer = $reviewmask;
        $quiz->reviewoverallfeedback = 0;

        // Security / completion defaults.
        $quiz->showuserpicture = 0;
        $quiz->showblocks = 0;
        $quiz->completionpass = 0;
        $quiz->completionattemptsexhausted = 0;
        $quiz->completionminattempts = 0;
        $quiz->allowofflineattempts = 0;
        $quiz->timemodified = time();

        // Add module.
        $cmid = add_moduleinfo($quiz, $course);
        $quizid = $cmid->instance;

        // Add questions to quiz.
        quiz_add_quiz_question($questionids, $quizid, 0);

        // Recalculate sumgrades.
        $quizrecord = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        quiz_update_sumgrades($quizrecord);

        wizard::mark_published($sessionid);

        return (int) $cmid->coursemodule;
    }
}
