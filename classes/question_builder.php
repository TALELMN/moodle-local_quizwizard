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
 * Question bank integration.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');

/**
 * Builds real Moodle question-bank questions from wizard question records.
 */
class question_builder {

    /**
     * Create a Moodle question from a wizard question.
     *
     * @param \stdClass $wizardquestion Wizard question with answers.
     * @param int $courseid
     * @param int|null $categoryid Question category id; if null, use default course category.
     * @return int Moodle question id.
     */
    public static function build(\stdClass $wizardquestion, int $courseid, ?int $categoryid = null): int {
        global $DB, $USER;

        if (empty($categoryid)) {
            $category = self::get_default_category($courseid);
            $categoryid = $category->id;
        }

        $qtype = 'multichoice';
        $question = new \stdClass();
        $question->qtype = $qtype;
        $question->category = $categoryid;
        $question->name = self::make_question_name($wizardquestion->questiontext);
        $question->questiontext = $wizardquestion->questiontext;
        $question->questiontextformat = $wizardquestion->questiontextformat;
        $question->generalfeedback = $wizardquestion->explanation ?? '';
        $question->generalfeedbackformat = $wizardquestion->explanationformat ?? FORMAT_HTML;
        $question->defaultmark = (float) $wizardquestion->points;
        $question->penalty = 0.3333333;
        $question->length = 1;
        $question->hidden = 0;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        $question->idnumber = null;
        $question->single = !empty($wizardquestion->single) ? 1 : 0;
        $question->shuffleanswers = 1;
        $question->answernumbering = 'abc';
        $question->showstandardinstruction = 0;
        $question->correctfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $question->partiallycorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];
        $question->incorrectfeedback = ['text' => '', 'format' => FORMAT_HTML];

        // Build answers for qtype_multichoice.
        $answers = [];
        $fractions = [];
        $correctcount = count(array_filter($wizardquestion->answers, function($a) {
            return !empty($a->fraction);
        }));
        $correctfraction = $correctcount > 0 ? (1.0 / $correctcount) : 0.0;
        foreach ($wizardquestion->answers as $index => $a) {
            $answers[$index] = [
                'text'   => $a->answertext,
                'format' => $a->answertextformat,
            ];
            $fractions[$index] = !empty($a->fraction) ? $correctfraction : 0.0;
        }
        $question->answer = $answers;
        $question->fraction = $fractions;

        $qtypeobj = \question_bank::get_qtype($qtype);
        $question = $qtypeobj->save_question($question, (object) ['answer' => $answers, 'fraction' => $fractions]);

        return (int) $question->id;
    }

    /**
     * Get default question category for a course, creating it if necessary.
     *
     * @param int $courseid
     * @return \stdClass
     */
    protected static function get_default_category(int $courseid): \stdClass {
        global $DB;
        $context = \context_course::instance($courseid);
        $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'parent' => 0]);
        if (!$category) {
            $category = new \stdClass();
            $category->name = get_string('defaultcategory', 'question');
            $category->contextid = $context->id;
            $category->info = '';
            $category->infoformat = FORMAT_HTML;
            $category->parent = 0;
            $category->sortorder = 999;
            $category->stamp = make_unique_id_code();
            $category->id = $DB->insert_record('question_categories', $category);
        }
        return $category;
    }

    /**
     * Derive a short question name from the question text.
     *
     * @param string $text
     * @return string
     */
    protected static function make_question_name(string $text): string {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if (mb_strlen($text) > 80) {
            $text = mb_substr($text, 0, 77) . '...';
        }
        if ($text === '') {
            $text = get_string('question', 'local_quizwizard') . ' ' . time();
        }
        return $text;
    }
}
