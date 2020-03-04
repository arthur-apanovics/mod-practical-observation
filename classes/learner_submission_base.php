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

    /**
     * This method should be used when changing submission state as it performs validation to detect possible issues.
     *
     * @param string $new_status {@link status}
     * @return self
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function update_status(string $new_status): self
    {
        if ($this->status == $new_status)
        {
            debugging(
                sprintf(
                    '%s %s is already "%s". This should not normally happen',
                    self::class,
                    self::COL_STATUS,
                    $new_status),
                DEBUG_DEVELOPER,
                debug_backtrace());
        }

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
            if (!in_array($value, $allowed))
            {
                throw new coding_exception(
                    sprintf("'$value' is not a valid value for '%s' in '%s'", self::COL_STATUS, get_class($this)));
            }
        }

        return parent::set($prop, $value, $save);
    }
}
