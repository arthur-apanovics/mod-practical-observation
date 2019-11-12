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
 * OJT item completion ajax toggler
 */

use mod_ojt\models\attempt;
use mod_ojt\models\ojt;
use mod_ojt\models\topic_item;

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/ojt/lib.php');
require_once($CFG->dirroot . '/mod/ojt/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$userid      = required_param('userid', PARAM_INT);
$ojtid       = required_param('bid', PARAM_INT);
$topicitemid = required_param('id', PARAM_INT);
$attempttext = required_param('attempttext', PARAM_TEXT);

$ojt    = new ojt($ojtid);
$course = $DB->get_record('course', array('id' => $ojt->course), '*', MUST_EXIST);
$cm     = get_coursemodule_from_instance('ojt', $ojt->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);
if (!ojt::can_evaluate($userid, context_module::instance($cm->id)))
{
    print_error('access denied');
}

$topic_item = new topic_item($topicitemid);
$user       = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');

// Update/insert attempt
$attempt = attempt::get_latest_user_attempt($topicitemid, $userid);
if ($attempt->id)
{
    // Update
    $attempt->text         = $attempttext;
    $attempt->timemodified = time();
    $attempt->update();
}
else
{
    // Insert
    $attempt               = new attempt();
    $attempt->topicitemid  = $topic_item->id;
    $attempt->userid       = $user->id;
    $attempt->text         = $attempttext;
    $attempt->timemodified = time();
    $attempt->id           = $attempt->create();
}

$jsonparams = array(
    'attempt' => $attempt,
);

echo json_encode($jsonparams);
