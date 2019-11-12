<?php


namespace mod_observation;


use coding_exception;
use dml_exception;
use mod_observation\models\email_assignment;
use mod_observation\models\external_request;

class user_external_request extends external_request
{

    /**
     * @var int
     */
    var $userid;

    /**
     * @var email_assignment[]
     */
    var $email_assignments;


    /**
     * user external request constructor.
     * @param int|object $id_or_record instance id, database record or existing class or base class
     * @param int        $userid
     * @throws coding_exception
     */
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);

        $this->userid            = $userid;
        $this->email_assignments = email_assignment::get_assignments($this->observationid, $this->topicid, $this->userid);
    }

    /**
     * @param int $observationid
     * @param int $topicid
     * @param int $userid
     * @return user_external_request
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_or_create_user_external_request_for_observation_topic(int $observationid, int $topicid, int $userid)
    {
        global $DB;

        $record = $DB->get_record('observation_external_request',
            ['observationid' => $observationid, 'topicid' => $topicid, 'userid' => $userid]);

        if ($record)
        {
            return new self($record, $userid);
        }
        else
        {
            $external_request          = new external_request();
            $external_request->observationid   = $observationid;
            $external_request->topicid = $topicid;
            $external_request->userid  = $userid;

            $external_request->id = $external_request->create();

            return new user_external_request($external_request, $userid);
        }
    }

    /**
     * @param int $observationid
     * @param int $topicid
     * @param int $userid
     * @return user_external_request|null Returns null if no record found
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function get_user_request_for_observation_topic(int $observationid, int $topicid, int $userid)
    {
        global $DB;

        $record = $DB->get_record('observation_external_request',
            ['observationid' => $observationid, 'topicid' => $topicid, 'userid' => $userid]);

        return $record ? new self($record, $userid) : null;
    }

    public function get_assigned_emails_list()
    {
        $emails = [];
        foreach ($this->email_assignments as $assignment)
        {
            $emails[$assignment->id] = $assignment->email;
        }

        return $emails;
    }

    /**
     * Find first assignment date in email assignments
     *
     * @return int -1 if no email assignments exist
     */
    public function get_first_email_assign_date(): int //has to return int!
    {
        if (!is_null($this->email_assignments) && count($this->email_assignments) > 0)
        {
            $first_date = -1;
            foreach ($this->email_assignments as $assignment)
            {
                if ($assignment->timeassigned > $first_date)
                {
                    $first_date = $assignment->timeassigned;
                }
            }

            if ($first_date <= 0)
            {
                throw new coding_exception("Incorrect email assignment date found in external request with id $this->id");
            }

            return $first_date;
        }
        else
        {
            return -1;
        }
    }
}