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
use mod_observation\observation_base;
use mod_observation\user_observation;
use mod_observation\user_topic;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$id      = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$b       = optional_param('n', 0, PARAM_INT);  // observation instance ID.
$topicid = optional_param('topic', 0, PARAM_INT); // Topic id

list($observation, $course, $cm) = observation_check_page_id_params_and_init($id, $b); /* @var $observation observation_base */

require_login($course, true, $cm);

// TODO topic viewed event?
// $event =  course_module_viewed::create(array(
//     'objectid' => $PAGE->cm->instance,
//     'context'  => $PAGE->context,
// ));
// $event->add_record_snapshot('course', $PAGE->course);
// $event->add_record_snapshot($PAGE->cm->modname, $observation->to_record());
// $event->trigger();

$topic = user_topic::get_user_topic($topicid, $USER->id);

// Print the page header.
$PAGE->set_url('/mod/observation/viewtopic.php', array('id' => $cm->id, 'topic' => $topicid));
$PAGE->set_title(format_string( "$topic->name - $observation->name"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($topic->name);

// Check access - we're assuming only $USER access on this page
$modcontext  = context_module::instance($cm->id);
$canevaluate = has_capability('mod/observation:evaluate', $modcontext);
$canevalself = has_capability('mod/observation:evaluateself', $modcontext);
$cansignoff  = has_capability('mod/observation:signoff', $modcontext);
$canmanage   = has_capability('mod/observation:manage', $modcontext);

if ($canevalself && !($canevaluate || $cansignoff))
{
    // Seeing as the user can only self-evaluate, but nothing else, redirect them straight to the eval page
    redirect(new moodle_url($CFG->wwwroot . '/mod/observation/evaluate.php',
        array('userid' => $USER->id, 'bid' => $observation->id)));
}

// Output starts here.
echo $OUTPUT->header();

// Manage topics button.
if ($canmanage)
{
    echo html_writer::start_tag('div', array('class' => 'mod-observation-manage-btn'));
    echo $OUTPUT->single_button(new moodle_url('/mod/observation/manage.php', array('cmid' => $cm->id)),
        get_string('edittopics', 'observation'), 'get');
    echo html_writer::end_tag('div');
}

// "Evaluate students" button
if (($canevaluate || $cansignoff))
{
    echo html_writer::start_tag('div', array('class' => 'mod-observation-evalstudents-btn'));
    echo $OUTPUT->single_button(new moodle_url('/mod/observation/report.php', array('cmid' => $cm->id)),
        get_string('evaluatestudents', 'observation'), 'get');
    echo html_writer::end_tag('div');
}

$userobservation = new user_observation($observation, $USER->id);

// "Evaluate self" button
if ($canevalself)
{
    echo html_writer::start_tag('div', array('class' => 'mod-observation-evalself-btn'));
    echo $OUTPUT->single_button(new moodle_url('/mod/observation/evaluate.php',
        array('userid' => $USER->id, 'bid' => $userobservation->id)),
        get_string('evaluate', 'observation'), 'get');
    echo html_writer::end_tag('div');
}

// Replace the following lines with you own code.
echo $OUTPUT->heading(format_string($observation->name));

echo $OUTPUT->box('', 'generalbox', 'observation-padding-box', ['style' => 'height: 2em;']);

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

echo $renderer->user_topic($userobservation, $topic);

// Finish the page.
echo $OUTPUT->footer();
