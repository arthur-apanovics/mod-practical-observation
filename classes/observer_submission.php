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
use dml_exception;
use ReflectionException;

class observer_submission_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer_submission';

    public const COL_OBSERVER_ASSIGNMENTID = 'observer_assignmentid';
    public const COL_TIMESTARTED           = 'timestarted';
    public const COL_TIMESUBMITTED         = 'timesubmitted';

    /**
     * @var int
     */
    protected $observer_assignmentid;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * @var int
     */
    protected $timesubmitted;
}

class observer_submission extends observer_submission_base
{
    /* NOTE: observer_feedback is attached to criteria class */

    // /**
    //  * @var observer_feedback[]
    //  */
    // private $observer_feedback;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        // $this->observer_feedback =
        //     observer_feedback::read_all_by_condition([observer_feedback::COL_OBSERVER_SUBMISSIONID => $this->id]);
    }

    /**
     * Fetches observer feedback from database
     *
     * @return observer_feedback[]
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_observer_feedback(): array
    {
        return observer_feedback::read_all_by_condition([observer_feedback::COL_OBSERVER_SUBMISSIONID => $this->id]);
    }
}
