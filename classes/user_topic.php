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


use mod_ojt\models\completion;
use mod_ojt\models\topic_signoff;
use mod_ojt\models\topic;

class user_topic extends topic
{
    /**
     * @var int
     */
    public $userid;

    /**
     * @var user_topic_item[]
     */
    public $topic_items;

    /**
     * @var topic_signoff
     */
    public $signoff;

    /**
     * One of 3 possible \mod_ojt\models\completion statuses:
     * STATUS_INCOMPLETE | STATUS_REQUIREDCOMPLETE | STATUS_COMPLETE
     * @var int
     */
    public $completion_status;

    /**
     * user_topic constructor.
     * @param int|object $id_or_record
     * @param int        $userid
     */
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);

        $this->userid      = $userid;
        $this->topic_items = user_topic_item::get_user_topic_items_for_topic($this->id, $this->userid);
        $this->signoff     = topic_signoff::get_user_topic_signoff($this->id, $this->userid);
        $this->completion_status = $this->get_completion_status();
    }

    public static function get_user_topics(int $ojtid, int $userid): array
    {
        global $DB;

        $topics = [];
        foreach ($DB->get_records('ojt_topic', ['ojtid' => $ojtid]) as $record)
            $topics[$record->id] = new self($record, $userid);

        return $topics;
    }

    public static function get_user_topic_records($userid, $ojtid)
    {
        global $DB;

        $sql = 'SELECT t.*, CASE WHEN c.status IS NULL THEN '
               . completion::STATUS_INCOMPLETE .
               ' ELSE c.status END AS status, s.signedoff, s.modifiedby AS signoffmodifiedby, s.timemodified AS signofftimemodified,'
               . get_all_user_name_fields(true, 'su', '', 'signoffuser') . '
               FROM {ojt_topic} t
               LEFT JOIN {ojt_completion} c ON t.id = c.topicid AND c.type = ? AND c.userid = ?
               LEFT JOIN {ojt_topic_signoff} s ON t.id = s.topicid AND s.userid = ?
               LEFT JOIN {user} su ON s.modifiedby = su.id
               WHERE t.ojtid = ?
               ORDER BY t.id';

        return $DB->get_records_sql($sql, array(completion::COMP_TYPE_TOPIC, $userid, $userid, $ojtid));
    }

    /**
     * Returns current topic completion status
     *
     * @return int One of 3 possible \mod_ojt\models\completion statuses: STATUS_INCOMPLETE | STATUS_REQUIREDCOMPLETE | STATUS_COMPLETE
     */
    private function get_completion_status()
    {
        $topics = $this->topic_items;

        $status = completion::STATUS_COMPLETE;
        foreach ($topics as $topic)
        {
            if ($topic->completion->status == completion::STATUS_INCOMPLETE)
            {
                if ($topic->completionreq == completion::REQ_REQUIRED)
                {
                    // All required topics not complete - bail!
                    $status = completion::STATUS_INCOMPLETE;
                    break;
                }
                else if ($topic->completionreq == completion::REQ_OPTIONAL)
                {
                    // Degrade status a bit
                    $status = completion::STATUS_REQUIREDCOMPLETE;
                }
            }
            else if ($topic->completion->status == completion::STATUS_REQUIREDCOMPLETE)
            {
                // Degrade status a bit
                $status = completion::STATUS_REQUIREDCOMPLETE;
            }
        }

        return $status;
    }
}