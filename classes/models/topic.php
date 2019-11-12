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

use competency_evidence;
use mod_observation\interfaces\crud;
use mod_observation\traits\db_record_base;
use mod_observation\traits\record_mapper;
use mod_observation\user_topic_item;
use stdClass;

class topic extends db_record_base
{
    protected const TABLE = 'observation_topic';

    /**
     * @var int
     */
    public $observationid;

    /**
     * @var string
     */
    public $name;

    /**
     * Topic description
     *
     * @var string
     */
    public $intro;

    /**
     * @var int
     */
    public $introformat;

    /**
     * Observation description
     *
     * @var string
     */
    public $observerintro;

    /**
     * @var int
     */
    public $observerintroformat;

    /**
     * @var int
     */
    public $completionreq;

    /**
     * @var string
     */
    public $competencies;

    /**
     * @var bool
     */
    public $allowcomments;


    public static function update_topic_completion(int $userid, int $observationid, int $topicid, string $observeremail)
    {
        global $DB, $USER;

        $observation = new observation($observationid);

        // // Check if all required topic items have been completed
        // $sql   = 'SELECT i.*, CASE WHEN c.status IS NULL THEN '
        //          . completion::STATUS_INCOMPLETE . ' ELSE c.status END AS status
        //          FROM {observation_topic_item} i
        //          LEFT JOIN {observation_completion} c ON i.id = c.topicitemid AND c.observationid = ? AND c.type = ? AND c.userid = ?
        //          WHERE i.topicid = ?';
        // $items = $DB->get_records_sql($sql, array($observationid, completion::COMP_TYPE_TOPICITEM, $userid, $topicid));

        $topic_items = user_topic_item::get_user_topic_items_for_topic($topicid, $userid);

        $status = completion::STATUS_COMPLETE;
        foreach ($topic_items as $topic_item)
        {
            if (is_null($topic_item->completion) || $topic_item->completion->status == completion::STATUS_INCOMPLETE)
            {
                if ($topic_item->completionreq == completion::REQ_REQUIRED)
                {
                    // All required items not complete - bail!
                    $status = completion::STATUS_INCOMPLETE;
                    break;
                }
                else if ($topic_item->completionreq == completion::REQ_OPTIONAL)
                {
                    // Degrade status a bit
                    $status = completion::STATUS_REQUIREDCOMPLETE;
                }
            }
        }

        if (in_array($status, array(completion::STATUS_COMPLETE, completion::STATUS_REQUIREDCOMPLETE))
            && $observation->itemwitness && !observation::topic_items_witnessed($topicid, $userid))
        {

            // All required items must also be witnessed - degrade status
            $status = completion::STATUS_INCOMPLETE;
        }

        $currentcompletion = $DB->get_record('observation_completion',
            array('userid' => $userid, 'topicid' => $topicid, 'type' => completion::COMP_TYPE_TOPIC));
        $currentcompletion = new completion($currentcompletion);

        if (empty($currentcompletion->status) || $status != $currentcompletion->status)
        {
            // Update topic completion
            $transaction = $DB->start_delegated_transaction();

            $completion               = is_null($currentcompletion->id) ? new completion() : $currentcompletion;
            $completion->status       = $status;
            $completion->timemodified = time();
            $completion->observeremail = $USER->id;

            if (empty($currentcompletion) || !$currentcompletion->id)
            {
                $completion->userid  = $userid;
                $completion->type    = completion::COMP_TYPE_TOPIC;
                $completion->observationid   = $observationid;
                $completion->topicid = $topicid;
                $completion->id      = $DB->insert_record('observation_completion', $completion);
            }
            else
            {
                $DB->update_record('observation_completion', $completion);
            }

            // Also update observation completion.
            observation::update_completion($userid, $observationid);

            topic::update_topic_competency_proficiency($userid, $topicid, $status);

            $transaction->allow_commit();
        }

        return empty($completion) ? $currentcompletion : $completion;
    }

    public static function update_topic_competency_proficiency($userid, $topicid, $status)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/evidence/lib.php');

        if (!in_array($status, array(completion::STATUS_COMPLETE, completion::STATUS_REQUIREDCOMPLETE)))
        {
            return;
        }

        $competencies = $DB->get_field('observation_topic', 'competencies', array('id' => $topicid));
        if (empty($competencies))
        {
            // Nothing to do here :)
            return;
        }
        $competencies = explode(',', $competencies);

        foreach ($competencies as $competencyid)
        {
            // this is copied from totara/hierarchy/prefix/competency/evidence/lib.php - hierarchy_add_competency_evidence()
            $todb = new competency_evidence(
                array(
                    'competencyid'   => $competencyid,
                    'userid'         => $userid,
                    'manual'         => 0,
                    'reaggregate'    => 1,
                    'assessmenttype' => 'observation'
                )
            );

            if ($recordid =
                $DB->get_field('comp_record', 'id', array('userid' => $userid, 'competencyid' => $competencyid)))
            {
                $todb->id = $recordid;
            }

            // Get the first 'proficient' scale value for the competency
            $sql           = "SELECT csv.id
            FROM {comp_scale} cs
            JOIN {comp_scale_values} csv ON cs.id = csv.scaleid
            JOIN {comp_scale_assignments} csa ON cs.id = csa.scaleid
            JOIN {comp} c ON csa.frameworkid = c.frameworkid
            WHERE c.id = ? AND csv.proficient = 1 ORDER BY csv.id LIMIT 1";
            $proficiencyid = $DB->get_field_sql($sql, array($competencyid), MUST_EXIST);

            // Update the user to 'proficient' for this competency
            $transaction = $DB->start_delegated_transaction();
            $todb->update_proficiency($proficiencyid);

            // Update stats block
            $currentuser  = $userid;
            $event        = STATS_EVENT_COMP_ACHIEVED;
            $data2        = $competencyid;
            $time         = time();
            $count        = $DB->count_records('block_totara_stats',
                array('userid' => $currentuser, 'eventtype' => $event, 'data2' => $data2));
            $isproficient = $DB->get_field('comp_scale_values', 'proficient', array('id' => $proficiencyid));

            // Check the proficiency is set to "proficient" and check for duplicate data.
            if ($isproficient && $count == 0)
            {
                totara_stats_add_event($time, $currentuser, $event, '', $data2);
            }
            $transaction->allow_commit();
        }
    }

    public static function delete_topic($topicid)
    {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('observation_topic', array('id' => $topicid));
        $DB->delete_records('observation_topic_item', array('topicid' => $topicid));
        $DB->delete_records('observation_completion', array('topicid' => $topicid));
        $DB->delete_records('observation_topic_signoff', array('topicid' => $topicid));

        $transaction->allow_commit();
    }
}