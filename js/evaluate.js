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
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_observation_evaluate = M.mod_observation_evaluate || {

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
        var observationobj = this;

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
            throw new Error('M.mod_observation_evaluate.init()-> jQuery dependency required for this module.');
        }

        var config = this.config;

        // Init observation completion toggles.
        $('.observation-completion-toggle').on('click', function () {
            var completionimg = $(this);
            // var itemid = $(this).closest('.observation-eval-actions').attr('observation-item-id');
            var itemid = $(this).parents('tr').find('.observation-eval-actions').attr('observation-item-id');
            $.ajax({
                url: M.cfg.wwwroot + '/mod/observation/evaluatesave.php',
                type: 'POST',
                data: {
                    'sesskey': M.cfg.sesskey,
                    'action': 'togglecompletion',
                    'token': config.token,
                    'bid': config.observationid,
                    'userid': config.userid,
                    'id': itemid
                },
                beforeSend: function () {
                    observationobj.replaceIcon(completionimg, 'loading');
                },
                success: function (data) {
                    if (data.item.status == config.Observation_COMPLETE) {
                        observationobj.replaceIcon(completionimg, 'completion-manual-y');
                    } else {
                        observationobj.replaceIcon(completionimg, 'completion-manual-n');
                    }

                    // Update the topic's completion too.
                    observationobj.setTopicStatusIcon(data.topic.status, $('#observation-topic-' + data.topic.topicid + ' .observation-topic-status'));

                    // Update modified string.
                    $('.mod-observation-modifiedstr[observation-item-id=' + itemid + ']').html(data.modifiedstr);

                    $(completionimg).next('.observation-completion-comment').focus();
                },
                error: function (data) {
                    console.log(data);
                    alert('Error saving completion...');
                }
            });
        });

        // Init comment inputs
        $('.observation-completion-comment').change(function () {
            var commentinput = this;
            var itemid = $(this).attr('observation-item-id');
            $.ajax({
                url: M.cfg.wwwroot + '/mod/observation/evaluatesave.php',
                type: 'POST',
                data: {
                    'sesskey': M.cfg.sesskey,
                    'action': 'savecomment',
                    'token': config.token,
                    'bid': config.observationid,
                    'userid': config.userid,
                    'id': itemid,
                    'comment': $(commentinput).val()
                },
                success: function (data) {

                    // Update comment text box, so we can get the date in there too
                    $(commentinput).val(data.item.comment);
                    // Update the comment print box
                    $('.observation-completion-comment-print[observation-item-id=' + itemid + ']').html(data.item.comment);

                    $('.mod-observation-modifiedstr[observation-item-id=' + itemid + ']').html(data.modifiedstr);
                },
                error: function (data) {
                    console.log(data);
                    alert('Error saving comment...');
                }
            });
        });

        // Init completion witness toggle.
        $('.observation-witness-toggle').on('click', function () {
            var completionimg = $(this);
            var itemid = $(this).closest('.observation-witness-item').attr('observation-item-id');
            $.ajax({
                url: M.cfg.wwwroot + '/mod/observation/witnesssave.php',
                type: 'POST',
                data: {
                    'sesskey': M.cfg.sesskey,
                    'token': config.token,
                    'bid': config.observationid,
                    'userid': config.userid,
                    'id': itemid
                },
                beforeSend: function () {
                    observationobj.replaceIcon(completionimg, 'loading');
                },
                success: function (data) {
                    if (data.item.witnessedby > 0) {
                        observationobj.replaceIcon(completionimg, 'completion-manual-y');
                    } else {
                        observationobj.replaceIcon(completionimg, 'completion-manual-n');
                    }

                    // Update the topic's completion too.
                    observationobj.setTopicStatusIcon(data.topic.status, $('#observation-topic-' + data.topic.topicid + ' .observation-topic-status'));

                    // Update modified string.
                    $('.mod-observation-witnessedstr[observation-item-id=' + itemid + ']').html(data.modifiedstr);
                },
                error: function (data) {
                    console.log(data);
                    alert('Error saving witness data...');
                }
            });
        });

        // Init topic signoffs
        $('.observation-topic-signoff-toggle').on('click', function () {
            var signoffimg = $(this);
            var topicid = $(this).closest('.mod-observation-topic-signoff');
            var topicid = $(topicid).attr('observation-topic-id');
            $.ajax({
                url: M.cfg.wwwroot + '/mod/observation/evaluatesignoff.php',
                type: 'POST',
                data: {
                    'sesskey': M.cfg.sesskey,
                    'bid': config.observationid,
                    'userid': config.userid,
                    'id': topicid
                },
                beforeSend: function () {
                    observationobj.replaceIcon(signoffimg, 'loading');
                },
                success: function (data) {
                    if (data.topicsignoff.signedoff) {
                        observationobj.replaceIcon(signoffimg, 'completion-manual-y');
                    } else {
                        observationobj.replaceIcon(signoffimg, 'completion-manual-n');
                    }

                    $('.mod-observation-topic-signoff[observation-topic-id=' + topicid + '] .mod-observation-topic-modifiedstr').html(data.modifiedstr);
                },
                error: function (data) {
                    console.log(data);
                    alert('Error saving signoff...');
                }
            });
        });
    },  // init

    replaceIcon: function (icon, newiconname) {
        require(['core/templates'], function (templates) {
            templates.renderIcon(newiconname).done(function (html) {
                icon.attr('data-flex-icon', $(html).attr('data-flex-icon'));
                icon.attr('class', $(html).attr('class'));
            });
        });

    },

    setTopicStatusIcon: function (topicstatus, statuscontainer) {
        var iconname = 'times-danger';
        if (topicstatus == this.config.Observation_COMPLETE) {
            iconname = 'check-success';
        } else if (topicstatus == this.config.Observation_REQUIREDCOMPLETE) {
            iconname = 'check-warning';
        }
        require(['core/templates'], function (templates) {
            templates.renderIcon(iconname).done(function (html) {
                statuscontainer.html(html);
            });
        });
    },
};

