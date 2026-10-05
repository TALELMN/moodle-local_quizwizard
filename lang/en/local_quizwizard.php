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
 * Language strings for Quiz Wizard.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quiz Wizard';
$string['quizwizard'] = 'Quiz Wizard';
$string['createquiz'] = 'Create quiz';
$string['dashboard'] = 'Quiz Wizard Dashboard';
$string['backtodashboard'] = 'Back to dashboard';
$string['editquiz'] = 'Edit quiz';
$string['previewquiz'] = 'Preview quiz';
$string['publishquiz'] = 'Publish quiz';
$string['savequiz'] = 'Save quiz';
$string['step1'] = 'Step 1: Quiz information';
$string['step2'] = 'Step 2: Build questions';
$string['step3'] = 'Step 3: Preview';
$string['next'] = 'Next';
$string['previous'] = 'Previous';
$string['finish'] = 'Finish';
$string['addquestion'] = 'Add question';
$string['editquestion'] = 'Edit question';
$string['duplicatequestion'] = 'Duplicate question';
$string['deletequestion'] = 'Delete question';
$string['deletequestionconfirm'] = 'Are you sure you want to delete this question?';
$string['question'] = 'Question';
$string['questions'] = 'Questions';
$string['answers'] = 'Answers';
$string['answer'] = 'Answer';
$string['addanswer'] = 'Add answer';
$string['correct'] = 'Correct';
$string['points'] = 'Points';
$string['explanation'] = 'Explanation (optional)';
$string['difficulty'] = 'Difficulty';
$string['difficulty_easy'] = 'Easy';
$string['difficulty_medium'] = 'Medium';
$string['difficulty_hard'] = 'Hard';
$string['category'] = 'Category (optional)';
$string['savequestion'] = 'Save question';
$string['cancel'] = 'Cancel';
$string['title'] = 'Quiz title';
$string['description'] = 'Description';
$string['course'] = 'Course';
$string['section'] = 'Course section';
$string['timelimit'] = 'Time limit (minutes)';
$string['attempts'] = 'Number of attempts';
$string['unlimitedattempts'] = 'Unlimited';
$string['grademethod'] = 'Grading method';
$string['grademethod_best'] = 'Best attempt';
$string['grademethod_average'] = 'Average of attempts';
$string['grademethod_first'] = 'First attempt';
$string['grademethod_last'] = 'Last attempt';
$string['reviewcorrectanswers'] = 'Show correct answers after submission';
$string['emptyquiz'] = 'Your quiz is empty!';
$string['emptyquiz_help'] = 'Add your first question to get started, or import questions from a file.';
$string['importquestions'] = 'Import questions';
$string['importcsv'] = 'Upload CSV / Excel';
$string['importpaste'] = 'Paste questions';
$string['importword'] = 'Upload Word document';
$string['previewimport'] = 'Preview import';
$string['importselected'] = 'Import selected questions';
$string['generatequestions'] = 'Generate questions';
$string['topic'] = 'Topic';
$string['numberofquestions'] = 'Number of questions';
$string['language'] = 'Language';
$string['qtype'] = 'Question type';
$string['addselected'] = 'Add selected questions';
$string['aigenerated'] = 'AI-generated questions';
$string['quiztitle'] = 'Quiz title';
$string['noquestions'] = 'This quiz has no questions yet.';
$string['questioncount'] = '{$a} question(s)';
$string['recentquizzes'] = 'Recent quizzes';
$string['draftquizzes'] = 'Draft quizzes';
$string['publishedquizzes'] = 'Published quizzes';
$string['lastmodified'] = 'Last modified';
$string['actions'] = 'Actions';
$string['confirmdeletequiz'] = 'Are you sure you want to delete this draft? The quiz has not been published yet.';
$string['err_fieldrequired'] = 'This field is required.';
$string['err_notitle'] = 'Please enter a quiz title.';
$string['err_noquestions'] = 'Add at least one question before publishing.';
$string['err_questionnotext'] = 'Question {$a} has no text.';
$string['err_questionnoanswers'] = 'Question {$a} has no answers.';
$string['err_questionnocorrect'] = 'Question {$a} needs at least one correct answer.';
$string['err_questionminanswers'] = 'Question {$a} needs at least two answer choices.';
$string['err_duplicateanswers'] = 'Question {$a} has duplicate answers.';
$string['err_invalidpoints'] = 'Question {$a} has invalid points.';
$string['err_importnofile'] = 'Please upload a file to import.';
$string['err_importinvalid'] = 'The import file is invalid.';
$string['err_importnocorrect'] = 'Imported question {$a} has no correct answer.';
$string['err_importnoquestion'] = 'Imported row {$a} has no question text.';
$string['err_aiconfig'] = 'AI generation is not configured.';
$string['err_aiinvalid'] = 'AI response could not be parsed.';
$string['success_quizsaved'] = 'Quiz saved successfully.';
$string['success_quizpublished'] = 'Quiz published successfully.';
$string['success_questionadded'] = 'Question added.';
$string['success_questionupdated'] = 'Question updated.';
$string['success_questiondeleted'] = 'Question deleted.';
$string['success_questionsimported'] = '{$a} question(s) imported.';
$string['published'] = 'Published';
$string['draft'] = 'Draft';
$string['openeditor'] = 'Open editor';
$string['close'] = 'Close';
$string['correctanswer'] = 'Correct answer';
$string['correctanswers'] = 'Correct answers';
$string['singleanswer'] = 'Single correct answer';
$string['multianswer'] = 'Multiple correct answers';
$string['timer'] = 'Timer';
$string['submit'] = 'Submit';
$string['questionnumber'] = 'Question {$a}';
$string['navigation'] = 'Question navigation';
$string['back'] = 'Back';
$string['aiheading'] = 'AI question generation (optional)';
$string['aiheading_desc'] = 'Configure an external API endpoint to enable optional AI question generation. The plugin works without this.';
$string['aienabled'] = 'Enable AI generation';
$string['aienabled_desc'] = 'If enabled, teachers can generate question drafts using the configured AI endpoint.';
$string['aiendpoint'] = 'API endpoint';
$string['aiendpoint_desc'] = 'URL of the AI API endpoint, e.g. https://api.openai.com/v1/chat/completions';
$string['aiapikey'] = 'API key';
$string['aiapikey_desc'] = 'API key for the AI service. Stored encrypted by Moodle.';
$string['aimodel'] = 'Model name';
$string['aimodel_desc'] = 'Model identifier passed to the API, e.g. gpt-4o-mini';
$string['privacy:metadata:local_quizwizard_session'] = 'Draft quiz sessions created by users.';
$string['privacy:metadata:local_quizwizard_session_question'] = 'Questions inside a draft quiz session.';
$string['privacy:metadata:session:course'] = 'The course the draft quiz belongs to.';
$string['privacy:metadata:session:userid'] = 'The user who created the draft quiz.';
$string['privacy:metadata:session:title'] = 'The title of the draft quiz.';
$string['privacy:metadata:session:intro'] = 'The description of the draft quiz.';
$string['privacy:metadata:session:status'] = 'The status of the draft quiz.';
$string['privacy:metadata:session:timecreated'] = 'When the draft was created.';
$string['privacy:metadata:session:timemodified'] = 'When the draft was last modified.';
$string['privacy:metadata:question:sessionid'] = 'The draft quiz this question belongs to.';
$string['privacy:metadata:question:questiontext'] = 'The question text.';
$string['privacy:metadata:question:points'] = 'The points assigned to the question.';
$string['privacy:metadata:question:difficulty'] = 'The difficulty level.';
$string['privacy:metadata:question:sortorder'] = 'The order of the question in the quiz.';
$string['eventquizpublished'] = 'Quiz published';
$string['eventquizcreated'] = 'Quiz draft created';
$string['eventquizupdated'] = 'Quiz draft updated';
$string['eventquestioncreated'] = 'Wizard question created';
$string['eventquestionupdated'] = 'Wizard question updated';
$string['eventquestiondeleted'] = 'Wizard question deleted';
$string['managequizzes'] = 'Manage Quiz Wizard quizzes';
$string['preview'] = 'Preview quizzes';
$string['none'] = 'None';
$string['error/phpspreadsheetnotavailable'] = 'PhpSpreadsheet is not available. Please install it or use CSV format.';
$string['err_aiparsing'] = 'Could not parse AI response. Please try again.';
