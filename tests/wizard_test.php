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
 * PHPUnit tests for Quiz Wizard wizard class.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizwizard;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for wizard CRUD operations.
 */
class wizard_test extends \advanced_testcase {

    /**
     * Test session creation and retrieval.
     */
    public function test_create_and_get_session() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $id = wizard::create_session($course->id, $user->id, ['title' => 'Test Quiz']);
        $this->assertGreaterThan(0, $id);

        $session = wizard::get_session($id);
        $this->assertEquals('Test Quiz', $session->title);
        $this->assertEquals($course->id, $session->course);
    }

    /**
     * Test adding and retrieving questions.
     */
    public function test_add_question() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $sid = wizard::create_session($course->id, $user->id, ['title' => 'Q Quiz']);
        $qid = wizard::add_question($sid, [
            'questiontext' => 'What is 2+2?',
            'single' => 1,
            'points' => 1,
            'answers' => [
                ['answertext' => '3', 'fraction' => 0],
                ['answertext' => '4', 'fraction' => 1],
            ],
        ]);

        $this->assertGreaterThan(0, $qid);
        $question = wizard::get_question($qid);
        $this->assertEquals('What is 2+2?', $question->questiontext);
        $this->assertCount(2, $question->answers);
    }

    /**
     * Test duplicate question.
     */
    public function test_duplicate_question() {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $sid = wizard::create_session($course->id, $user->id, ['title' => 'Dup Quiz']);
        $qid = wizard::add_question($sid, [
            'questiontext' => 'Q?',
            'single' => 1,
            'points' => 2,
            'answers' => [
                ['answertext' => 'A', 'fraction' => 0],
                ['answertext' => 'B', 'fraction' => 1],
            ],
        ]);
        $newid = wizard::duplicate_question($qid);
        $this->assertGreaterThan($qid, $newid);

        $questions = wizard::get_questions($sid);
        $this->assertCount(2, $questions);
    }

    /**
     * Test validator catches missing answers.
     */
    public function test_validator_missing_correct() {
        $question = (object) [
            'questiontext' => 'No correct answer?',
            'answers' => [
                (object) ['answertext' => 'A', 'fraction' => 0],
                (object) ['answertext' => 'B', 'fraction' => 0],
            ],
            'points' => 1,
        ];
        $errors = validator::validate_question_data($question);
        $this->assertNotEmpty($errors);
    }

    /**
     * Test CSV importer.
     */
    public function test_csv_importer() {
        $csv = "question,answer1,answer2,answer3,correct,points\n" .
               "Capital of France?,London,Paris,Madrid,2,1\n";
        $dtos = importer\csv_importer::parse($csv);
        $this->assertCount(1, $dtos);
        $this->assertEquals('Capital of France?', $dtos[0]->questiontext);
        $this->assertEquals([2], $dtos[0]->correct);
    }
}
