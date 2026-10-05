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
 * Main wizard page.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/quizwizard/lib.php');

$id = required_param('id', PARAM_INT);
$step = optional_param('step', 1, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$session = \local_quizwizard\wizard::get_session($id);
$course = get_course($session->course);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/quizwizard:managequizzes', $context);

if ($session->userid != $USER->id && !has_capability('moodle/course:manageactivities', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'managequizzes');
}

$PAGE->set_url(new moodle_url('/local/quizwizard/wizard.php', ['id' => $id, 'step' => $step]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('editquiz', 'local_quizwizard'));
$PAGE->set_heading(get_string('editquiz', 'local_quizwizard'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->js_call_amd('local_quizwizard/quizwizard', 'initWizard');

$errors = [];

// Save step 1.
if ($step === 1 && optional_param('savestep1', 0, PARAM_INT) && confirm_sesskey()) {
    $data = [
        'title' => required_param('title', PARAM_TEXT),
        'intro' => optional_param('intro', '', PARAM_RAW),
        'introformat' => optional_param('introformat', FORMAT_HTML, PARAM_INT),
        'section' => optional_param('section', 0, PARAM_INT),
        'timelimit' => optional_param('timelimit', 0, PARAM_INT),
        'attempts' => optional_param('attempts', 0, PARAM_INT),
        'grademethod' => optional_param('grademethod', QUIZ_GRADEHIGHEST, PARAM_INT),
        'reviewcorrectanswers' => optional_param('reviewcorrectanswers', 0, PARAM_INT),
    ];
    $errors = \local_quizwizard\validator::validate_session($data);
    if (empty($errors)) {
        \local_quizwizard\wizard::update_session($id, $data);
        redirect(new moodle_url('/local/quizwizard/wizard.php', ['id' => $id, 'step' => 2]));
    } else {
        // Preserve submitted values for re-display.
        $session->title = $data['title'];
        $session->intro = $data['intro'];
        $session->section = $data['section'];
        $session->timelimit = $data['timelimit'] * 60;
        $session->attempts = $data['attempts'];
        $session->grademethod = $data['grademethod'];
        $session->reviewcorrectanswers = $data['reviewcorrectanswers'];
    }
}

// Publish.
if ($action === 'publish' && confirm_sesskey()) {
    $errors = \local_quizwizard\validator::validate_session_for_publish($id);
    if (empty($errors)) {
        $cmid = \local_quizwizard\quiz_builder::publish($id);
        $viewurl = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
        redirect($viewurl, get_string('success_quizpublished', 'local_quizwizard'));
    } else {
        $step = 2;
    }
}

$questions = \local_quizwizard\wizard::get_questions($id);

$output = $PAGE->get_renderer('local_quizwizard');
$page = new \local_quizwizard\output\wizard_page($session, $questions, $step, $context, $errors);

echo $output->header();
echo $output->render_wizard_page($page);
echo $output->footer();
