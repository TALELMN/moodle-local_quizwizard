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
 * AJAX endpoint for Quiz Wizard.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/quizwizard/lib.php');

define('AJAX_SCRIPT', true);

$action = required_param('action', PARAM_ALPHA);
$sessionid = required_param('sessionid', PARAM_INT);

$session = \local_quizwizard\wizard::get_session($sessionid);
$course = get_course($session->course);
$context = context_course::instance($course->id);

require_login($course);
require_sesskey();
require_capability('local/quizwizard:managequizzes', $context);

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($action) {
        case 'getquestion':
            $questionid = required_param('questionid', PARAM_INT);
            $q = \local_quizwizard\wizard::get_question($questionid);
            $response['success'] = true;
            $response['data'] = [
                'id' => $q->id,
                'questiontext' => $q->questiontext,
                'single' => $q->single,
                'points' => $q->points,
                'explanation' => $q->explanation,
                'difficulty' => $q->difficulty,
                'category' => $q->category,
                'answers' => $q->answers,
            ];
            break;

        case 'savequestion':
            $data = self_parse_question_data();
            $questionid = optional_param('questionid', 0, PARAM_INT);
            if ($questionid) {
                \local_quizwizard\wizard::update_question($questionid, $data);
                $response['message'] = get_string('success_questionupdated', 'local_quizwizard');
            } else {
                $questionid = \local_quizwizard\wizard::add_question($sessionid, $data);
                $response['message'] = get_string('success_questionadded', 'local_quizwizard');
            }
            $response['success'] = true;
            $response['data'] = ['questionid' => $questionid];
            break;

        case 'deletequestion':
            $questionid = required_param('questionid', PARAM_INT);
            \local_quizwizard\wizard::delete_question($questionid);
            $response['success'] = true;
            $response['message'] = get_string('success_questiondeleted', 'local_quizwizard');
            break;

        case 'duplicatequestion':
            $questionid = required_param('questionid', PARAM_INT);
            $newid = \local_quizwizard\wizard::duplicate_question($questionid);
            $response['success'] = true;
            $response['data'] = ['questionid' => $newid];
            break;

        case 'reorderquestions':
            $order = required_param_array('order', PARAM_INT);
            \local_quizwizard\wizard::reorder_questions($sessionid, array_values($order));
            $response['success'] = true;
            break;

        case 'previewimport':
            $dtos = handle_import_preview($sessionid);
            foreach ($dtos as $dto) {
                $dto->errors = validator::validate_import_dto($dto, $dto->row);
                $dto->valid = empty($dto->errors);
            }
            $_SESSION['local_quizwizard_import_' . $sessionid] = $dtos;
            $response['data'] = $dtos;
            $response['success'] = true;
            break;

        case 'importselected':
            $selected = required_param_array('selected', PARAM_INT);
            $cachekey = 'local_quizwizard_import_' . $sessionid;
            $dtos = isset($_SESSION[$cachekey]) ? $_SESSION[$cachekey] : [];
            $imported = 0;
            foreach ($selected as $idx) {
                if (!isset($dtos[$idx])) {
                    continue;
                }
                $dto = $dtos[$idx];
                $errors = \local_quizwizard\validator::validate_import_dto($dto, $dto->row);
                if (!empty($errors)) {
                    continue;
                }
                $answers = [];
                foreach ($dto->answers as $i => $text) {
                    $answers[] = [
                        'answertext' => $text,
                        'fraction' => in_array($i + 1, $dto->correct) ? 1 : 0,
                    ];
                }
                \local_quizwizard\wizard::add_question($sessionid, [
                    'questiontext' => $dto->questiontext,
                    'single' => $dto->single,
                    'points' => $dto->points,
                    'explanation' => $dto->explanation,
                    'difficulty' => $dto->difficulty,
                    'answers' => $answers,
                ]);
                $imported++;
            }
            unset($_SESSION[$cachekey]);
            $response['success'] = true;
            $response['message'] = get_string('success_questionsimported', 'local_quizwizard', $imported);
            $response['data'] = ['imported' => $imported];
            break;

        case 'aigenerate':
            if (!\local_quizwizard\ai\ai_generator::is_enabled()) {
                throw new moodle_exception('err_aiconfig', 'local_quizwizard');
            }
            $params = [
                'topic' => required_param('topic', PARAM_TEXT),
                'count' => required_param('count', PARAM_INT),
                'difficulty' => required_param('difficulty', PARAM_ALPHA),
                'language' => required_param('language', PARAM_TEXT),
            ];
            $dtos = \local_quizwizard\ai\ai_generator::generate($params);
            $response['data'] = array_map(function($dto, $idx) {
                $answers = [];
                foreach ($dto->answers as $i => $text) {
                    $answers[] = [
                        'text' => $text,
                        'correct' => in_array($i + 1, $dto->correct),
                    ];
                }
                return [
                    'index' => $idx,
                    'questiontext' => $dto->questiontext,
                    'single' => $dto->single,
                    'points' => $dto->points,
                    'explanation' => $dto->explanation,
                    'difficulty' => $dto->difficulty,
                    'answers' => $answers,
                    'valid' => empty(\local_quizwizard\validator::validate_import_dto($dto, $idx + 1)),
                ];
            }, $dtos, array_keys($dtos));
            $response['success'] = true;
            break;

        default:
            throw new moodle_exception('invalidaction');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['success'] = false;
}

echo json_encode($response);

/**
 * Parse question data from POST.
 *
 * @return array
 */
function self_parse_question_data(): array {
    $questiontext = required_param('questiontext', PARAM_RAW);
    $single = optional_param('single', 1, PARAM_INT);
    $points = optional_param('points', 1, PARAM_FLOAT);
    $explanation = optional_param('explanation', '', PARAM_RAW);
    $difficulty = optional_param('difficulty', '', PARAM_ALPHA);
    $category = optional_param('category', '', PARAM_TEXT);

    $answertexts = optional_param_array('answertext', [], PARAM_RAW);
    $correctflags = optional_param_array('correct', [], PARAM_INT);

    $answers = [];
    foreach ($answertexts as $i => $text) {
        $answers[] = [
            'answertext' => $text,
            'fraction' => !empty($correctflags[$i]) ? 1 : 0,
        ];
    }

    return [
        'questiontext' => $questiontext,
        'single' => $single,
        'points' => $points,
        'explanation' => $explanation,
        'difficulty' => $difficulty,
        'category' => $category,
        'answers' => $answers,
    ];
}

/**
 * Handle import preview.
 *
 * @param int $sessionid
 * @return array
 */
function handle_import_preview(int $sessionid): array {
    $source = required_param('source', PARAM_ALPHA);
    if ($source === 'paste') {
        $content = required_param('content', PARAM_RAW);
        return \local_quizwizard\importer\csv_importer::parse($content);
    }

    // File upload.
    if (empty($_FILES['importfile']) || empty($_FILES['importfile']['tmp_name'])) {
        throw new moodle_exception('err_importnofile', 'local_quizwizard');
    }
    $file = $_FILES['importfile'];
    $filename = clean_param($file['name'], PARAM_FILE);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['csv', 'xlsx', 'xls'];
    if (!in_array($extension, $allowed)) {
        throw new moodle_exception('err_importinvalid', 'local_quizwizard');
    }
    $filepath = make_temp_directory('local_quizwizard') . '/' . md5($filename . time()) . '_' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new moodle_exception('err_importinvalid', 'local_quizwizard');
    }
    $dtos = \local_quizwizard\importer\excel_importer::parse($filepath, $filename);
    @unlink($filepath);
    return $dtos;
}
