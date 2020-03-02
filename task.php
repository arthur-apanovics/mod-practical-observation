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

use mod_observation\event\course_module_viewed;
use mod_observation\observation;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$cmid = required_param('id', PARAM_INT);
$taskid = required_param('taskid', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login($course, true, $cm);

// TODO: Event

$observation = new observation($cm);

// Print the page header.
$PAGE->set_url('/mod/observation/task.php', array('id' => $cm->id));
$PAGE->set_title($observation->get_formatted_name());
$PAGE->set_heading(format_string($course->fullname));

$PAGE->add_body_class('observation-task');

// Output starts here.
echo $OUTPUT->header();

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

// check if learner
echo $renderer->task_learner_view($observation, $taskid);

// todo check if observer

// todo check if assessor

// Finish the page.
echo $OUTPUT->footer();
