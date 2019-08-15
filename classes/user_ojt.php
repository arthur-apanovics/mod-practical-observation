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
use dml_exception;
use mod_ojt\models\completion;
use mod_ojt\models\ojt;
use mod_ojt\models\topic;
use mod_ojt\traits\record_mapper;
use stdClass;

class user_ojt extends ojt
{
    use record_mapper;

    /**
     * @var int
     */
    public $userid;

    /**
     * @var user_topic[]
     */
    public $topics;


    public function __construct($id_or_record, $userid)
    {
        parent::__construct($id_or_record);

        $this->userid = $userid;
        $this->topics = user_topic::get_user_topics($this->id, $this->userid);
    }

    /**
     * Get OJT database object from OJT id and user id
     *
     * @param int $ojtid
     * @param int $userid
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     *
     * @deprecated Do not use, this method was written for PHP5
     */
    public static function get_user_ojt(int $ojtid, int $userid)
    {
        global $DB;

        // Get the ojt details.
        $sql = 'SELECT ' . $userid . ' AS userid, b.*, CASE WHEN c.status IS NULL THEN '
               . completion::STATUS_INCOMPLETE . ' ELSE c.status END AS status, c.comment
               FROM {ojt} b
               LEFT JOIN {ojt_completion} c ON b.id = c.ojtid 
               AND c.type = ? 
               AND c.userid = ?
               WHERE b.id = ?';
        $ojt = $DB->get_record_sql($sql, array(completion::COMP_TYPE_OJT, $userid, $ojtid), MUST_EXIST);

        // Add topics and completion data.
        $ojt->topics = user_topic::get_user_topic_records($userid, $ojtid);
        foreach ($ojt->topics as $i => $topic)
        {
            $ojt->topics[$i]->items = array();
        }
        if (empty($ojt->topics))
        {
            return $ojt;
        }

        // Add items and completion info.
        list($insql, $params) = $DB->get_in_or_equal(array_keys($ojt->topics));
        $sql    = "SELECT i.*, CASE WHEN c.status IS NULL THEN "
                  . completion::STATUS_INCOMPLETE .
                  " ELSE c.status END AS status, c.comment, c.timemodified, c.modifiedby, bw.witnessedby, bw.timewitnessed,"
                  . get_all_user_name_fields(true, 'moduser', '', 'modifier')
                  . "," . get_all_user_name_fields(true, 'witnessuser', '', 'itemwitness') . "
                  FROM {ojt_topic_item} i
                  LEFT JOIN {ojt_completion} c ON i.id = c.topicitemid AND c.type = ? AND c.userid = ?
                  LEFT JOIN {user} moduser ON c.modifiedby = moduser.id
                  LEFT JOIN {ojt_item_witness} bw ON bw.topicitemid = i.id AND bw.userid = ?
                  LEFT JOIN {user} witnessuser ON bw.witnessedby = witnessuser.id
                  WHERE i.topicid {$insql}
                  ORDER BY i.topicid, i.id";
        $params = array_merge(array(completion::COMP_TYPE_TOPICITEM, $userid, $userid), $params);
        $items  = $DB->get_records_sql($sql, $params);

        foreach ($items as $i => $item)
        {
            $ojt->topics[$item->topicid]->items[$i] = $item;
        }

        return $ojt;
    }
}