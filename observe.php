<?php
/*
 * Copyright (C) 2020 onwards Like-Minded Learning
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
 * @author  Arthur Apanovics <arthur.a@likeminded.co.nz>
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Prints a particular instance of observation for the current user.
 *
 */

use core\notification;
use mod_observation\lib;
use mod_observation\observer_assignment;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$token = required_param('token', PARAM_ALPHANUM);
$observer_assignment = observer_assignment::read_by_token_or_null($token, true);
$learner_task_submission = $observer_assignment->get_learner_task_submission_base();
$learner = core_user::get_user($learner_task_submission->get_userid());
$observer = $observer_assignment->get_observer();
$task = $learner_task_submission->get_task_base();
$observation = $task->get_observation_base();

// Print the page header.
$name = get_string('observer_page_title', OBSERVATION, fullname($learner));
$PAGE->set_context(null);
$PAGE->set_url($observer_assignment->get_review_url());
$PAGE->set_title($name);
$PAGE->set_heading($name);
$PAGE->set_pagelayout('popup');

$PAGE->add_body_class('observation-observe');


if (is_null($observer_assignment))
{
    print_error(get_string('error:invalid_token', OBSERVATION));
}
else if (!$observer_assignment->is_active())
{
    if (!$observer_assignment->is_declined())
    {
        print_error(get_string('error:not_active_observer', OBSERVATION));
    }
}
// seems to print error once a second observation has been submitted instead of printing 'complete' view
// else if ($observer_assignment->is_observation_complete())
// {
//     print_error(get_string('error:observation_complete', 'observation'));
// }

//Check id a user is trying to observe himself somehow or observer has account in lms
if (isloggedin())
{
    if (is_siteadmin($USER->id))
    {
        notification::add(
            sprintf(
                'Hello %s. Please remember that, if you submit this observation, your name will not appear anywhere and learner will think that it was submitted by the observer ',
                fullname($USER)),
            notification::WARNING);
    }
    else
    {
        // TODO: notify admins of logged in user observation
    }

    if ($learner_task_submission->get_userid() == $USER->id)
    {
        print_error('You cannot observe yourself!');

        //TODO notify assessor of self observation attempt
    }
    else if ($USER->email != $observer->get_email())
    {
        //TODO notify admins
        // has account in lms, is logged in, is not one being observed, is not assigned observer (based on email)
    }
}

// form submissions
if (optional_param('submit-accept', 0, PARAM_BOOL))
{
    // observation accepted.
    // this should not happen but let's confirm just to be safe
    if (!optional_param('acknowledge_checkbox', 0, PARAM_BOOL))
    {
        print_error('You haven\'t acknowledged meeting the criteria, please go back and try again');
    }

    $observer_assignment->accept();
}
else if (optional_param('submit-decline', 0, PARAM_BOOL))
{
    $observer_assignment->decline();

    // TODO: Event

    // emails
    $course = $observation->get_course();
    $review_url = $observer_assignment->get_review_url();

    $lang_data = [
        'learner_fullname'  => fullname($learner),
        'observer_fullname' => $observer->get_formatted_name(),
        'task_name'         => $task->get_formatted_name(),
        'activity_name'     => $observation->get_formatted_name(),
        'activity_url'      => $observation->get_url(),
        'observe_url'       => $review_url,
        'course_fullname'   => $course->fullname,
        'course_shortname'  => $course->shortname,
        'course_url'        => new \moodle_url('/course/view.php', ['id' => $course->id]),
    ];

    // send confirmation email to observer
    lib::email_external(
        $observer->get_email(),
        get_string('email:observer_observation_declined_subject', OBSERVATION, $lang_data),
        get_string('email:observer_observation_declined_body', OBSERVATION, $lang_data));

    // notify learner of declined observation
    lib::email_user(
        $learner,
        get_string('email:learner_observation_declined_subject', OBSERVATION, $lang_data),
        get_string('email:learner_observation_declined_body', OBSERVATION, $lang_data));

    // simply redirect, we'll show a special page for declined observations
    redirect($review_url);
}

/* @var $renderer mod_observation_renderer get this early as we might need it if observation has been declined */
$renderer = $PAGE->get_renderer('observation');

echo $OUTPUT->header();

if ($observer_assignment->is_declined())
{
    echo $renderer->view_observer_declined($observer_assignment, $learner_task_submission, $task);
}
else if ($observer_submission = $observer_assignment->get_observer_submission_or_null())
{
    if ($observer_submission->is_submitted())
    {
        echo $renderer->view_observer_completed($learner_task_submission, $task);
    }
}
else if (!$observation->is_activity_available())
{
    // not yet open or already closed
    echo $renderer->render_from_template(
        'view-observer_not_available', [
        'error_message' => lib::get_activity_timing_error_string($observation)
    ]);
}
else if (!$observer_assignment->is_accepted())
{
    // show observation EULA page
    echo $renderer->view_observer_landing($observation, $observer_assignment);
}
else
{
    // This is a hack to get around authenticating anonymous users when viewing files in observation.
    unset($SESSION->observation_usertoken);
    $SESSION->observation_usertoken = $token;

    // show observation page
    echo $renderer->view_task_observer($observation, $observer_assignment);
}

// Finish the page.
echo $OUTPUT->footer();
