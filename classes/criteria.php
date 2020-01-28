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

class criteria_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_criteria';

    public const COL_TASKID             = 'taskid';
    public const COL_DESCRIPTION        = 'description';
    public const COL_DESCRIPTION_FORMAT = 'description_format';
    public const COL_ORDER              = 'order';

    /**
     * @var int
     */
    protected $taskid;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var int
     */
    protected $description_format;
    /**
     * sequence number in task
     *
     * @var int
     */
    protected $order;
}

class criteria extends criteria_base
{
    /**
     * @var observer_feedback[]
     */
    private $observer_feedback;

    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);

        $this->observer_feedback = array_map(
            function ($record) use ($userid)
            {
                return new observer_feedback($record, $userid);
            },
            observer_feedback::read_all_by_condition(
                [observer_feedback::COL_CRITERIAID => $this->id, $this->observer_feedback]));
    }
}
