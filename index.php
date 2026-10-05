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
 * Dashboard page.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/quizwizard/lib.php');

$courseid = required_param('course', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/quizwizard:managequizzes', $context);

$PAGE->set_url(new moodle_url('/local/quizwizard/index.php', ['course' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('dashboard', 'local_quizwizard'));
$PAGE->set_heading(get_string('dashboard', 'local_quizwizard'));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->js_call_amd('local_quizwizard/quizwizard', 'initDashboard');

// Handle delete.
if ($delete && confirm_sesskey()) {
    $session = \local_quizwizard\wizard::get_session($delete);
    if ($session->course == $courseid && ($session->userid == $USER->id || has_capability('moodle/course:manageactivities', $context))) {
        \local_quizwizard\wizard::delete_session($delete);
        redirect($PAGE->url, get_string('success_questiondeleted', 'local_quizwizard'));
    }
}

// Create new draft if requested.
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'create') {
    require_sesskey();
    $id = \local_quizwizard\wizard::create_session($courseid, $USER->id, ['title' => get_string('draft', 'local_quizwizard')]);
    redirect(new moodle_url('/local/quizwizard/wizard.php', ['id' => $id]));
}

$sessions = \local_quizwizard\wizard::get_user_sessions($courseid, $USER->id);
$createurl = new moodle_url('/local/quizwizard/index.php', ['course' => $courseid, 'action' => 'create', 'sesskey' => sesskey()]);

$output = $PAGE->get_renderer('local_quizwizard');
$page = new \local_quizwizard\output\dashboard_page($courseid, $sessions, $createurl->out(false));

echo $output->header();
echo $output->render_dashboard_page($page);
echo $output->footer();
