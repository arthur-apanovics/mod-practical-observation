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
 * OJT evaluation for a user
 */

use mod_ojt\models\completion;
use mod_ojt\models\ojt;
use mod_ojt\user_ojt;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/ojt/lib.php');
require_once($CFG->dirroot . '/mod/ojt/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$userid = required_param('userid', PARAM_INT);
$id     = optional_param('cmid', 0, PARAM_INT); // Course_module ID
$b      = optional_param('bid', 0, PARAM_INT);  // ... ojt instance ID - it should be named as the first character of the module.

$user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

list($ojt, $course, $cm) = ojt_check_page_id_params_and_init($id, $b); /* @var $ojt ojt */

require_login($course, true, $cm);

$modcontext  = context_module::instance($cm->id);
$canevaluate = ojt::can_evaluate($userid, $modcontext);
$cansignoff  = has_capability('mod/ojt:signoff', $modcontext);
$canwitness  = has_capability('mod/ojt:witnessitem', $modcontext);
if (!($canevaluate || $cansignoff || $canwitness))
{
    print_error('accessdenied', 'ojt');
}

$userojt = new user_ojt($ojt, $userid);

// Print the page header.
$PAGE->set_url('/mod/ojt/evaluate.php', array('cmid' => $cm->id, 'userid' => $userid));
$PAGE->set_title(format_string($ojt->name));
$PAGE->set_heading(format_string($ojt->name) . ' - ' . get_string('evaluate', 'ojt'));
if (has_capability('mod/ojt:evaluate', $modcontext) || has_capability('mod/ojt:signoff', $modcontext))
{
    $PAGE->navbar->add(get_string('evaluatestudents', 'ojt'),
        new moodle_url('/mod/ojt/report.php', array('cmid' => $cm->id)));
}
$PAGE->navbar->add(fullname($user));

/* @var $renderer mod_ojt_renderer */
$renderer = $PAGE->get_renderer('ojt');

list($args, $jsmodule) = $renderer->get_evaluation_js_args($ojt->id, $userid);
$PAGE->requires->js_init_call('M.mod_ojt_evaluate.init', $args, false, $jsmodule);

// Output starts here
echo $OUTPUT->header();

echo $renderer->get_print_button($ojt->name, fullname($user));

if ($ojt->intro)
{
    echo $OUTPUT->box(format_module_intro('ojt', $ojt, $cm->id), 'generalbox mod_introbox', 'ojtintro');
}

echo $renderer->user_ojt($userojt, $canevaluate, $cansignoff, $canwitness);

// Finish the page.
echo $OUTPUT->footer();
