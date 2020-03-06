/**
 * @module     mod_observation/observer_view
 * @class      mod_observation
 * @package    observer_view
 */
define(['jquery', 'core/ajax', 'core/notification'],
    function ($, ajax, notification) {

        return {
            init: function () {
                // init edit table
                var tableSelector = 'table.observer-details';
                var editSelector = 'a.edit-details';

                var $container = $('.personal-details');
                var $table = $container.find(tableSelector);
                var $editBtn = $container.find(editSelector);

                var revertTable = function (response) {
                    // back to normal
                    $container.find('button[type=submit]')
                        .fadeOut(500, function () {
                            $(this).remove();
                        });

                    $container.unwrap();
                    $editBtn.show();

                    var $elements = $table.find('#details-values > td:not(#email)');
                    if (response) {
                        $elements.each(function () {
                            $(this)
                                .removeAttr('data-original_value')
                                .html(response[this.id]);
                        });
                    } else {
                        // ajax failed - revert
                        $elements.each(function () {
                            $(this)
                                .html($(this).data('original_value'))
                                .removeAttr('data-original_value');
                        });
                    }
                };

                var submitEditForm = function (ev) {
                    ev.preventDefault();

                    // TODO: VALIDATE DATA

                    var $row = $(this).find('tr#details-values');
                    var args = {
                        observerid: $row.data('observerid'),
                        fullname: $row.find('[name=fullname]').val(),
                        phone: $row.find('[name=phone]').val(),
                        position_title: $row.find('[name=position_title]').val()
                    };

                    $container.find('button[type=submit]').attr('disabled', true);

                    ajax.call([{
                        methodname: 'mod_observation_observer_update_details',
                        args: args,
                        done: revertTable,
                        fail: function (ex) {
                            revertTable(null);

                            // TODO: log the error
                            notification.alert(
                                'There was a problem',
                                'Sorry, something went wrong when updating your details.<br>Please contact an administrator regarding this issue'
                            );
                            console.error(ex);
                        }
                    }], true, false);
                };

                $editBtn.on('click', function (ev) {
                    console.log('clicked!');

                    $table.addClass('editing');

                    // create input fields
                    $table.find('#details-values > td:not(#email)').each(function () {
                        var originalValue = $(this).text();
                        $(this)
                            // make data backup in case ajax fails
                            .attr('data-original_value', originalValue)
                            .html($('<input required/>').attr(
                                {
                                    'type': 'text',
                                    'name': this.id,
                                    'value': originalValue
                                }
                            ));
                    });

                    // add submit btn
                    $container.append(
                        $('<div style="text-align: right; display: none;"><button type="submit">Save</button></div>')
                            .fadeIn(500));
                    // hide edit link
                    $editBtn.hide();

                    // wrap in <form>
                    $form = $('<form id="edit-details-form">').on('submit', submitEditForm);
                    $container.wrap($form);
                });

                // init checkbox validation
                var $checkbox = $('form#requirement-acknowledge #acknowledge_checkbox');
                var $submitBtn = $('form#requirement-acknowledge #submit-accept');

                $checkbox.attr('disabled', false);
                $checkbox.on('change', function (ev) {
                        $submitBtn.attr('disabled', !this.checked);
                    });
            }
        };
    });