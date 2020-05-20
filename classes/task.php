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

namespace mod_observation;

use coding_exception;
use dml_exception;
use dml_missing_record_exception;
use mod_observation\interfaces\templateable;

class task extends task_base implements templateable
{
    /**
     * @var criteria[]
     */
    private $criteria;
    /**
     * @var learner_task_submission[]
     */
    private $learner_task_submissions;

    /**
     * @var bool Determines if task is filtered by userid
     */
    private $is_filtered = false;

    public function __construct($id_or_record, int $userid = null)
    {
        parent::__construct($id_or_record);

        $criterias = criteria_base::read_all_by_condition(
            [criteria::COL_TASKID => $this->id], criteria::COL_SEQUENCE);
        if (!empty($criterias))
        {
            foreach ($criterias as $criteria_base)
            {
                $this->criteria[] = new criteria($criteria_base, $userid);
            }
        }
        else
        {
            $this->criteria = [];
        }

        $params = [learner_task_submission::COL_TASKID => $this->id];
        if (!is_null($userid))
        {
            $params[learner_task_submission::COL_USERID] = $userid;
            $this->is_filtered = true;
        }
        $this->learner_task_submissions = learner_task_submission::read_all_by_condition($params);
    }

    /**
     * Checks if task has been observed for given userid
     *
     * @param int $userid
     * @return bool
     * @throws coding_exception
     */
    public function is_observed(int $userid)
    {
        if ($submission = $this->get_learner_task_submission_or_null($userid))
        {
            return $submission->is_observation_complete();
        }

        return false;
    }

    public function get_learner_task_submissions(): array
    {
        return $this->learner_task_submissions;
    }

    /**
     * @param int $userid
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public function get_learner_task_submission_or_null(int $userid): ?learner_task_submission
    {
        if ($this->is_filtered)
        {
            return learner_task_submission::read_by_condition_or_null(
                [learner_task_submission::COL_USERID => $userid, learner_task_submission::COL_TASKID => $this->id]);
        }
        else
        {
            return lib::find_in_assoc_array_by_key_value_or_null(
                $this->learner_task_submissions, learner_task_submission::COL_USERID, $userid);
        }
    }

    /**
     * Checks if task has been completed (observed & assessed) for given user id
     *
     * @param int $userid
     * @return bool
     * @throws coding_exception
     */
    public function is_complete(int $userid): bool
    {
        if ($submission = $this->get_learner_task_submission_or_null($userid))
        {
            return $submission->is_assessment_complete();
        }

        return false;
    }

    public function is_submitted(int $userid): bool
    {
        if ($submission = $this->get_learner_task_submission_or_null($userid))
        {
            return $submission->is_observation_pending_or_in_progress();
        }

        return false;
    }

    public function is_assessed(int $userid): bool
    {
        if (!$submission = $this->get_learner_task_submission_or_null($userid))
        {
            return false;
        }

        return $submission->is_assessment_complete_or_incomplete();
    }

    /**
     * @return criteria[] empty if no criteria in task
     */
    public function get_criteria(): array
    {
        return $this->criteria;
    }

    public function has_criteria(): bool
    {
        return (bool) count($this->criteria);
    }

    /**
     * @param int $userid
     * @return learner_task_submission
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public function get_learner_task_submission_or_create(int $userid): learner_task_submission
    {
        $submission = submission::read_by_condition_or_null(
            [submission::COL_OBSERVATIONID => $this->observationid, submission::COL_USERID => $userid], true);

        if (!$task_submission = $this->get_learner_task_submission_or_null($userid))
        {
            $task_submission = new learner_task_submission_base();
            $task_submission->set(learner_task_submission::COL_TASKID, $this->id);
            $task_submission->set(learner_task_submission::COL_SUBMISISONID, $submission->get_id_or_null());
            $task_submission->set(learner_task_submission::COL_USERID, $userid);
            $task_submission->set(learner_task_submission::COL_TIMESTARTED, time());
            $task_submission->set(learner_task_submission::COL_TIMECOMPLETED, 0);
            $task_submission->set(learner_task_submission::COL_ATTEMPTS_OBSERVATION, 0);
            $task_submission->set(
                learner_task_submission::COL_STATUS, learner_task_submission::STATUS_LEARNER_PENDING);

            // create record and initialize submission class instance
            $task_submission = new learner_task_submission($task_submission->create());

            $this->learner_task_submissions[] = $task_submission;
        }

        return $task_submission;
    }

    /**
     * @param int|null $userid
     * @return bool
     * @throws coding_exception
     */
    public function has_submissions(int $userid = null): bool
    {
        if ($this->is_filtered && !is_null($userid))
        {
            return !is_null(parent::get_learner_task_submission_or_null($userid));
        }
        else if (!$this->is_filtered && !is_null($userid))
        {
            return !is_null(
                lib::find_in_assoc_array_by_criteria_or_null(
                    $this->learner_task_submissions,
                    [
                        learner_task_submission::COL_USERID => $userid,
                        learner_task_submission::COL_TASKID => $this->id
                    ]));
        }
        else
        {
            // no user id and not filtered, check if any submissions exist
            return (bool) count($this->learner_task_submissions);
        }
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $criteria_data = [];
        foreach ($this->criteria as $criteria)
        {
            // has to be a simple array otherwise mustache won't loop over
            $criteria_data[] = $criteria->export_template_data();
        }
        // sort by sequence again, just in case
        $criteria_data = lib::sort_by_field($criteria_data, criteria::COL_SEQUENCE);

        $learner_task_submissions_data = [];
        $assessor_feedback_data = [];
        foreach ($this->learner_task_submissions as $learner_task_submission)
        {
            $learner_task_submissions_data[] = $learner_task_submission->export_template_data();

            foreach ($learner_task_submission->get_all_assessor_feedback() as $assessor_feedback)
            {
                if ($assessor_feedback->is_submitted())
                {
                    $assessor_feedback_data[] = $assessor_feedback->export_template_data();
                }
            }
        }

        $learner_submission_status = null;
        $assessor_submission_status = null;
        $learner_submission_status_description = null;
        $assessor_submission_status_description = null;
        $has_feedback = false;
        // task contains only ONE submission in this task, we can give the task a status
        if ($this->is_filtered)
        {
            if (isset($learner_task_submission)) // $learner_task_submission set in loop
            {
                $learner_submission_status = $learner_task_submission->get(learner_task_submission::COL_STATUS);
                $learner_submission_status_description = lib::get_status_string($learner_submission_status);

                if ($assessor_task_submission = $learner_task_submission->get_assessor_task_submission_or_null())
                {
                    $attempt = $learner_task_submission->get_latest_attempt_or_null();
                    // assessor status comes from feedback as task submission retains status from previous assessment
                    $assessor_submission_status =
                        $assessor_task_submission->get_task_outcome_from_feedback_or_null($attempt->get_id_or_null());
                    $assessor_submission_status_description = lib::get_outcome_string($assessor_submission_status);
                    $has_feedback = (bool) count($assessor_task_submission->get_all_feedback());
                }
            }
        }

        return [
            // general
            self::COL_ID                      => $this->id,
            self::COL_OBSERVATIONID           => $this->observationid,
            self::COL_NAME                    => $this->name,
            self::COL_SEQUENCE                => $this->sequence,
            // intros
            self::COL_INTRO_LEARNER           => $this->intro_learner,
            self::COL_INTRO_OBSERVER          => $this->intro_observer,
            self::COL_INTRO_ASSESSOR          => $this->intro_assessor,
            self::COL_INT_ASSIGN_OBS_LEARNER  => $this->int_assign_obs_learner,
            self::COL_INT_ASSIGN_OBS_OBSERVER => $this->int_assign_obs_observer,

            'criteria'                 => $criteria_data,
            'learner_task_submissions' => $learner_task_submissions_data,
            'assessor_feedback'        => $assessor_feedback_data,

            // other data
            'has_submission'                         => $this->has_submissions(),
            'has_feedback'                           => $has_feedback,
            'learner_submission_status'              => $learner_submission_status,
            'assessor_submission_status'             => $assessor_submission_status,
            'learner_submission_status_description'  => $learner_submission_status_description,
            'assessor_submission_status_description' => $assessor_submission_status_description,
        ];
    }
}
