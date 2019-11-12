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

namespace mod_ojt;


use coding_exception;
use mod_ojt\models\attempt;
use mod_ojt\models\attempt_feedback;

class user_attempt extends attempt
{
    /**
     * @var int
     */
    public $userid;

    /**
     * @var attempt_feedback
     */
    public $feedback;

    /**
     * user_attempt constructor.
     * @param int|object $id_or_record instance id, database record or existing class or base class
     * @param int $userid
     * @throws coding_exception
     */
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);
        $this->userid = $userid;
        $this->feedback = attempt_feedback::get_feedback_for_attempt($this->id);
    }

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
            $attempts[] = new self($record, $userid);
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
        // TODO Optimise with a sql query
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