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

class observer_submission_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer_submission';

    public const COL_OBSERVER_ASSIGNMENTID = 'observer_assignmentid';
    public const COL_TIMESTARTED           = 'timestarted';
    public const COL_OUTCOME               = 'outcome';
    public const COL_TIMESUBMITTED         = 'timesubmitted';

    public const OUTCOME_NOT_COMPLETE = 'not_complete';
    public const OUTCOME_COMPLETE     = 'complete';

    /**
     * @var int
     */
    protected $observer_assignmentid;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * Outcome of observation, NULL if not submitted yet.
     * Possible values: null, {@link OUTCOME_NOT_COMPLETE}, {@link OUTCOME_COMPLETE}
     * @var string|null
     */
    protected $outcome;
    /**
     * @var int
     */
    protected $timesubmitted;

    /**
     * @param string $outcome {@link outcome}
     * @return observer_submission_base
     */
    public function submit(string $outcome): self
    {
        // setting outcome here also validates that correct value has been passed
        $this->set(self::COL_OUTCOME, $outcome);
        $this->set(self::COL_TIMESUBMITTED, time());

        $observer_assignment = $this->get_observer_assignment_base();
        $learner_submission = $observer_assignment->get_learner_submission_base();

        $status = ($outcome == self::OUTCOME_COMPLETE)
            ? learner_submission::STATUS_ASSESSMENT_PENDING
            : learner_submission::STATUS_OBSERVATION_INCOMPLETE;
        $learner_submission->update_status_and_save($status);

        //TODO: notifications

        return $this->update();
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

    public function is_complete(): bool
    {
        return $this->outcome == self::OUTCOME_COMPLETE;
    }

    public function is_submitted(): bool
    {
        return !is_null($this->outcome);
    }

    public function get_observer_assignment_base()
    {
        return observer_assignment_base::read_by_condition_or_null(
            [observer_assignment::COL_ID => $this->observer_assignmentid], true);
    }
}
