<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
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
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Prints a particular instance of observation for the current user.
 *
 */

use mod_observation\event\course_module_viewed;
use mod_observation\observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$cmid = optional_param('id', 0, PARAM_INT); // instance id

list($course, $cm) = get_course_and_cm_from_cmid($cmid);

require_login($course, true, $cm);

// TODO: Event
// $event = course_module_viewed::create(array(
//     'objectid' => $PAGE->cm->instance,
//     'context'  => $PAGE->context,
// ));
// $event->add_record_snapshot('course', $PAGE->course);
// $event->add_record_snapshot($PAGE->cm->modname, $observation->get_record_from_object());
// $event->trigger();

$observation = new observation($cm);
$name = $observation->get_formatted_name();

// Print the page header.
$PAGE->set_url('/mod/observation/view.php', array('id' => $cm->id));
$PAGE->set_title($name);
$PAGE->set_heading(format_string($course->fullname));

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($name);

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

$renderer->activity_view($observation);

// Finish the page.
echo $OUTPUT->footer();
