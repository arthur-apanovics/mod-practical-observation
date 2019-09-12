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
use dml_exception;
use mod_ojt\interfaces\crud;
use mod_ojt\traits\db_record_base;
use mod_ojt\traits\record_mapper;
use stdClass;

class completion extends db_record_base
{
    /**
     * OJT completion types
     */
    const COMP_TYPE_OJT       = 0;
    const COMP_TYPE_TOPIC     = 1;
    const COMP_TYPE_TOPICITEM = 2;

    /**
     * OJT completion statuses
     */

    /**
     * Completion criteria not met
     */
    const STATUS_INCOMPLETE       = 0;
    /**
     * Required topics have been completed
     */
    const STATUS_REQUIREDCOMPLETE = 1;
    /**
     * Completion requirementshave been met
     */
    const STATUS_COMPLETE         = 2;

    /**
     * OJT completion requirements
     */
    const REQ_REQUIRED = 0;
    const REQ_OPTIONAL = 1;

    protected const TABLE = 'ojt_completion';

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
     * @var string external user email
     */
    public $observeremail;


    /**
     * @param int $topicitemid
     * @param int $userid
     * @param int|null $type COMP_TYPE_OJT | COMP_TYPE_TOPIC | COMP_TYPE_TOPICITEM; Indicates completion requirement type
     * @return completion
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_user_completion(int $topicitemid, int $userid, int $type = null)
    {
        global $DB;
        $args = ['topicitemid' => $topicitemid, 'userid' => $userid];
        $args = is_int($type) ? $args + ['type' => $type] : $args;
        $rec = $DB->get_record('ojt_completion', $args);

        $completion = new self($rec);

        if (!$rec)
        {
            $completion->status = self::STATUS_INCOMPLETE;
        }

        return $completion;
    }
}