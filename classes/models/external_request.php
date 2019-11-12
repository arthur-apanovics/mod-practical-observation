<?php


namespace mod_observation\models;


use coding_exception;
use mod_observation\interfaces\crud;
use mod_observation\traits\db_record_base;
use mod_observation\traits\record_mapper;
use stdClass;

class external_request extends db_record_base
{
    protected const TABLE = 'observation_external_request';

    /**
     * @var int
     */
    var $observationid;

    /**
     * @var int
     */
    var $topicid;

    /**
     * @var int
     */
    var $userid;

    /**
     * @var int
     */
    var $timedue;


    public static function get_request_for_observation_topic(int $observationid, int $topicid, int $userid)
    {
        global $DB;

        $record = $DB->get_record('observation_external_request',
            ['observationid' => $observationid, 'topicid' => $topicid, 'userid' => $userid]);

        return $record ? new self($record) : null;
    }

    /**
     * Checks that the timestamp for a given due date is valid in terms of being further in future
     * than now and the current due date if there is one.
     *
     * The timestamp should be cleaned as an integer before this (e.g. by setting the type on a form element).
     *
     * 0 is equivalent to not set. This allows timestamps to go from being set to not set.
     *
     * @param int $observationid
     * @param int $topicid
     * @param int $userid
     * @param int $newduedate
     * @return array array containing error strings
     * @throws coding_exception
     */
    public static function validate_new_timedue(int $observationid, int $topicid, int $userid, int $newduedate)
    {
        $errors = array();

        // We currently allow setting of empty values.
        if (!empty($newduedate))
        {
            $request = self::get_request_for_observation_topic($observationid, $topicid, $userid);

            // If they have set a due date check that it is in the future.
            if ($newduedate < time())
            {
                $errors['duedate'] = get_string('error:duedatepast', 'totara_feedback360');
            }

            $oldduedate = $request->timedue;

            // If we are updating an existing request, check that the due date is the same or further in the future.
            if (!empty($oldduedate) and ($oldduedate > $newduedate))
            {
                $errors['duedate'] = get_string('error:newduedatebeforeold', 'totara_feedback360');
            }
        }

        return $errors;
    }
}