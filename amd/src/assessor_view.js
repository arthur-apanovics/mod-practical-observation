/**
 * @module     mod_observation/assessor_view
 * @class      mod_observation
 * @package    assessor_view
 */
define(['jquery', 'core/notification'],
    function ($, notification) {
        return {
            init: function (observerRequirements) {
                if (!observerRequirements) {
                    throw new Error('No observer requirements passed');
                }

                var $link = $('#criteria-modal');
                $link.on('click', function () {
                    notification.alert('Observer requirements', observerRequirements, 'Close');
                });
            }
        };
    });
