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
 * Observation item completion ajax toggler
 */

use mod_observation\observation;

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_sesskey();

$userid  = required_param('userid', PARAM_INT);
$observationid   = required_param('bid', PARAM_INT);
$topicid = required_param('id', PARAM_INT);

$user   = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
$observation    = $DB->get_record('observation', array('id' => $observationid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
$cm     = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

require_capability('mod/observation:signoff', context_module::instance($cm->id));

if (!$observation->managersignoff)
{
    print_error('manager signoff not enabled for this observation');
}

// Get the observation topic
$sql   = "SELECT t.*, t.id AS topicid
    FROM {observation_topic} t
    WHERE t.observationid = ? AND t.id = ?";
$topic = $DB->get_record_sql($sql, array($observation->id, $topicid), MUST_EXIST);

// Update/delete the signoff record
$topicsignoff               = new stdClass();
$topicsignoff->userid       = $userid;
$topicsignoff->topicid      = $topic->id;
$topicsignoff->timemodified = time();
$topicsignoff->modifiedby   = $USER->id;

if ($currentsignoff = $DB->get_record('observation_topic_signoff', array('userid' => $userid, 'topicid' => $topicid)))
{
    // Update
    $topicsignoff->id        = $currentsignoff->id;
    $topicsignoff->signedoff = !($currentsignoff->signedoff);
    $DB->update_record('observation_topic_signoff', $topicsignoff);
}
else
{
    // Insert
    $topicsignoff->signedoff = 1;
    $topicsignoff->id        = $DB->insert_record('observation_topic_signoff', $topicsignoff);
}

$modifiedstr = observation::get_modifiedstr_user($topicsignoff->timemodified);

$jsonparams = array(
    'topicsignoff' => $topicsignoff,
    'modifiedstr'  => $modifiedstr
);

echo json_encode($jsonparams);
