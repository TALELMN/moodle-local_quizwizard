# Quiz Wizard — Manual QA Checklist

## Installation

- [ ] Plugin installs without errors on a fresh Moodle instance.
- [ ] Plugin installs without errors on an existing Moodle instance with quizzes.
- [ ] Database tables are created correctly.
- [ ] Capabilities are registered.
- [ ] Privacy provider passes `vendor/bin/phpunit privacy/tests/privacy/provider_test.php`.

## Permissions

- [ ] Editing teacher can access dashboard and wizard.
- [ ] Non-editing teacher can access preview if given capability.
- [ ] Student cannot access wizard pages.
- [ ] Teacher cannot access another teacher's draft unless they have course manage capability.

## Dashboard

- [ ] Dashboard lists drafts for the current course.
- [ ] Clicking "Create quiz" creates a new draft.
- [ ] Deleting a draft requires confirmation.
- [ ] Deleted draft removes its questions and answers.

## Step 1 — Quiz information

- [ ] Empty title shows friendly error.
- [ ] Valid data saves and advances to Step 2.
- [ ] Time limit converts minutes to seconds correctly.
- [ ] Section selector shows course sections.
- [ ] Grading method options persist.

## Step 2 — Build questions

- [ ] Empty state is shown when no questions exist.
- [ ] "Add question" opens the modal.
- [ ] Saving a question with empty text shows error.
- [ ] Saving a question with only one answer shows error.
- [ ] Saving a question with no correct answer shows error.
- [ ] Saving a valid question adds a card.
- [ ] Editing a question updates the card.
- [ ] Duplicating a question creates an identical copy.
- [ ] Deleting a question removes the card after confirmation.
- [ ] Drag-and-drop reordering persists after refresh.
- [ ] Multiple correct answers are displayed correctly.
- [ ] Points and explanation are saved.

## Import

- [ ] CSV import preview shows all rows.
- [ ] Invalid rows are marked with a warning.
- [ ] Only selected rows are imported.
- [ ] Excel import works when PhpSpreadsheet is installed.
- [ ] Pasting CSV content works.
- [ ] Uploading a non-CSV/Excel file shows an error.

## AI generation

- [ ] AI generation only appears when enabled and configured.
- [ ] Generated questions are shown for review.
- [ ] Only selected generated questions are added.
- [ ] Generated questions can be edited before publishing.

## Preview

- [ ] Preview displays questions in order.
- [ ] Navigation links move between questions.
- [ ] Timer displays when time limit is set.
- [ ] Single-choice and multiple-choice answers render correctly.
- [ ] Empty quiz preview shows helpful message.

## Publish

- [ ] Publishing with no questions shows friendly error.
- [ ] Publishing with invalid questions shows friendly error.
- [ ] Publishing creates a standard mod_quiz activity.
- [ ] Questions appear in the course question bank.
- [ ] Quiz sumgrades equals total points.
- [ ] Redirects to the new quiz view page.

## Responsive and accessibility

- [ ] Layout is usable on desktop.
- [ ] Layout is usable on tablet.
- [ ] Keyboard navigation works through forms and modals.
- [ ] Focus states are visible.
- [ ] Color contrast meets WCAG AA.
- [ ] Screen reader announces question cards and buttons.

## Backup and upgrades

- [ ] Published quizzes backup and restore correctly (core mod_quiz).
- [ ] Plugin upgrade from initial version runs cleanly.
