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
 * Observation evaluation for a user
 */

use mod_observation\completion;
use mod_observation\observation_base;
use mod_observation\user_observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$userid = required_param('userid', PARAM_INT);
$id     = optional_param('cmid', 0, PARAM_INT); // Course_module ID
$b      = optional_param('bid', 0, PARAM_INT);  // ... observation instance ID - it should be named as the first character of the module.

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

list($observation, $course, $cm) = observation_check_page_id_params_and_init($id, $b); /* @var $observation observation_base */

require_login($course, true, $cm);

$modcontext  = context_module::instance($cm->id);
$canevaluate = observation_base::can_evaluate($userid, $modcontext);
$cansignoff  = has_capability('mod/observation:signoff', $modcontext);
$canwitness  = has_capability('mod/observation:witnessitem', $modcontext);
if (!($canevaluate || $cansignoff || $canwitness))
{
    print_error('accessdenied', 'observation');
}

$userobservation = new user_observation($observation, $userid);

// Print the page header.
$PAGE->set_url('/mod/observation/evaluate.php', array('cmid' => $cm->id, 'userid' => $userid));
$PAGE->set_title(format_string($observation->name));
$PAGE->set_heading(format_string($observation->name) . ' - ' . get_string('evaluate', 'observation'));
if (has_capability('mod/observation:evaluate', $modcontext) || has_capability('mod/observation:signoff', $modcontext))
{
    $PAGE->navbar->add(get_string('evaluatestudents', 'observation'),
        new moodle_url('/mod/observation/report.php', array('cmid' => $cm->id)));
}
$PAGE->navbar->add(fullname($user));

/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

list($args, $jsmodule) = $renderer->get_evaluation_js_args($observation->id, $userid);
$PAGE->requires->js_init_call('M.mod_observation_evaluate.init', $args, false, $jsmodule);

// Output starts here
echo $OUTPUT->header();

echo $renderer->get_print_button($observation->name, fullname($user));

if ($observation->intro)
{
    echo $OUTPUT->box(format_module_intro('observation', $observation, $cm->id), 'generalbox mod_introbox', 'observationintro');
}

echo $renderer->user_observation($userobservation, $canevaluate, $cansignoff, $canwitness);

// Finish the page.
echo $OUTPUT->footer();
