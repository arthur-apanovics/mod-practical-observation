/**
 * @module     mod_observation/developer_view
 * @class      mod_observation
 * @package    developer_view
 */
define(['jquery', 'core/ajax', 'core/notification', 'mod_observation/Sortable'],
    function ($, ajax, notification, Sortable) {

        return {
            init: function () {
                var updateSequenceAjax = function (event) {
                    var oldIndex = event.oldIndex;
                    var newIndex = event.newIndex;

                    if (oldIndex === newIndex) {
                        // nothing changed
                        return;
                    }

                    var methodName = null;
                    var args = null;
                    switch (event.target.id) {
                        default:
                            debugger;
                            break;
                    }


                    ajax.call([{
                        methodname: methodName,
                        args: args,
                        done: console.info,
                        fail: console.error
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