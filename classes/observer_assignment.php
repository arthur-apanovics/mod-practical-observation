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

class observer_assignment extends observer_assignment_base implements templateable
{
    /**
     * @var observer
     */
    private $observer;
    /**
     * @var observer_submission
     */
    private $observer_submission;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->observer = new observer($this->observerid);

        $this->observer_submission = observer_submission::read_by_condition(
            [observer_submission::COL_OBSERVER_ASSIGNMENTID => $this->id],
            $this->observation_accepted // must exist if observation has been accepted
        );
    }

    /**
     * @return observer
     */
    public function get_observer(): observer
    {
        return $this->observer;
    }

    /**
     * @return observer_submission
     */
    public function get_observer_submission(): observer_submission
    {
        return $this->observer_submission;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        return [
            self::COL_ID                   => $this->id,
            self::COL_CHANGE_EXPLAIN       => $this->change_explain,
            self::COL_OBSERVATION_ACCEPTED => $this->observation_accepted,
            self::COL_TIMEASSIGNED         => usertime($this->timeassigned),
            self::COL_ACTIVE               => $this->active,

            'observer'            => $this->observer->export_template_data(),
            'observer_submission' => $this->observer_submission->export_template_data(),
        ];
    }
}
