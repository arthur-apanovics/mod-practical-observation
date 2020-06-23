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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');

$cmid = required_param('id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, OBSERVATION);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability(observation::CAP_MANAGE, $context);

// do not filter observation by userid as we need to check if submissions exist
$observation = new observation($cm);

$title = $title = get_string('title:manage', \OBSERVATION, $observation->get_formatted_name());

// Print the page header.
$PAGE->set_url(OBSERVATION_MODULE_PATH . 'manage.php', array('id' => $cm->id));
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));

$PAGE->add_body_class('observation-manage');

$PAGE->navbar->add(get_string('breadcrumb:manage', \OBSERVATION));

$PAGE->requires->js_call_amd(OBSERVATION_MODULE . '/developer_view', 'init');

// Output starts here.
echo $OUTPUT->header();

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer(\OBSERVATION);

echo $renderer->view_manage($observation);

// Finish the page.
echo $OUTPUT->footer();
