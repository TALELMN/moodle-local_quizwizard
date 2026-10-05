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
 * Wizard page renderable.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Wizard page.
 */
class wizard_page implements \renderable, \templatable {

    /** @var \stdClass Session. */
    protected $session;

    /** @var array Questions. */
    protected $questions;

    /** @var int Current step 1-3. */
    protected $step;

    /** @var array Errors. */
    protected $errors;

    /** @var \context_course Context. */
    protected $context;

    /**
     * Constructor.
     *
     * @param \stdClass $session
     * @param array $questions
     * @param int $step
     * @param \context_course $context
     * @param array $errors
     */
    public function __construct(\stdClass $session, array $questions, int $step, \context_course $context, array $errors = []) {
        $this->session = $session;
        $this->questions = $questions;
        $this->step = $step;
        $this->context = $context;
        $this->errors = $errors;
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $CFG;

        $data = new \stdClass();
        $data->session = clone $this->session;
        $data->session->timelimit_minutes = (int) ($this->session->timelimit / 60);
        $data->session->reviewcorrectanswers_checked = !empty($this->session->reviewcorrectanswers) ? 'checked' : '';
        $data->step = $this->step;
        $data->step1active = $this->step === 1;
        $data->step2active = $this->step === 2;
        $data->step3active = $this->step === 3;
        $data->errors = $this->errors;
        $data->sesskey = sesskey();
        $data->courseid = $this->session->course;
        $data->ajaxurl = (new \moodle_url('/local/quizwizard/ajax.php'))->out(false);
        $data->previewurl = (new \moodle_url('/local/quizwizard/preview.php', ['id' => $this->session->id]))->out(false);
        $data->dashboardurl = (new \moodle_url('/local/quizwizard/index.php', ['course' => $this->session->course]))->out(false);
        $data->publishurl = (new \moodle_url('/local/quizwizard/wizard.php', ['id' => $this->session->id, 'action' => 'publish', 'sesskey' => sesskey()]))->out(false);

        // Section options.
        $course = get_course($this->session->course);
        $data->sections = [];
        $courseformat = course_get_format($course);
        $sectionnum = 0;
        while ($section = $courseformat->get_section($sectionnum)) {
            $data->sections[] = [
                'num' => $sectionnum,
                'name' => get_section_name($course, $section),
                'selected' => ((int) $this->session->section === $sectionnum) ? 'selected' : '',
            ];
            $sectionnum++;
            if ($sectionnum > 50) {
                break;
            }
        }

        // Grade method options.
        $data->grademethods = [
            ['value' => QUIZ_GRADEHIGHEST, 'name' => get_string('grademethod_best', 'local_quizwizard'), 'selected' => ((int) $this->session->grademethod === QUIZ_GRADEHIGHEST) ? 'selected' : ''],
            ['value' => QUIZ_GRADEAVERAGE, 'name' => get_string('grademethod_average', 'local_quizwizard'), 'selected' => ((int) $this->session->grademethod === QUIZ_GRADEAVERAGE) ? 'selected' : ''],
            ['value' => QUIZ_GRADEFIRST, 'name' => get_string('grademethod_first', 'local_quizwizard'), 'selected' => ((int) $this->session->grademethod === QUIZ_GRADEFIRST) ? 'selected' : ''],
            ['value' => QUIZ_GRADELAST, 'name' => get_string('grademethod_last', 'local_quizwizard'), 'selected' => ((int) $this->session->grademethod === QUIZ_GRADELAST) ? 'selected' : ''],
        ];

        // Questions.
        $data->questions = [];
        foreach ($this->questions as $index => $q) {
            $item = clone $q;
            $item->num = $index + 1;
            $item->correctlabel = !empty($q->single) ? get_string('correctanswer', 'local_quizwizard') : get_string('correctanswers', 'local_quizwizard');
            $item->answertype = !empty($q->single) ? get_string('singleanswer', 'local_quizwizard') : get_string('multianswer', 'local_quizwizard');
            $item->hasexplanation = !empty($q->explanation);
            $item->explanationtext = $q->explanation;
            $item->answerlist = [];
            foreach ($q->answers as $a) {
                $ans = clone $a;
                $ans->iscorrect = !empty($a->fraction);
                $item->answerlist[] = $ans;
            }
            $data->questions[] = $item;
        }
        $data->hasquestions = !empty($data->questions);
        $data->questioncount = count($data->questions);
        $data->aienabled = (bool) get_config('local_quizwizard', 'aienabled');

        return $data;
    }
}
