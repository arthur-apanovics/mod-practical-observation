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
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation\models;

use coding_exception;
use mod_observation\interfaces\crud;
use mod_observation\traits\db_record_base;
use mod_observation\traits\record_mapper;
use stdClass;

class topic_item extends db_record_base
{
    protected const TABLE = 'observation_topic_item';

    /**
     * @var int
     */
    public $topicid;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $completionreq;

    /**
     * @var bool
     */
    public $allowfileuploads;

    /**
     * @var bool
     */
    public $allowselffileuploads;


    public static function get_topic_items_for_topic($topicid)
    {
        global $DB;

        $topic_items = [];
        foreach ($DB->get_records('observation_topic_item', ['topicid' => $topicid]) as $record)
        {
            $topic_items[] = new self($record);
        }

        return $topic_items;
    }

    public static function delete_topic_item($itemid, $context)
    {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('observation_topic_item', array('id' => $itemid));
        $DB->delete_records('observation_completion', array('topicitemid' => $itemid));
        $DB->delete_records('observation_item_witness', array('topicitemid' => $itemid));

        // Delete item files
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_observation', 'topicitemfiles' . $itemid);

        $transaction->allow_commit();
    }
}