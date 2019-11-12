/*
 * Copyright (C) 2015 onwards Catalyst IT
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
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_ojt_attempt = M.mod_ojt_attempt || {

    Y: null,

    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param Y object    YUI instance
     * @param args string    args supplied in JSON format
     */
    init: function (Y, args) {
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.mod_ojt_evaluate.init()-> jQuery dependency required for this module.');
        }

        var config = this.config;

        // Init comment inputs
        $('.ojt-completion-submission').change(function () {
            var attemptText = this;
            var itemid = $(this).attr('ojt-item-id');
            $.ajax({
                url: M.cfg.wwwroot + '/mod/ojt/attemptsave.php',
                type: 'POST',
                data: {
                    'sesskey': M.cfg.sesskey,
                    'bid': config.ojtid,
                    'userid': config.userid,
                    'id': itemid,
                    'attempttext': $(attemptText).val()
                },
                success: function (data) {
                    // Update comment text box, so we can get the date in there too
                    $(attemptText).val(data.attempt.text);
                    // Update the comment print box
                    $('.ojt-completion-submission-print[ojt-item-id=' + itemid + ']').html(data.attempt.text);
                },
                error: function (data) {
                    console.log(data);
                    alert('Error saving comment...');
                }
            });
        });
    }  // init
};

