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
use mod_observation\event\attempt_submitted;
use mod_observation\traits\submission_status_store;

class learner_task_submission_base extends submission_status_store
{
    public const TABLE = OBSERVATION . '_learner_task_submission';

    public const COL_TASKID               = 'taskid';
    public const COL_SUBMISISONID         = 'submisisonid';
    public const COL_USERID               = 'userid';
    public const COL_STATUS               = 'status';
    public const COL_TIMESTARTED          = 'timestarted';
    public const COL_TIMECOMPLETED        = 'timecompleted';
    public const COL_ATTEMPTS_OBSERVATION = 'attempts_observation';

    /**
     * @var int
     */
    protected $taskid;
    /**
     * @var int {@link submission_base}
     */
    protected $submisisonid;
    /**
     * @var int
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
     * @var int
     */
    protected $timecompleted;
    /**
     * @var int number of observation attempts
     */
    protected $attempts_observation;


    public function get_latest_observer_assignment_or_null(): ?observer_assignment_base
    {
        global $DB;

        $result = $DB->get_records(
            observer_assignment::TABLE,
            [observer_assignment::COL_LEARNER_TASK_SUBMISSIONID => $this->id],
            sprintf('%s DESC', observer_assignment::COL_TIMEASSIGNED),
            '*',
            0,
            1);

         return !empty($result) ? new observer_assignment_base(reset($result)) : null;
    }

    /**
     * Fetches currently active observer assignment or null if one does not exist
     *
     * @return observer_assignment_base|null null if no record found
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_active_observer_assignment_or_null(): ?observer_assignment_base
    {
        return observer_assignment_base::read_by_condition_or_null(
            [
                observer_assignment::COL_LEARNER_TASK_SUBMISSIONID => $this->id,
                observer_assignment::COL_ACTIVE                    => true
            ]);
    }

    /**
     * @return assessor_task_submission_base|null
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_assessor_task_submission_or_null()
    {
        return assessor_task_submission_base::read_by_condition_or_null(
            [assessor_task_submission::COL_LEARNER_TASK_SUBMISSIONID => $this->id]);
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
            [learner_attempt::COL_LEARNER_TASK_SUBMISSIONID => $this->id],
            learner_attempt::COL_TIMESUBMITTED . ' ASC');

        $attempts = [];
        foreach ($records as $record)
        {
            $attempts[] = new learner_attempt($record, $this);
        }

        return $attempts;
    }

    /**
     * @param int $attempt_id
     * @return learner_attempt_base|null
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_learner_attempt_or_null(int $attempt_id): ?learner_attempt_base
    {
        return learner_attempt_base::read_or_null($attempt_id);
    }

    public function get_latest_learner_attempt_or_null(): ?learner_attempt_base
    {
        global $DB;

        $sql = 'SELECT * 
                FROM {' . learner_attempt::TABLE . '}
                WHERE ' . learner_attempt::COL_LEARNER_TASK_SUBMISSIONID . ' = ?
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
        WHERE ' . learner_attempt::COL_LEARNER_TASK_SUBMISSIONID . ' = ?';
        $res = $DB->get_field_sql($sql, [$this->id]);

        return $res === false ? 0 : $res;
    }

    /**
     * @return submission_base
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_submission(): submission_base
    {
        return submission_base::read_or_null($this->submisisonid, true);
    }

    /**
     * @return task_base
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     * @throws \dml_exception
     */
    public function get_task_base()
    {
        return task_base::read_or_null($this->taskid);
    }

    /**
     * @return int
     */
    public function get_userid(): int
    {
        return $this->userid;
    }

    /**
     * @param learner_attempt_base $learner_attempt
     * @return $this
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function submit(learner_attempt_base $learner_attempt): self
    {
        $learner_attempt->validate($this);

        // increment observation attempt count
        $this->increment_observation_attempt_number(false);
        // set new status and save
        $this->update_status_and_save(self::STATUS_OBSERVATION_PENDING);

        $submisison = $this->get_submission();
        $observation = $submisison->get_observation();
        if ($observation->all_tasks_observation_pending_or_in_progress($this->get_userid()))
        {
            // submission for every task has been made, update activity submission status
            $submisison->update_status_and_save(submission::STATUS_OBSERVATION_PENDING);
        }

        // trigger event
        $event = attempt_submitted::create(
            [
                'context'  => \context_module::instance($observation->get_cm()->id),
                'objectid' => $learner_attempt->get_id_or_null(),
                'userid' => $this->userid,
                'other'    => [
                    'learner_task_submissionid' => $this->get_id_or_null(),
                ]
            ]);
        $event->trigger();

        return $this;
    }

    /**
     * @param bool $save save immediately
     * @return int current observation attempt
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function increment_observation_attempt_number(bool $save): int
    {
        $this->set(self::COL_ATTEMPTS_OBSERVATION, ($this->attempts_observation + 1), $save);

        return $this->attempts_observation;
    }

    /**
     * This method should be used when changing submission state as it performs validation to detect possible issues
     * and sets stuff like 'timecompleted', etc.
     *
     * @param string $new_status {@link status}
     * @return self
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function update_status_and_save(string $new_status): self
    {
        // TODO: perform other status validations, e.g. status == assessor_*, new_status = observer_*, which is not permitted

        if ($new_status === self::STATUS_COMPLETE)
        {
            $this->set(self::COL_TIMECOMPLETED, time());
        }

        $this->set(self::COL_STATUS, $new_status, true);

        return $this;
    }
}
