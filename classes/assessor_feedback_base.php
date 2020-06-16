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

class assessor_feedback_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_assessor_feedback';

    public const COL_ASSESSOR_TASK_SUBMISSIONID = 'assessor_task_submissionid';
    public const COL_ATTEMPTID                  = 'attemptid';
    public const COL_TEXT                       = 'text';
    public const COL_TEXT_FORMAT                = 'text_format';
    public const COL_OUTCOME                    = 'outcome';
    public const COL_TIMESUBMITTED              = 'timesubmitted';

    public const OUTCOME_NOT_COMPLETE = 'not_complete';
    public const OUTCOME_COMPLETE     = 'complete';

    /**
     * @var int
     */
    protected $assessor_task_submissionid;
    /**
     * @var int {@link learner_attempt}
     */
    protected $attemptid;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var int
     */
    protected $text_format;
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
    /**
     * @var int
     */
    protected $timesubmitted;


    public function get_assessor_task_submission()
    {
        return assessor_task_submission_base::read_or_null($this->assessor_task_submissionid);
    }

    public function is_submitted(): bool
    {
        return (!is_null($this->outcome) && !empty($this->timesubmitted));
    }

    public function is_marked_complete(): bool
    {
        return ($this->is_submitted() && $this->outcome === self::OUTCOME_COMPLETE);
    }

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_OUTCOME)
        {
            // validate status is correctly set.
            $allowed = [self::OUTCOME_NOT_COMPLETE, self::OUTCOME_COMPLETE];
            if (!in_array($value, $allowed))
            {
                throw new coding_exception(
                    sprintf("'$value' is not a valid value for property '%s'", $prop));
            }

            // because feedback can be re-submitted, we allow setting outcome of the same value
        }

        return parent::set($prop, $value, $save);
    }
}
