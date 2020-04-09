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

class observer_assignment_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer_assignment';

    public const COL_LEARNER_SUBMISSIONID = 'learner_submissionid';
    public const COL_OBSERVERID           = 'observerid';
    public const COL_CHANGE_EXPLAIN       = 'change_explain';
    public const COL_TIMEASSIGNED         = 'timeassigned';
    public const COL_OBSERVATION_ACCEPTED = 'observation_accepted';
    public const COL_TIMEACCEPTED         = 'timeaccepted';
    public const COL_TOKEN                = 'token';
    public const COL_ACTIVE               = 'active';

    /**
     * @var int
     */
    protected $learner_submissionid;
    /**
     * @var int
     */
    protected $observerid;
    /**
     * optional. used when observer change is requested
     *
     * @var string
     */
    protected $change_explain;
    /**
     * @var int
     */
    protected $timeassigned;
    /**
     * null if no decision made yet, false if observer declined observation, true if accepted and observer requirements confirmed
     *
     * @var bool
     */
    protected $observation_accepted;
    /**
     * @var null|int
     */
    protected $timeaccepted;
    /**
     * @var string
     */
    protected $token;
    /**
     * indicates if this is the current assignment for related learner_submission
     *
     * @var bool
     */
    protected $active;

    public function is_active()
    {
        return (bool) $this->active;
    }

    /**
     * @return bool true if accepted, false if declined OR no decision yet
     */
    public function is_accepted()
    {
        return (bool) $this->observation_accepted;
    }

    public function get_learner_submission_base()
    {
        return new learner_submission_base($this->learner_submissionid);
    }

    public function get_observer_submission_base_or_null(): ?observer_submission_base
    {
        return observer_submission_base::read_by_condition_or_null(
            [observer_submission::COL_OBSERVER_ASSIGNMENTID => $this->id]);
    }
}
