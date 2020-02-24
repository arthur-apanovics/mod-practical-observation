/**
 * @module     mod_observation/developer_view
 * @class      mod_observation
 * @package    developer_view
 */
define(['jquery', 'core/ajax', 'core/notification', 'mod_observation/Sortable'],
    function ($, ajax, notification, Sortable) {

        return {
            init: function () {
                var sortList = function (listElement) {
                    $(listElement).find("li").sort(sort_li).appendTo(listElement);

                    function sort_li(a, b) {
                        return ($(b).data('sequence')) < ($(a).data('sequence')) ? 1 : -1;
                    }
                };

                var updateSequenceAjax = function (event) {
                    // sequence is NOT zero indexed
                    var oldSequence = event.oldIndex + 1;
                    var newSequence = event.newIndex + 1;

                    if (oldSequence === newSequence) {
                        // nothing changed
                        return;
                    }

                    var methodName = null;
                    var args = null;
                    switch (event.target.id) {
                        case 'mainTaskList':
                            methodName = 'mod_observation_task_update_sequence';
                            args = {taskid: event.item.getAttribute('taskid'), newsequence: newSequence};
                            break;
                        default:
                            throw new Error('Unsupported element');
                    }


                    ajax.call([{
                        methodname: methodName,
                        args: args,
                        done: console.info,
                        fail: function (ex) {
                            // undo re-order
                            sortList(event.target);
                            notification.exception(ex);
                        }
                    }], true, true);
                };

                var taskListId = 'mainTaskList';
                var nestedCriteriaClass = 'nestedCriteria';

                Sortable.create(document.getElementById(taskListId), {
                    handle: '.my-handle',
                    animation: 150,
                    onEnd: updateSequenceAjax,
                });

                var nestedSortables = document.getElementsByClassName(nestedCriteriaClass);
                // Loop through each nested sortable element
                for (var i = 0; i < nestedSortables.length; i++) {
                    new Sortable(nestedSortables[i], {
                        group: {
                            name: nestedCriteriaClass,
                            pull: false
                        },
                        animation: 150,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        handle: '.my-handle',
                        onEnd: updateSequenceAjax,
                    });
                }
            }
        };
    });