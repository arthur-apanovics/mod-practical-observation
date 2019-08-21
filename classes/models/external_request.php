<?php


namespace mod_ojt\models;


use coding_exception;
use mod_ojt\interfaces\crud;
use mod_ojt\traits\record_mapper;
use stdClass;

class external_request implements crud
{
    use record_mapper;

    /**
     * @var int
     */
    var $id;

    /**
     * @var int
     */
    var $ojtid;

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

    /**
     * external feedback constructor.
     * @param int|object $id_or_record instance id, database record or existing class or base class
     * @throws coding_exception
     */
    public function __construct($id_or_record = null)
    {
        self::create_from_id_or_map_to_record($id_or_record);
    }

    public static function get_request_for_ojt_topic(int $ojtid, int $topicid, int $userid)
    {
        global $DB;

        $record = $DB->get_record('ojt_external_request',
            ['ojtid' => $ojtid, 'topicid' => $topicid, 'userid' => $userid]);

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
     * @param int $ojtid
     * @param int $topicid
     * @param int $userid
     * @param int $newduedate
     * @return array array containing error strings
     * @throws coding_exception
     */
    public static function validate_new_timedue(int $ojtid, int $topicid, int $userid, int $newduedate)
    {
        $errors = array();

        // We currently allow setting of empty values.
        if (!empty($newduedate))
        {
            $request = self::get_request_for_ojt_topic($ojtid, $topicid, $userid);

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

    /**
     * Fetch record from database.
     * @param int $id
     * @return stdClass|false false if record not found
     */
    public static function fetch_record_from_id(int $id)
    {
        global $DB;
        return $DB->get_record('ojt_external_request', ['id' => $id]);
    }

    /**
     * Create DB entry from current state
     *
     * @return bool|int new record id or false if failed
     */
    public function create()
    {
        global $DB;
        return $DB->insert_record('ojt_external_request', self::get_record_from_object());
    }

    /**
     * Read latest values from DB and refresh current object
     *
     * @return object
     */
    public function read()
    {
        global $DB;
        $this->map_to_record($DB->get_record('ojt_external_request', ['id' => $this->id]));
		return $this;
    }

    /**
     * Save current state to DB
     *
     * @return bool
     */
    public function update()
    {
        global $DB;
        return $DB->update_record('ojt_external_request', $this->get_record_from_object());
    }

    /**
     * Delete current object from DB
     *
     * @return bool
     */
    public function delete()
    {
        global $DB;
        return $DB->delete_records('ojt_external_request', ['id' => $this->id]);
    }
}