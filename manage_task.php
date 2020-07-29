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

use mod_observation\lib;
use mod_observation\observation;
use mod_observation\observation_base;
use mod_observation\task;
use mod_observation\task_base;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('forms.php');

$cmid = required_param('id', PARAM_INT);
$taskid = optional_param('taskid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, OBSERVATION);
$context = context_module::instance($cm->id);

$observation = new observation_base($cm->instance); // we don't need full instance here
$manage_url = new moodle_url(OBSERVATION_MODULE_PATH . 'manage.php', array('id' => $cm->id));

require_login($course, false, $cm);
require_capability(observation::CAP_MANAGE, $context);

// Print the page header.
$PAGE->set_url(OBSERVATION_MODULE_PATH . 'manage_task.php', array('id' => $cm->id, 'taskid' => $taskid));

if ($delete)
{
    $task = new task_base($taskid);

    $confirm = optional_param('confirm', 0, PARAM_BOOL);
    if (!$confirm)
    {
        /* @var $renderer mod_observation_renderer */
        $renderer = ($PAGE->get_renderer(OBSERVATION));
        $renderer->echo_confirmation_page_and_die(
            get_string('confirm_delete_task', 'observation', $task->get_formatted_name()),
            ['delete' => 1]);
    }
    else
    {
        $task->delete();
        totara_set_notification(
            get_string('deleted_task', \OBSERVATION, $task->get_formatted_name()),
            $manage_url, // <<< REDIRECTION
            ['class' => 'notifysuccess']);
    }
}

$form = new observation_task_form(null, ['cmid' => $cm->id, 'taskid' => $taskid]);
if ($form->is_cancelled())
{
    redirect($manage_url);
}
if ($data = $form->get_data())
{
    // data is being posted
    $task = new task_base();
    $task->set(task::COL_OBSERVATIONID, $observation->get_id_or_null());
    $task->set(task::COL_NAME, $data->{task::COL_NAME});

    if (empty($data->taskid))
    {
        // create
        $task->set($task::COL_SEQUENCE, $task->get_next_sequence_number_in_activity());
        // intro cannot be null
        foreach (task::get_intro_fields() as $intro)
        {
            $task->set($intro, '');
            $task->set("{$intro}_format", 1);
        }

        $task->create();
    }
    else
    {
        $task->set(task::COL_ID, $data->taskid);
        $task->set($task::COL_SEQUENCE, $task->get_current_sequence_number());
    }


    // set intro values
    foreach (task::get_intro_fields() as $intro)
    {
        list($area, $itemid) = lib::get_filearea_and_itemid_for_intro($intro, $task->get_id_or_null());

        $format = "{$intro}_format";
        $text = lib::save_intro((array) $data->{$intro}, $area, $itemid, $context);

        $task->set($intro, $text);
        $task->set($format, $data->{$intro}['format']);
    }

    $task->update();

    redirect($manage_url);
}

if (!empty($taskid))
{
    $task = new task_base($taskid);
    $form->set_data($task->get_moodle_form_data());
}
else
{
    $form->set_data($observation->get_form_defaults_for_new_task());
}

$observation_title = $observation->get_formatted_name();
$actionstr = empty($taskid)
    ? get_string('add_task', \OBSERVATION)
    : get_string('edit_task', \OBSERVATION);
$PAGE->set_title($observation_title);
$PAGE->set_heading(sprintf('%s - %s', $observation_title, $actionstr));

$PAGE->add_body_class('observation-manage-task');

$PAGE->navbar->add(get_string('breadcrumb:manage', \OBSERVATION),
    new moodle_url(OBSERVATION_MODULE_PATH . 'manage.php', array('id' => $cmid)));

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->heading);

// Display
$form->display();

// Finish the page.
echo $OUTPUT->footer();
