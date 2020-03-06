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

use mod_observation\learner_submission;
use mod_observation\observer_assignment;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$token = required_param('token', PARAM_ALPHANUM);
$observer_assignment = observer_assignment::read_by_token_or_null($token, true);

if (is_null($observer_assignment))
{
    print_error(get_string('error:invalid_token', 'observation'));
}
else if (!$observer_assignment->is_active())
{
    print_error(get_string('error:not_active_observer', 'observation'));
}
else if ($observer_assignment->is_observation_complete())
{
    print_error(get_string('error:observation_complete', 'observation'));
}

//Check id a user is trying to observe himself somehow or observer has account in lms
if (isloggedin())
{
    $learner_submission = $observer_assignment->get_learner_submission_base();
    if ($learner_submission->get_userid() == $USER->id)
    {
        print_error('You cannot observe yourself!');

        //TODO notify assessor of self observation attempt
    }
    else if ($USER->email != $observer_assignment->get_observer()->get_email())
    {
        //TODO notify admins
        // has account in lms, is logged in, is not one being observed, is not assigned observer (based on email)
    }
}

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
    // TODO: declined observation
    $observer_assignment->decline();
}

// TODO: Event

// Print the page header.
$name = 'TODO'; // TODO name name name name name name name name name name name
$PAGE->set_context(null);
$PAGE->set_url(OBSERVATION_MODULE_PATH . 'observe.php', array('token' => $token));
$PAGE->set_title($name);
$PAGE->set_heading($name);
$PAGE->set_pagelayout('popup');

$PAGE->add_body_class('observation-observe');

// Output starts here.
/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

echo $OUTPUT->header();

if (!$observer_assignment->is_accepted())
{
    // show observation EULA page
    echo $renderer->observer_landing_view($observer_assignment);
}
else
{
    // This is a hack to get around authenticating anonymous users when viewing files in observation.
    unset($SESSION->observation_usertoken);
    $SESSION->observation_usertoken = $token;

    // show observation page
    echo $renderer->task_observer_view($observer_assignment);
}

// Finish the page.
echo $OUTPUT->footer();
