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

use mod_observation\event\activity_assessed;

class submission extends submission_base /*TODO: implements templateable*/
{
    /**
     * @var learner_task_submission[]
     */
    private $learner_task_submissions;

    /**
     * submission constructor.
     * @param $id_or_record
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->learner_task_submissions = learner_task_submission::read_all_by_condition(
            [
                learner_task_submission::COL_SUBMISISONID => $this->id,
                learner_task_submission::COL_USERID       => $this->userid,
            ]
        );
    }

    public function get_observed_task_count(): int
    {
        $observed = 0;
        foreach ($this->learner_task_submissions as $task_submission)
        {
            $observed += $task_submission->is_observation_complete();
        }

        return $observed;
    }

    /**
     * @return learner_task_submission[]
     */
    public function get_learner_task_submissions(): array
    {
        return $this->learner_task_submissions;
    }

    /**
     * @param int $taskid
     * @return learner_task_submission|null
     * @throws \coding_exception
     */
    public function get_learner_task_submisison_or_null(int $taskid)
    {
        return lib::find_in_assoc_array_by_key_value_or_null(
            $this->learner_task_submissions, learner_task_submission::COL_TASKID, $taskid);
    }

    /**
     * @return bool
     */
    public function is_observed(): bool
    {
        if (empty($this->learner_task_submissions))
        {
            // no submissions made
            return false;
        }

        if (!$this->is_all_tasks_have_submission())
        {
            // not all tasks have submissions
            return false;
        }

        // check each submission status
        foreach ($this->learner_task_submissions as $task_submission)
        {
            if (!$task_submission->is_observation_complete())
            {
                return false;
            }
        }

        return true;
    }

    public function is_all_tasks_have_submission(): bool
    {
        return count($this->get_learner_task_submissions()) === $this->get_observation()->get_task_count();
    }

    public function is_all_tasks_no_learner_action_required(): bool
    {
        if (!$this->is_all_tasks_have_submission())
        {
            // has to submit an attempt for every task
            return false;
        }

        foreach ($this->get_learner_task_submissions() as $task_submission)
        {
            if ($task_submission->is_learner_action_required())
            {
                return false;
            }
        }

        return true;
    }

    public function all_tasks_observation_pending_or_in_progress(): bool
    {
        if (!$this->is_all_tasks_have_submission())
        {
            return false;
        }

        foreach ($this->get_learner_task_submissions() as $task_submission)
        {
            if ($task_submission->get_active_observer_assignment_or_null() // has observer assigned
                && $task_submission->is_observation_pending_or_in_progress()) //observation pending or in progress
            {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Releases final assessor 'grade' after assessor has left feedback for each task in activity.
     *
     * @param observation_base|null $observation
     * @return string assessmnet outcome {@link status}
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function release_assessment(observation_base $observation = null): string
    {
        if (is_null($observation))
        {
            $observation = $this->get_observation();
        }

        $learner_task_submissions = $this->get_learner_task_submissions();

        $completed_tasks = 0;
        foreach ($learner_task_submissions as $learner_task_submission)
        {
            $feedback = $learner_task_submission->get_latest_learner_attempt_or_null()->get_assessor_feedback_or_null();
            $assessor_task_submission = $feedback->get_assessor_task_submission();
            $outcome = $feedback->get(assessor_feedback::COL_OUTCOME);

            // validate is graded
            if (!$feedback->is_submitted())
            {
                throw new \coding_exception(
                    sprintf(
                        'Cannot release assessment - feedback with id "%d" has not been submitted',
                        $feedback->get_id_or_null()));
            }

            $completed_tasks += ($outcome === assessor_feedback::OUTCOME_COMPLETE);
            // update assessor task submission outcome
            $assessor_task_submission->submit($outcome, $feedback);

            // learner task submission status is updated later because we need to determine outcome
        }

        // update activity submission status
        if ($completed_tasks == $observation->get_task_count())
        {
            // all tasks marked as complete - activity complete
            $new_status = self::STATUS_COMPLETE;
        }
        else
        {
            // if at least one task not complete we mark all incomplete as per business logic
            $new_status = self::STATUS_ASSESSMENT_INCOMPLETE;
        }

        // update all tasks with outcome
        foreach ($learner_task_submissions as $task_submission)
        {
            $task_submission->update_status_and_save($new_status);
        }

        // update activity submission status
        $this->update_status_and_save($new_status);

        // TODO: notifications

        // trigger event
        $event = activity_assessed::create(
            [
                'context'  => \context_module::instance($observation->get_cm()->id),
                'objectid' => $this->id,
                'relateduserid' => $this->userid,
            ]);
        $event->trigger();

        return $new_status;
    }
}