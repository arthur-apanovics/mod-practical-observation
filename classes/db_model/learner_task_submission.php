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

namespace mod_observation\db_model\obsolete;

use mod_observation\db_model\db_model_base;

class task_submission_status
{
    const NOT_STARTED             = 'not_started';
    const LEARNER_IN_PROGRESS     = 'learner_in_progress';
    const OBSERVATION_PENDING     = 'observation_pending';
    const OBSERVATION_IN_PROGRESS = 'observation_in_progress';
    const OBSERVATION_INCOMPLETE  = 'observation_incomplete';
    const ASSESSMENT_PENDING      = 'assessment_pending';
    const ASSESSMENT_IN_PROGRESS  = 'assessment_in_progress';
    const ASSESSMENT_INCOMPLETE   = 'assessment_incomplete';
    const COMPLETE                = 'complete';
}

class learner_task_submission_model extends db_model_base
{
    protected const TABLE = 'learner_task_submission';

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
    protected $user; // fk user
    /**
     * @var int
     */
    protected $task;
    /**
     * ENUM ('not_started', 'learner_in_progress', 'observation_pending', 'observation_in_progress', 'observation_incomplete', 'assessment_pending', 'assessment_in_progress', 'assessment_incomplete', 'complete')
     * @var task_submission_status
     */
    protected $status;
    /**
     * @var bigint
     */
    protected $timestarted;
    /**
     * @var bigint
     */
    protected $timecompleted;
}