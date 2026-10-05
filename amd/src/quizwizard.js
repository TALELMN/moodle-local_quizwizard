/* eslint-disable */
/**
 * Quiz Wizard main AMD module.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/notification'], function($, Str, Notification) {
    'use strict';

    var Wizard = {
        sessionid: null,
        ajaxurl: null,
        importCache: [],
        aiCache: [],

        /**
         * Initialise dashboard behaviours.
         */
        initDashboard: function() {
            $('.qw-delete-confirm').on('click', function(e) {
                var message = $(this).data('message');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        },

        /**
         * Initialise wizard behaviours.
         */
        initWizard: function() {
            var root = $('.local-quizwizard-wizard');
            Wizard.sessionid = root.data('sessionid');
            Wizard.ajaxurl = root.data('ajaxurl');

            Wizard.initQuestionModal();
            Wizard.initImportModal();
            Wizard.initAiModal();
            Wizard.initCardActions();
            Wizard.initDragDrop();
            Wizard.initPublishWarning();
        },

        /**
         * Question modal logic.
         */
        initQuestionModal: function() {
            var modal = $('#qw-question-modal');

            $('[data-action="open-question-modal"]').on('click', function() {
                Wizard.resetQuestionForm();
                Str.get_string('addquestion', 'local_quizwizard').done(function(s) {
                    modal.find('.modal-title').text(s);
                });
                modal.modal('show');
            });

            $('#qw-add-answer-btn').on('click', function() {
                var row = $('.qw-answer-row').first().clone();
                row.find('input').val('');
                row.find('input[type="checkbox"]').prop('checked', false);
                Str.get_string('answer', 'local_quizwizard').done(function(s) {
                    row.find('.form-control').attr('placeholder', s);
                });
                $('#qw-answers-container').append(row);
            });

            $('#qw-save-question-btn').on('click', function() {
                Wizard.saveQuestion();
            });

            modal.on('click', '[data-action="edit-question"]', function() {
                // Handled via delegated listener on cards.
            });
        },

        /**
         * Reset the question form.
         */
        resetQuestionForm: function() {
            $('#qw-question-form')[0].reset();
            $('#qw-question-id').val(0);
            $('#qw-question-error').hide();
            $('#qw-answers-container').html('');
            for (var i = 0; i < 4; i++) {
                $('#qw-add-answer-btn').trigger('click');
            }
        },

        /**
         * Save a question via AJAX.
         */
        saveQuestion: function() {
            var form = $('#qw-question-form');
            var data = {
                action: 'savequestion',
                sessionid: Wizard.sessionid,
                sesskey: $('input[name="sesskey"]').first().val(),
                questionid: $('#qw-question-id').val(),
                questiontext: $('#qw-question-text').val(),
                single: $('#qw-single').is(':checked') ? 1 : 0,
                points: $('#qw-points').val(),
                explanation: $('#qw-explanation').val(),
                difficulty: $('#qw-difficulty').val(),
                category: $('#qw-category').val(),
                answertext: [],
                correct: []
            };

            $('#qw-answers-container .qw-answer-row').each(function() {
                data.answertext.push($(this).find('input[name="answertext[]"]').val());
                data.correct.push($(this).find('input[name="correct[]"]').is(':checked') ? 1 : 0);
            });

            $.ajax({
                url: Wizard.ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#qw-question-modal').modal('hide');
                        window.location.reload();
                    } else {
                        $('#qw-question-error').text(response.message).show();
                    }
                },
                error: function() {
                    $('#qw-question-error').text('An unexpected error occurred.').show();
                }
            });
        },

        /**
         * Card action listeners.
         */
        initCardActions: function() {
            $('#qw-questions-list').on('click', '[data-action="edit-question"]', function(e) {
                e.stopPropagation();
                var qid = $(this).data('questionid');
                Wizard.loadQuestionForEdit(qid);
            });

            $('#qw-questions-list').on('click', '[data-action="duplicate-question"]', function(e) {
                e.stopPropagation();
                var qid = $(this).data('questionid');
                Wizard.callAction('duplicatequestion', {questionid: qid});
            });

            $('#qw-questions-list').on('click', '[data-action="delete-question"]', function(e) {
                e.stopPropagation();
                var qid = $(this).data('questionid');
                Str.get_string('deletequestionconfirm', 'local_quizwizard').done(function(msg) {
                    if (confirm(msg)) {
                        Wizard.callAction('deletequestion', {questionid: qid});
                    }
                });
            });
        },

        /**
         * Load a question into the modal for editing.
         */
        loadQuestionForEdit: function(questionid) {
            $.ajax({
                url: Wizard.ajaxurl,
                type: 'GET',
                data: {action: 'getquestion', sessionid: Wizard.sessionid, questionid: questionid, sesskey: $('input[name="sesskey"]').first().val()},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Wizard.populateQuestionForm(response.data);
                        $('#qw-question-modal').modal('show');
                    }
                }
            });
        },

        /**
         * Populate question form from data.
         */
        populateQuestionForm: function(data) {
            $('#qw-question-id').val(data.id);
            $('#qw-question-text').val(data.questiontext);
            $('#qw-single').prop('checked', !!data.single);
            $('#qw-points').val(data.points);
            $('#qw-explanation').val(data.explanation);
            $('#qw-difficulty').val(data.difficulty);
            $('#qw-category').val(data.category);
            $('#qw-answers-container').html('');
            $.each(data.answers, function(i, a) {
                var row = $('.qw-answer-row').first().clone();
                row.find('input[name="answertext[]"]').val(a.answertext);
                row.find('input[name="correct[]"]').prop('checked', !!a.fraction);
                $('#qw-answers-container').append(row);
            });
        },

        /**
         * Simple drag-and-drop reordering.
         */
        initDragDrop: function() {
            var list = document.getElementById('qw-questions-list');
            if (!list) {
                return;
            }
            var dragged = null;
            $(list).on('dragstart', '.qw-question-card', function(e) {
                dragged = this;
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                $(this).addClass('dragging');
            }).on('dragend', '.qw-question-card', function() {
                $(this).removeClass('dragging');
                Wizard.saveOrder();
            }).on('dragover', '.qw-question-card', function(e) {
                e.preventDefault();
                if (this !== dragged) {
                    $(this).before(dragged);
                }
            });
            $(list).find('.qw-question-card').attr('draggable', 'true');
        },

        /**
         * Save current card order.
         */
        saveOrder: function() {
            var order = [];
            $('#qw-questions-list .qw-question-card').each(function() {
                order.push($(this).data('questionid'));
            });
            Wizard.callAction('reorderquestions', {order: order}, false);
        },

        /**
         * Import modal logic.
         */
        initImportModal: function() {
            $('[data-import-tab]').on('click', function() {
                $('[data-import-tab]').removeClass('active');
                $(this).addClass('active');
                var tab = $(this).data('import-tab');
                $('.qw-import-panel').hide();
                $('#qw-import-' + tab).show();
            });

            $('[data-action="open-import-modal"]').on('click', function() {
                $('#qw-import-modal').modal('show');
            });

            $('#qw-preview-import-btn').on('click', function() {
                Wizard.previewImport();
            });

            $('#qw-import-selected-btn').on('click', function() {
                Wizard.importSelected();
            });
        },

        /**
         * Preview import.
         */
        previewImport: function() {
            var tab = $('[data-import-tab].active').data('import-tab');
            var formData = new FormData();
            formData.append('action', 'previewimport');
            formData.append('sessionid', Wizard.sessionid);
            formData.append('sesskey', $('input[name="sesskey"]').first().val());
            formData.append('source', tab);

            if (tab === 'file') {
                var file = $('#qw-import-file-input')[0].files[0];
                if (!file) {
                    $('#qw-import-error').text('Please select a file.').show();
                    return;
                }
                formData.append('importfile', file);
            } else {
                var content = $('#qw-import-paste-input').val();
                if (!content.trim()) {
                    $('#qw-import-error').text('Please paste some content.').show();
                    return;
                }
                formData.append('content', content);
            }

            $.ajax({
                url: Wizard.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Wizard.importCache = response.data;
                        Wizard.renderImportPreview(response.data);
                    } else {
                        $('#qw-import-error').text(response.message).show();
                    }
                }
            });
        },

        /**
         * Render import preview.
         */
        renderImportPreview: function(questions) {
            var container = $('#qw-import-preview-list');
            container.html('');
            $.each(questions, function(i, q) {
                var valid = q.valid !== undefined ? q.valid : true;
                var row = $('<div class="qw-import-item">');
                row.append('<input type="checkbox" class="qw-import-check" data-index="' + i + '" ' + (valid ? 'checked' : '') + '>');
                row.append('<span class="qw-import-status">' + (valid ? '&#10003;' : '&#9888;') + '</span>');
                row.append('<span class="qw-import-text">' + $('<div>').text(q.questiontext).html() + '</span>');
                container.append(row);
            });
            $('#qw-import-preview').show();
            $('#qw-import-selected-btn').show();
        },

        /**
         * Import selected questions.
         */
        importSelected: function() {
            var selected = [];
            $('.qw-import-check:checked').each(function() {
                selected.push($(this).data('index'));
            });
            Wizard.callAction('importselected', {selected: selected});
        },

        /**
         * AI modal logic.
         */
        initAiModal: function() {
            $('[data-action="open-ai-modal"]').on('click', function() {
                $('#qw-ai-modal').modal('show');
            });

            $('#qw-generate-btn').on('click', function() {
                Wizard.generateQuestions();
            });

            $('#qw-add-selected-btn').on('click', function() {
                Wizard.addAiSelected();
            });
        },

        /**
         * Generate AI questions.
         */
        generateQuestions: function() {
            var data = {
                action: 'aigenerate',
                sessionid: Wizard.sessionid,
                sesskey: $('input[name="sesskey"]').first().val(),
                topic: $('#qw-ai-topic').val(),
                count: $('#qw-ai-count').val(),
                difficulty: $('#qw-ai-difficulty').val(),
                language: $('#qw-ai-language').val()
            };
            $.ajax({
                url: Wizard.ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Wizard.aiCache = response.data;
                        Wizard.renderAiPreview(response.data);
                    } else {
                        $('#qw-ai-error').text(response.message).show();
                    }
                }
            });
        },

        /**
         * Render AI preview.
         */
        renderAiPreview: function(questions) {
            var container = $('#qw-ai-preview-list');
            container.html('');
            $.each(questions, function(i, q) {
                var row = $('<div class="qw-ai-item">');
                row.append('<input type="checkbox" class="qw-ai-check" data-index="' + i + '" checked>');
                row.append('<span class="qw-ai-text">' + $('<div>').text(q.questiontext).html() + '</span>');
                container.append(row);
            });
            $('#qw-ai-preview').show();
            $('#qw-add-selected-btn').show();
        },

        /**
         * Add selected AI questions as drafts.
         */
        addAiSelected: function() {
            var selected = [];
            $('.qw-ai-check:checked').each(function() {
                selected.push($(this).data('index'));
            });
            var count = selected.length;
            $.each(selected, function(i, idx) {
                var q = Wizard.aiCache[idx];
                var answers = [];
                $.each(q.answers, function(j, a) {
                    answers.push({answertext: a.text, fraction: a.correct ? 1 : 0});
                });
                var data = {
                    action: 'savequestion',
                    sessionid: Wizard.sessionid,
                    sesskey: $('input[name="sesskey"]').first().val(),
                    questiontext: q.questiontext,
                    single: q.single,
                    points: q.points,
                    explanation: q.explanation,
                    difficulty: q.difficulty,
                    category: '',
                    answertext: $.map(answers, function(a) { return a.answertext; }),
                    correct: $.map(answers, function(a) { return a.fraction; })
                };
                $.ajax({
                    url: Wizard.ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    async: false
                });
            });
            window.location.reload();
        },

        /**
         * Generic AJAX action that reloads on success.
         */
        callAction: function(action, params, reload) {
            reload = reload !== false;
            var data = $.extend({
                action: action,
                sessionid: Wizard.sessionid,
                sesskey: $('input[name="sesskey"]').first().val()
            }, params);
            $.ajax({
                url: Wizard.ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success && reload) {
                        window.location.reload();
                    } else if (!response.success) {
                        Notification.alert('Error', response.message);
                    }
                }
            });
        },

        /**
         * Warn before publishing if validation issues exist (server enforces anyway).
         */
        initPublishWarning: function() {
            $('#qw-publish-btn').on('click', function(e) {
                if ($('#qw-questions-list .qw-question-card').length === 0) {
                    e.preventDefault();
                    Notification.alert('Error', 'Add at least one question before publishing.');
                }
            });
        }
    };

    return Wizard;
});
