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

use core_user;
use mod_observation\interfaces\templateable;

class observer extends observer_base implements templateable
{
    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $added_by  = fullname(core_user::get_user($this->added_by));
        $timeadded = usertime($this->timeadded);

        $modified_by  = null;
        $timemodified = null;
        if (!is_null($this->modified_by) && !is_null($this->timemodified))
        {
            $modified_by  = fullname(core_user::get_user($this->modified_by));
            $timemodified = usertime($this->timemodified);
        }

        return [
            self::COL_ID             => $this->id,
            self::COL_FULLNAME       => $this->fullname,
            self::COL_PHONE          => $this->phone,
            self::COL_EMAIL          => $this->email,
            self::COL_POSITION_TITLE => $this->position_title,

            self::COL_ADDED_BY     => $added_by,
            self::COL_TIMEADDED    => $timeadded,
            self::COL_MODIFIED_BY  => $modified_by,
            self::COL_TIMEMODIFIED => $timemodified,
        ];
    }
}
