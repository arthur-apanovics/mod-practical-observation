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

use mod_observation;

class observer_assignment extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer_assignment';

    public const COL_LEARNER_TASK_SUBMISSION = 'learner_task_submission';
    public const COL_OBSERVER                = 'observer';
    public const COL_CHANGE_EXPLAIN          = 'change_explain';
    public const COL_OBSERVATION_ACCEPTED    = 'observation_accepted';
    public const COL_TIMEASSIGNED            = 'timeassigned';
    public const COL_TOKEN                   = 'token';

    /**
     * @var int
     */
    protected $learner_task_submission;
    /**
     * @var int
     */
    protected $observer;
    /**
     * optional. used when observer change is requested
     *
     * @var string
     */
    protected $change_explain;
    /**
     * null if no decision made yet, false if observer declined observation, true if accepted and observer requirements confirmed
     *
     * @var bool
     */
    protected $observation_accepted;
    /**
     * @var int
     */
    protected $timeassigned;
    /**
     * @var string
     */
    protected $token;
}