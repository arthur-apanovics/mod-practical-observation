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
use mod_observation\traits\submission_status_store;
use ReflectionException;

class submission_base extends submission_status_store
{
    public const TABLE = OBSERVATION . '_submission';

    public const COL_OBSERVATIONID        = 'observationid';
    public const COL_USERID               = 'userid';
    public const COL_STATUS               = 'status';
    public const COL_ATTEMPTS_ASSESSMENT  = 'attempts_assessment';
    public const COL_TIMESTARTED          = 'timestarted';
    public const COL_TIMECOMPLETED        = 'timecompleted';

    /**
     * @var int
     */
    protected $observationid;
    /**
     * @var int learner user id
     */
    protected $userid;
    /**
     * One of:
     * <ul>
     *  <li>{@link STATUS_LEARNER_PENDING}</li>
     *  <li>{@link STATUS_LEARNER_IN_PROGRESS}</li>
     *  <li>{@link STATUS_OBSERVATION_PENDING}</li>
     *  <li>{@link STATUS_OBSERVATION_IN_PROGRESS}</li>
     *  <li>{@link STATUS_OBSERVATION_INCOMPLETE}</li>
     *  <li>{@link STATUS_ASSESSMENT_PENDING}</li>
     *  <li>{@link STATUS_ASSESSMENT_IN_PROGRESS}</li>
     *  <li>{@link STATUS_ASSESSMENT_INCOMPLETE}</li>
     *  <li>{@link STATUS_COMPLETE}</li>
     * </ul>
     *
     * @var string
     */
    protected $status;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * @var int number of assessment attempts
     */
    protected $attempts_assessment;
    /**
     * @var int
     */
    protected $timecompleted;

    // GETTER FIELDS
    /**
     * DO NOT ACCESS DIRECTLY
     * @var learner_task_submission_base[]|null
     */
    private $_learner_task_submissions;
    /**
     * DO NOT ACCESS DIRECTLY
     * @var observation_base|null
     */
    private $_observation;


    /**
     * @param string $new_status
     * @param bool   $allow_same should we freak out if status is alreadt equalt to $new_status. true = do not freak out
     * @return $this
     * @throws coding_exception
     * @throws dml_exception
     */
    public function update_status_and_save(string $new_status, bool $allow_same = false): self
    {
        // extra validation to be done here

        if ($new_status !== $this->status)
        {
            $this->set(self::COL_STATUS, $new_status, true);
        }
        else
        {
            $msg = sprintf(
                'Attempted to set submission with id "%d" status to "%s" but that is already the current status',
                $this->id, $new_status);

            if (!$allow_same)
            {
                throw new coding_exception($msg);
            }
            else
            {
                debugging($msg, DEBUG_DEVELOPER, debug_backtrace());
            }
        }

        return $this;
    }

    public function get_userid()
    {
        return $this->userid;
    }

    /**
     * @return learner_task_submission_base[]
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_learner_task_submissions(): array
    {
        if (is_null($this->_learner_task_submissions))
        {
            $this->_learner_task_submissions = learner_task_submission_base::read_all_by_condition(
                [
                    learner_task_submission::COL_SUBMISISONID => $this->id,
                    learner_task_submission::COL_USERID => $this->userid,
                ]);
        }

        return $this->_learner_task_submissions;
    }

    /**
     * @return observation_base
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public function get_observation()
    {
        if (is_null($this->_observation))
        {
            $this->_observation = observation_base::read_or_null($this->observationid, true);
        }

        return $this->_observation;
    }

    /**
     * @return array ['task_id' => 'attempts_number']
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_task_observation_attempts(): array
    {
        $result = [];
        foreach ($this->get_learner_task_submissions() as $task_submission)
        {
            $result[$task_submission->get(learner_task_submission::COL_TASKID)] =
                $task_submission->get(learner_task_submission::COL_ATTEMPTS_OBSERVATION);
        }

        return $result;
    }

    public function is_observed()
    {
        $task_submissions = $this->get_learner_task_submissions();
        if (empty($task_submissions))
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
        foreach ($task_submissions as $task_submission)
        {
            if (!$task_submission->is_observation_complete())
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Check EACH task submission status to determine overall activity submission status.
     * Use this for updating submission status.
     *
     * @return bool
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function is_observed_as_incomplete(): bool
    {
        foreach ($this->get_learner_task_submissions() as $task_submission)
        {
            if (!$task_submission->is_observation_incomplete()) {
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
        foreach ($this->get_learner_task_submissions() as $task_submission)
        {
            if ($task_submission->is_learner_action_required())
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param bool $save update db record immediately
     * @return int current assessment attempt
     * @throws coding_exception
     * @throws dml_exception
     */
    public function increment_assessment_attempt_number(bool $save): int
    {
        $this->set(self::COL_ATTEMPTS_ASSESSMENT, ($this->attempts_assessment + 1), $save);

        return $this->attempts_assessment;
    }

    /**
     * Submitted time depends on when the submission was last observed, get time of last observation that was submitted.
     *
     * @return int|null null if activity not observed
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_time_submitted_or_null(): ?int
    {
        if (!$this->is_observed())
        {
            return null;
        }

        $last = 0;
        foreach ($this->get_learner_task_submissions() as $task_submission)
        {
            $observer_submission = $task_submission
                ->get_active_observer_assignment_or_null()
                ->get_observer_submission_base_or_null();
            $time_submitted = $observer_submission->get(observer_task_submission::COL_TIMESUBMITTED);

            if ($last < $time_submitted)
            {
                $last = $time_submitted;
            }
        }

        return $last;
    }

    /**
     * Returns a grade based on "Binary competence scale"
     *
     * @return int 0 = no grade, 1 = not competent, 2 = competent
     */
    public function get_gradebook_grade(): int
    {
        if ($this->is_assessment_complete())
        {
            // activity complete
            return 2;
        }
        else if ($this->is_assessment_incomplete())
        {
            // assessment failed - not competent
            return 1;
        }
        else
        {
            // we treat every other status as 'not graded'
            return 0;
        }
    }

    public function update_gradebook(\stdClass $observation_record)
    {
        global $USER;

        $grade = [];
        $grade['userid'] = $this->userid;
        $grade['usermodified'] = $USER->id;
        $grade['dategraded'] = $this->timecompleted;
        $grade['datesubmitted'] = $this->timestarted;
        $grade['feedbackformat'] = FORMAT_PLAIN;
        $grade['feedback'] = '';
        $grade['rawgrade'] = $this->get_gradebook_grade();

        observation_grade_item_update($observation_record, $grade);
    }
}
