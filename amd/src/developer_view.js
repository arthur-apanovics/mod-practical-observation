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
                        var o = evt.oldIndex;
                        console.log('old index', o);
                        var t = evt.newIndex;
                        console.log('new index', t);
                    }  
                });   
                
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
                             var prevIndex = evt.oldIndex;
                             // send ajax request
                             console.log('Send ajax request to save in db', prevIndex);
                             var currentIndex = evt.newIndex;
                             // send ajax request
                             console.log('Send ajax request to save in db', currentIndex);
                        },
                    });
                }                          
            }
    };
});