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
use mod_observation\observation_base;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('locallib.php');

$cmid = required_param('id', PARAM_INT);
$taskid = required_param('taskid', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login($course, true, $cm);

$observation_base = new observation_base($cm->instance);
$task = new task($taskid, $USER->id);

$title = get_string(
    'title:task', \OBSERVATION, [
    'task_name'        => $task->get_formatted_name(),
    'observation_name' => $observation_base->get_formatted_name()
]);

// Print the page header.
$PAGE->set_url(OBSERVATION_MODULE_PATH . 'task.php', array('id' => $cm->id, 'taskid' => $taskid));
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

$PAGE->add_body_class('observation-task');

$PAGE->navbar->add(get_string('breadcrumb:task', \OBSERVATION, $task->get_formatted_name()));

// Output starts here.
echo $OUTPUT->header();

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer(\OBSERVATION);

if (!$observation_base->is_activity_available())
{
    // activity closed, let learner know
    \core\notification::error(lib::get_activity_timing_error_string($observation_base));
}

echo $renderer->view_task_learner($observation_base, $task);

// Finish the page.
echo $OUTPUT->footer();
