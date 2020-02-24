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

    public const COL_USERID        = 'userid';
    public const COL_TASKID        = 'taskid';
    public const COL_STATUS        = 'status';
    public const COL_TIMESTARTED   = 'timestarted';
    public const COL_TIMECOMPLETED = 'timecompleted';

    public const STATUS_NOT_STARTED             = 'not_started';
    public const STATUS_LEARNER_IN_PROGRESS     = 'learner_in_progress';
    public const STATUS_OBSERVATION_PENDING     = 'observation_pending';
    public const STATUS_OBSERVATION_IN_PROGRESS = 'observation_in_progress';
    public const STATUS_OBSERVATION_INCOMPLETE  = 'observation_incomplete';
    public const STATUS_ASSESSMENT_PENDING      = 'assessment_pending';
    public const STATUS_ASSESSMENT_IN_PROGRESS  = 'assessment_in_progress';
    public const STATUS_ASSESSMENT_INCOMPLETE   = 'assessment_incomplete';
    public const STATUS_COMPLETE                = 'complete';

    /**
     * @var int
     */
    protected $userid;
    /**
     * @var int
     */
    protected $taskid;
    /**
     * ENUM ('not_started', 'learner_in_progress', 'observation_pending', 'observation_in_progress',
     * 'observation_incomplete', 'assessment_pending', 'assessment_in_progress', 'assessment_incomplete', 'complete')
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
                self::STATUS_NOT_STARTED,
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

    // /**
    //  * @param int $id observation id
    //  * @param int $userid
    //  * @param int $taskid
    //  * @return learner_submission_base[]
    //  * @throws \dml_exception
    //  */
    // public static function get_submissions(int $id, int $userid = null, int $taskid = null): array
    // {
    //     // TODO check for multiple records if taskid provided and throw
    //     return self::read_all_by_condition(
    //         [self::COL_ID => $id, self::COL_USERID => $userid, self::COL_TASKID => $taskid]);
    // }
}
