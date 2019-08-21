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
use mod_ojt\interfaces\crud;
use mod_ojt\traits\record_mapper;
use stdClass;

class topic_signoff implements crud
{
    use record_mapper;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $userid;

    /**
     * @var int
     */
    public $topicid;

    /**
     * @var bool
     */
    public $signedoff;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var int
     */
    public $timemodified;

    /**
     * @var int
     */
    public $modifiedby;


    /**
     * signoff constructor.
     * @param int|object $id_or_record instance id, database record or existing class or base class
     * @throws coding_exception
     */
    public function __construct($id_or_record = null)
    {
        self::create_from_id_or_map_to_record($id_or_record);
    }

    public static function get_user_topic_signoff(int $topicid, int $userid)
    {
        global $DB;
        return new topic_signoff(
            $DB->get_record('ojt_topic_signoff', ['userid' => $userid, 'topicid' => $topicid]));
    }

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    public static function fetch_record_from_id(int $id)
    {
        global $DB;
        return $DB->get_record('ojt_topic_signoff', array('id' => $id));
    }

    /**
     * Create DB entry from current state
     *
     * @return bool|int new record id or false if failed
     */
    public function create()
    {
        global $DB;
        return $DB->insert_record('ojt_topic_signoff', self::get_record_from_object());
    }

    /**
     * Read latest values from DB and refresh current object
     *
     * @return object
     */
    public function read()
    {
        global $DB;
        $this->map_to_record($DB->get_record('ojt_topic_signoff', ['id' => $this->id]));
		return $this;
    }

    /**
     * Save current state to DB
     *
     * @return bool
     */
    public function update()
    {
        global $DB;
        return $DB->update_record('ojt_topic_signoff', $this->get_record_from_object());
    }

    /**
     * Delete current object from DB
     *
     * @return bool
     */
    public function delete()
    {
        global $DB;
        return $DB->delete_records('ojt_topic_signoff', ['id' => $this->id]);
    }
}