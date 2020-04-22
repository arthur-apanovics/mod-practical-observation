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

class submission extends submission_base /*TODO: implements templateable*/
{
    /**
     * @var learner_task_submission[]
     */
    private $task_submissions;

    /**
     * submission constructor.
     * @param $id_or_record
     * @throws \coding_exception
     * @throws \dml_missing_record_exception
     */
    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->task_submissions = learner_task_submission::read_all_by_condition(
            [
                learner_task_submission::COL_SUBMISISONID => $this->id,
                learner_task_submission::COL_USERID       => $this->userid,
            ]
        );
    }

    /**
     * @return observation|observation_base
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function get_observation()
    {
        return new observation(
            new observation_base($this->observationid));
    }

    public function get_observed_task_count(): int
    {
        $observed = 0;
        foreach ($this->task_submissions as $task_submission)
        {
            $observed += $task_submission->is_observation_complete();
        }

        return $observed;
    }

    /**
     * @param int $taskid
     * @return learner_task_submission|null
     * @throws \coding_exception
     */
    public function get_learner_task_submisison_or_null(int $taskid)
    {
        return lib::find_in_assoc_array_by_key_value_or_null(
            $this->task_submissions, learner_task_submission::COL_TASKID, $taskid);
    }

    public function release_assessment(observation_base $observation = null)
    {
        if (is_null($observation))
        {
            $observation = parent::get_observation();
        }

        $learner_task_submissions = $this->get_learner_task_submisisons();

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
                    sprintf('Cannot release assessment - feedback with id "%d" has not been submitted'),
                    $feedback->get_id_or_null());
            }

            // update learner task submission status
            if ($outcome === assessor_feedback::OUTCOME_COMPLETE)
            {
                $new_status = learner_task_submission::STATUS_COMPLETE;
                $completed_tasks += 1;
            }
            else
            {
                $new_status = learner_task_submission::STATUS_ASSESSMENT_INCOMPLETE;
            }

            // update assessor task submission outcome
            $assessor_task_submission->set(assessor_task_submission::COL_OUTCOME, $outcome);
            // update learner task submission status
            $learner_task_submission->update_status_and_save($new_status);
        }

        // update activity submission status
        if ($completed_tasks == $observation->get_task_count())
        {
            // all tasks marked as complete - activity complete
            $new_status = self::STATUS_COMPLETE;
        }
        else
        {
            $new_status = self::STATUS_ASSESSMENT_INCOMPLETE;
        }

        $this->update_status_and_save($new_status);

        // TODO: notifications
    }

    // /**
    //  * @inheritDoc
    //  */
    // public function export_template_data(): array
    // {
    //     return [
    //         self::COL_ID                          => $this->id,
    //     ];
    // }
}