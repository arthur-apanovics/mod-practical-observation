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
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ojt\models;

use mod_ojt\traits\db_record_base;

class attempt_feedback extends db_record_base
{
    protected const TABLE = 'ojt_attempt_feedback';

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $attemptid;

    /**
     * @var int
     */
    public $emailassignmentid;

    /**
     * @var string
     */
    public $text;

    /**
     * @var int
     */
    public $timemodified;


    /**
     * @param int $id
     * @return attempt_feedback|null
     * @throws \coding_exception
     */
    public static function get_feedback_for_attempt(int $id)
    {
        if ($record = self::fetch_record_from_id($id))
        {
            return new self($record);
        }

        return null;
    }

}