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

/**
 * Prints a particular instance of observation for the current user.
 *
 */

use mod_observation\lib;
use mod_observation\observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('locallib.php');

$cmid = required_param('id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login($course, true, $cm);

$observation = new observation($cm, $USER->id);
$name = $observation->get_formatted_name();

// Print the page header.
$PAGE->set_url($observation->get_url());
$PAGE->set_title($name);
$PAGE->set_heading(format_string($course->fullname));

$PAGE->add_body_class('observation-view');

// Output starts here.
echo $OUTPUT->header();

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer(\OBSERVATION);

if (!$observation->is_activity_available())
{
    // whoever's viewing needs to know that activity is not available
    \core\notification::warning(lib::get_activity_timing_error_string($observation));
}
else if ($enddate_msg = $observation->get_activity_end_date_or_null())
{
    // activity has an end date - display message
    \core\notification::info($enddate_msg);
}

echo $renderer->view_activity($observation);

// Finish the page.
echo $OUTPUT->footer();
