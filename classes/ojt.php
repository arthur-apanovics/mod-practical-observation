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
use completion_info;
use context;
use dml_exception;
use dml_transaction_exception;
use mod_ojt\traits\record_mapper;
use stdClass;

class ojt
{
    use record_mapper;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var stdClass
     */
    protected $course;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $intro;

    /**
     * @var int
     */
    protected $introformat;

    /**
     * @var bool
     */
    protected $completiontopics;

    /**
     * @var int
     */
    protected $timecreated;

    /**
     * @var int
     */
    protected $timemodified;

    /**
     * @var bool
     */
    protected $managersignoff;

    /**
     * @var bool
     */
    protected $itemwitness;


    // /**
    //  * @var topic[]
    //  */
    // public $topics;


    /**
     * ojt constructor.
     * @param int|stdClass $id_or_record
     * @throws coding_exception
     */
    public function __construct($id_or_record = null)
    {
        self::createFromIdOrMapToRecord($id_or_record);
    }

    /**
     * Get OJT object by OJT id and user id
     *
     * @param int $ojtid
     * @param int $userid
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_user_ojt(int $ojtid, int $userid)
    {
        global $DB;

        // Get the ojt details.
        $sql =
            'SELECT ' . $userid . ' AS userid, b.*, CASE WHEN c.status IS NULL THEN ' . completion::STATUS_INCOMPLETE . ' ELSE c.status END AS status, c.comment
        FROM {ojt} b
        LEFT JOIN {ojt_completion} c ON b.id = c.ojtid AND c.type = ? AND c.userid = ?
        WHERE b.id = ?';
        $ojt = $DB->get_record_sql($sql, array(completion::COMP_TYPE_OJT, $userid, $ojtid), MUST_EXIST);

        // Add topics and completion data.
        $ojt->topics = topic::get_user_topic_records($userid, $ojtid);
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
        $sql    = "SELECT i.*, CASE WHEN c.status IS NULL THEN " . completion::STATUS_INCOMPLETE . " ELSE c.status END AS status,
            c.comment, c.timemodified, c.modifiedby,bw.witnessedby,bw.timewitnessed," .
                  get_all_user_name_fields(true, 'moduser', '', 'modifier') . "," .
                  get_all_user_name_fields(true, 'witnessuser', '', 'itemwitness') . "
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

    /**
     * @param int $userid
     * @param int $ojtid
     * @return mixed|stdClass
     * @throws dml_exception
     * @throws dml_transaction_exception
     */
    public static function update_completion(int $userid, int $ojtid)
    {
        global $DB, $USER;

        // Check if all required ojt topics have been completed, then complete the ojt
        $topics = topic::get_user_topic_records($userid, $ojtid);

        $status = completion::STATUS_COMPLETE;
        foreach ($topics as $topic)
        {
            if ($topic->status == completion::STATUS_INCOMPLETE)
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
            else if ($topic->status == completion::STATUS_REQUIREDCOMPLETE)
            {
                // Degrade status a bit
                $status = completion::STATUS_REQUIREDCOMPLETE;
            }
        }

        $transaction       = $DB->start_delegated_transaction();
        $currentcompletion = $DB->get_record('ojt_completion',
            array('userid' => $userid, 'ojtid' => $ojtid, 'type' => completion::COMP_TYPE_OJT));
        if (empty($currentcompletion->status) || $status != $currentcompletion->status)
        {
            // Update ojt completion
            $completion               = empty($currentcompletion) ? new stdClass() : $currentcompletion;
            $completion->status       = $status;
            $completion->timemodified = time();
            $completion->modifiedby   = $USER->id;
            if (empty($currentcompletion))
            {
                $completion->userid = $userid;
                $completion->type   = completion::COMP_TYPE_OJT;
                $completion->ojtid  = $ojtid;
                $completion->id     = $DB->insert_record('ojt_completion', $completion);
            }
            else
            {
                $DB->update_record('ojt_completion', $completion);
            }

            // Update activity completion state
            self::update_activity_completion($ojtid, $userid, $status);
        }
        $transaction->allow_commit();

        return empty($completion) ? $currentcompletion : $completion;
    }

    public static function update_activity_completion($ojtid, $userid, $ojtstatus)
    {
        global $DB;

        $ojt = $DB->get_record('ojt', array('id' => $ojtid), '*', MUST_EXIST);
        if ($ojt->completiontopics)
        {
            $course = $DB->get_record('course', array('id' => $ojt->course), '*', MUST_EXIST);

            $cm          = get_coursemodule_from_instance('ojt', $ojt->id, $ojt->course, false, MUST_EXIST);
            $ccompletion = new completion_info($course);
            if ($ccompletion->is_enabled($cm))
            {
                if (in_array($ojtstatus, array(completion::STATUS_COMPLETE, completion::STATUS_REQUIREDCOMPLETE)))
                {
                    $ccompletion->update_state($cm, COMPLETION_COMPLETE, $userid);
                }
                else
                {
                    $ccompletion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
                }
            }
        }
    }

    /**
     * Check if all the required items in a topic have been witnessed
     *
     * @param int $topicid
     * @param int $userid
     * @return bool
     * @throws dml_exception
     */
    public static function topic_items_witnessed($topicid, $userid)
    {
        global $DB;

        $sql = "SELECT ti.id
        FROM {ojt_topic_item} ti
        LEFT JOIN {ojt_item_witness} iw ON ti.id = iw.topicitemid AND iw.witnessedby != 0 AND iw.userid = ?
        WHERE ti.completionreq = ? AND ti.topicid = ? AND iw.witnessedby IS NULL";

        return !$DB->record_exists_sql($sql, array($userid, completion::REQ_REQUIRED, $topicid));
    }

    public static function get_modifiedstr($timemodified, $user = null)
    {
        global $USER;

        if (empty($user))
        {
            $user = $USER;
        }

        if (empty($timemodified))
        {
            return '';
        }

        return 'by ' . fullname($user) . ' on ' .
               userdate($timemodified, get_string('strftimedatetimeshort', 'core_langconfig'));
    }

    /**
     * Checks if a user has capabilities to evaluate an ojt activity
     *
     * @param int     $userid
     * @param context $context
     * @return bool
     * @throws coding_exception
     */
    public static function can_evaluate($userid, $context)
    {
        global $USER;

        if (!has_capability('mod/ojt:evaluate', $context)
            && !(has_capability('mod/ojt:evaluateself', $context)
                 && $USER->id == $userid))
        {
            return false;
        }

        return true;
    }

    protected function getRecordFromId(int $id)
    {
        global $DB;

        return $DB->get_record('ojt', array('id' => $id));
    }
}