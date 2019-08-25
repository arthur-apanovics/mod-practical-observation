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

use mod_ojt\models\ojt;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('cmid', 0, PARAM_INT); // Course_module ID
$b  = optional_param('b', 0, PARAM_INT);  // OJT instance ID

list($ojt, $course, $cm) = ojt_check_page_id_params_and_init($id, $b); /* @var $ojt ojt */

require_login($course, true, $cm);
require_capability('mod/ojt:manage', context_module::instance($cm->id));

// Print the page header.
$PAGE->set_url('/mod/ojt/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($ojt->name) . ' - ' . get_string('manage', 'ojt'));

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading($PAGE->heading);

$addtopicurl = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id));
echo html_writer::tag('div', $OUTPUT->single_button($addtopicurl, get_string('addtopic', 'ojt')),
    array('class' => 'mod-ojt-topic-addbtn'));

$topics   = $DB->get_records('ojt_topic', array('ojtid' => $ojt->id));
/* @var $renderer mod_ojt_renderer */
$renderer = $PAGE->get_renderer('mod_ojt');
echo $renderer->config_topics($ojt);

// Finish the page.
echo $OUTPUT->footer();
