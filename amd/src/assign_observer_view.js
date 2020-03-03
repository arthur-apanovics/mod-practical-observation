/**
 * @module     mod_observation/assign_observer_view
 * @class      mod_observation
 * @package    assign_observer_view
 */
define(['jquery', 'core/notification'],
    function ($, notification) {

        return {
            init: function () {
                var tableSelector = '#observer-history-table';
                var buttonSelector = '.assign'; // 'assign' buttons within table
                var assignFormSelector = '#assign-observer-form';
                var fields = ['fullname', 'phone', 'email', 'position_title'];

                var populate = function (el) {
                    el = el.target || el; // if this is an event

                    var currentRow = $(el).closest("tr");
                    for (var i in fields) {
                        var fieldName = fields[i];
                        var $field = $(assignFormSelector + ' [name="' + fieldName + '"');
                        // set new value
                        $($field).val(currentRow.data(fieldName));
                        // trigger validation // TODO: DOES NOT TRIGGER VALIDATION
                        $($field).closest('.fitem')
                        .trigger('change')
                        .trigger('blur');
                    }
                };

                // populate default
                populate($(tableSelector + ' .observer-row[data-active=1] ' + buttonSelector));
                // populate on click
                $(tableSelector).on('click', buttonSelector, populate);
            }
        };
    });
