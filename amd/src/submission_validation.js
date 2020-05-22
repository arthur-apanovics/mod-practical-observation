/**
 * @module     mod_observation/submission_validation
 * @class      mod_observation
 * @package    submission_validation
 */
define(['jquery', 'core/notification'],
    function ($, notification) {
        function countWordsInElement($element) {
            var len = 0;

            try {
                var text = $($element).html();
                var regex = /\s+/gi;
                var regex2 = /<\/?[\w\s="/.':;#-\/\?]+>/gi;

                var temp = text.trim().replace(regex, ' ');
                temp = temp.replace(regex2, ' ').trim()
                    .replace(regex, ' ').trim();

                if (temp.length == 0) {
                    return 0;
                }

                len = temp.split(' ').length;
            } catch (e) {
                // ignore
            }

            return len;
        }

        return {
            init: function (submissionType) {
                var hintString;
                switch (submissionType) {
                    case 'learner':
                        hintString = 'Please provide information in the attempt field before requesting observation';
                        break;
                    case 'observer':
                        hintString = 'Please fill in all feedback fields before submitting';
                        break;
                    case 'assessor':
                        hintString = 'Please provide assessment feedback before saving';
                        break;
                    default:
                        throw new Error('Unsupported submission type: ' + submissionType);
                }

                var _$editorContainers = $('.editor-container');
                var $submitButton = $('form button[type="submit"]');

                // big no-no but getting closure problems otherwise...
                document.observationContainerStates = {};

                /**
                 * Callback for validating all containers in array
                 *
                 * @param containerState [string, Object]
                 * @returns {boolean}
                 */
                var isContainerValid = function (containerState) {
                    return containerState[1].valid;
                };
                // does all the work
                var clickHandlerAssigned = false; // no clue how to check if event handler assigned otherwise
                var initAttoValidation = function (attoContentWrapOrEvent) {

                    var $attoContentWrap = attoContentWrapOrEvent.target
                        ? $(attoContentWrapOrEvent.target).find('.editor_atto_content_wrap')
                        : attoContentWrapOrEvent;
                    var containerId = $($attoContentWrap).find('.editor_atto_content').prop('id');

                    // keep track of container validation states
                    document.observationContainerStates[containerId] = {
                        id: containerId,
                        valid: false
                    };

                    /**
                     * Validates all containers on change
                     */
                    var onEditorChange = function () {
                        // only validate if all editors initialized
                        if (Object.keys(document.observationContainerStates).length === _$editorContainers.length) {

                            document.observationContainerStates[containerId].valid =
                                Boolean(countWordsInElement($attoContentWrap));
                            var allValid = Object.entries(document.observationContainerStates).every(isContainerValid);

                            if (submissionType === 'assessor') {
                                // also check for observer criteria checkbox
                                $('input[name="meets-criteria"]')
                                    .prop('required', ($('select[name="outcome"]').val() === 'complete'));
                            }

                            $($submitButton).prop({
                                'title': (allValid ? 'Submit' : hintString)
                            });

                            // add alert if user tries to click
                            if (!allValid) {
                                if (!clickHandlerAssigned) {
                                    $($submitButton).on('click', function () {
                                        notification.alert('Cannot submit', hintString);
                                        return false;
                                    });

                                    clickHandlerAssigned = true;
                                }
                            } else {
                                $($submitButton).off('click');

                                clickHandlerAssigned = false;
                            }
                        }
                    };

                    // handle text input
                    $($attoContentWrap).on('keyup keydown propertychange paste', onEditorChange);
                    // handle drafts and saved content
                    $($attoContentWrap).find('.editor_atto_content')
                        .on('DOMNodeInserted', function () {
                            // draft restored or previously saved content
                            $(this).off('DOMNodeInserted');
                            onEditorChange();
                        });

                    if (submissionType === 'assessor') {
                        $('select[name="outcome"]').on('change', onEditorChange);
                    }

                    // fire initial validation manually
                    onEditorChange();
                };

                function onDomNodeInserted(event) {
                    // event keeps firing multiple times...
                    var id = $(this).find('.editor_atto_content').prop('id');
                    if (!document.observationContainerStates[id]) {
                        initAttoValidation(event);
                    }

                    this.removeEventListener('DOMNodeInserted', onDomNodeInserted, {capture: true, once: true});
                }

                $(_$editorContainers).each(function () {
                    // perform initial validation
                    if ($(this).find('.editor_atto').length) {
                        // atto editor already initialised
                        var $wrap = $(this).find('.editor_atto_content_wrap');
                        initAttoValidation.bind(this)($wrap);
                    } else {
                        // initialise as elements get inserted
                        this.addEventListener('DOMNodeInserted', onDomNodeInserted, {capture: true, once: true});
                    }
                });
            }
        };
    });
