# Quiz Wizard

A Moodle local plugin that makes creating MCQ quizzes extremely simple for teachers.

## What it does

Quiz Wizard provides a modern, step-by-step wizard inside a Moodle course. Teachers can:

1. Enter basic quiz information.
2. Build questions using a visual card interface.
3. Preview the quiz as a student would see it.
4. Publish the quiz as a normal Moodle quiz activity.

The plugin does **not** replace Moodle's quiz engine. Instead, it creates standard `mod_quiz` activities and genuine Moodle question-bank questions.

## Requirements

- Moodle 4.1 or later.
- `mod_quiz` enabled.
- `qtype_multichoice` enabled.
- (Optional) PhpSpreadsheet for Excel import.
- (Optional) External AI API for AI-assisted generation.

## Installation

1. Copy the plugin folder to `local/quizwizard/` in your Moodle directory.
2. Log in as an administrator.
3. Go to **Site administration ▶ Notifications** and complete the installation.
4. Configure optional AI settings under **Site administration ▶ Plugins ▶ Local plugins ▶ Quiz Wizard** if desired.

## Usage for teachers

1. Open a course where you have editing-teacher or manager role.
2. Go to **Course navigation ▶ Quiz Wizard**.
3. Click **Create quiz**.
4. Fill in the quiz title, description, section, time limit, attempts, grading method, and review settings.
5. Click **Next**.
6. Add questions one by one, or import many from CSV/Excel.
7. Click **Preview** to see how students will experience the quiz.
8. Click **Publish quiz**. A normal Moodle quiz activity is created.

## Import format

Upload a CSV or Excel file with these columns (case-insensitive):

- `question` — question text
- `answer1`, `answer2`, `answer3`, ... — answer choices
- `correct` — 1-based index or letter of correct answer(s); comma-separated for multiple
- `explanation` — optional feedback
- `points` — optional points
- `difficulty` — optional easy/medium/hard

Example:

```csv
question,answer1,answer2,answer3,correct,points
What is the capital of France?,London,Paris,Madrid,2,1
```

## Architecture

- **Plugin type:** `local`
- **Session storage:** `local_quizwizard_session`, `local_quizwizard_session_question`, `local_quizwizard_session_answer`
- **Moodle APIs:** `$DB`, context/capability APIs, question bank, `qtype_multichoice`, `mod_quiz`, file API, Mustache/AMD, Privacy API
- **Frontend:** Mustache templates, AMD modules, scoped CSS

## Running tests

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit local/quizwizard/tests/wizard_test.php
```

## Security notes

- All management pages require the `local/quizwizard:managequizzes` capability.
- Preview requires `local/quizwizard:preview`.
- Every write action checks `sesskey`.
- Parameters are validated server-side.
- Output is escaped via Mustache.

## Limitations

- Draft quiz sessions are not included in course backup/restore.
- Images and code blocks in questions require the standard Moodle editor (rich-text support is basic in this MVP).
- Word document import is not implemented.
- AI generation requires a separate API key and is entirely optional.

## License

GPL v3 or later.
