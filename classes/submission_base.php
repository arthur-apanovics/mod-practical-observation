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


use mod_observation\traits\submission_status_store;

class submission_base extends submission_status_store
{
    public const TABLE = OBSERVATION . '_submission';

    public const COL_OBSERVATIONID = 'observationid';
    public const COL_USERID        = 'userid';
    public const COL_STATUS        = 'status';
    public const COL_TIMESTARTED   = 'timestarted';
    public const COL_TIMECOMPLETED = 'timecompleted';

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
     * @var int
     */
    protected $timecompleted;


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

    public function update_status_and_save(string $new_status): self
    {
        // extra validation to be done here

        $this->set(self::COL_STATUS, $new_status, true);
        return $this;
    }

    public function get_userid()
    {
        return $this->userid;
    }

    /**
     * @return learner_task_submission_base[]
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_learner_task_submisisons(): array
    {
        return learner_task_submission_base::read_all_by_condition([
                learner_task_submission::COL_SUBMISISONID => $this->id,
                learner_task_submission::COL_USERID => $this->userid,
            ]);
    }

    /**
     * @return observation_base
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function get_observation()
    {
        return new observation_base($this->observationid);
    }
}
