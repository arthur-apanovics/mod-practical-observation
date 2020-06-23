<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The only purpose of this file is to redirect users to the activity landing page
 * when they are redirected here from the gradebook.
 * https://docs.moodle.org/dev/Gradebook_API#Functions
 */

use mod_observation\observation;

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');

$cmid = required_param('id', PARAM_INT);
$itemnumber = optional_param('itemnumber', 0, PARAM_INT); // Item number, may be != 0 for activities that allow more than one grade per user
$userid = optional_param('userid', null, PARAM_INT); // Graded user ID (optional)
$itemid = optional_param('itemid', null, PARAM_INT); // Graded user ID (optional)
$gradeid = optional_param('gradeid', null, PARAM_INT); // Graded user ID (optional)

if ($userid && has_capability(observation::CAP_ASSESS, context_module::instance($cmid)))
{
    redirect(
        new moodle_url(OBSERVATION_MODULE_PATH . 'activity_assess.php', ['id' => $cmid, 'learnerid' => $userid]));
}
else
{
    redirect(
        new moodle_url(OBSERVATION_MODULE_PATH . 'view.php', ['id' => $cmid]));
}
