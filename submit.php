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

use mod_observation\learner_attempt;
use mod_observation\learner_attempt_base;
use mod_observation\learner_submission_base;
use mod_observation\lib;
use mod_observation\observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once($CFG->libdir.'/filelib.php');

$cmid = required_param('cmid', PARAM_INT);
$learner_submissionid = optional_param('learner_submission_id', null, PARAM_INT);
$observer_submissionid = optional_param('observer_submission_id', null, PARAM_INT);
$assessor_submissionid = optional_param('assessor_submission_id', null, PARAM_INT);

// list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);

require_login();

// TODO: Events

if ($learner_submissionid)
{
    $attempt_id = required_param('attempt_id', PARAM_INT);

    // check id's are correct
    $learner_submission = new learner_submission_base($learner_submissionid);
    $attempt = new learner_attempt_base($attempt_id);

    // get text
    $text_field = lib::get_input_field_name_from_class(learner_attempt::class);
    $attempt_text = required_param($text_field, PARAM_RAW);
    $attempt_text_format = required_param("{$text_field}_format", PARAM_INT);
    // get files
    $draft_itemid = required_param('attachments_itemid', PARAM_INT);

    // update attempt
    $attempt->set(learner_attempt::COL_TEXT, $attempt_text);
    $attempt->set(learner_attempt::COL_TEXT_FORMAT, $attempt_text_format);
    $attempt->update();

    // save files
    file_save_draft_area_files(
        $draft_itemid,
        $context->id,
        \OBSERVATION,
        observation::FILE_AREA_TRAINEE,
        $attempt->get_id_or_null());

    redirect(
        new moodle_url(
            OBSERVATION_MODULE_PATH . 'request.php',
            ['cmid' => $cmid, 'learner_submission_id' => $learner_submission->get_id_or_null()]));
}
else if ($observer_submissionid)
{
    required_param('observer_feedback_id', PARAM_INT);
}
else if ($assessor_submissionid)
{
    required_param('assessor_feedback_id', PARAM_INT);
}
else
{
    throw new coding_exception('No submission id provided, cannot proceed with submission!');
}

// $PAGE->set_url('/mod/observation/submit.php', array('id' => $cm->id));
