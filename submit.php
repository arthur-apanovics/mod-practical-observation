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

use mod_observation\observation;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

// $cmid = required_param('id', PARAM_INT);
// $taskid = required_param('taskid', PARAM_INT);
$learner_submissionid = optional_param('lsid', null, PARAM_INT);
$observer_submissionid = optional_param('osid', null, PARAM_INT);
$assessor_submissionid = optional_param('asid', null, PARAM_INT);

// list($course, $cm) = get_course_and_cm_from_cmid($cmid);
// $context = context_module::instance($cmid);

require_login();

// TODO: Events

if ($learner_submissionid)
{
    required_param('learner_attempt_id', PARAM_INT);
}
else if ($observer_submissionid)
{
    required_param('observer_feedback_id', PARAM_INT);
}
else if ($assessor_submissionid)
{
    required_param('assessor_feedback_id', PARAM_INT);
}
else
{
    throw new coding_exception('No submission id provided, cannot proceed with submission!');
}

// $PAGE->set_url('/mod/observation/submit.php', array('id' => $cm->id));
