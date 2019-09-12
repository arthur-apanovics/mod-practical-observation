<?php


namespace mod_ojt\models;


use coding_exception;
use dml_exception;
use html_writer;
use mod_ojt\interfaces\crud;
use mod_ojt\traits\db_record_base;
use mod_ojt\traits\record_mapper;
use mod_ojt\user_external_request;
use moodle_exception;
use moodle_url;
use stdClass;

class email_assignment extends db_record_base
{
    protected const TABLE = 'ojt_email_assignment';

    /**
     * @var int
     */
    var $externalrequestid;

    /**
     * @var string
     */
    var $email;

    /**
     * @var int
     */
    var $timeassigned;

    /**
     * @var int
     */
    var $timecompleted;

    /**
     * @var bool
     */
    var $viewed;

    /**
     * @var string
     */
    var $token;


    /**
     * Create the records for external users and sends notifications.
     *
     * @param stdClass                   $form_data
     * @param bool                       $asmanager Whether we are sending as the user or as their manager defaults to sending as the user.
     * @param stdClass                   $userfrom User object for sender.
     * @param stdClass                   $strvars Variables to be substituted into strings.
     * @param user_external_request|null $external_request
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function update_and_notify_email(stdClass $form_data, bool $asmanager, stdClass $userfrom, stdClass $strvars = null, user_external_request $external_request = null)
    {
        global $USER;

        // Create new email and resp assignments for emails given.

        $emailnew  = !empty($form_data->emailnew) ? explode(',', $form_data->emailnew) : array();
        $emailkeep = !empty($form_data->emailkeep) ? explode(',', $form_data->emailkeep) : array();

        if (is_null($external_request))
        {
            $external_request = user_external_request::get_user_request_for_ojt_topic(
                $form_data->ojtid, $form_data->topicid, $form_data->userid);
        }

        list($emailnew, $emailkeep, $emailcancel) =
            self::sort_submitted_emails(
                array_merge($emailnew, $emailkeep), $external_request->get_assigned_emails_list());

        // Add new email assignments and send out messages
        foreach ($emailnew as $email)
        {
            $assignment                    = new self();
            $assignment->externalrequestid = $external_request->id;
            $assignment->email             = $email;
            $assignment->timeassigned      = time();
            $assignment->token             = self::generate_token($email, $userfrom->id);

            $assignment->create();

            // Send email to newly assigned address.
            self::send_assignment_email($userfrom, $assignment, $asmanager);
        }

        // Delete emails
        foreach ($emailcancel as $id => $email)
        {
            $assignment = new self($id);

            if (!empty($assignment->timecompleted))
            {
                // response already given - skip
                continue;
            }
            else
            {
                $assignment->delete();
            }
        }

        if ($form_data->duenotifications && !empty($emailkeep))
        {
            // Send an email with an updated duedate to everyone in emailkeep.
            // No need to translate these messages as they are going to external users.
            if ($asmanager)
            {
                $emailsubject = get_string('managerupdatesubject', 'totara_feedback360', $strvars);
                $email_str    = get_string('managerupdateemail', 'totara_feedback360', $strvars);
                $sendas       = $USER;
            }
            else
            {
                $emailsubject = get_string('updatesubject', 'totara_feedback360', $strvars);
                $email_str    = get_string('updateemail', 'totara_feedback360', $strvars);
                $sendas       = $userfrom;
            }

            foreach ($emailkeep as $email)
            {
                $userto = \totara_core\totara_user::get_external_user($email);
                email_to_user($userto, $sendas, $emailsubject, $email_str, $email_str);
            }
        }
    }

    /**
     * @param int $ojtid
     * @param int $topicid
     * @param int $userid
     * @return email_assignment[]
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function get_assignments(int $ojtid, int $topicid, int $userid)
    {
        global $DB;

        $sql     = 'SELECT ea.* 
                FROM {ojt_email_assignment} ea
                JOIN {ojt_external_request} ef ON ef.id = ea.externalrequestid
                WHERE ef.ojtid = ?
                AND ef.topicid = ?
                AND ef.userid = ?';
        $records = $DB->get_records_sql($sql, [$ojtid, $topicid, $userid]);

        $email_assignments = [];
        foreach ($records as $record)
        {
            $email_assignments[$record->id] = new self($record);
        }

        return $email_assignments;
    }

    /**
     * Fetch object by email token.
     * Note: Does not check if record exists!
     *
     * @param string $token
     * @return email_assignment
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_from_token(string $token)
    {
        global $DB;
        return new self($DB->get_record('ojt_email_assignment', array('token' => $token), '*'));
    }

    /**
     * Marks email assignment as viewed and updates DB record
     */
    public function mark_viewed()
    {
        $this->viewed = true;
        $this->update();
    }

    /**
     * Given an array of emails sorts them into three other arrays based on the following:
     * - 0 => emails in the supplied list that ARE NOT already assigned as responders to this user assignment (new).
     * - 1 => emails in the supplied list that ARE already assigned as responders to this user assignment (keep).
     * - 2 => emails that are already assigned as responders but are not in the supplied list (cancel).
     *
     * @param string[] $submitted_emails - should be for emails that are intended to be assigned, including new and existing.
     * @param string[] $existing_emails
     * @return array (array new, array keep, array cancel) with keys as ojt_email_assignment id's
     */
    private static function sort_submitted_emails(array $submitted_emails, array $existing_emails)
    {
        $new    = array();
        $keep   = array();
        $cancel = array();

        foreach ($submitted_emails as $email)
        {
            if ($assignmentid = array_search($email, $existing_emails))
            {
                // Current behaviour is not to validate emails that are existing, this is to
                // reduce the risk of data loss.
                $keep[$assignmentid] = $email;
            }
            else
            {
                // If the email is invalid, don't add it.
                if (validate_email($email))
                {
                    $new[] = $email;
                }
            }
        }

        // Any existing users not in keep are added to cancel.
        foreach ($existing_emails as $id => $email)
        {
            if (!isset($keep[$id]))
            {
                $cancel[$id] = $email;
            }
        }

        return array($new, $keep, $cancel);
    }

    /**
     * Create a token used to validate external user later
     *
     * @param string $email
     * @param int    $userid
     * @return string
     */
    private static function generate_token(string $email, int $userid)
    {
        return sha1($email . 'responder' . $userid . time() . get_site_identifier());
    }

    public static function is_valid_token(int $ojtid, int $userid, string $token)
    {
        global $DB;

        $sql = 'SELECT ea.id
                FROM {ojt_email_assignment} ea
                JOIN {ojt_external_request} er on er.id = ea.externalrequestid
                WHERE er.ojtid = ?
                AND er.userid = ?
                AND ea.token = ?';
        return $DB->record_exists_sql($sql, [$ojtid, $userid, $token]);
    }

    /**
     * Send email to assigned email address
     *
     * @param stdClass         $userfrom
     * @param email_assignment $assignment
     * @param bool             $asmanager
     * @return int|false id of meail or false if sending failed
     * @throws moodle_exception
     * @throws coding_exception
     */
    private static function send_assignment_email(stdClass $userfrom, email_assignment $assignment, bool $asmanager)
    {
        global $USER;

        $emailvars = new stdClass();
        if ($asmanager)
        {
            $emailvars->fullname  = fullname($USER);
            $emailvars->staffname = fullname($userfrom);
            $userfrom             = $USER;
        }
        else
        {
            $emailvars->fullname = fullname($userfrom);
        }

        $params          = ['token' => $assignment->token];
        $url             = new moodle_url('/mod/ojt/observe.php', $params);
        $emailvars->link = html_writer::link($url, $url->out());
        $emailvars->url  = $url->out();

        if ($asmanager)
        {
            $emailplain   = get_string('manageremailrequeststr', 'totara_feedback360', $emailvars);
            $emailhtml    = get_string('manageremailrequesthtml', 'totara_feedback360', $emailvars);
            $emailsubject = get_string('manageremailrequestsubject', 'totara_feedback360', $emailvars);
        }
        else
        {
            $emailplain   = get_string('emailrequeststr', 'totara_feedback360', $emailvars);
            $emailhtml    = get_string('emailrequesthtml', 'totara_feedback360', $emailvars);
            $emailsubject = get_string('emailrequestsubject', 'totara_feedback360', $emailvars);
        }
        $userto = \totara_core\totara_user::get_external_user($assignment->email);

        // Create the message.
        $message                    = new stdClass();
        $message->component         = 'moodle';
        $message->name              = 'instantmessage';
        $message->courseid          = SITEID;
        $message->userfrom          = $userfrom;
        $message->userto            = $userto;
        $message->subject           = $emailsubject;
        $message->fullmessage       = $emailplain;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = $emailhtml;
        $message->smallmessage      = $emailplain;

        return message_send($message);
    }
}