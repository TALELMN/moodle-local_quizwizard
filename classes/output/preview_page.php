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
 * Preview page renderable.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Preview page.
 */
class preview_page implements \renderable, \templatable {

    /** @var \stdClass Session. */
    protected $session;

    /** @var array Questions. */
    protected $questions;

    /** @var int Current question number. */
    protected $current;

    /**
     * Constructor.
     *
     * @param \stdClass $session
     * @param array $questions
     * @param int $current
     */
    public function __construct(\stdClass $session, array $questions, int $current = 1) {
        $this->session = $session;
        $this->questions = $questions;
        $this->current = max(1, min($current, max(1, count($questions))));
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $data = new \stdClass();
        $data->session = clone $this->session;
        $data->title = $this->session->title;
        $data->total = count($this->questions);
        $data->current = $this->current;
        $data->timelimit = (int) $this->session->timelimit;
        $data->hasquestions = !empty($this->questions);

        if (!empty($this->questions)) {
            $q = $this->questions[$this->current - 1];
            $item = clone $q;
            $item->num = $this->current;
            $item->hasexplanation = !empty($q->explanation);
            $item->explanationtext = $q->explanation;
            $item->answerlist = [];
            foreach ($q->answers as $a) {
                $ans = clone $a;
                $ans->inputtype = !empty($q->single) ? 'radio' : 'checkbox';
                $item->answerlist[] = $ans;
            }
            $data->question = $item;
        }

        $data->navigation = [];
        foreach ($this->questions as $index => $q) {
            $data->navigation[] = [
                'num' => $index + 1,
                'active' => ($index + 1 === $this->current),
                'url' => (new \moodle_url('/local/quizwizard/preview.php', ['id' => $this->session->id, 'q' => $index + 1]))->out(false),
            ];
        }

        $data->prevurl = ($this->current > 1) ? (new \moodle_url('/local/quizwizard/preview.php', ['id' => $this->session->id, 'q' => $this->current - 1]))->out(false) : '';
        $data->nexturl = ($this->current < $data->total) ? (new \moodle_url('/local/quizwizard/preview.php', ['id' => $this->session->id, 'q' => $this->current + 1]))->out(false) : '';
        $data->editurl = (new \moodle_url('/local/quizwizard/wizard.php', ['id' => $this->session->id, 'step' => 2]))->out(false);

        return $data;
    }
}
