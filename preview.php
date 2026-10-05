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
 * Student preview page.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/quizwizard/lib.php');

$id = required_param('id', PARAM_INT);
$q = optional_param('q', 1, PARAM_INT);

$session = \local_quizwizard\wizard::get_session($id);
$course = get_course($session->course);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/quizwizard:preview', $context);

if ($session->userid != $USER->id && !has_capability('local/quizwizard:managequizzes', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'preview');
}

$PAGE->set_url(new moodle_url('/local/quizwizard/preview.php', ['id' => $id, 'q' => $q]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('previewquiz', 'local_quizwizard'));
$PAGE->set_heading(get_string('previewquiz', 'local_quizwizard'));
$PAGE->set_pagelayout('incourse');

$questions = \local_quizwizard\wizard::get_questions($id);

$output = $PAGE->get_renderer('local_quizwizard');
$page = new \local_quizwizard\output\preview_page($session, $questions, $q);

echo $output->header();
echo $output->render_preview_page($page);
echo $output->footer();
