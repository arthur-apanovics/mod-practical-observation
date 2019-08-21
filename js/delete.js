/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package totara
 * @subpackage totara_feedback360
 */
$(document).ready(function() {
    var elements = document.getElementsByClassName('system_record_del');
    var index;
    for (index = 0; index < elements.length; ++index) {
        elements[index].hidden = false;
    }
});

$(document).on('click', '.external_record_del', function (event) {
    event.preventDefault();

    var email = this.id;

    // Remove the email assignment from the display.
    var external_record = document.getElementById('external_user_' + email);
    external_record.parentNode.removeChild(external_record);

    // Add the email to #cancelledemails (commaseperated);
    var cancelled = $('input[name="emailcancel"]');

    var newval = [];
    if (cancelled.val()) {
        newval = cancelled.val().split(',');
    }

    newval.push(email);
    cancelled.val(newval.join(','));
});
