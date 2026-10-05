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
 * Quiz updated event.
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
class quiz_updated extends \core\event\base {

    /**
     * Initialise event data.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_quizwizard_session';
    }

    /**
     * Get name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventquizupdated', 'local_quizwizard');
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function get_description() {
        return "User {$this->userid} updated quiz wizard draft {$this->objectid}.";
    }

    /**
     * Get url.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/quizwizard/wizard.php', ['id' => $this->objectid]);
    }
}
