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
    private $id;

    /**
     * @var int
     */
    private $userid;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $ojtid;

    /**
     * @var topic
     */
    private $topic;

    /**
     * @var topic_item
     */
    private $topicitem;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var int
     */
    private $timemodified;

    /**
     * @var int userid
     */
    private $modifiedby;


    /**
     * completion constructor.
     * @param int|stdClass $id_or_record
     * @throws coding_exception
     */
    public function __construct($id_or_record = null)
    {
        self::createFromIdOrMapToRecord($id_or_record);
    }

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    protected function getRecordFromId(int $id)
    {
        global $DB;

        return $DB->get_record('ojt_completion', array('id' => $id));
    }
}