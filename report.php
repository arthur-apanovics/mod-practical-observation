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
 * Prints a particular instance of observation
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$cmid   = optional_param('cmid', 0, PARAM_INT); // Course_module ID
$observationid  = optional_param('bid', 0,
    PARAM_INT);  // ... observation instance ID - it should be named as the first character of the module.
$format = optional_param('format', '', PARAM_TEXT);
$sid    = optional_param('sid', '0', PARAM_INT);
$debug  = optional_param('debug', 0, PARAM_INT);

if ($cmid)
{
    $cm     = get_coursemodule_from_id('observation', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $observation    = $DB->get_record('observation', array('id' => $cm->instance), '*', MUST_EXIST);
}
else if ($observationid)
{
    $observation    = $DB->get_record('observation', array('id' => $observationid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
    $cm     = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);
}
else
{
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$modcontext = context_module::instance($cm->id);
if (!(has_capability('mod/observation:evaluate', $modcontext) || has_capability('mod/observation:signoff', $modcontext)))
{
    print_error('accessdenied', 'observation');
}

if (!$report = reportbuilder_get_embedded_report('observation_evaluation', array('observationid' => $observation->id), false, $sid))
{
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

$PAGE->set_url('/mod/observation/report.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($observation->name));
$headingstr = format_string($observation->name) . ' - ' . get_string('evaluate', 'observation');
$PAGE->set_heading($headingstr);


$renderer = $PAGE->get_renderer('totara_reportbuilder');

if ($format != '')
{
    $report->export_data($format);
    die;
}

$report->include_js();

echo $OUTPUT->header();

echo $OUTPUT->heading($headingstr);

// Standard report stuff.
echo $OUTPUT->container_start('', 'observation_evaluation');

if ($debug)
{
    $report->debug($debug);
}
echo $renderer->print_description($report->description, $report->_id);

$report->display_search();
$report->display_sidebar_search();

$report->display_table();

// Export button.
$renderer->export_select($report, $sid);

echo $OUTPUT->container_end();

echo $OUTPUT->footer();

