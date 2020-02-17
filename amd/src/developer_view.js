/**
 * @module     mod_observation/developer_view
 * @class      mod_observation
 * @package    developer_view
 */
define(['jquery', 'core/ajax', 'core/notification', 'mod_observation/Sortable',],
    function ($, ajax, notification, Sortable) {

    return {    
        init: function () {
                Sortable.create(mainTaskList, {
                    handle: '.my-handle',
                    animation: 150,
                    onChange: function(evt) {
                        var mainTaskOldIndex = evt.oldIndex;
                        // send ajax request
                        console.log('Send ajax request to save in db', mainTaskOldIndex);
                        var mainTaskCurrentIndex = evt.newIndex;
                        // send ajax request
                        console.log('Send ajax request to save in db', mainTaskCurrentIndex);
                    }  
                });

                // Loop through each nested sortable element
                for (var i = 0; i < nestedSortables.length; i++) {
                    new Sortable(nestedSortables[i], {
                        group: {
                            name: 'nestedSortables',
                            pull:  false
                          },
                        animation: 150,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        handle: '.my-handle',
                        onEnd: function(evt) {
                             var criteriaPrevIndex = evt.oldIndex;
                             // send ajax request
                             console.log('Send ajax request to save in db', criteriaPrevIndex);
                             var criteriaCurrentIndex = evt.newIndex;
                             // send ajax request
                             console.log('Send ajax request to save in db', criteriaCurrentIndex);
                        },
                    });
                }                          
            }
    };
});