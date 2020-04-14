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
use mod_observation\learner_submission;
use mod_observation\observer;
use mod_observation\observer_base;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('forms.php');

$cmid = required_param('id', PARAM_INT);
$learner_submission_id = required_param('learner_submission_id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login($course, true, $cm);

// TODO: Event

$learner_submission = new learner_submission($learner_submission_id);
$task_id = $learner_submission->get($learner_submission::COL_TASKID);
$task = new task($task_id, $USER->id);
$name = get_string('assign_observer:page_title', 'observation', $task->get_formatted_name());

$activity_url = new moodle_url(OBSERVATION_MODULE_PATH . 'view.php', ['id' => $cmid]);

// Print the page header.
$PAGE->set_url(
    OBSERVATION_MODULE_PATH . 'request.php',
    ['id' => $cm->id, 'learner_submission_id' => $learner_submission_id]);
$PAGE->set_title($name);
$PAGE->set_heading('wazaaaa');

$PAGE->add_body_class('observation-request');

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

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
    $learner_submission->assign_observer($observer, $message, $explanation);

    redirect(
        $activity_url,
        get_string(
            'notification:observer_assigned_new', 'observation',
            ['task' => $task->get_formatted_name(), 'email' => $submitted->get(observer::COL_EMAIL)]),
        null,
        notification::NOTIFY_SUCCESS);
}

$form = new observation_assign_observer_form();
if ($data = $form->get_data())
{
    // observer object
    $submitted = new observer_base();
    $submitted->set(observer::COL_FULLNAME, $data->{observer::COL_FULLNAME});
    $submitted->set(observer::COL_PHONE, $data->{observer::COL_PHONE});
    $submitted->set(observer::COL_EMAIL, $data->{observer::COL_EMAIL});
    $submitted->set(observer::COL_POSITION_TITLE, $data->{observer::COL_POSITION_TITLE});

    $id = observer_base::try_get_id_for_observer($submitted);

    // check if an assignment already exists
    if ($current_assignment = $learner_submission->get_active_observer_assignment_or_null())
    {
        // observer already exists, we need to make some checks.
        // check if submitted observer exists in database OR if submitted observer is NOT same as current one
        $submitted_observer_id = observer::try_get_id_for_observer($submitted);
        $current = $current_assignment->get_observer();
        if (empty($submitted_observer_id) || ($submitted_observer_id != $current->get_id_or_null()))
        {
            // we have an existing assignment but a different observer
            // is being assigned (new or existing), confirm change
            $lang_params = [
                'current' => $current->get_formatted_name(),
                'new'     => $submitted->get_formatted_name(),
                'task'    => $task->get_formatted_name()
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
        else if ($submitted_observer_id == $current->get_id_or_null())
        {
            // assignment exists and it's for the same observer, nothing to do here
            redirect(
                $activity_url,
                get_string(
                    'notification:observer_assigned_no_change', 'observation',
                    ['task' => $task->get_formatted_name(), 'email' => $submitted->get(observer::COL_EMAIL)]),
                null,
                notification::NOTIFY_WARNING);
        }
    }

    //  no observer assignment OR submitted observer is the same as currently assigned observer
    $observer = observer::update_or_create($submitted);
    // assign observer to this submission
    $learner_submission->assign_observer($observer, $data->message);

    redirect(
        $activity_url,
        get_string(
            'notification:observer_assigned_same', 'observation',
            ['task' => $task->get_formatted_name(), 'email' => $submitted->get(observer::COL_EMAIL)]),
        null,
        notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo $renderer->view_request_observation($task, $learner_submission);

// Finish the page.
echo $OUTPUT->footer();
