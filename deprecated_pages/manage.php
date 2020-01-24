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

use mod_observation\observation_base;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('cmid', 0, PARAM_INT); // Course_module ID
$b  = optional_param('b', 0, PARAM_INT);  // Observation instance ID

list($observation, $course, $cm) = observation_check_page_id_params_and_init($id, $b); /* @var $observation observation_base */

require_login($course, true, $cm);
require_capability('mod/observation:manage', context_module::instance($cm->id));

// Print the page header.
$PAGE->set_url('/mod/observation/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($observation->name) . ' - ' . get_string('manage', 'observation'));

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading($PAGE->heading);

$addtopicurl = new moodle_url('/mod/observation/topic.php', array('bid' => $observation->id));
echo html_writer::tag('div', $OUTPUT->single_button($addtopicurl, get_string('addtopic', 'observation')),
    array('class' => 'mod-observation-topic-addbtn'));

$topics   = $DB->get_records('observation_topic', array('observationid' => $observation->id));
/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('mod_observation');
echo $renderer->config_topics($observation);

// Finish the page.
echo $OUTPUT->footer();
