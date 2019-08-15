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
use mod_ojt\traits\record_mapper;
use stdClass;

class completion
{
    use record_mapper;

    /**
     * OJT completion types
     */
    const COMP_TYPE_OJT       = 0;
    const COMP_TYPE_TOPIC     = 1;
    const COMP_TYPE_TOPICITEM = 2;

    /**
     * OJT completion statuses
     */
    const STATUS_INCOMPLETE       = 0;
    const STATUS_REQUIREDCOMPLETE = 1;
    const STATUS_COMPLETE         = 2;

    /**
     * OJT completion requirements
     */
    const REQ_REQUIRED = 0;
    const REQ_OPTIONAL = 1;

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
    public $type;

    /**
     * @var int
     */
    public $ojtid;

    /**
     * @var topic
     */
    public $topicid;

    /**
     * @var topic_item
     */
    public $topicitemid;

    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var int
     */
    public $timemodified;

    /**
     * @var int userid
     */
    public $modifiedby;


    /**
     * completion constructor.
     * @param int|object $id_or_record
     * @throws coding_exception
     */
    public function __construct($id_or_record = null)
    {
        self::create_from_id_or_map_to_record($id_or_record);
    }

    public static function get_user_completion(int $topicitemid, int $userid)
    {
        global $DB;
        return new completion(
            $DB->get_record('ojt_completion', ['topicitemid' => $topicitemid, 'userid' => $userid]));
    }

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    protected function get_record_from_id(int $id)
    {
        global $DB;
        return $DB->get_record('ojt_completion', array('id' => $id));
    }
}