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

use coding_exception;
use mod_ojt\traits\db_record_base;

class attempt extends db_record_base
{
    protected const TABLE = 'ojt_attempt';

    /**
     * @var int
     */
    public $userid;

    /**
     * @var int
     */
    public $topicitemid;

    /**
     * attempt sequence number
     *
     * @var int
     */
    public $sequence;

    /**
     * @var string
     */
    public $text;

    /**
     * @var int
     */
    public $timemodified;


    /**
     * @param int $topicitemid
     * @param int $userid
     * @return attempt[] attempts in ascending order
     * @throws \dml_exception
     * @throws coding_exception
     */
    public static function get_user_attempts(int $topicitemid, int $userid)
    {
        global $DB;

        $records  = $DB->get_records(self::TABLE, ['topicitemid' => $topicitemid, 'userid' => $userid], 'sequence');
        $attempts = [];
        foreach ($records as $record)
        {
            $attempts[] = new self($record);
        }

        return $attempts;
    }

    /**
     * @param int $topicitemid
     * @param int $userid
     * @return attempt|null null if not attempts found
     */
    public static function get_latest_user_attempt(int $topicitemid, int $userid)
    {
        if ($recs = self::get_user_attempts($topicitemid, $userid))
        {
            return $recs[count($recs) - 1];
        }
        else
        {
            return null;
        }
    }
}