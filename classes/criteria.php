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

class criteria extends criteria_base implements templateable
{
    /**
     * @var observer_feedback[]
     */
    private $observer_feedback;

    public function __construct($id_or_record, int $userid = null)
    {
        parent::__construct($id_or_record);

        $this->observer_feedback = observer_feedback::to_class_instances(
            observer_feedback::read_all_by_condition([observer_feedback::COL_CRITERIAID => $this->id]));
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $observer_feedback_data = [];
        foreach ($this->observer_feedback as $observer_feedback)
        {
            $observer_feedback_data[] = $observer_feedback->export_template_data();
        }

        return [
            self::COL_ID          => $this->id,
            self::COL_NAME        => $this->name,
            self::COL_DESCRIPTION => $this->description,
            self::COL_SEQUENCE    => $this->sequence,

            'observer_feedback' => $observer_feedback_data
        ];
    }
}
