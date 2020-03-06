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

use mod_observation\interfaces\templateable;

class observer_submission extends observer_submission_base implements templateable
{
    /**
     * @var observer_feedback[]
     */
    private $observer_feedback;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->observer_feedback = observer_feedback::read_all_by_condition(
            [observer_feedback::COL_OBSERVER_SUBMISSIONID => $this->id]);
    }

    /**
     * Fetches observer feedback from database
     *
     * @return observer_feedback[] empty array if no feedback
     */
    public function get_observer_feedback(): array
    {
        return $this->observer_feedback;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $timesubmitted = null;
        if (!is_null($this->timesubmitted))
        {
            $timesubmitted = userdate($this->timesubmitted);
        }

        return [
            self::COL_ID            => $this->id,
            self::COL_TIMESTARTED   => $this->timestarted,
            self::COL_TIMESUBMITTED => $timesubmitted,
            self::COL_STATUS        => !is_null($this->status) ? lib::get_status_string($this->status) : null,
        ];
    }
}
