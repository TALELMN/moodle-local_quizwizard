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
 * Library functions for Quiz Wizard.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend course navigation to add Quiz Wizard link.
 *
 * @param navigation_node $nav
 * @param stdClass $course
 * @param context $context
 */
function local_quizwizard_extend_navigation_course($nav, $course, $context) {
    if (has_capability('local/quizwizard:managequizzes', $context)) {
        $url = new moodle_url('/local/quizwizard/index.php', ['course' => $course->id]);
        $nav->add(
            get_string('pluginname', 'local_quizwizard'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'local_quizwizard',
            new pix_icon('i/settings', '')
        );
    }
}
