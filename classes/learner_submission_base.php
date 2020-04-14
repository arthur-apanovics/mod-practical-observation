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

class learner_submission_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_learner_submission';

    public const COL_TASKID        = 'taskid';
    public const COL_USERID        = 'userid';
    public const COL_STATUS        = 'status';
    public const COL_TIMESTARTED   = 'timestarted';
    public const COL_TIMECOMPLETED = 'timecompleted';

    // learner statuses
    public const STATUS_LEARNER_PENDING     = 'learner_pending';
    public const STATUS_LEARNER_IN_PROGRESS = 'learner_in_progress';

    // observer statuses
    public const STATUS_OBSERVATION_PENDING     = 'observation_pending';
    public const STATUS_OBSERVATION_IN_PROGRESS = 'observation_in_progress';
    public const STATUS_OBSERVATION_INCOMPLETE  = 'observation_incomplete';

    // assessor statuses
    public const STATUS_ASSESSMENT_PENDING     = 'assessment_pending';
    public const STATUS_ASSESSMENT_IN_PROGRESS = 'assessment_in_progress';
    public const STATUS_ASSESSMENT_INCOMPLETE  = 'assessment_incomplete';

    public const STATUS_COMPLETE = 'complete';

    /**
     * @var int
     */
    protected $userid;
    /**
     * @var int
     */
    protected $taskid;
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
     * @var int
     */
    protected $timecompleted;

    public function is_observation_complete()
    {
        $this->validate_status();

        // if status is either one of these, then observation is complete
        $complete_statuses = [
            self::STATUS_ASSESSMENT_PENDING,
            self::STATUS_ASSESSMENT_IN_PROGRESS,
            self::STATUS_ASSESSMENT_INCOMPLETE, // todo: will this status ever be used?
        ];

        return in_array($this->status, $complete_statuses);
    }

    public function is_assessment_started_inprogress_or_complete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS
            || $this->status === self::STATUS_ASSESSMENT_INCOMPLETE
            || $this->status === self::STATUS_COMPLETE;
    }

    public function is_assessment_in_progress_or_complete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS
            || $this->status === self::STATUS_COMPLETE;
    }

    public function is_assessment_in_progress_or_incomplete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS
            || $this->status === self::STATUS_ASSESSMENT_INCOMPLETE;
    }

    public function is_assessment_in_progress()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS;
    }

    public function is_assessment_complete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_COMPLETE;
    }

    public function is_observation_pending_or_in_progress()
    {
        $this->validate_status();
        return in_array($this->status, [self::STATUS_OBSERVATION_PENDING, self::STATUS_OBSERVATION_IN_PROGRESS]);
    }

    public function is_observation_in_progress()
    {
        $this->validate_status();
        return $this->status == self::STATUS_OBSERVATION_IN_PROGRESS;
    }

    public function is_observation_pending()
    {
        $this->validate_status();
        return $this->status == self::STATUS_OBSERVATION_PENDING;
    }

    public function is_learner_pending()
    {
        $this->validate_status();
        return $this->status == self::STATUS_LEARNER_PENDING;
    }

    public function is_learner_action_required()
    {
        $this->validate_status();
        return ($this->status === self::STATUS_LEARNER_PENDING || $this->status === self::STATUS_LEARNER_IN_PROGRESS);
    }

    public function has_been_observed()
    {
        if ($assignment = $this->get_observer_assignment_base_or_null())
        {
            if ($observer_submission = $assignment->get_observer_submission_base_or_null())
            {
                return $observer_submission->is_submitted();
            }
        }

        return false;
    }

    public function get_observer_assignment_base_or_null(): ?observer_assignment_base
    {
        return observer_assignment_base::read_by_condition_or_null(
            [observer_assignment::COL_LEARNER_SUBMISSIONID => $this->id]);
    }

    /**
     * @return array empty array if none found
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_learner_attempts(): array
    {
        global $DB;

        // fetch stuff manually for performance reasons
        $records = $DB->get_records(
            learner_attempt::TABLE,
            [learner_attempt::COL_LEARNER_SUBMISSIONID => $this->id],
            learner_attempt::COL_TIMESUBMITTED . ' ASC');

        $attempts = [];
        foreach ($records as $record)
        {
            $attempts[] = new learner_attempt($record, $this);
        }

        return $attempts;
    }

    public function get_latest_learner_attempt_or_null(): ?learner_attempt_base
    {
        global $DB;

        $sql = 'SELECT * 
                FROM {' . learner_attempt::TABLE . '}
                WHERE ' . learner_attempt::COL_LEARNER_SUBMISSIONID . ' = ?
                AND ' . learner_attempt::COL_ATTEMPT_NUMBER . ' = ?';
        $record = $DB->get_record_sql($sql, [$this->id, $this->get_last_attemptnumber()]);

        return $record === false ? null : new learner_attempt_base($record);
    }

    /**
     * @return int 0 if no attempts
     * @throws \dml_exception
     */
    public function get_last_attemptnumber(): int
    {
        global $DB;

        $sql = 'SELECT max(' . learner_attempt::COL_ATTEMPT_NUMBER . ')
        FROM {' . learner_attempt::TABLE . '}
        WHERE ' . learner_attempt::COL_LEARNER_SUBMISSIONID . ' = ?';
        $res = $DB->get_field_sql($sql, [$this->id]);

        return $res === false ? 0 : $res;
    }

    /**
     * @return task_base
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_task_base()
    {
        return new task_base($this->taskid);
    }

    /**
     * @return int
     */
    public function get_userid(): int
    {
        return $this->userid;
    }

    public function submit(learner_attempt_base $learner_attempt): self
    {
        $error_message = null;
        if (empty($learner_attempt->get(learner_attempt::COL_TIMESUBMITTED))) // empty(0) = true
        {
            $error_message = sprintf(
                'learner attempt with id "%s" has invalid "%s" value',
                $learner_attempt->get_id_or_null(),
                learner_attempt::COL_TIMESUBMITTED);
        }
        else if (empty($learner_attempt->get(learner_attempt::COL_TEXT)))
        {
            $error_message = sprintf(
                'learner attempt with id "%s" has no text',
                $learner_attempt->get_id_or_null());
        }
        else if ($this->status != self::STATUS_LEARNER_IN_PROGRESS)
        {
            $error_message = sprintf(
                'learner submission with id "%s" has invalid "%s" value',
                $learner_attempt->get_id_or_null(),
                self::COL_STATUS);
        }
        // check and throw
        if (!is_null($error_message))
        {
            throw new coding_exception($error_message);
        }

        $this->update_status_and_save(self::STATUS_OBSERVATION_PENDING);
        //TODO: NOTIFICATIONS

        return $this;
    }

    /**
     * This method should be used when changing submission state as it performs validation to detect possible issues.
     *
     * @param string $new_status {@link status}
     * @return self
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function update_status_and_save(string $new_status): self
    {
        // TODO: perform other status validations, e.g. status == assessor_*, new_status = observer_*, which is not permitted

        $this->set(self::COL_STATUS, $new_status, true);

        return $this;
    }

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_STATUS)
        {
            // validate status is correctly set
            $allowed = [
                self::STATUS_LEARNER_PENDING,
                self::STATUS_LEARNER_IN_PROGRESS,
                self::STATUS_OBSERVATION_PENDING,
                self::STATUS_OBSERVATION_IN_PROGRESS,
                self::STATUS_OBSERVATION_INCOMPLETE,
                self::STATUS_ASSESSMENT_PENDING,
                self::STATUS_ASSESSMENT_IN_PROGRESS,
                self::STATUS_ASSESSMENT_INCOMPLETE,
                self::STATUS_COMPLETE,
            ];
            lib::validate_prop(self::COL_STATUS, $this->status, $value, $allowed, true);
        }

        return parent::set($prop, $value, $save);
    }

    private function validate_status(): void
    {
        if (empty($this->status))
        {
            throw new coding_exception(
                sprintf('Accessing observation status on an uninitialized %s class', self::class));
        }
    }
}
