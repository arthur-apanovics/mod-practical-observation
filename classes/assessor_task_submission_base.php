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

class assessor_task_submission_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_assessor_task_submission';

    public const COL_ASSESSORID                = 'assessorid';
    public const COL_LEARNER_TASK_SUBMISSIONID = 'learner_task_submissionid';
    public const COL_OUTCOME                   = 'outcome';

    public const OUTCOME_NOT_COMPLETE = 'not_complete';
    public const OUTCOME_COMPLETE     = 'complete';

    /**
     * @var int
     */
    protected $assessorid;
    /**
     * @var int
     */
    protected $learner_task_submissionid;
    /**
     * One of:
     * <ul>
     * <li>null (not yet submitted)</li>
     * <li>{@link OUTCOME_COMPLETE}</li>
     * <li>{@link OUTCOME_NOT_COMPLETE}</li>
     *</ul>
     * @var string
     */
    protected $outcome;

    public function get_learner_task_submission()
    {
        return learner_task_submission_base::read_by_condition_or_null(
            [learner_task_submission::COL_ID => $this->learner_task_submissionid], true);
    }

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_OUTCOME)
        {
            // validate status is correctly set
            $allowed = [self::OUTCOME_NOT_COMPLETE, self::OUTCOME_COMPLETE];
            lib::validate_prop(self::COL_OUTCOME, $this->outcome, $value, $allowed, false);
        }

        return parent::set($prop, $value, $save);
    }
}
