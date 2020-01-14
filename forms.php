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

use mod_observation\db_model\obsolete\external_request;
use mod_observation\user_external_request;

if (!defined('MOODLE_INTERNAL'))
{
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/uploadlib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');

/**
 * Observation topic form
 */
class observation_topic_form extends moodleform
{
    function definition()
    {
        global $CFG;
        $mform    =& $this->_form;
        $courseid = $this->_customdata['courseid'];
        $observationid    = $this->_customdata['observationid'];
        $cm = get_coursemodule_from_instance('observation', $observationid);
        $context = context_module::instance($cm->id);

        $mform->addElement('text', 'name', get_string('name', 'observation'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        //TODO: FILE UPLOAD
        $editor_params = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean'  => true,
            'context'  => $context,
            'subdirs'  => true
        ];

        $mform->addElement('editor', 'intro', get_string('todo_langstring-topic_intro_desc', 'mod_observation'), $editor_params);
        $mform->setType('intro', PARAM_RAW); // no XSS prevention here, users must be trusted

        $mform->addElement('editor', 'observerintro', get_string('todo_langstring-topic_observerintro_desc', 'mod_observation'), $editor_params);
        $mform->setType('observerintro', PARAM_RAW); // no XSS prevention here, users must be trusted

        $mform->addElement('advcheckbox', 'completionreq', get_string('optionalcompletion', 'observation'));

        if (!empty($CFG->enablecompetencies))
        {
            require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');
            $competency   = new competency();
            $coursecomps  = $competency->get_course_evidence($courseid);
            $competencies = array();
            foreach ($coursecomps as $c)
            {
                $competencies[$c->id] = format_string($c->fullname);
            }
            if (!empty($competencies))
            {
                $select = $mform->addElement('select', 'competencies', get_string('competencies', 'observation'), $competencies,
                    array('size' => 7));
                $select->setMultiple(true);
                $mform->setType('competencies', PARAM_INT);
                $mform->addHelpButton('competencies', 'competencies', 'observation');
            }
        }

        if ($CFG->usecomments)
        {
            $mform->addElement('advcheckbox', 'allowcomments', get_string('allowcomments', 'observation'));
        }
        else
        {
            $mform->addElement('hidden', 'allowcomments', false);
        }
        $mform->setType('allowcomments', PARAM_BOOL);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'bid');
        $mform->setType('bid', PARAM_INT);
        $mform->setDefault('bid', $observationid);

        $this->add_action_buttons(false);
    }
}


/**
 * Observation topic item form
 */
class observation_topic_item_form extends moodleform
{
    function definition()
    {
        $mform   =& $this->_form;
        $observationid   = $this->_customdata['observationid'];
        $topicid = $this->_customdata['topicid'];

        $mform->addElement('text', 'name', get_string('name', 'observation'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'completionreq', get_string('optionalcompletion', 'observation'));

        $mform->addElement('advcheckbox', 'allowfileuploads', get_string('allowfileuploads', 'observation'));
        $mform->setType('allowfileuploads', PARAM_BOOL);

        $mform->addElement('advcheckbox', 'allowselffileuploads', get_string('allowselffileuploads', 'observation'));
        $mform->setType('allowselffileuploads', PARAM_BOOL);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'bid');
        $mform->setType('bid', PARAM_INT);
        $mform->setDefault('bid', $observationid);
        $mform->addElement('hidden', 'tid');
        $mform->setType('tid', PARAM_INT);
        $mform->setDefault('tid', $topicid);

        $this->add_action_buttons(false);
    }
}


class observation_request_select_users extends moodleform
{
    private $emailsexisting = null;

    public function definition()
    {
        $mform =& $this->_form;

        // Header - Manage users requested.
        $mform->addElement('header', 'manageuserrequests', get_string('manageuserrequests', 'totara_feedback360'));

        // Some hidden elements for the form.

        $mform->addElement('hidden', 'cmid', 0);
        $mform->setType('cmid', PARAM_INT);
        // Topic id
        $mform->addElement('hidden', 'topicid', 0);
        $mform->setType('topicid', PARAM_INT);
        // The id of the user requesting feedback.
        $mform->addElement('hidden', 'userid', 0);
        $mform->setType('userid', PARAM_INT);
        // The action being preformed.
        $mform->addElement('hidden', 'action', 'users');
        $mform->setType('action', PARAM_ALPHA);
        // A list of existing email assignments.
        $mform->addElement('hidden', 'emailold', '');
        $mform->setType('emailold', PARAM_TEXT);
        // A list of cancelled email assignments.
        $mform->addElement('hidden', 'emailcancel', '');
        $mform->setType('emailcancel', PARAM_TEXT);
        // A hidden datefield to complare the new vs old dates when editing.
        $mform->addElement('hidden', 'oldduedate', 0);
        $mform->setType('oldduedate', PARAM_INT);

        // //TODO? A hidden field used by js to popup a preview window.
        // $popupurl = $CFG->wwwroot . "/mod/observation/observe.php?userid={$USER->id}&preview=1&feedback360id=";
        // $mform->addElement('hidden', 'popupurl', $popupurl);
        // $mform->setType('popupurl', PARAM_TEXT);

        // Text area to add new emails.
        $emailnew_attributes = 'wrap="virtual" rows="5" cols="50" placeholder="'
                               . get_string('addemailplaceholder', 'mod_observation') . '"';
        $mform->addElement('textarea', 'emailnew', get_string('emailrequestsnew', 'totara_feedback360'),
            $emailnew_attributes);
        $mform->addHelpButton('emailnew', 'emailrequestsnew', 'totara_feedback360');

        // Create a place to show existing external users.
        $mform->addElement('static', 'existing_external', '', '');

        // Target date.
        $mform->addElement('date_selector', 'duedate', get_string('duedate', 'totara_feedback360'),
            array('optional' => true));
        $mform->addHelpButton('duedate', 'duedate', 'totara_feedback360');
        $mform->setType('duedate', PARAM_INT);
    }

    public function set_data($data)
    {
        global $USER, $PAGE;

        $mform    =& $this->_form;
        $renderer = $PAGE->get_renderer('mod_observation');
        /* @var $renderer mod_observation_renderer */

        if (!empty($data['userid']))
        {
            $mform->getElement('userid')->setValue($data['userid']);
        }
        else
        {
            $mform->getElement('userid')->setValue($USER->id);
        }

        if (!empty($data['cmid']))
        {
            $mform->cmid = $data['cmid'];
        }
        else
        {
            print_error('error:noformselected', 'totara_feedback360');
        }

        $cm = get_coursemodule_from_id('observation', $data['cmid']);

        if (!empty($data['topicid']) && !empty($data['topicname']))
        {
            $title          = get_string('manageuserrequests', 'totara_feedback360');
            $name           = $data['topicname'];

            // $preview_params = array(
            //     'userid'  => $USER->id,
            //     'observationid'   => $data['cmid'],
            //     'topicid' => $data['topicid'],
            //     'preview' => true);
            // // TODO? FEEDBACK PREVIEW URL
            // $preview_url  = new moodle_url('/mod/observation/observe.php', $preview_params);
            // $preview_link = html_writer::link(
            //     $preview_url,
            //     get_string('previewencased', 'totara_feedback360'),
            //     array('class' => 'previewlink', 'id' => $data['topicid'])
            // );

            $mform->getElement('manageuserrequests')->setValue($title . ': ' . $name); //. $preview_link);
        }

        if (!empty($data['emailexisting']))
        {
            $existing_html    = array();
            $external_request = user_external_request::get_user_request_for_observation_topic(
                $cm->instance, $data['topicid'], $data['userid']);

            foreach ($external_request->email_assignments as $assignment)
            {
                $existing_html[] = $renderer->external_user_record($assignment);
            }

            $mform->getElement('emailold')->setValue(implode(',', $data['emailexisting']));
            $mform->getElement('existing_external')->setValue(implode($existing_html, ''));
        }

        if (!empty($data['duedate']))
        {
            $mform->getElement('oldduedate')->setValue($data['duedate']);
            $mform->getElement('duedate')->setValue($data['duedate']);
        }

        if (!empty($data['update']))
        {
            $submitstr = get_string('update', 'totara_feedback360');
        }
        else
        {
            $submitstr = get_string('request', 'totara_feedback360');
        }

        // Add the action buttons.
        $this->add_action_buttons(true, $submitstr);

        parent::set_data($data);
    }

    public function validation($data, $files)
    {
        $cm     = get_coursemodule_from_id('observation', $data['cmid']);
        $errors = array();

        // Check form is defined.
        if (empty($data['cmid']) || !is_numeric($data['cmid']) || $data['cmid'] < 1)
        {
            $errors['cmid'] = get_string('error:noformselected', 'totara_feedback360');
        }

        // Trim extra whitespace/commas off the edges of the string.
        $data['emailnew'] = trim($data['emailnew']);

        $emails_new = !empty($data['emailnew']) ? explode('\r\n', $data['emailnew']) : array();
        $emails_old = !empty($data['emailold']) ? explode(',', $data['emailold']) : array();

        // Check atleast one email.
        if (count($emails_new + $emails_old) < 1)
        {
            $errors['systemnew'] = get_string('error:emptyuserrequests', 'totara_feedback360');
        }

        // Check email format.
        if (!empty($emails_new))
        {
            $formaterror = array();
            foreach ($emails_new as $email)
            {
                if (!validate_email($email))
                {
                    $formaterror[] = $email;
                }
            }

            // Check for duplicate emails.
            $duplicateerror = array();
            while (!empty($emails_new))
            {
                $email = array_pop($emails_new);
                if (in_array($email, $emails_new))
                {
                    $duplicateerror[] = $email;
                }
            }

            //TODO CHECK FOR USERS OWN EMAIL

            if (!empty($formaterror))
            {
                $errors['emailnew'] = get_string('error:emailformat',
                        'totara_feedback360') . implode($formaterror, "- ");
            }

            if (!empty($duplicateerror))
            {
                $errors['emailnew'] = get_string('error:emailduplicate',
                        'totara_feedback360') . implode($duplicateerror, "- ");
            }
        }

        $duedateerrors = external_request::validate_new_timedue(
            $cm->instance, $data['topicid'], $data['userid'], $data['duedate']);

        $errors = array_merge($errors, $duedateerrors);

        return $errors;
    }
}

class observation_request_confirmation extends moodleform
{
    public function definition()
    {
        $mform =& $this->_form;

        // The id of the user requesting feedback.
        $mform->addElement('hidden', 'cmid', 0);
        $mform->setType('cmid', PARAM_INT);
        // Topic id
        $mform->addElement('hidden', 'topicid', 0);
        $mform->setType('topicid', PARAM_INT);
        // The id of the user requesting feedback.
        $mform->addElement('hidden', 'userid', 0);
        $mform->setType('userid', PARAM_INT);
        // The action being preformed.
        $mform->addElement('hidden', 'action', 'confirm');
        $mform->setType('action', PARAM_ALPHA);
        // The date that the feedback shold be completed by as a timestamp.
        $mform->addElement('hidden', 'duedate', 0);
        $mform->setType('duedate', PARAM_INT);
        // The previous duedate to check against if it has changed.
        $mform->addElement('hidden', 'oldduedate', 0);
        $mform->setType('oldduedate', PARAM_INT);
        // A flag of wether to send due date notifications.
        $mform->addElement('hidden', 'duenotifications', false);
        $mform->setType('duenotifications', PARAM_BOOL);
        // A list of currently unasked emails to create requests for.
        $mform->addElement('hidden', 'emailnew', '');
        $mform->setType('emailnew', PARAM_TEXT);
        // A list of current assigned external users to keep.
        $mform->addElement('hidden', 'emailkeep', null);
        $mform->setType('emailkeep', PARAM_TEXT);
        // A list of currently assigned external users to cancel.
        $mform->addElement('hidden', 'emailcancel', null);
        $mform->setType('emailcancel', PARAM_TEXT);

        // Set up the header of the form.
        $mform->addElement('header', 'requestfeedback360', get_string('requestfeedback360', 'totara_feedback360'));

        // Set up the confirmation string.
        $strconfirm = get_string('requestfeedback360confirm', 'totara_feedback360');
        $mform->addElement('static', 'confirmation', '', $strconfirm);

        // Create a place to show request changes for confirmation.
        $mform->addElement('static', 'show_changes', '', '');

        // And the confirm/cancel buttons.
        $this->add_action_buttons(true, get_string('confirm'));
    }

    public function set_data($data)
    {
        global $DB, $USER;

        $mform =& $this->_form;

        if (!empty($data['userid']))
        {
            $mform->getElement('userid')->setValue($data['userid']);
        }
        else
        {
            $mform->getElement('userid')->setValue($USER->id);
        }

        if (!empty($data['cmid']))
        {
            $mform->cmid = $data['cmid'];
        }
        else
        {
            print_error('error:noformselected', 'totara_feedback360');
        }

        if (!empty($data['topicid']) && !empty($data['topicname']))
        {
            $mform->topicid = $data['topicid'];
            $mform->topicid = $data['topicname'];
        }
        else
        {
            // TODO ERROR CODE FOR NO TOPICID
            print_error('NO TOPIC ID', 'TODO');
        }

        // Include the list of all external emails.
        $newexternal = array();
        if (!empty($data['emailnew']))
        {
            $newexternal = explode(',', $data['emailnew']);
            $mform->getElement('emailnew')->setValue($data['emailnew']);
        }

        // Show cancellations.
        $cancelexternal = array();
        if (!empty($data['emailcancel']))
        {
            $cancelexternal = explode(',', $data['emailcancel']);
            $mform->getElement('emailcancel')->setValue($data['emailcancel']);
        }

        $keepexternal = array();
        if (!empty($data['emailkeep']))
        {
            $keepexternal = explode(',', $data['emailkeep']);
            $mform->getElement('emailkeep')->setValue(implode(',', $keepexternal));
        }

        $oldduedate = 0;
        if (!empty($data['oldduedate']))
        {
            $oldduedate = $data['oldduedate'];
            $mform->getElement('oldduedate')->setValue($oldduedate);
        }

        $newduedate = 0;
        if (!empty($data['newduedate']))
        {
            $newduedate = $data['newduedate'];
            $mform->getElement('duedate')->setValue($newduedate);
        }

        $duenotifications = false;
        if ((!empty($oldduedate) && $oldduedate < $newduedate) // due date updated
            || (empty($newduedate) && !(empty($oldduedate)))) // due date removed
        {
            $duenotifications = true;
            $mform->getElement('duenotifications')->setValue(true);
        }

        // Display all this on the screen.
        $changesstr = '';

        // New requests.
        if (!empty($newexternal))
        {
            $newrequests = html_writer::start_tag('div', array('class' => 'new_requests'));
            $newrequests .= get_string('requestfeedback360create', 'totara_feedback360');
            $newrequests .= html_writer::start_tag('ul') . html_writer::empty_tag('li');
            $newrequests .= implode(html_writer::empty_tag('li'), $newexternal);
            $newrequests .= html_writer::end_tag('ul');
            $newrequests .= html_writer::end_tag('div');
            $changesstr  .= $newrequests;
        }

        // Cancel requests.
        if (!empty($cancelexternal))
        {
            $cancelledrequests = html_writer::start_tag('div', array('class' => 'cancelled_requests'));
            $cancelledrequests .= get_string('requestfeedback360delete', 'totara_feedback360');
            $cancelledrequests .= html_writer::start_tag('ul') . html_writer::empty_tag('li');
            $cancelledrequests .= implode(html_writer::empty_tag('li'), $cancelexternal);
            $cancelledrequests .= html_writer::end_tag('ul');
            $cancelledrequests .= html_writer::end_tag('div');
            $changesstr        .= $cancelledrequests;
        }

        // Due notifications.
        if ($duenotifications)
        {
            if (!empty($oldduedate) && $oldduedate < $newduedate)
            {
                // due date updated
                $strdata        = ['from' => userdate($oldduedate), 'to' => userdate($newduedate)];
                $duedateupdated = html_writer::start_tag('div', array('class' => 'duedate_update'));
                //TODO lang string
                $duedateupdated .= get_string('requestupdateduedate', 'mod_observation', $strdata);
                $duedateupdated .= html_writer::end_tag('div');
                $changesstr     .= $duedateupdated;
            }
            else if (empty($newduedate) && !empty($oldduedate))
            {
                // due date removed
                $duedateupdated = html_writer::start_tag('div', array('class' => 'duedate_update'));
                //TODO lang string
                $duedateupdated .= get_string('requestdeleteduedate', 'mod_observation', userdate($oldduedate));
                $duedateupdated .= html_writer::end_tag('div');
                $changesstr     .= $duedateupdated;
            }
            if (!empty($keepexternal))
            {
                $keeprequests = html_writer::start_tag('div', array('class' => 'duedate_reminders'));
                $keeprequests .= get_string('requestfeedback360keep', 'totara_feedback360');
                $keeprequests .= html_writer::start_tag('ul') . html_writer::empty_tag('li');
                $keeprequests .= implode(html_writer::empty_tag('li'), $keepexternal);
                $keeprequests .= html_writer::end_tag('ul');
                $keeprequests .= html_writer::end_tag('div');
                $changesstr   .= $keeprequests;
            }
        }

        $mform->getElement('show_changes')->setValue($changesstr);

        parent::set_data($data);
    }
}

/**
 * The form for editing evidence
 */
class observation_topicitem_files_form extends moodleform
{

    /**
     * Requires the following $_customdata to be passed into the constructor:
     * topicitemid, userid.
     *
     * @global object $DB
     */
    function definition()
    {
        global $FILEPICKER_OPTIONS;

        $mform =& $this->_form;

        // Determine permissions from evidence
        $topicitemid    = $this->_customdata['topicitemid'];
        $userid         = $this->_customdata['userid'];
        $evidencetypeid = isset($this->_customdata['evidencetypeid']) ? $this->_customdata['evidencetypeid'] : null;
        $fileoptions    =
            isset($this->_customdata['fileoptions']) ? $this->_customdata['fileoptions'] : $FILEPICKER_OPTIONS;

        $mform->addElement('hidden', 'tiid', $topicitemid);
        $mform->setType('tiid', PARAM_INT);

        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('filemanager', 'topicitemfiles_filemanager',
            get_string('topicitemfiles', 'observation'), null, $fileoptions);

        $this->add_action_buttons(true, get_string('updatefiles', 'observation'));
    }
}
