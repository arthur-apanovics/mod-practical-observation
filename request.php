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
 * @author David Curry <david.curry@totaralms.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara
 * @subpackage totara_feedback360
 */

use mod_observation\db_model\obsolete\email_assignment;
use mod_observation\db_model\obsolete\external_request;
use mod_observation\user_external_request;
use mod_observation\user_observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once(dirname(__FILE__) . '/forms.php');

require_login();

$cmid    = required_param('cmid', PARAM_INT); // Course_module ID
$topicid = required_param('topicid', PARAM_INT); // observation topic id
$action  = required_param('action', PARAM_ALPHA);
$userid  = required_param('userid', PARAM_INT);

$systemcontext = context_system::instance();
$usercontext   = context_user::instance($userid);

// Set up the page.
$PAGE->set_url(new moodle_url('/totara/feedback360/index.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);

// TODO REQUEST PAGE BREADCRUMBS
$PAGE->navbar->add('TODO: BREADCRUMBS');

$cm         = get_coursemodule_from_id('observation', $cmid);
$modcontext = context_module::instance($cm->id);
$asmanager  = $USER->id != $userid && has_capability('mod/observation:evaluate', $modcontext);
$owner      = $DB->get_record('user', array('id' => $userid));

//Now we can set up the rest of the page.
if ($asmanager)
{
    $userxfeedback = get_string('userxfeedback', 'mod_observation', fullname($owner));
    $PAGE->set_title($userxfeedback);
    $PAGE->set_heading($userxfeedback);
}
else
{
    $strrequestfeedback = get_string('requestobservation', 'mod_observation');
    $PAGE->set_title($strrequestfeedback);
    $PAGE->set_heading($strrequestfeedback);
}

// Set up the javascript for the page.
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

// Setup lightbox.
local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));

$PAGE->requires->js('/totara/feedback360/js/preview.js', false);

$user_observation = new user_observation($cm->instance, $userid);
$topic    = $user_observation->get_topic_by_id($topicid);
// Set up the forms based off of the action.
if ($action == 'users')
{
    $update           = optional_param('update', 0, PARAM_INT);
    $selected         = optional_param('selected', '', PARAM_SEQUENCE);
    $external_request = user_external_request::get_or_create_user_external_request_for_observation_topic(
        $cm->instance, $topicid, $userid);

    $data              = array();
    $data['cmid']      = $cmid;
    $data['topicid']   = $topic->id;
    $data['topicname'] = $topic->name;
    $data['userid']    = $userid;
    $data['topicname'] = format_string($topic->name);
    $data['duedate']   = $external_request->timedue;
    $data['update']    = $update;

    $data['emailexisting'] = array();
    foreach ($external_request->email_assignments as $assignment)
    {
        $data['emailexisting'][$assignment->id] = $assignment->email;
    }

    $args = array('args' => '{"userid":' . $userid . ','
                            . '"observationid":' . $cmid . ','
                            . '"topicid":' . $topicid . ','
                            . '"sesskey":"' . sesskey()
                            . '"}'
    );

    $PAGE->requires->js('/totara/feedback360/js/delete.js', false);

    $mform = new observation_request_select_users();
    $mform->set_data($data);
}
else if ($action == 'confirm')
{
    $emailnew    = required_param('emailnew', PARAM_TEXT);
    $emailcancel = required_param('emailcancel', PARAM_TEXT);
    $emailkeep   = required_param('emailkeep', PARAM_TEXT);
    $newduedate  = required_param('duedate', PARAM_INT);
    $oldduedate  = required_param('oldduedate', PARAM_INT);
    $mform       = new observation_request_confirmation();

    $data                = array();
    $data['userid']      = $userid;
    $data['cmid']        = $cmid;
    $data['topicid']     = $topic->id;
    $data['topicname']   = $topic->name;
    $data['emailnew']    = $emailnew;
    $data['emailcancel'] = $emailcancel;
    $data['emailkeep']   = $emailkeep;
    $data['oldduedate']  = $oldduedate;
    $data['newduedate']  = $newduedate;
    $data['strings']     = '';

    $mform->set_data($data);
}
else
{
    print_error('error:unrecognisedaction', 'mod_observation', null, $action);
}

// Handle forms being submitted.
if ($mform->is_cancelled())
{
    $cancelurl = new moodle_url('/mod/observation/view.php', array('id' => $cmid, 'userid' => $userid));
    redirect($cancelurl);
}
else if ($data = $mform->get_data())
{
    if (!empty($cmid))
    {
        // There was a formid that we validated at the beginning of this page.
        // This won't happen if the user is selecting a form to choose users for.
        if ($cmid != $data->cmid)
        {
            // It doesn't match. No need to validate against user again as this shouldn't happen.
            print_error('error:accessdenied', 'totara_feedback');
        }
    }

    if ($action == 'users')
    {
        // Include the list of all external emails.
        $newemail = array();
        if (!empty($data->emailnew))
        {
            $newemail = explode("\r\n", $data->emailnew);
        }

        // Show cancellations.
        $cancelemail = array();
        $keepemail   = array();
        if (!empty($data->emailcancel))
        {
            $cancelemail = explode(',', $data->emailcancel);
        }
        if (!empty($data->emailold))
        {
            $oldemail = explode(',', $data->emailold);

            foreach ($oldemail as $email)
            {
                if (!in_array($email, $cancelemail))
                {
                    $keepemail[] = $email;
                }
            }
        }

        if (!empty($newemail) || !empty($cancelemail) || $data->duedate != $data->oldduedate)
        {
            $params = array(
                'cmid'        => $cmid,
                'userid'      => $data->userid,
                'topicid'     => $topicid,
                'action'      => 'confirm', // <--- confirm action
                'emailnew'    => implode(',', $newemail),
                'emailkeep'   => implode(',', $keepemail),
                'emailcancel' => implode(',', $cancelemail),
                'duedate'     => $data->duedate,
                'oldduedate'  => $data->oldduedate,
            );

            $url = new moodle_url('/mod/observation/request.php', $params);
            redirect($url);
        }
        else
        {
            $params = array(
                'cmid'    => $cmid,
                'userid'  => $data->userid,
                'topicid' => $topicid,
                'action'  => 'users'
            );

            $url = new moodle_url('/mod/observation/request.php', $params);

            totara_set_notification(
                get_string('nochangestobemade', 'totara_feedback360'),
                $url,
                array('class' => 'notifysuccess'));
        }
    }
    else if ($action == 'confirm')
    {
        // Update the timedue in the request.

        $external_request = user_external_request::get_user_request_for_observation_topic($cm->instance, $topicid, $userid);

        $timeduevalidation = external_request::validate_new_timedue(
            $cm->instance, $topicid, $userid, $data->duedate);

        // We're updating if it's still valid. If it's not, then ignore, the date entered by the user
        // in the interface should have been found during the 'users' action so something else is happening here.
        if (empty($timeduevalidation))
        {
            $external_request->timedue = $data->duedate;
            $external_request->update();
        }

        $userfrom = $DB->get_record('user', array('id' => $userid));

        if ($data->duenotifications)
        {
            $strvars               = new stdClass();
            $strvars->userfrom     = fullname($userfrom);
            $strvars->feedbackname = "$topic->name - $user_observation->name";
            $strvars->timedue      = userdate($data->duedate, get_string('strftimedatetime'));

            if ($asmanager)
            {
                $staffmember        = $DB->get_record('user', array('id' => $data->userid));
                $strvars->staffname = fullname($staffmember);
            }
        }
        else
        {
            $strvars =
                // $userfrom =
                null;
        }

        email_assignment::update_and_notify_email($data, $asmanager, $userfrom, $strvars, $external_request);

        // Redirect to the observation page with a success notification.
        if (empty($emailkeep) && empty($emailcancel))
        {
            $successstr = get_string('requestcreatedsuccessfully', 'totara_feedback360');
        }
        else
        {
            $successstr = get_string('requestupdatedsuccessfully', 'totara_feedback360');
        }

        $returnurl = new moodle_url('/mod/observation/view.php', array('id' => $cmid));
        totara_set_notification($successstr, $returnurl, array('class' => 'notifysuccess'));
    }
    else
    {
        print_error('error:unrecognisedaction', 'totara_feedback360', null, $action);
    }
}

$renderer = $PAGE->get_renderer('mod_observation');
/* @var $renderer mod_observation_renderer */

echo $renderer->header();

echo $renderer->display_userview_header($owner);

$mform->display();

echo $renderer->footer();
