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
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Prints a particular instance of ojt for the current user.
 *
 */

use mod_ojt\event\course_module_viewed;
use mod_ojt\models\ojt;
use mod_ojt\user_ojt;
use mod_ojt\user_topic;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/ojt/lib.php');
require_once($CFG->dirroot . '/mod/ojt/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$id      = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$b       = optional_param('n', 0, PARAM_INT);  // ojt instance ID.
$topicid = optional_param('topic', 0, PARAM_INT); // Topic id

list($ojt, $course, $cm) = ojt_check_page_id_params_and_init($id, $b); /* @var $ojt ojt */

require_login($course, true, $cm);

// TODO topic viewed event?
// $event =  course_module_viewed::create(array(
//     'objectid' => $PAGE->cm->instance,
//     'context'  => $PAGE->context,
// ));
// $event->add_record_snapshot('course', $PAGE->course);
// $event->add_record_snapshot($PAGE->cm->modname, $ojt->get_record_from_object());
// $event->trigger();

$topic = user_topic::get_user_topic($topicid, $USER->id);

// Print the page header.
$PAGE->set_url('/mod/ojt/viewtopic.php', array('id' => $cm->id, 'topic' => $topicid));
$PAGE->set_title(format_string( "$topic->name - $ojt->name"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($topic->name);

// Check access - we're assuming only $USER access on this page
$modcontext  = context_module::instance($cm->id);
$canevaluate = has_capability('mod/ojt:evaluate', $modcontext);
$canevalself = has_capability('mod/ojt:evaluateself', $modcontext);
$cansignoff  = has_capability('mod/ojt:signoff', $modcontext);
$canmanage   = has_capability('mod/ojt:manage', $modcontext);

if ($canevalself && !($canevaluate || $cansignoff))
{
    // Seeing as the user can only self-evaluate, but nothing else, redirect them straight to the eval page
    redirect(new moodle_url($CFG->wwwroot . '/mod/ojt/evaluate.php',
        array('userid' => $USER->id, 'bid' => $ojt->id)));
}

// Output starts here.
echo $OUTPUT->header();

// Manage topics button.
if ($canmanage)
{
    echo html_writer::start_tag('div', array('class' => 'mod-ojt-manage-btn'));
    echo $OUTPUT->single_button(new moodle_url('/mod/ojt/manage.php', array('cmid' => $cm->id)),
        get_string('edittopics', 'ojt'), 'get');
    echo html_writer::end_tag('div');
}

// "Evaluate students" button
if (($canevaluate || $cansignoff))
{
    echo html_writer::start_tag('div', array('class' => 'mod-ojt-evalstudents-btn'));
    echo $OUTPUT->single_button(new moodle_url('/mod/ojt/report.php', array('cmid' => $cm->id)),
        get_string('evaluatestudents', 'ojt'), 'get');
    echo html_writer::end_tag('div');
}

$userojt = new user_ojt($ojt, $USER->id);

// "Evaluate self" button
if ($canevalself)
{
    echo html_writer::start_tag('div', array('class' => 'mod-ojt-evalself-btn'));
    echo $OUTPUT->single_button(new moodle_url('/mod/ojt/evaluate.php',
        array('userid' => $USER->id, 'bid' => $userojt->id)),
        get_string('evaluate', 'ojt'), 'get');
    echo html_writer::end_tag('div');
}

// Replace the following lines with you own code.
echo $OUTPUT->heading(format_string($ojt->name));

echo $OUTPUT->box('', 'generalbox', 'ojt-padding-box', ['style' => 'height: 2em;']);

/* @var $renderer mod_ojt_renderer */
$renderer = $PAGE->get_renderer('ojt');

echo $renderer->user_topic($userojt, $topic);

// Finish the page.
echo $OUTPUT->footer();
