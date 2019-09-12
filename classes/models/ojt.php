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
use completion_info;
use context;
use dml_exception;
use dml_transaction_exception;
use mod_ojt\interfaces\crud;
use mod_ojt\traits\db_record_base;
use mod_ojt\traits\record_mapper;
use mod_ojt\user_topic;
use stdClass;

class ojt extends db_record_base
{
    protected const TABLE = 'ojt';

    /**
     * @var int
     */
    public $course;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $intro;

    /**
     * @var int
     */
    public $introformat;

    /**
     * @var bool
     */
    public $completiontopics;

    /**
     * @var int
     */
    public $timecreated;

    /**
     * @var int
     */
    public $timemodified;

    /**
     * @var bool
     */
    public $managersignoff;

    /**
     * @var bool
     */
    public $itemwitness;


    /**
     * @param int $userid
     * @param int $ojtid
     * @return mixed|stdClass
     * @throws dml_exception
     * @throws dml_transaction_exception
     *
     * @deprecated do not use
     */
    public static function update_completion(int $userid, int $ojtid)
    {
        global $DB, $USER;

        // Check if all required ojt topics have been completed, then complete the ojt
        $topics = user_topic::get_user_topic_records($userid, $ojtid);

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
                WHERE ti.completionreq = ? 
                AND ti.topicid = ? 
                AND iw.witnessedby IS NULL";

        return !$DB->record_exists_sql($sql, array($userid, completion::REQ_REQUIRED, $topicid));
    }

    /**
     * @param int           $timemodified
     * @param stdClass|null $user
     * @return string
     * @throws coding_exception
     */
    public static function get_modifiedstr_user($timemodified, $user = null)
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
     * @param int  $timemodified
     * @param string $email
     * @return string
     * @throws coding_exception
     */
    public static function get_modifiedstr_email($timemodified, $email)
    {
        if (empty($timemodified) || empty($email))
        {
            return '';
        }

        return 'by ' . $email . ' on ' .
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
}