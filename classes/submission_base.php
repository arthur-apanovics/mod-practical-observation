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

    public const COL_OBSERVATIONID        = 'observationid';
    public const COL_USERID               = 'userid';
    public const COL_STATUS               = 'status';
    public const COL_ATTEMPTS_OBSERVATION = 'attempts_observation';
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
     * @var int number of observation attempts
     */
    protected $attempts_observation;
    /**
     * @var int number of assessment attempts
     */
    protected $attempts_assessment;
    /**
     * @var int
     */
    protected $timecompleted;


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
        return learner_task_submission_base::read_all_by_condition(
            [
                learner_task_submission::COL_SUBMISISONID => $this->id,
                learner_task_submission::COL_USERID       => $this->userid,
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

    /**
     * @return int current observation attempt
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function increment_observation_attempt_number_and_save(): int
    {
        $this->set(self::COL_ATTEMPTS_ASSESSMENT, ($this->attempts_observation + 1), true);

        return $this->attempts_observation;
    }

    /**
     * @return int current assessment attempt
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function increment_assessment_attempt_number_and_save(): int
    {
        $this->set(self::COL_ATTEMPTS_ASSESSMENT, ($this->attempts_assessment + 1), true);

        return $this->attempts_assessment;
    }
}
