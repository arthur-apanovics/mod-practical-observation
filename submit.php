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

use mod_observation\criteria;
use mod_observation\criteria_base;
use mod_observation\learner_attempt;
use mod_observation\learner_attempt_base;
use mod_observation\learner_submission_base;
use mod_observation\lib;
use mod_observation\observation;
use mod_observation\observer_assignment;
use mod_observation\observer_feedback;
use mod_observation\observer_submission;
use mod_observation\observer_submission_base;
use mod_observation\task_base;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once($CFG->libdir . '/filelib.php');

// PROCESSES SUBMISSIONS FROM LEARNER, OBSERVER & ASSESSOR

$cmid = required_param('cmid', PARAM_INT);

$context = context_module::instance($cmid);

// TODO: Events

if ($learner_submissionid = optional_param('learner_submission_id', null, PARAM_INT))
{
    require_login();

    $attempt_id = required_param('attempt_id', PARAM_INT);

    // check id's are correct
    $learner_submission = new learner_submission_base($learner_submissionid);
    $attempt = new learner_attempt_base($attempt_id);

    // get editor content
    list($input_base) = lib::get_editor_attributes_for_class(learner_attempt::class);
    $attempt_editor = required_param_array($input_base, PARAM_RAW);

    // get files
    $draft_itemid = required_param('attachments_itemid', PARAM_INT);

    // update attempt
    $attempt->set(learner_attempt::COL_TEXT, $attempt_editor['text']);
    $attempt->set(learner_attempt::COL_TEXT_FORMAT, $attempt_editor['format']);
    $attempt->set(learner_attempt::COL_TIMESUBMITTED, time());
    $attempt->update();

    // save files
    lib::save_files(
        $draft_itemid, $context->id, observation::FILE_AREA_TRAINEE, $attempt->get_id_or_null());

    $learner_submission->submit($attempt);

    redirect(
        new moodle_url(
            OBSERVATION_MODULE_PATH . 'request.php',
            ['id' => $cmid, 'learner_submission_id' => $learner_submission->get_id_or_null()]));
}
/* ================================================================================================================== */
else if ($observer_submissionid = optional_param('observer_submission_id', null, PARAM_INT))
{
    // gather data from post request
    $observer_submission_base = new observer_submission_base($observer_submissionid);
    $observations = lib::required_param_array('criteria', PARAM_RAW);

    if ($observer_submission_base->is_complete())
    {
        throw new coding_exception('Observation already complete');
    }

    // validate observation count
    $first_criteria = new criteria_base(key($observations)); // needed to get task id
    $task = new task_base($first_criteria->get(criteria::COL_TASKID));
    $criteria_count = $task->get_criteria_count();
    if (count($observations) !== $criteria_count)
    {
        throw new coding_exception('Submitted feedback count does not match total criteria count in task');
    }
    unset($first_criteria, $task); // no longer needed (we still need criteria_count though)

    // needed to extract text and format from editor
    list($feedback_editor_base) = lib::get_editor_attributes_for_class(observer_feedback::class);
    $completed_count = 0;
    foreach ($observations as $criteria_id => $observation_outcome)
    {
        $criteria_base = new criteria_base($criteria_id);
        $observer_feedback = new observer_feedback($observation_outcome['feedback_id']);

        if ($criteria_base->is_feedback_required())
        {
            if (!$feedback_editor = $observation_outcome[$feedback_editor_base])
            {
                throw new coding_exception("No feedback provided for criteria with id $criteria_id");
            }
            else
            {
                $observer_feedback->set(observer_feedback::COL_TEXT, $feedback_editor['text']);
                $observer_feedback->set(observer_feedback::COL_TEXT_FORMAT, $feedback_editor['format']);
            }
        }

        $observer_feedback->set(observer_feedback::COL_OUTCOME, $observation_outcome['outcome']);
        $observer_feedback->update();

        $completed_count += ($observation_outcome['outcome'] == observer_feedback::OUTCOME_COMPLETE);
    }

    $observation_submission_outcome = ($completed_count === $criteria_count)
        ? observer_submission::OUTCOME_COMPLETE
        : observer_submission::OUTCOME_NOT_COMPLETE;

    $observer_submission_base->submit($observation_submission_outcome);

    $observer_assignment = $observer_submission_base->get_observer_assignment_base();
    redirect(
        new moodle_url(
            OBSERVATION_MODULE_PATH . 'observe.php',
            ['token' => $observer_assignment->get(observer_assignment::COL_TOKEN)]));
}
/* ================================================================================================================== */
else if ($assessor_submissionid = optional_param('assessor_submission_id', null, PARAM_INT))
{
    require_login();

    required_param('assessor_feedback_id', PARAM_INT);
}
/* ================================================================================================================== */
else
{
    throw new coding_exception('No submission id provided, cannot proceed with submission!');
}

// $PAGE->set_url('/mod/observation/submit.php', array('id' => $cm->id));
