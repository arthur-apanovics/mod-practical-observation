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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('locallib.php');

$cmid = required_param('id', PARAM_INT);
$taskid = required_param('taskid', PARAM_INT);
$learnerid = required_param('learnerid', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login($course, false, $cm);
require_capability(observation::CAP_ASSESS, $context);

// TODO: Event

$observation = new observation($cm, $learnerid, $taskid);
$task = $observation->get_task($taskid);
$learner = core_user::get_user($learnerid);

$title = get_string(
    'title:task_assess', \OBSERVATION, [
    'learner_fullname' => fullname($learner),
    'task_name'        => $task->get_formatted_name(),
    'observation_name' => $observation->get_formatted_name()
]);

// Print the page header.
$PAGE->set_url('/mod/observation/assess_task.php', array('id' => $cm->id));
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

$PAGE->add_body_class('observation-task-assess');

$PAGE->navbar->add(
    get_string('breadcrumb:assessing_activity', \OBSERVATION, fullname($learner)),
    new moodle_url(OBSERVATION_MODULE_PATH . 'activity_assess.php', ['id' => $cmid, 'learnerid' => $learnerid]));
$PAGE->navbar->add(get_string('breadcrumb:assessing_task', \OBSERVATION, $task->get_formatted_name()));

// Output starts here.
echo $OUTPUT->header();

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

if (!$observation->is_activity_available())
{
    // remind assessor that activity is unavailable
    \core\notification::warning(lib::get_activity_timing_error_string($observation));
}

echo $renderer->view_task_assessor($observation, $learnerid, $task);

// Finish the page.
echo $OUTPUT->footer();
