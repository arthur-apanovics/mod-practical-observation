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
    public const COL_STATUS                = 'status';
    public const COL_TIMESUBMITTED         = 'timesubmitted';

    public const STATUS_NOT_COMPLETE = 'not_complete';
    public const STATUS_COMPLETE     = 'complete';

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
     * Possible values: {@link STATUS_NOT_COMPLETE}, {@link STATUS_COMPLETE}
     * @var string
     */
    protected $status;
    /**
     * @var int
     */
    protected $timesubmitted;

    public function is_complete()
    {
        return $this->status == self::STATUS_COMPLETE;
    }
}
