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
 * Question deleted event.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event class.
 */
class question_deleted extends \core\event\base {

    /**
     * Initialise event data.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_quizwizard_session_question';
    }

    /**
     * Get name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventquestiondeleted', 'local_quizwizard');
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function get_description() {
        return "User {$this->userid} deleted wizard question {$this->objectid}.";
    }
}
