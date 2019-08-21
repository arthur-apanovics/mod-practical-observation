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

use mod_ojt\models\email_assignment;
use mod_ojt\models\external_request;
use mod_ojt\user_ojt;

require_once(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/forms.php');


$preview    = optional_param('preview', 0, PARAM_INT);
$viewanswer = optional_param('myfeedback', 0, PARAM_INT);
$returnurl  = new moodle_url('/totara/feedback360/index.php');

$token          = optional_param('token', '', PARAM_ALPHANUM);
$isexternaluser = ($token != '');

if (!$isexternaluser)
{
    require_login();
    if (isguestuser())
    {
        $SESSION->wantsurl = qualified_me();
        redirect(get_login_url());
    }
}

$email_assignment = email_assignment::fetch_from_token($token);
if (!$email_assignment || is_null($email_assignment->id))
{
    totara_set_notification(get_string('feedback360notfound', 'totara_feedback360'),
        new moodle_url('/'), array('class' => 'notifyproblem'));
}

$external_request = new external_request($email_assignment->externalrequestid);
$user_ojt         = new user_ojt($external_request->ojtid, $external_request->userid);

//TODO determine evaluationcriteria
// $modcontext  = context_module::instance($cm->id);
$canevaluate = true;//ojt::can_evaluate($userid, $modcontext);
$cansignoff  = true;//has_capability('mod/ojt:signoff', $modcontext);
$canwitness  = true;//has_capability('mod/ojt:witnessitem', $modcontext);

// Get response assignment object, and check who is viewing the page.
$viewasown = false;
unset($SESSION->ojt_usertoken);
if ($isexternaluser)
{
    // This is a hack to get around authenticating anonymous users when viewing files in ojt.
    $SESSION->ojt_usertoken = $token;
    // Get the user's email address from the token.

    $returnurl = new moodle_url('/mod/ojt/observe.php', array('token' => $token));
}
// else if ($preview)
// {
//     $feedback360id = required_param('feedback360id', PARAM_INT);
//
//     $systemcontext = context_system::instance();
//     $canmanage     = has_capability('totara/feedback360:managefeedback360', $systemcontext);
//     $assigned      = feedback360::has_user_assignment($USER->id, $feedback360id);
//     $manager       = feedback360::check_managing_assigned($feedback360id, $USER->id);
//
//     if ($assigned)
//     {
//         require_capability('totara/feedback360:manageownfeedback360', $systemcontext);
//         $viewasown = true;
//     }
//
//     if (!empty($manager))
//     {
//         $usercontext = context_user::instance($manager[0]); // Doesn't matter which user, just check one.
//         require_capability('totara/feedback360:managestafffeedback', $usercontext);
//     }
//
//     if (!$canmanage && !$assigned && empty($manager))
//     {
//         print_error('error:previewpermissions', 'totara_feedback360');
//     }
//
//     $respassignment = feedback360_responder::by_preview($feedback360id);
// }
else if ($viewanswer)
{
    throw new coding_exception('Not implemented: View observation');
    // Retrieve responses via their associated requester token rather than by id as this guards anonymity
    // for anonymous feedback.
    $requestertoken = required_param('requestertoken', PARAM_ALPHANUM);
    $respassignment = feedback360_responder::get_by_requester_token($requestertoken);

    if ($respassignment->subjectid != $USER->id)
    {
        // If you arent the owner of the feedback request.
        if (\totara_job\job_assignment::is_managing($USER->id, $respassignment->subjectid))
        {
            // Or their manager.
            $capability_context = context_user::instance($respassignment->subjectid);
            require_capability('totara/feedback360:viewstaffreceivedfeedback360', $capability_context);
        }
        else if (!is_siteadmin())
        {
            // Or a site admin, then you shouldnt see this page.
            throw new feedback360_exception('error:accessdenied');
        }
    }
    else
    {
        $systemcontext = context_system::instance();
        require_capability('totara/feedback360:viewownreceivedfeedback360', $systemcontext);
        // You are the owner of the feedback request.
        $viewasown = true;
    }

    // You are viewing something that hasn't been viewed, mark it as viewed.
    if (!$respassignment->viewed)
    {
        $respassignment->viewed = true;
        $respassignment->save();
    }
}
else
{
    // You shouldn't be viewing this page.
    print_error('error:accessdenied');
}

// Set up the page.
$pageurl = new moodle_url('/mod/ojt/observe.php');
$PAGE->set_context(null);
$PAGE->set_url($pageurl);

if ($preview || $isexternaluser)
{
    $PAGE->set_pagelayout('popup');
}
else
{
    $PAGE->set_pagelayout('noblocks');
}

if ($isexternaluser)
{
    $heading = get_string('observation', 'mod_ojt');

    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    // TODO navbar for external user?
    $PAGE->navbar->add($heading);
    $PAGE->navbar->add(get_string('givefeedback', 'totara_feedback360'));
}
else if ($viewasown)
{
    $heading = get_string('myfeedback', 'totara_feedback360');

    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);
    $PAGE->set_totara_menu_selected('appraisals');
    $PAGE->navbar->add(get_string('feedback360', 'totara_feedback360'),
        new moodle_url('/totara/feedback360/index.php'));
    $PAGE->navbar->add(get_string('givefeedback', 'totara_feedback360'));
}
else
{
    throw new coding_exception('Cannot determine viewing type');
}

// $form = new feedback360_answer_form(null,
//     array(
//         'feedback360' => $feedback360,
//         'resp'        => $respassignment,
//         'preview'     => $preview,
//         'backurl'     => $backurl), 'post', '',
//     array('class' => 'totara-question-group'));

// $jsmodule = array(
//     'name'     => 'totara_feedback360_feedback',
//     'fullpath' => '/totara/feedback360/js/feedback.js',
//     'requires' => array('json'));
// $PAGE->requires->js_init_call('M.totara_feedback360_feedback.init', array($form->_form->getAttribute('id')),
//     false, $jsmodule);

$numresponders = $DB->get_field('ojt_email_assignment', 'COUNT(id)',
    array('externalrequestid' => $email_assignment->externalrequestid));

/* @var $renderer mod_ojt_renderer */
$renderer = $PAGE->get_renderer('ojt');

// echo $renderer->header();
echo $OUTPUT->header();

echo html_writer::start_div('container');

if ($preview)
{
    throw new coding_exception('preview not implemented');
    $feedbackname =
        $DB->get_field_select('feedback360', 'name', 'id = :fbid', array('fbid' => $respassignment->feedback360id));
    echo $renderer->display_preview_feedback_header($respassignment, $feedbackname);
}
else
{
    $subjectuser = $DB->get_record('user', array('id' => $external_request->userid));
    echo $renderer->display_feedback_header($email_assignment, $subjectuser, $numresponders);
}

list($args, $jsmodule) = $renderer->get_evaluation_js_args($user_ojt->id, $user_ojt->userid);
$PAGE->requires->js_init_call('M.mod_ojt_evaluate.init', $args, false, $jsmodule);

echo $renderer->get_print_button($user_ojt->name, fullname($subjectuser));

echo $renderer->user_topic(
    $user_ojt,
    $user_ojt->get_topic_by_id($external_request->topicid),
    $canevaluate,
    $cansignoff,
    $canwitness
);

echo html_writer::end_div();

// Finish the page.
echo $OUTPUT->footer();
