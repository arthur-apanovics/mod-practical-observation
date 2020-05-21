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

use core\output\notification;
use mod_observation\learner_attempt_base;
use mod_observation\learner_task_submission;
use mod_observation\lib;
use mod_observation\observer;
use mod_observation\observer_base;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('forms.php');

$cmid = required_param('id', PARAM_INT);
$learner_task_submission_id = required_param('learner_task_submission_id', PARAM_INT);
$attempt_id = required_param('attempt_id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login($course, true, $cm);

// TODO: Event

// creating class instances also validates provided id's
$task_submission = new learner_task_submission($learner_task_submission_id);
$attempt = new learner_attempt_base($attempt_id);
$task_id = $task_submission->get($task_submission::COL_TASKID);
$task = new task($task_id, $USER->id);
$observation = $task->get_observation_base();

$activity_url = $observation->get_url();

// Print the page header.
$PAGE->set_url(
    OBSERVATION_MODULE_PATH . 'request.php',
    ['id' => $cm->id, 'learner_task_submission_id' => $learner_task_submission_id, 'attempt_id' => $attempt_id]);

$name = get_string('assign_observer:page_title', 'observation', $task->get_formatted_name());
$PAGE->set_title($name);
$PAGE->set_heading($name);

$PAGE->add_body_class('observation-request');

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

if (!$observation->is_activity_available())
{
    // should not have gotten here in the first place
    throw new moodle_exception(lib::get_activity_timing_error_string($observation), \OBSERVATION);
}

function email_observer(observer_base $observer, array $lang_data): bool
{
    return lib::email_external(
        $observer->get_email_or_null(),
        get_string('email:observer_assigned_subject', OBSERVATION, $lang_data),
        (!empty($message)
            ? get_string('email:observer_assigned_body_with_user_message', OBSERVATION, $lang_data)
            : get_string('email:observer_assigned_body', OBSERVATION, $lang_data)));
}

// is confirming observer change?
if (optional_param('confirm', 0, PARAM_BOOL))
{
    // we're replacing existing observer with new one,
    // user has already confirmed the change
    $submitted = new observer_base();
    $submitted->set(observer::COL_FULLNAME, required_param(observer::COL_FULLNAME, PARAM_TEXT));
    $submitted->set(observer::COL_PHONE, required_param(observer::COL_PHONE, PARAM_TEXT));
    $submitted->set(observer::COL_EMAIL, required_param(observer::COL_EMAIL, PARAM_TEXT));
    $submitted->set(observer::COL_POSITION_TITLE, required_param(observer::COL_POSITION_TITLE, PARAM_TEXT));
    $message = required_param('message', PARAM_TEXT);

    $observer = observer::update_or_create($submitted);

    $explanation = required_param('user_input', PARAM_TEXT);
    $assignment = $task_submission->assign_observer($observer, $explanation);

    // submit task submission and attempt
    $attempt->submit($task_submission);
    $task_submission->submit($attempt);

    // send email to assigned observer
    $lang_data = [
        'learner_fullname'  => fullname(\core_user::get_user($task_submission->get_userid())),
        'learner_message'   => $message,
        'observer_fullname' => $observer->get_formatted_name_or_null(),
        'task_name'         => $task->get_formatted_name(),
        'activity_name'     => $observation->get_formatted_name(),
        'activity_url'      => $activity_url,
        'observe_url'       => $assignment->get_review_url(true),
        'course_fullname'   => $course->fullname,
        'course_shortname'  => $course->shortname,
        'course_url'        => new \moodle_url('/course/view.php', ['id' => $course->id]),
    ];
    email_observer($observer, $lang_data);

    redirect(
        $activity_url,
        get_string('notification:observer_assigned_new', 'observation',
            ['task' => $task->get_formatted_name(), 'email' => $submitted->get(observer::COL_EMAIL)]),
        null,
        notification::NOTIFY_SUCCESS);
}

$form = new observation_assign_observer_form();
// is submitting observer form?
if ($data = $form->get_data())
{
    // observer object
    $submitted = new observer_base();
    $submitted->set(observer::COL_FULLNAME, $data->{observer::COL_FULLNAME});
    $submitted->set(observer::COL_PHONE, $data->{observer::COL_PHONE});
    $submitted->set(observer::COL_EMAIL, $data->{observer::COL_EMAIL});
    $submitted->set(observer::COL_POSITION_TITLE, $data->{observer::COL_POSITION_TITLE});

    // check if an assignment already exists
    if ($current_assignment = $task_submission->get_active_observer_assignment_or_null())
    {
        // observer already exists, we need to make some checks.
        // check if submitted observer exists in database OR if submitted observer is NOT same as current one
        $submitted_observer_id = observer::try_get_id_for_observer($submitted);
        $current = $current_assignment->get_observer();

        // is learner attempt submitted?
        if ($task_submission->is_observation_pending())
        {
            // yes, we are re-assigning a new observer - ignore if same
            if ($current->get_id_or_null() == $submitted_observer_id)
            {
                // same observer, notify learner
                redirect(
                    $activity_url,
                    get_string(
                        'notification:observer_assigned_no_change', 'observation',
                        ['task' => $task->get_formatted_name(), 'email' => $current->get(observer::COL_EMAIL)]),
                    null,
                    notification::NOTIFY_WARNING);
            }
        }

        if (empty($submitted_observer_id) || ($submitted_observer_id != $current->get_id_or_null()))
        {
            // we have an existing assignment but a different observer
            // is being assigned (new or existing), confirm change
            $lang_params = [
                'current'       => $current->get_formatted_name_or_null(),
                'current_email' => $current->get_email_or_null(),
                'new'           => $submitted->get_formatted_name_or_null(),
                'new_email'     => $submitted->get_email_or_null(),
                'task'          => $task->get_formatted_name()
            ];

            $renderer->echo_confirmation_page_and_die(
                get_string('assign_observer:confirm_change', 'observation', $lang_params),
                [
                    'confirm'                    => 1,
                    observer::COL_FULLNAME       => $submitted->get(observer::COL_FULLNAME),
                    observer::COL_PHONE          => $submitted->get(observer::COL_PHONE),
                    observer::COL_EMAIL          => $submitted->get(observer::COL_EMAIL),
                    observer::COL_POSITION_TITLE => $submitted->get(observer::COL_POSITION_TITLE),
                    'message'                    => $data->message
                ],
                true,
                get_string('assign_observer:input_prompt', 'observation')
            );
            // dies here
        }
    }
    // no observer assignment OR submitted observer is the same as currently assigned observer

    // attempt can be in a submitted state already, check
    if (!$attempt->is_submitted())
    {
        // submit task submission and attempt
        $attempt->submit($task_submission);
        $task_submission->submit($attempt);
    }
    else
    {
        // 'denied observation' scenario where a submission has already been made
        if ($latest_assignment = $task_submission->get_latest_observer_assignment_or_null())
        {
            if (!$latest_assignment->is_declined())
            {
                throw new coding_exception(
                    sprintf(
                        'Attempt id %d has not been submitted when observation request was made',
                        $attempt->get_id_or_null()));
            }
        }
        else
        {
            throw new coding_exception(
                sprintf(
                    'No observer assignment exists for already submitted attempt with id %d',
                    $attempt->get_id_or_null()));
        }
    }

    // create/update observer record
    $observer = observer::update_or_create($submitted);
    // assign observer to this submission
    $assignment = $task_submission->assign_observer($observer);

    // send email to assigned observer
    $lang_data = [
        'learner_fullname'  => fullname(\core_user::get_user($task_submission->get_userid())),
        'learner_message'   => $data->message,
        'observer_fullname' => $observer->get_formatted_name_or_null(),
        'task_name'         => $task->get_formatted_name(),
        'activity_name'     => $observation->get_formatted_name(),
        'activity_url'      => $activity_url,
        'observe_url'       => $assignment->get_review_url(true),
        'course_fullname'   => $course->fullname,
        'course_shortname'  => $course->shortname,
        'course_url'        => new \moodle_url('/course/view.php', ['id' => $course->id]),
    ];
    email_observer($observer, $lang_data);

    redirect(
        $activity_url,
        get_string(
            'notification:observer_assigned_same', 'observation',
            ['task' => $task->get_formatted_name(), 'email' => $submitted->get(observer::COL_EMAIL)]),
        null,
        notification::NOTIFY_SUCCESS);
}

// not confirming change and not submitting form - display request observation page
echo $OUTPUT->header();

echo $renderer->view_request_observation($task, $task_submission, $attempt);

// Finish the page.
echo $OUTPUT->footer();
