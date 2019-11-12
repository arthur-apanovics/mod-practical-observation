<?php
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_feedback360
 */

use mod_observation\models\email_assignment;
use mod_observation\models\external_request;
use mod_observation\user_observation;

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/forms.php');

$token = required_param('token', PARAM_ALPHANUM);

$email_assignment = email_assignment::get_from_token($token);
if (!$email_assignment || is_null($email_assignment->id))
{
    totara_set_notification(get_string('feedback360notfound', 'totara_feedback360'),
        new moodle_url('/'), array('class' => 'notifyproblem'));
}

$external_request = new external_request($email_assignment->externalrequestid);
$subjectuser      = $DB->get_record('user', array('id' => $external_request->userid));

//Check id a user is trying to observe himself somehow or observer has account in lms
if (isloggedin())
{
    if ($subjectuser->id == $USER->id)
    {
        print_error('You cannot evaluate yourself. Your assessor have been notified of this attempt');
        //TODO notify assessor of self eval attempt
    }
    else if ($USER->email != $email_assignment->email)
    {
        //TODO set user id for item completion to indicate a lms user with a different email has completed observation
    }
}

$user_observation         = new user_observation($external_request->observationid, $external_request->userid);

// This is a hack to get around authenticating anonymous users when viewing files in observation.
unset($SESSION->observation_usertoken);
$SESSION->observation_usertoken = $token;

$returnurl = new moodle_url('/mod/observation/observe.php', array('token' => $token));

// Set up the page.
$pageurl = new moodle_url('/mod/observation/observe.php');
$PAGE->set_context(null);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('popup');

$heading = get_string('observation', 'mod_observation');

$PAGE->set_title($heading);
$PAGE->set_heading($heading);
// TODO navbar for external user?
$PAGE->navbar->add($heading);
$PAGE->navbar->add(get_string('givefeedback', 'totara_feedback360'));

$email_assignment->mark_viewed();

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

// echo $renderer->header();
echo $OUTPUT->header();

echo html_writer::start_div('container');

echo $renderer->display_feedback_header($email_assignment, $subjectuser);

list($args, $jsmodule) = $renderer->get_evaluation_js_args($user_observation->id, $user_observation->userid, $token);
$PAGE->requires->js_init_call('M.mod_observation_evaluate.init', $args, false, $jsmodule);

echo $renderer->get_print_button($user_observation->name, fullname($subjectuser));
echo $renderer->user_topic_external($user_observation, $user_observation->get_topic_by_id($external_request->topicid), $email_assignment);

echo html_writer::end_div();// .container

// Finish the page.
echo $OUTPUT->footer();
