<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
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
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

use coding_exception;

class assessor_task_submission extends db_model_base
{
    public const TABLE = OBSERVATION . '_assessor_task_submission';

    public const COL_ASSESSOR                = 'assessor';
    public const COL_LEARNER_TASK_SUBMISSION = 'learner_task_submission';
    public const COL_STATUS                  = 'status';

    public const STATUS_REQUESTED_NEW_OBSERVATION = 'requested_new_observation';
    public const STATUS_NOT_COMPLETE              = 'not_complete';
    public const STATUS_COMPLETE                  = 'complete';

    /**
     * @var int
     */
    protected $assessor;
    /**
     * @var int
     */
    protected $learner_task_submission;
    /**
     * ENUM ('requested_new_observation', 'not_complete', 'complete')
     *
     * @var
     */
    protected $status;

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_STATUS)
        {
            // validate status is correctly set
            $allowed = [self::STATUS_REQUESTED_NEW_OBSERVATION, self::STATUS_NOT_COMPLETE, self::STATUS_COMPLETE];
            if (!in_array($value, $allowed))
            {
                throw new coding_exception(
                    sprintf("'$value' is not a valid value for '%s' in '%s'", self::COL_STATUS, __CLASS__));
            }
        }

        return parent::set($prop, $value, $save);
    }
}