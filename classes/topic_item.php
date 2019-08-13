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

class topic_item
{
    use record_mapper;

    /**
     * @var int
     */
    public $id;

    /**
     * @var topic
     */
    public $topic;

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

    public static function delete_topic_item($itemid, $context)
    {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('ojt_topic_item', array('id' => $itemid));
        $DB->delete_records('ojt_completion', array('topicitemid' => $itemid));
        $DB->delete_records('ojt_item_witness', array('topicitemid' => $itemid));

        // Delete item files
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_ojt', 'topicitemfiles' . $itemid);

        $transaction->allow_commit();
    }

    /**
     * topic_item constructor.
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

        return $DB->get_record('ojt_topic_item', array('id' => $id));
    }
}