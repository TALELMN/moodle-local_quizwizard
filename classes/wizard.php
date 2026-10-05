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
 * Wizard session manager.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages draft quiz wizard sessions.
 */
class wizard {

    /**
     * Create a new wizard session.
     *
     * @param int $courseid Course id.
     * @param int $userid Owner user id.
     * @param array $data Quiz info data.
     * @return int Session id.
     */
    public static function create_session(int $courseid, int $userid, array $data): int {
        global $DB;

        $now = time();
        $record = new \stdClass();
        $record->course = $courseid;
        $record->userid = $userid;
        $record->title = $data['title'] ?? '';
        $record->intro = $data['intro'] ?? '';
        $record->introformat = $data['introformat'] ?? FORMAT_HTML;
        $record->section = $data['section'] ?? 0;
        $record->timelimit = isset($data['timelimit']) ? (int) $data['timelimit'] * 60 : 0;
        $record->attempts = $data['attempts'] ?? 0;
        $record->grademethod = $data['grademethod'] ?? QUIZ_GRADEHIGHEST;
        $record->reviewcorrectanswers = isset($data['reviewcorrectanswers']) ? 1 : 0;
        $record->status = 'draft';
        $record->timecreated = $now;
        $record->timemodified = $now;

        $id = $DB->insert_record('local_quizwizard_session', $record);

        // Trigger event.
        $event = \local_quizwizard\event\quiz_created::create([
            'context' => \context_course::instance($courseid),
            'objectid' => $id,
            'relateduserid' => $userid,
        ]);
        $event->trigger();

        return $id;
    }

    /**
     * Update a session's quiz info.
     *
     * @param int $sessionid
     * @param array $data
     */
    public static function update_session(int $sessionid, array $data) {
        global $DB;

        $record = $DB->get_record('local_quizwizard_session', ['id' => $sessionid], '*', MUST_EXIST);
        $record->title = $data['title'] ?? $record->title;
        $record->intro = $data['intro'] ?? $record->intro;
        $record->introformat = $data['introformat'] ?? $record->introformat;
        $record->section = $data['section'] ?? $record->section;
        $record->timelimit = isset($data['timelimit']) ? (int) $data['timelimit'] * 60 : $record->timelimit;
        $record->attempts = $data['attempts'] ?? $record->attempts;
        $record->grademethod = $data['grademethod'] ?? $record->grademethod;
        $record->reviewcorrectanswers = isset($data['reviewcorrectanswers']) ? 1 : 0;
        $record->timemodified = time();

        $DB->update_record('local_quizwizard_session', $record);

        $event = \local_quizwizard\event\quiz_updated::create([
            'context' => \context_course::instance($record->course),
            'objectid' => $sessionid,
        ]);
        $event->trigger();
    }

    /**
     * Get a session record.
     *
     * @param int $sessionid
     * @return \stdClass
     */
    public static function get_session(int $sessionid): \stdClass {
        global $DB;
        return $DB->get_record('local_quizwizard_session', ['id' => $sessionid], '*', MUST_EXIST);
    }

    /**
     * Delete a session and all its questions.
     *
     * @param int $sessionid
     */
    public static function delete_session(int $sessionid) {
        global $DB;
        $questions = $DB->get_records('local_quizwizard_session_question', ['sessionid' => $sessionid]);
        foreach ($questions as $q) {
            $DB->delete_records('local_quizwizard_session_answer', ['questionid' => $q->id]);
        }
        $DB->delete_records('local_quizwizard_session_question', ['sessionid' => $sessionid]);
        $DB->delete_records('local_quizwizard_session', ['id' => $sessionid]);
    }

    /**
     * Get sessions for a user/course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    public static function get_user_sessions(int $courseid, int $userid): array {
        global $DB;
        return $DB->get_records('local_quizwizard_session', ['course' => $courseid, 'userid' => $userid], 'timemodified DESC');
    }

    /**
     * Get all questions for a session, with answers.
     *
     * @param int $sessionid
     * @return array
     */
    public static function get_questions(int $sessionid): array {
        global $DB;
        $questions = $DB->get_records('local_quizwizard_session_question', ['sessionid' => $sessionid], 'sortorder ASC, id ASC');
        foreach ($questions as $q) {
            $q->answers = array_values($DB->get_records('local_quizwizard_session_answer', ['questionid' => $q->id], 'sortorder ASC, id ASC'));
        }
        return array_values($questions);
    }

    /**
     * Get a single question with answers.
     *
     * @param int $questionid
     * @return \stdClass
     */
    public static function get_question(int $questionid): \stdClass {
        global $DB;
        $question = $DB->get_record('local_quizwizard_session_question', ['id' => $questionid], '*', MUST_EXIST);
        $question->answers = array_values($DB->get_records('local_quizwizard_session_answer', ['questionid' => $questionid], 'sortorder ASC, id ASC'));
        return $question;
    }

    /**
     * Add a question to a session.
     *
     * @param int $sessionid
     * @param array $data
     * @return int Question id.
     */
    public static function add_question(int $sessionid, array $data): int {
        global $DB;

        $maxorder = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {local_quizwizard_session_question} WHERE sessionid = :sessionid',
            ['sessionid' => $sessionid]
        );
        $sortorder = $maxorder === null ? 0 : $maxorder + 1;

        $record = self::fill_question_record(new \stdClass(), $data);
        $record->sessionid = $sessionid;
        $record->sortorder = $sortorder;
        $record->timecreated = time();
        $record->timemodified = time();

        $questionid = $DB->insert_record('local_quizwizard_session_question', $record);
        self::save_answers($questionid, $data['answers'] ?? []);

        self::touch_session($sessionid);

        $event = \local_quizwizard\event\question_created::create([
            'context' => \context_course::instance(self::get_session($sessionid)->course),
            'objectid' => $questionid,
        ]);
        $event->trigger();

        return $questionid;
    }

    /**
     * Update a question.
     *
     * @param int $questionid
     * @param array $data
     */
    public static function update_question(int $questionid, array $data) {
        global $DB;

        $record = $DB->get_record('local_quizwizard_session_question', ['id' => $questionid], '*', MUST_EXIST);
        $record = self::fill_question_record($record, $data);
        $record->timemodified = time();

        $DB->update_record('local_quizwizard_session_question', $record);
        $DB->delete_records('local_quizwizard_session_answer', ['questionid' => $questionid]);
        self::save_answers($questionid, $data['answers'] ?? []);

        self::touch_session($record->sessionid);

        $event = \local_quizwizard\event\question_updated::create([
            'context' => \context_course::instance(self::get_session($record->sessionid)->course),
            'objectid' => $questionid,
        ]);
        $event->trigger();
    }

    /**
     * Delete a question.
     *
     * @param int $questionid
     */
    public static function delete_question(int $questionid) {
        global $DB;
        $question = $DB->get_record('local_quizwizard_session_question', ['id' => $questionid], '*', MUST_EXIST);
        $DB->delete_records('local_quizwizard_session_answer', ['questionid' => $questionid]);
        $DB->delete_records('local_quizwizard_session_question', ['id' => $questionid]);
        self::touch_session($question->sessionid);

        $event = \local_quizwizard\event\question_deleted::create([
            'context' => \context_course::instance(self::get_session($question->sessionid)->course),
            'objectid' => $questionid,
        ]);
        $event->trigger();
    }

    /**
     * Duplicate a question.
     *
     * @param int $questionid
     * @return int New question id.
     */
    public static function duplicate_question(int $questionid): int {
        global $DB;
        $question = self::get_question($questionid);
        $data = [
            'questiontext' => $question->questiontext,
            'questiontextformat' => $question->questiontextformat,
            'single' => $question->single,
            'points' => $question->points,
            'explanation' => $question->explanation,
            'explanationformat' => $question->explanationformat,
            'difficulty' => $question->difficulty,
            'category' => $question->category,
            'answers' => [],
        ];
        foreach ($question->answers as $a) {
            $data['answers'][] = [
                'answertext' => $a->answertext,
                'answertextformat' => $a->answertextformat,
                'fraction' => $a->fraction,
            ];
        }
        return self::add_question($question->sessionid, $data);
    }

    /**
     * Reorder questions.
     *
     * @param int $sessionid
     * @param array $order Array of question ids in desired order.
     */
    public static function reorder_questions(int $sessionid, array $order) {
        global $DB;
        foreach ($order as $index => $questionid) {
            $DB->set_field('local_quizwizard_session_question', 'sortorder', $index, ['id' => (int) $questionid, 'sessionid' => $sessionid]);
        }
        self::touch_session($sessionid);
    }

    /**
     * Mark a session as published.
     *
     * @param int $sessionid
     */
    public static function mark_published(int $sessionid) {
        global $DB;
        $DB->set_field('local_quizwizard_session', 'status', 'published', ['id' => $sessionid]);
        $record = self::get_session($sessionid);
        $event = \local_quizwizard\event\quiz_published::create([
            'context' => \context_course::instance($record->course),
            'objectid' => $sessionid,
        ]);
        $event->trigger();
    }

    /**
     * Helper: save answers for a question.
     *
     * @param int $questionid
     * @param array $answers
     */
    protected static function save_answers(int $questionid, array $answers) {
        global $DB;
        foreach ($answers as $index => $answer) {
            $rec = new \stdClass();
            $rec->questionid = $questionid;
            $rec->answertext = $answer['answertext'] ?? '';
            $rec->answertextformat = $answer['answertextformat'] ?? FORMAT_HTML;
            $rec->fraction = !empty($answer['fraction']) ? 1 : 0;
            $rec->sortorder = $index;
            $rec->timecreated = time();
            $rec->timemodified = time();
            $DB->insert_record('local_quizwizard_session_answer', $rec);
        }
    }

    /**
     * Helper: fill question record from data.
     *
     * @param \stdClass $record
     * @param array $data
     * @return \stdClass
     */
    protected static function fill_question_record(\stdClass $record, array $data): \stdClass {
        $record->questiontext = $data['questiontext'] ?? '';
        $record->questiontextformat = $data['questiontextformat'] ?? FORMAT_HTML;
        $record->single = isset($data['single']) ? (int) $data['single'] : 1;
        $record->points = isset($data['points']) ? (float) $data['points'] : 1.0;
        $record->explanation = $data['explanation'] ?? '';
        $record->explanationformat = $data['explanationformat'] ?? FORMAT_HTML;
        $record->difficulty = $data['difficulty'] ?? '';
        $record->category = $data['category'] ?? '';
        return $record;
    }

    /**
     * Helper: update session modification time.
     *
     * @param int $sessionid
     */
    protected static function touch_session(int $sessionid) {
        global $DB;
        $DB->set_field('local_quizwizard_session', 'timemodified', time(), ['id' => $sessionid]);
    }
}
