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

use mod_observation\assessor_feedback;
use mod_observation\assessor_task_submission;
use mod_observation\criteria;
use mod_observation\learner_attempt;
use mod_observation\learner_task_submission;
use mod_observation\observation;
use mod_observation\observer;
use mod_observation\observer_assignment;
use mod_observation\observer_feedback;
use mod_observation\observer_task_submission;
use mod_observation\submission;
use mod_observation\task;

class backup_observation_activity_structure_step extends backup_activity_structure_step
{
    protected function define_structure()
    {
        // define elements
        $observation = new backup_nested_element(
            'observation', ['id'], [
            observation::COL_COURSE,
            observation::COL_NAME,
            observation::COL_INTRO,
            observation::COL_INTROFORMAT,
            observation::COL_TIMEOPEN,
            observation::COL_TIMECLOSE,
            observation::COL_TIMECREATED,
            observation::COL_TIMEMODIFIED,
            observation::COL_LASTMODIFIEDBY,
            observation::COL_DELETED,
            observation::COL_COMPLETION_TASKS,
            observation::COL_FAIL_ALL_TASKS,
            observation::COL_DEF_I_TASK_LEARNER,
            observation::COL_DEF_I_TASK_OBSERVER,
            observation::COL_DEF_I_TASK_ASSESSOR,
            observation::COL_DEF_I_ASS_OBS_LEARNER,
            observation::COL_DEF_I_ASS_OBS_OBSERVER,
            observation::COL_DEF_I_TASK_LEARNER_FORMAT,
            observation::COL_DEF_I_TASK_OBSERVER_FORMAT,
            observation::COL_DEF_I_TASK_ASSESSOR_FORMAT,
            observation::COL_DEF_I_ASS_OBS_LEARNER_FORMAT,
            observation::COL_DEF_I_ASS_OBS_OBSERVER_FORMAT,
        ]);

        $tasks = new backup_nested_element('tasks');
        $task = new backup_nested_element(
            'task', ['id'], [
            task::COL_OBSERVATIONID,
            task::COL_NAME,
            task::COL_INTRO_LEARNER,
            task::COL_INTRO_LEARNER_FORMAT,
            task::COL_INTRO_OBSERVER,
            task::COL_INTRO_OBSERVER_FORMAT,
            task::COL_INTRO_ASSESSOR,
            task::COL_INTRO_ASSESSOR_FORMAT,
            task::COL_INT_ASSIGN_OBS_LEARNER,
            task::COL_INT_ASSIGN_OBS_LEARNER_FORMAT,
            task::COL_INT_ASSIGN_OBS_OBSERVER,
            task::COL_INT_ASSIGN_OBS_OBSERVER_FORMAT,
            task::COL_SEQUENCE,
        ]);

        $criterias = new backup_nested_element('criterias');
        $criteria = new backup_nested_element(
            'criteria', ['id'], [
            criteria::COL_TASKID,
            criteria::COL_NAME,
            criteria::COL_DESCRIPTION,
            criteria::COL_DESCRIPTION_FORMAT,
            criteria::COL_FEEDBACK_REQUIRED,
            criteria::COL_SEQUENCE,
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element(
            'submission', ['id'], [
            submission::COL_OBSERVATIONID,
            submission::COL_USERID,
            submission::COL_STATUS,
            submission::COL_ATTEMPTS_ASSESSMENT,
            submission::COL_TIMESTARTED,
            submission::COL_TIMECOMPLETED,
        ]);

        $learner_task_submissions = new backup_nested_element('learner_task_submissions');
        $learner_task_submission = new backup_nested_element(
            'learner_task_submission', ['id'], [
            learner_task_submission::COL_TASKID,
            learner_task_submission::COL_SUBMISISONID,
            learner_task_submission::COL_USERID,
            learner_task_submission::COL_STATUS,
            learner_task_submission::COL_TIMESTARTED,
            learner_task_submission::COL_TIMECOMPLETED,
            learner_task_submission::COL_ATTEMPTS_OBSERVATION,
        ]);

        $learner_attempts = new backup_nested_element('learner_attempts');
        $learner_attempt = new backup_nested_element(
            'learner_attempt', ['id'], [
            learner_attempt::COL_LEARNER_TASK_SUBMISSIONID,
            learner_attempt::COL_TIMESTARTED,
            learner_attempt::COL_TIMESUBMITTED,
            learner_attempt::COL_TEXT,
            learner_attempt::COL_TEXT_FORMAT,
            learner_attempt::COL_ATTEMPT_NUMBER,
        ]);

        $observers = new backup_nested_element('observers');
        $observer = new backup_nested_element(
            'observer', ['id'], [
            observer::COL_FULLNAME,
            observer::COL_PHONE,
            observer::COL_EMAIL,
            observer::COL_POSITION_TITLE,
            observer::COL_ADDED_BY,
            observer::COL_TIMEADDED,
            observer::COL_MODIFIED_BY,
            observer::COL_TIMEMODIFIED,
        ]);

        $observer_assignments = new backup_nested_element('observer_assignments');
        $observer_assignment = new backup_nested_element(
            'observer_assignment', ['id'], [
            observer_assignment::COL_LEARNER_TASK_SUBMISSIONID,
            observer_assignment::COL_OBSERVERID,
            observer_assignment::COL_CHANGE_EXPLAIN,
            observer_assignment::COL_TIMEASSIGNED,
            observer_assignment::COL_OBSERVATION_ACCEPTED,
            observer_assignment::COL_TIMEACCEPTED,
            observer_assignment::COL_TOKEN,
            observer_assignment::COL_ACTIVE,
        ]);

        $observer_task_submissions = new backup_nested_element('observer_task_submissions');
        $observer_task_submission = new backup_nested_element(
            'observer_task_submission', ['id'], [
            observer_task_submission::COL_OBSERVER_ASSIGNMENTID,
            observer_task_submission::COL_TIMESTARTED,
            observer_task_submission::COL_OUTCOME,
            observer_task_submission::COL_TIMESUBMITTED,
        ]);

        $observer_feedbacks = new backup_nested_element('observer_feedbacks');
        $observer_feedback = new backup_nested_element(
            'observer_feedback', ['id'], [
            observer_feedback::COL_ATTEMPTID,
            observer_feedback::COL_CRITERIAID,
            observer_feedback::COL_OBSERVER_SUBMISSIONID,
            observer_feedback::COL_OUTCOME,
            observer_feedback::COL_TEXT,
            observer_feedback::COL_TEXT_FORMAT,
        ]);

        $assessor_task_submissions = new backup_nested_element('assessor_task_submissions');
        $assessor_task_submission = new backup_nested_element(
            'assessor_task_submission', ['id'], [
            assessor_task_submission::COL_ASSESSORID,
            assessor_task_submission::COL_LEARNER_TASK_SUBMISSIONID,
            assessor_task_submission::COL_OUTCOME,
        ]);

        $assessor_feedbacks = new backup_nested_element('assessor_feedbacks');
        $assessor_feedback = new backup_nested_element(
            'assessor_feedback', ['id'], [
            assessor_feedback::COL_ATTEMPTID,
            assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID,
            assessor_feedback::COL_TEXT,
            assessor_feedback::COL_TEXT_FORMAT,
            assessor_feedback::COL_OUTCOME,
            assessor_feedback::COL_TIMESUBMITTED,
        ]);

        // build the sctructure tree
        $observation->add_child($tasks);
        $tasks->add_child($task);

        // $observation->add_child($criteria);
        $task->add_child($criterias);
        $criterias->add_child($criteria);

        $observation->add_child($observers);
        $observers->add_child($observer);

        $observation->add_child($submissions);
        $submissions->add_child($submission);

        $submission->add_child($learner_task_submissions);
        $learner_task_submissions->add_child($learner_task_submission);

        $learner_task_submission->add_child($assessor_task_submissions);
        $assessor_task_submissions->add_child($assessor_task_submission);

        $learner_task_submission->add_child($learner_attempts);
        $learner_attempts->add_child($learner_attempt);

        $learner_task_submission->add_child($observer_assignments);
        $observer_assignments->add_child($observer_assignment);

        $learner_attempt->add_child($assessor_feedbacks);
        $assessor_feedbacks->add_child($assessor_feedback);

        $observer_assignment->add_child($observer_task_submissions);
        $observer_task_submissions->add_child($observer_task_submission);

        $observer_task_submission->add_child($observer_feedbacks);
        $observer_feedbacks->add_child($observer_feedback);

        // set sources
        $observation->set_source_table(OBSERVATION, ['id' => backup::VAR_ACTIVITYID]);
        $task->set_source_table(task::TABLE, [task::COL_OBSERVATIONID => backup::VAR_PARENTID]);
        $criteria->set_source_table(criteria::TABLE, [criteria::COL_TASKID => backup::VAR_PARENTID]);

        if ($this->get_setting_value('userinfo'))
        {
            // user info is included in this backup

            $submission->set_source_table(
                submission::TABLE,
                [submission::COL_OBSERVATIONID => backup::VAR_PARENTID]);
            $learner_task_submission->set_source_table(
                learner_task_submission::TABLE,
                [learner_task_submission::COL_SUBMISISONID => backup::VAR_PARENTID]);
            $learner_attempt->set_source_table(
                learner_attempt::TABLE,
                [learner_attempt::COL_LEARNER_TASK_SUBMISSIONID => backup::VAR_PARENTID]);

            $observer->set_source_table(
                observer::TABLE,
                []);
            $observer_assignment->set_source_table(
                observer_assignment::TABLE,
                [observer_assignment::COL_LEARNER_TASK_SUBMISSIONID => backup::VAR_PARENTID]);
            $observer_task_submission->set_source_table(
                observer_task_submission::TABLE,
                [observer_task_submission::COL_OBSERVER_ASSIGNMENTID => backup::VAR_PARENTID]);
            $observer_feedback->set_source_table(
                observer_feedback::TABLE,
                [
                    observer_feedback::COL_OBSERVER_SUBMISSIONID => backup::VAR_PARENTID,
                ]);

            $assessor_task_submission->set_source_table(
                assessor_task_submission::TABLE,
                [assessor_task_submission::COL_LEARNER_TASK_SUBMISSIONID => backup::VAR_PARENTID]);
            $assessor_feedback->set_source_table(
                assessor_feedback::TABLE,
                [
                    assessor_feedback::COL_ATTEMPTID => backup::VAR_PARENTID,
                    // assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID => '../assessor_task_submission/id'
                ]);
        }

        // annotations
        $user = 'user';

        $submission->annotate_ids($user, submission::COL_USERID);
        $learner_task_submission->annotate_ids($user, learner_task_submission::COL_USERID);
        $learner_task_submission->annotate_ids(task::TABLE, learner_task_submission::COL_TASKID);
        $observer->annotate_ids($user, observer::COL_ADDED_BY);
        $observer->annotate_ids($user, observer::COL_MODIFIED_BY);
        $observer_assignment->annotate_ids(observer::TABLE, observer_assignment::COL_OBSERVERID);
        $observer_feedback->annotate_ids(learner_attempt::TABLE, observer_feedback::COL_ATTEMPTID);
        $observer_feedback->annotate_ids(criteria::TABLE, observer_feedback::COL_CRITERIAID);
        $assessor_task_submission->annotate_ids($user, assessor_task_submission::COL_ASSESSORID);
        $assessor_feedback->annotate_ids(assessor_task_submission::TABLE, assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID);

        // file area annotations

        // intro and default intro's
        $observation->annotate_files(OBSERVATION_MODULE, observation::FILE_AREA_INTRO, null);
        $observation->annotate_files(OBSERVATION_MODULE, observation::FILE_AREA_GENERAL, null);

        // task intro's
        $task->annotate_files(OBSERVATION_MODULE, observation::FILE_AREA_GENERAL, 'id');

        // criteria description
        $criteria->annotate_files(OBSERVATION_MODULE, observation::FILE_AREA_GENERAL, 'id');

        // attempts
        $learner_attempt->annotate_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_TRAINEE,
            'id',
            $this->task->get_contextid());

        // feedback
        $assessor_feedback->annotate_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_ASSESSOR,
            'id',
            $this->task->get_contextid());

        // observer
        $observer_feedback->annotate_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_OBSERVER,
            'id',
            $this->task->get_contextid());

        return $this->prepare_activity_structure($observation);
    }
}