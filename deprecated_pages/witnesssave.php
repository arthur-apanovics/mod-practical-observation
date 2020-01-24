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
 * Observation witness ajax toggler
 */

use mod_observation\email_assignment;
use mod_observation\item_witness;
use mod_observation\observation_base;
use mod_observation\topic;
use mod_observation\user_topic_item;

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_sesskey();

$userid      = required_param('userid', PARAM_INT);
$observationid       = required_param('bid', PARAM_INT);
$topicitemid = required_param('id', PARAM_INT);
$token       = optional_param('token', '', PARAM_ALPHANUM);

$external_user = $token !== '';

if (!email_assignment::is_valid_token($observationid, $userid, $token))
{
    print_error('accessdenied', 'observation');
}
if (!$external_user)
{
    require_login($course, true, $cm);
    require_capability('mod/observation:witnessitem', context_module::instance($cm->id));
}
if (!$observation->itemwitness)
{
    print_error('itemwitness disabled for this observation');
}

$course     = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);
$topicitem  = new user_topic_item($topicitemid, $userid);
$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');

// Update/insert the user completion record
$transaction = $DB->start_delegated_transaction();
$params      = array(
    'userid'      => $userid,
    'topicitemid' => $topicitemid
);

if (!is_null($topicitem->witness))
{
    // Update
    $removewitness                     = !empty($topicitem->witness->witnessedby);
    $topicitem->witness->witnessedby   = $removewitness ? 0 : $USER->id;
    $topicitem->witness->timewitnessed = $removewitness ? 0 : time();
    $topicitem->witness->update();
}
else
{
    // Insert
    $witness                = new item_witness();
    $witness->userid        = $userid;
    $witness->topicitemid   = $topicitemid;
    $witness->witnessedby   = $USER->id;
    $witness->timewitnessed = time();
    $witness->id            = $witness->create();

    $topicitem->witness = $witness;
}

// Update topic completion
$topiccompletion = topic::update_topic_completion($userid, $observationid, $topicitem->topicid);

$transaction->allow_commit();

$modifiedstr = observation_base::get_modifiedstr_user($topicitem->witness->timewitnessed);

$jsonparams = array(
    'item'        => $topicitem->witness,
    'modifiedstr' => $modifiedstr,
    'topic'       => $topiccompletion
);

echo json_encode($jsonparams);
