<?php
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

/**
 * Observation item completion ajax toggler
 */

use mod_observation\completion;
use mod_observation\email_assignment;
use mod_observation\observation_base;
use mod_observation\topic;
use mod_observation\topic_item;

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$userid      = required_param('userid', PARAM_INT);
$observationid       = required_param('bid', PARAM_INT);
$topicitemid = required_param('id', PARAM_INT);
$action      = required_param('action', PARAM_TEXT);
$token       = optional_param('token', '', PARAM_ALPHANUM);

$observation    = new observation_base($observationid);
$course = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
$cm     = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);

if ($token == '') //system user
{
    require_login($course, true, $cm);
    if (!observation_base::can_evaluate($userid, context_module::instance($cm->id)))
    {
        print_error('access denied');
    }
}
else if (!email_assignment::is_valid_token($observationid, $userid, $token))
{
    print_error('accessdenied', 'observation');
}

$topic_item       = new topic_item($topicitemid);
$email_assignment = email_assignment::get_from_token($token);
$user             = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$dateformat       = get_string('strftimedatetimeshort', 'core_langconfig');

// Update/insert the user completion record

$completion = completion::get_user_completion($topicitemid, $userid, completion::COMP_TYPE_TOPICITEM);
if ($completion->id)
{
    // Update
    switch ($action)
    {
        case 'togglecompletion':
            $completion->status = $completion->status == completion::STATUS_COMPLETE
                ? completion::STATUS_INCOMPLETE
                : completion::STATUS_COMPLETE;
            break;
        case 'savecomment':
            $completion->comment = required_param('comment', PARAM_TEXT);
            // append a date to the comment string
            $completion->comment .= ' - ' . userdate(time(), $dateformat) . '.';
            break;
        default:
    }

    $completion->timemodified  = time();
    $completion->observeremail = $email_assignment->email;
    $completion->update();
}
else
{
    // Insert
    $completion              = new completion();
    $completion->userid      = $userid;
    $completion->observationid       = $observationid;
    $completion->topicid     = $topic_item->topicid;
    $completion->topicitemid = $topicitemid;
    $completion->type        = completion::COMP_TYPE_TOPICITEM;

    switch ($action)
    {
        case 'togglecompletion':
            $completion->status = completion::STATUS_COMPLETE;
            break;
        case 'savecomment':
            $completion->comment = required_param('comment', PARAM_TEXT);
            // append a date to the comment string
            $completion->comment .= ' - ' . userdate(time(), $dateformat) . '.';
            break;
        default:
    }

    $completion->timemodified  = time();
    $completion->observeremail = $email_assignment->email;
    $completion->id            = $completion->create();
}

$modifiedstr = observation_base::get_modifiedstr_email($completion->timemodified, $email_assignment->email);

$jsonparams = array(
    'item'        => $completion,
    'modifiedstr' => $modifiedstr
);
if ($action == 'togglecompletion')
{
    $topiccompletion     = topic::update_topic_completion($userid, $observationid, $topic_item->topicid, $email_assignment->email);
    $jsonparams['topic'] = $topiccompletion;
}

echo json_encode($jsonparams);