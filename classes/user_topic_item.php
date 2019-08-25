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
use mod_ojt\models\completion;
use mod_ojt\models\item_witness;
use mod_ojt\models\topic_item;
use mod_ojt\traits\record_mapper;
use stdClass;

class user_topic_item extends topic_item
{
    /**
     * @var int
     */
    public $userid;

    /**
     * @var completion
     */
    public $completion;

    /**
     * @var item_witness|null null if no record exists!
     */
    public $witness;

    /**
     * user_topic_item constructor.
     * @param int|object $id_or_record instance id, database record or existing class or base class
     * @param int $userid
     * @throws coding_exception
     */
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);

        $this->userid     = $userid;
        $this->completion = completion::get_user_completion($this->id, $this->userid);
        $this->witness    = item_witness::get_user_item_witness($this->id, $userid);
    }

    public static function get_user_topic_items_for_topic(int $topicid, int $userid)
    {
        global $DB;

        $topic_items = [];
        foreach ($DB->get_records('ojt_topic_item', ['topicid' => $topicid]) as $record)
            $topic_items[$record->id] = new self($record, $userid);

        return $topic_items;
    }
}