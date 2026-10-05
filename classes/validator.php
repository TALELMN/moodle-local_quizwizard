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
 * Validation helpers.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates wizard data and imported questions.
 */
class validator {

    /**
     * Validate quiz info data.
     *
     * @param array $data
     * @return array List of errors keyed by field or '' for general.
     */
    public static function validate_session(array $data): array {
        $errors = [];
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            $errors['title'] = get_string('err_notitle', 'local_quizwizard');
        }
        $grademethod = (int) ($data['grademethod'] ?? QUIZ_GRADEHIGHEST);
        $allowed = [QUIZ_GRADEHIGHEST, QUIZ_GRADEAVERAGE, QUIZ_GRADEFIRST, QUIZ_GRADELAST];
        if (!in_array($grademethod, $allowed)) {
            $errors['grademethod'] = get_string('err_fieldrequired', 'local_quizwizard');
        }
        return $errors;
    }

    /**
     * Validate a session can be published.
     *
     * @param int $sessionid
     * @return array Errors with keys like question_5.
     */
    public static function validate_session_for_publish(int $sessionid): array {
        global $DB;
        $errors = [];
        $questions = wizard::get_questions($sessionid);
        if (empty($questions)) {
            $errors['general'] = get_string('err_noquestions', 'local_quizwizard');
            return $errors;
        }

        foreach ($questions as $index => $q) {
            $num = $index + 1;
            $qerrors = self::validate_question_data($q);
            foreach ($qerrors as $err) {
                $errors["question_{$num}"] = $err;
            }
        }
        return $errors;
    }

    /**
     * Validate a question record or data array.
     *
     * @param \stdClass|array $question
     * @return array
     */
    public static function validate_question_data($question): array {
        $errors = [];
        $question = (object) $question;
        $text = trim(strip_tags($question->questiontext ?? ''));
        if ($text === '') {
            $errors[] = get_string('err_questionnotext', 'local_quizwizard', '');
        }
        $answers = $question->answers ?? [];
        if (empty($answers)) {
            $errors[] = get_string('err_questionnoanswers', 'local_quizwizard', '');
            return $errors;
        }
        $nonemptyanswers = 0;
        $answertexts = [];
        $hascorrect = false;
        foreach ($answers as $a) {
            $atext = trim(strip_tags($a->answertext ?? ''));
            if ($atext !== '') {
                $nonemptyanswers++;
                $answertexts[] = mb_strtolower($atext);
            }
            if (!empty($a->fraction)) {
                $hascorrect = true;
            }
        }
        if ($nonemptyanswers < 2) {
            $errors[] = get_string('err_questionminanswers', 'local_quizwizard', '');
        }
        if (!$hascorrect) {
            $errors[] = get_string('err_questionnocorrect', 'local_quizwizard', '');
        }
        if (count($answertexts) !== count(array_unique($answertexts))) {
            $errors[] = get_string('err_duplicateanswers', 'local_quizwizard', '');
        }
        $points = (float) ($question->points ?? 0);
        if ($points <= 0) {
            $errors[] = get_string('err_invalidpoints', 'local_quizwizard', '');
        }
        return $errors;
    }

    /**
     * Validate an imported question DTO and return human-readable messages.
     *
     * @param \stdClass $dto
     * @param int $row
     * @return array Errors.
     */
    public static function validate_import_dto(\stdClass $dto, int $row): array {
        $errors = [];
        $text = trim(strip_tags($dto->questiontext ?? ''));
        if ($text === '') {
            $errors[] = get_string('err_importnoquestion', 'local_quizwizard', $row);
        }
        if (empty($dto->answers) || count(array_filter($dto->answers, function($a) {
            return trim(strip_tags($a['answertext'] ?? '')) !== '';
        })) < 2) {
            $errors[] = get_string('err_questionminanswers', 'local_quizwizard', $row);
        }
        if (empty($dto->correct)) {
            $errors[] = get_string('err_importnocorrect', 'local_quizwizard', $row);
        }
        return $errors;
    }

    /**
     * Turn validation errors into a single display string.
     *
     * @param array $errors
     * @return string
     */
    public static function format_errors(array $errors): string {
        $items = [];
        foreach ($errors as $key => $message) {
            $items[] = is_int($key) ? $message : "{$key}: {$message}";
        }
        return implode("\n", $items);
    }
}
