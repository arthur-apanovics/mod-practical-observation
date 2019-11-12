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

namespace mod_observation;


use dml_exception;
use mod_observation\models\completion;
use mod_observation\models\topic_signoff;
use mod_observation\models\topic;

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
     * @var topic_signoff|null null if no record exists!
     */
    public $signoff;

    /**
     * @var user_external_request|null null if no record exists!
     */
    public $external_request;

    /**
     * One of 3 possible \mod_observation\models\completion statuses:
     * STATUS_INCOMPLETE | STATUS_REQUIREDCOMPLETE | STATUS_COMPLETE
     * @var int
     */
    public $completion_status;

    /**
     * user_topic constructor.
     * @param int|object $id_or_record instance id, database record or existing class or base class
     * @param int        $userid
     */
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);

        $this->userid      = $userid;
        $this->topic_items = user_topic_item::get_user_topic_items_for_topic($this->id, $this->userid);
        $this->signoff     = topic_signoff::get_user_topic_signoff($this->id, $this->userid);
        $this->external_request = user_external_request::get_user_request_for_observation_topic(
            $this->observationid, $this->id, $this->userid);
        $this->completion_status = $this->get_completion_status();
    }

    /**
     * @param int $topicid
     * @param int $userid
     * @return user_topic
     * @throws dml_exception
     */
    public static function get_user_topic(int $topicid, int $userid)
    {
        global $DB;
        return new self($DB->get_record('observation_topic', ['id' => $topicid]), $userid);
    }

    /**
     * Returns all user topics in given observation instance
     * @param int $observationid
     * @param int $userid
     * @return user_topic[]
     * @throws dml_exception
     */
    public static function get_user_topics(int $observationid, int $userid): array
    {
        global $DB;

        $topics = [];
        foreach ($DB->get_records('observation_topic', ['observationid' => $observationid]) as $record)
            $topics[$record->id] = new self($record, $userid);

        return $topics;
    }

    /**
     * @param $userid
     * @param $observationid
     * @return array
     * @throws dml_exception
     *
     * @deprecated do not use as this returns an associative array instead of a proper class instance
     */
    public static function get_user_topic_records($userid, $observationid)
    {
        global $DB;

        $sql = 'SELECT t.*, CASE WHEN c.status IS NULL THEN '
               . completion::STATUS_INCOMPLETE .
               ' ELSE c.status END AS status, s.signedoff, s.modifiedby AS signoffmodifiedby, s.timemodified AS signofftimemodified,'
               . get_all_user_name_fields(true, 'su', '', 'signoffuser') . '
               FROM {observation_topic} t
               LEFT JOIN {observation_completion} c ON t.id = c.topicid AND c.type = ? AND c.userid = ?
               LEFT JOIN {observation_topic_signoff} s ON t.id = s.topicid AND s.userid = ?
               LEFT JOIN {user} su ON s.modifiedby = su.id
               WHERE t.observationid = ?
               ORDER BY t.id';

        return $DB->get_records_sql($sql, array(completion::COMP_TYPE_TOPIC, $userid, $userid, $observationid));
    }

    /**
     * Returns current topic completion status
     *
     * @return int One of 3 possible \mod_observation\models\completion statuses: STATUS_INCOMPLETE | STATUS_REQUIREDCOMPLETE | STATUS_COMPLETE
     */
    private function get_completion_status()
    {
        $status = completion::STATUS_COMPLETE;
        foreach ($this->topic_items as $topic_item)
        {
            if (is_null($topic_item->completion) || $topic_item->completion->status == completion::STATUS_INCOMPLETE)
            {
                if ($topic_item->completionreq == completion::REQ_REQUIRED)
                {
                    // All required topics not complete - bail!
                    $status = completion::STATUS_INCOMPLETE;
                    break;
                }
                else if ($topic_item->completionreq == completion::REQ_OPTIONAL)
                {
                    // Degrade status a bit
                    $status = completion::STATUS_REQUIREDCOMPLETE;
                }
            }
            else if ($topic_item->completion->status == completion::STATUS_REQUIREDCOMPLETE)
            {
                // Degrade status a bit
                $status = completion::STATUS_REQUIREDCOMPLETE;
            }
        }

        return $status;
    }

    /**
     * Checks if this topic is submitted
     *
     * @return bool
     */
    public function is_submitted():bool
    {
        if (!is_null($this->external_request))
        {
            if (!is_null($this->external_request->email_assignments))
            {
                return count($this->external_request->email_assignments) > 0;
            }
        }

        return false;
    }
}