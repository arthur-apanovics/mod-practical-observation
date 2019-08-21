<?php


namespace mod_ojt;


use coding_exception;
use dml_exception;
use mod_ojt\models\email_assignment;
use mod_ojt\models\external_request;

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
        $this->email_assignments = email_assignment::get_assignments($this->ojtid, $this->topicid, $this->userid);
    }

    /**
     * @param int $ojtid
     * @param int $topicid
     * @param int $userid
     * @return user_external_request
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_or_create_user_external_request_for_ojt_topic(int $ojtid, int $topicid, int $userid)
    {
        global $DB;

        $record = $DB->get_record('ojt_external_request',
            ['ojtid' => $ojtid, 'topicid' => $topicid, 'userid' => $userid]);

        if ($record)
        {
            return new self($record, $userid);
        }
        else
        {
            $external_request = new external_request();
            $external_request->ojtid = $ojtid;
            $external_request->topicid = $topicid;
            $external_request->userid = $userid;

            $external_request->id = $external_request->create();

            return new user_external_request($external_request, $userid);
        }
    }

    /**
     * @param int $ojtid
     * @param int $topicid
     * @param int $userid
     * @return user_external_request|null Returns null if no record found
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function get_user_request_for_ojt_topic(int $ojtid, int $topicid, int $userid)
    {
        global $DB;

        $record = $DB->get_record('ojt_external_request',
            ['ojtid' => $ojtid, 'topicid' => $topicid, 'userid' => $userid]);

        return $record ? new self($record, $userid) : null;
    }

    public function get_assigned_emails_list()
    {
        $emails = [];
        foreach ($this->email_assignments as $assignment)
            $emails[$assignment->id] = $assignment->email;

        return $emails;
    }
}