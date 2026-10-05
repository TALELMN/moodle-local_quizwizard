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
 * Dashboard page renderable.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Dashboard page.
 */
class dashboard_page implements \renderable, \templatable {

    /** @var int Course id. */
    protected $courseid;

    /** @var array Sessions. */
    protected $sessions;

    /** @var string Create URL. */
    protected $createurl;

    /**
     * Constructor.
     *
     * @param int $courseid
     * @param array $sessions
     * @param string $createurl
     */
    public function __construct(int $courseid, array $sessions, string $createurl) {
        $this->courseid = $courseid;
        $this->sessions = $sessions;
        $this->createurl = $createurl;
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $data = new \stdClass();
        $data->courseid = $this->courseid;
        $data->createurl = $this->createurl;
        $data->sessions = [];
        global $DB;
        foreach ($this->sessions as $s) {
            $item = clone $s;
            $item->lastmodified = userdate($s->timemodified);
            $count = $DB->count_records('local_quizwizard_session_question', ['sessionid' => $s->id]);
            $item->questioncountstr = get_string('questioncount', 'local_quizwizard', $count);
            $item->editurl = (new \moodle_url('/local/quizwizard/wizard.php', ['id' => $s->id]))->out(false);
            $item->deleteurl = (new \moodle_url('/local/quizwizard/index.php', ['course' => $this->courseid, 'delete' => $s->id, 'sesskey' => sesskey()]))->out(false);
            $data->sessions[] = $item;
        }
        return $data;
    }
}
