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

use mod_observation\observation;
use mod_observation\observation_base;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('forms.php');

$cmid = required_param('id', PARAM_INT);
$taskid = optional_param('task', 0, PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, OBSERVATION);
$context = context_module::instance($cm->id);

$observation = new observation_base($cm->instance);
$manage_url = new moodle_url(OBSERVATION_MODULE_PATH . 'manage.php', array('id' => $cm->id));

require_login($course, false, $cm);
require_capability(observation::CAP_MANAGE, $context);

// TODO: Event

// Print the page header.
$PAGE->set_url(OBSERVATION_MODULE_PATH . 'manage_task.php', array('id' => $cm->id, 'task' => $taskid));

// todo delete action

$form = new observation_task_form(null, ['id' => $cmid, 'task' => $taskid]);
if ($form->is_cancelled())
{
    redirect($manage_url);
}
if ($data = $form->get_data())
{
    // data is being posted
    if (empty($data->id))
    {
        // create
    }
    else
    {
        // update

    }

    redirect($manage_url);
}

if (!empty($taskid))
{
    $task = new task($taskid);
    $form->set_data($task->to_record());
}

$observation_title = $observation->get_formatted_name();
$actionstr = empty($topicid)
    ? get_string('addtask', \OBSERVATION)
    : get_string('edittask', \OBSERVATION);
$PAGE->set_title($observation_title);
$PAGE->set_heading(sprintf('%s - %s', $observation_title, $actionstr));

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->heading);

// Display
$form->display();

// Finish the page.
echo $OUTPUT->footer();
