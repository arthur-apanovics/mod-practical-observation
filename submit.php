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

use core\output\notification;
use mod_observation\assessor_feedback;
use mod_observation\assessor_task_submission;
use mod_observation\criteria;
use mod_observation\criteria_base;
use mod_observation\learner_attempt;
use mod_observation\learner_attempt_base;
use mod_observation\learner_task_submission_base;
use mod_observation\lib;
use mod_observation\observation;
use mod_observation\observation_base;
use mod_observation\observer_feedback;
use mod_observation\observer_task_submission;
use mod_observation\observer_task_submission_base;
use mod_observation\submission;
use mod_observation\task_base;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once($CFG->libdir . '/filelib.php');

// PROCESSES SUBMISSIONS FROM LEARNER, OBSERVER & ASSESSOR

$cmid = required_param('cmid', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);
$context = context_module::instance($cmid);
$observation = new observation_base($cm->instance);


if ($learner_task_submissionid = optional_param('learner_task_submission_id', null, PARAM_INT))
{
    require_login();

    if (!$observation->is_activity_available())
    {
        throw new moodle_exception(lib::get_activity_timing_error_string($observation), \OBSERVATION);
    }

    $attempt_id = required_param('attempt_id', PARAM_INT);

    // check id's are correct
    $learner_task_submission = new learner_task_submission_base($learner_task_submissionid);
    $attempt = new learner_attempt_base($attempt_id);

    // get editor content
    // TODO: SANITISE INPUT!!!!!
    list($input_base) = lib::get_editor_attributes_for_class(learner_attempt::class);
    $attempt_editor = required_param_array($input_base, PARAM_RAW);

    // update attempt
    $attempt->set(learner_attempt::COL_TEXT, $attempt_editor['text']);
    $attempt->set(learner_attempt::COL_TEXT_FORMAT, $attempt_editor['format']);
    $attempt->update();

    // save files
    $draft_itemid = required_param('attachments_itemid', PARAM_INT);
    lib::save_files(
        $draft_itemid, $context->id, observation::FILE_AREA_TRAINEE, $attempt->get_id_or_null());

    // do not submit yet as learner needs to assign an observer first
    $attempt->save($learner_task_submission);

    redirect(
        new moodle_url(
            OBSERVATION_MODULE_PATH . 'request.php',
            [
                'id'                         => $cmid,
                'learner_task_submission_id' => $learner_task_submission->get_id_or_null(),
                'attempt_id'                 => $attempt->get_id_or_null()
            ]));
}
/* ================================================================================================================== */
else if ($observer_submissionid = optional_param('observer_submission_id', null, PARAM_INT))
{
    // external observer submitting, check dates
    if (!$observation->is_activity_available())
    {
        throw new moodle_exception(lib::get_activity_timing_error_string($observation), \OBSERVATION);
    }

    // gather data from post request
    $observer_submission_base = new observer_task_submission_base($observer_submissionid);
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

    // needed to extract text and format from editor
    list($feedback_editor_base) = lib::get_editor_attributes_for_class(observer_feedback::class);
    // keep track of how many criteria are completed to determine task outcome
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
        ? observer_task_submission::OUTCOME_COMPLETE
        : observer_task_submission::OUTCOME_NOT_COMPLETE;

    // submit observation
    $observer_submission_base->submit($observation_submission_outcome);

    // send emails
    $observer_assignment = $observer_submission_base->get_observer_assignment_base();
    $observer = $observer_assignment->get_observer();
    $learner_task_submission = $observer_assignment->get_learner_task_submission_base();
    $learner = \core_user::get_user($learner_task_submission->get_userid());

    $lang_data = [
        'learner_fullname'  => fullname($learner),
        'observer_fullname' => $observer->get_formatted_name_or_null(),
        'task_name'         => $task->get_formatted_name(),
        'activity_name'     => $observation->get_formatted_name(),
        'activity_url'     => $observation->get_url(),
        'observe_url'       => $observer_assignment->get_review_url(),
        'course_fullname'   => $course->fullname,
        'course_shortname'  => $course->shortname,
        'course_url'        => new \moodle_url('/course/view.php', ['id' => $course->id]),
    ];

    // send confirmation email to observer
    lib::email_external(
        $observer->get_email_or_null(),
        get_string('email:observer_observation_complete_subject', OBSERVATION, $lang_data),
        get_string('email:observer_observation_complete_body', OBSERVATION, $lang_data));
    
    // send "observation complete" email to learner
    lib::email_user(
        $learner,
        get_string('email:learner_observation_complete_subject', OBSERVATION, $lang_data),
        get_string('email:learner_observation_complete_body', OBSERVATION, $lang_data));

    redirect($observer_assignment->get_review_url());
}
/* ================================================================================================================== */
else if ($assessor_task_submissionid = optional_param('assessor_task_submission_id', null, PARAM_INT))
{
    // assessor saving task feedback
    require_login();

    $assessor_task_submission = new assessor_task_submission($assessor_task_submissionid);
    $learner_task_submission = $assessor_task_submission->get_learner_task_submission();
    $assessor_feedback = new assessor_feedback(
        required_param('assessor_feedback_id', PARAM_INT));

    // get outcome and validate
    $outcome = required_param('outcome', PARAM_TEXT);
    if (!in_array($outcome, [assessor_task_submission::OUTCOME_NOT_COMPLETE, assessor_task_submission::OUTCOME_COMPLETE]))
    {
        throw new coding_exception("Invalid outcome '$outcome'");
    }
    // if outcome is 'complete' then observer has to meet criteria
    if ($outcome == assessor_task_submission::OUTCOME_COMPLETE)
    {
        // we don't use this param for anything apart from validation
        required_param('meets-criteria', PARAM_BOOL);
    }

    // needed to extract text and format from editor
    list($feedback_editor_base) = lib::get_editor_attributes_for_class(assessor_feedback::class);
    $editor = required_param_array($feedback_editor_base, PARAM_RAW);

    // save feedback until assessor decides to release
    $assessor_feedback->set(assessor_feedback::COL_TIMESUBMITTED, time());
    $assessor_feedback->set(assessor_feedback::COL_TEXT, $editor['text']);
    $assessor_feedback->set(assessor_feedback::COL_TEXT_FORMAT, $editor['format']);
    $assessor_feedback->set(assessor_feedback::COL_OUTCOME, $outcome);
    $assessor_feedback->update();

    // save files
    $draft_itemid = required_param('attachments_itemid', PARAM_INT);
    lib::save_files(
        $draft_itemid, $context->id, observation::FILE_AREA_ASSESSOR, $assessor_feedback->get_id_or_null());

    // set activity submission status to 'grading'
    $submission = $learner_task_submission->get_submission();
    $status = $submission->get(submission::COL_STATUS);
    if ($status !== submission::STATUS_ASSESSMENT_IN_PROGRESS)
    {
        // extra debugging just in case
        if ($status !== submission::STATUS_ASSESSMENT_PENDING)
        {
            debugging(
                sprintf(
                    'Submission with id "%d" status was expected to be %s, got %s instead',
                    $submission->get_id_or_null(), submission::STATUS_ASSESSMENT_PENDING, $status), DEBUG_DEVELOPER,
                debug_backtrace());
        }

        $submission->update_status_and_save(submission::STATUS_ASSESSMENT_IN_PROGRESS);
    }

    $learnerid = $learner_task_submission->get_userid();
    redirect(
        new moodle_url(
            OBSERVATION_MODULE_PATH . 'activity_assess.php', ['id' => $cmid, 'learnerid' => $learnerid]));
}
/* ================================================================================================================== */
else if ($submission_id = optional_param('activity_submission_id', null, PARAM_INT))
{
    // assessor releasing grade
    $submission = submission::read_or_null($submission_id);
    $assessment_outcome = $submission->release_assessment($observation);

    // send email
    $learner = \core_user::get_user($submission->get_userid());
    $lang_data = [
        'learner_fullname'   => fullname(\core_user::get_user($submission->get_userid())),
        'assessor_fullname'  => fullname($USER),
        'assessment_outcome' => $assessment_outcome,
        'activity_name'      => $observation->get_formatted_name(),
        'activity_url'       => $observation->get_url(),
        'course_fullname'    => $course->fullname,
        'course_shortname'   => $course->shortname,
        'course_url'         => new \moodle_url('/course/view.php', ['id' => $course->id]),
    ];

    lib::email_user(
        $learner,
        get_string('email:learner_assessment_released_subject', OBSERVATION, $lang_data),
        get_string('email:learner_assessment_released_body', OBSERVATION, $lang_data));

    redirect(
        new moodle_url(OBSERVATION_MODULE_PATH . 'view.php', ['id' => $cmid]),
        get_string(
            'notification:assessment_released',
            'observation',
            fullname(core_user::get_user($submission->get_userid()))),
        null,
        notification::NOTIFY_SUCCESS
    );
}
/* ================================================================================================================== */
else
{
    throw new coding_exception('No submission id provided, cannot proceed with submission!');
}
