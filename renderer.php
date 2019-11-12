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

defined('MOODLE_INTERNAL') || die();

use core\output\flex_icon;
use mod_observation\models\completion;
use mod_observation\models\email_assignment;
use mod_observation\models\observation;
use mod_observation\user_attempt;
use mod_observation\user_observation;
use mod_observation\user_topic;
use mod_observation\user_topic_item;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');

class mod_observation_renderer extends plugin_renderer_base
{
    private $course;
    private $cm;
    private $context;

    /**
     * @param user_observation        $userobservation
     * @throws coding_exception
     * @throws dml_exception
     */
    private function set_properties(user_observation $userobservation): void
    {
        global $DB;

        $this->course  = $DB->get_record('course', array('id' => $userobservation->course), '*', MUST_EXIST);
        $this->cm      = get_coursemodule_from_instance('observation', $userobservation->id, $this->get_course()->id, false, MUST_EXIST);
        $this->context = context_module::instance($this->get_cm()->id);
    }

    private function get_course()
    {
        if (!isset($this->course))
        {
            throw new coding_exception('renderer course not set');
        }

        return $this->course;
    }

    private function get_cm()
    {
        if (!isset($this->cm))
        {
            throw new coding_exception('renderer course module not set');
        }

        return $this->cm;
    }

    private function get_context()
    {
        if (!isset($this->context))
        {
            $this->context = context_module::instance($this->get_cm()->id);
        }

        return $this->context;
    }

    /**
     * Topic configuration page
     *
     * @param      $observation
     * @param bool $config
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function config_topics($observation, $config = true)
    {
        global $DB;

        $out = '';
        $out .= html_writer::start_tag('div', array('id' => 'config-mod-observation-topics'));

        $topics = $DB->get_records('observation_topic', array('observationid' => $observation->id), 'id');
        if (empty($topics))
        {
            return html_writer::tag('p', get_string('notopics', 'observation'));
        }
        foreach ($topics as $topic)
        {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-observation-topic'));
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-observation-topic-heading'));
            $optionalstr =
                $topic->completionreq == completion::REQ_OPTIONAL ? ' (' . get_string('optional', 'observation') . ')' : '';
            $out .= format_string($topic->name) . $optionalstr;
            if ($config)
            {
                $additemurl = new moodle_url('/mod/observation/topicitem.php', array('bid' => $observation->id, 'tid' => $topic->id));
                $out .= $this->output->action_icon($additemurl,
                    new flex_icon('plus', ['alt' => get_string('additem', 'observation')]));
                $editurl = new moodle_url('/mod/observation/topic.php', array('bid' => $observation->id, 'id' => $topic->id));
                $out .= $this->output->action_icon($editurl,
                    new flex_icon('edit', ['alt' => get_string('edittopic', 'observation')]));
                $deleteurl =
                    new moodle_url('/mod/observation/topic.php', array('bid' => $observation->id, 'id' => $topic->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl,
                    new flex_icon('delete', ['alt' => get_string('deletetopic', 'observation')]));
            }
            $out .= html_writer::end_tag('div');

            $out .= $this->config_topic_items($observation->id, $topic->id, $config);
            $out .= html_writer::end_tag('div');
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Topic Item configuration page
     *
     * @param      $observationid
     * @param      $topicid
     * @param bool $config
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function config_topic_items($observationid, $topicid, $config = true)
    {
        global $DB;

        $out = '';

        $items = $DB->get_records('observation_topic_item', array('topicid' => $topicid), 'id');

        $out .= html_writer::start_tag('div', array('class' => 'config-mod-observation-topic-items'));
        foreach ($items as $item)
        {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-observation-topic-item'));
            $optionalstr =
                $item->completionreq == completion::REQ_OPTIONAL ? ' (' . get_string('optional', 'observation') . ')' : '';
            $out .= format_string($item->name) . $optionalstr;
            if ($config)
            {
                $editurl = new moodle_url('/mod/observation/topicitem.php',
                    array('bid' => $observationid, 'tid' => $topicid, 'id' => $item->id));
                $out .= $this->output->action_icon($editurl,
                    new flex_icon('edit', ['alt' => get_string('edititem', 'observation')]));
                $deleteurl = new moodle_url('/mod/observation/topicitem.php',
                    array('bid' => $observationid, 'tid' => $topicid, 'id' => $item->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl,
                    new flex_icon('delete', ['alt' => get_string('deleteitem', 'observation')]));
            }
            $out .= html_writer::end_tag('div');
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * @param user_observation $user_observation
     * @param stdClass $cm course module
     * @return string html
     * @throws coding_exception
     * @throws moodle_exception
     */
    function userobservation_topic_summary(user_observation $user_observation, stdClass $cm)
    {
        $out = '';
        $data = [];
        $data['has_topics'] = !empty($user_observation->topics);

        foreach ($user_observation->topics as $topic)
        {
            $topic_data = [];
            $topic_data['id'] = $topic->id;
            $topic_data['title'] = $topic->name;
            $topic_data['is_submitted'] = $topic->is_submitted();
            $topic_data['submission_status_icon'] = $this->get_topic_submission_status_icon($topic);
            $topic_data['completion_icon_html'] = $this->get_completion_icon($topic);

            $topic_data['url'] = new moodle_url('/mod/observation/viewtopic.php',
                ['id' => $cm->id, 'topic' => $topic->id]);
            $topic_data['manage_url'] = new moodle_url('/mod/observation/request.php',
                ['cmid' => $cm->id, 'topicid' => $topic->id, 'userid' => $user_observation->userid, 'action' => 'users']);

            $data['topics'][] = $topic_data;
        }

        $out .= $this->render_from_template('mod_observation/topic_summary', $data);

        return $out;
    }

    /**
     * Renders all observation topic content
     *
     * @param user_observation $userobservation
     * @param bool     $evaluate
     * @param bool     $signoff
     * @param bool     $itemwitness
     * @return string html
     */
    function user_observation(user_observation $userobservation, $evaluate = false, $signoff = false, $itemwitness = false)
    {
        $out = '';
        $out .= html_writer::start_tag('div', array('id' => 'mod-observation-user-observation'));

        foreach ($userobservation->topics as $topic)
        {
            $out .= $this->user_topic($userobservation, $topic, $evaluate, $signoff, $itemwitness);
        }

        $out .= html_writer::end_tag('div');  // mod-observation-user-observation

        return $out;
    }

    /**
     * @param user_observation   $userobservation
     * @param user_topic $topic
     * @param bool       $evaluate
     * @param bool       $signoff
     * @param bool       $itemwitness
     * @return string html
     */
    public function user_topic(user_observation $userobservation, user_topic $topic, $evaluate = false, $signoff = false, $itemwitness = false)
    {
        global $PAGE;

        $this->set_properties($userobservation);

        $submitted = $topic->is_submitted();
        $out       = '';

        if ($submitted && !$evaluate)
        {
            \core\notification::add('Submisison is locked until an observer has reviewed or released it', \core\output\notification::NOTIFY_INFO);
        }

        // open topic container
        $out .= $this->get_topic_start($topic);

        if ($evaluate)
        {
            $out .= html_writer::div(format_text($topic->observerintro, $topic->observerintroformat), 'observation-topic_intro observation-topic_observerintro');
        }
        else
        {
            $out .= html_writer::div(format_text($topic->intro, $topic->introformat), 'observation-topic_intro');
        }

        // render topic table
        $out .= $this->get_topic_table($userobservation, $topic, $evaluate, $submitted);

        // TOPIC SIGNOFF
        if ($userobservation->managersignoff)
        {
            $out .= html_writer::start_tag('div',
                array('class' => 'mod-observation-topic-signoff', 'observation-topic-id' => $topic->id));
            $out .= get_string('managersignoff', 'observation');
            if ($signoff)
            {
                $out .= $this->output->flex_icon($topic->signoff->signedoff ? 'completion-manual-y' :
                    'completion-manual-n',
                    ['classes' => 'observation-topic-signoff-toggle']);
            }
            else
            {
                if ($topic->signoff->signedoff)
                    $out .= $this->output->flex_icon('check-success', ['alt' => get_string('signedoff', 'observation')]);
                else
                    $out .= $this->output->flex_icon('times-danger', ['alt' => get_string('notsignedoff', 'observation')]);
            }

            $userobj = new stdClass();
            $userobj = username_load_fields_from_object($userobj, $topic, $prefix = 'signoffuser');
            $out .= html_writer::tag('div', observation::get_modifiedstr_user($topic->signoff->timemodified, $userobj),
                array('class' => 'mod-observation-topic-modifiedstr'));
            $out .= html_writer::end_tag('div');
        }

        if (!$evaluate && !$submitted)
        {
            $args     = array(
                'args' =>
                    '{ "observationid":' . $userobservation->id .
                    ', "userid":' . $userobservation->userid .
                    ', "Observation_COMPLETE":' . completion::STATUS_COMPLETE .
                    ', "Observation_REQUIREDCOMPLETE":' . completion::STATUS_REQUIREDCOMPLETE .
                    ', "Observation_INCOMPLETE":' . completion::STATUS_INCOMPLETE .
                    '}');
            $jsmodule = array(
                'name'     => 'mod_observation_attempt',
                'fullpath' => '/mod/observation/js/attempt.js',
                'requires' => array('json')
            );
            $PAGE->requires->js_init_call('M.mod_observation_attempt.init', $args, false, $jsmodule);

            // display 'submit attempt' button
            $out .= html_writer::link(
                new moodle_url('/mod/observation/request.php',
                    ['cmid' => $this->get_cm()->id, 'topicid' => $topic->id, 'userid' => $userobservation->userid, 'action' => 'users']),
                'Submit for observation',
                ['class' => 'btn btn-secondary', 'style' => 'margin: 2em 0 2em 0']);
        }

        // Topic comments
        $out .= $this->get_topic_comments($topic);

        // close topic container
        $out .= html_writer::end_tag('div');  // mod-observation-topic

        return $out;
    }

    /**
     * @param user_observation         $userobservation
     * @param user_topic       $topic
     * @param email_assignment $email_assignment email assignment for external user that is viewing the page
     * @return string
     * @throws coding_exception
     * @throws comment_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function user_topic_external(user_observation $userobservation, user_topic $topic, email_assignment $email_assignment)
    {
        global $DB;

        $this->set_properties($userobservation);

        $user = $DB->get_record('user', ['id' => $userobservation->userid], '*', MUST_EXIST);
        $out  = '';

        // open topic container
        $out .= $this->get_topic_start($topic);

        $out .= html_writer::div(format_text($topic->observerintro, $topic->observerintroformat), 'observation-topic_intro observation-topic_observerintro');

        // render topic table
        $table = new html_table();
        $table->attributes['class'] = 'mod-observation-topic-items generaltable';

        if ($userobservation->itemwitness)
        {
            $table->head = array('', '', get_string('witnessed', 'mod_observation'));
        }
        $table->data = array();

        foreach ($topic->topic_items as $item)
        {
            $row = array();
            $optionalstr = $item->completionreq == completion::REQ_OPTIONAL ?
                html_writer::tag('em', ' (' . get_string('optional', 'observation') . ')') : '';
            $row[] = format_string($item->name) . $optionalstr;

            $cellcontent = '';

            // USER SUBMISSION
            $cellcontent .= html_writer::start_div('observation-submission-container');
            // SUBMITTED TEXT:
            $cellcontent .= html_writer::span('<b>' . fullname($user) . ' says:</b>', 'observation-submission-title');
            $cellcontent .= html_writer::tag('p',
                'Sample trainee submission. This is how an observer will see traineess submitted text. Over time, a conversation history will be seen.');
            $cellcontent .= html_writer::end_div();

            // SUBMITTED FILES:
            $cellcontent .= html_writer::span('<b>Attached files:</b>', 'observation-submission-title');
            $cellcontent .= html_writer::tag('div',
                $this->list_topic_item_files($this->get_context()->id, $userobservation->userid, $item->id),
                array('class' => 'mod-observation-topicitem-files'));

            // EXTERNAL USER:
            $cellcontent .= html_writer::start_tag('div', array('class' => 'observation-eval-actions', 'observation-item-id' => $item->id));
            $cellcontent .= html_writer::span('<b>' . $email_assignment->email . '\'s response:</b>');
            $cellcontent .= html_writer::tag('textarea', $item->completion->comment,
                array(
                    'name'        => 'comment-' . $item->id,
                    'rows'        => 4,
                    'class'       => 'observation-completion-comment',
                    'observation-item-id' => $item->id));
            $cellcontent .= html_writer::tag('div', format_text($item->completion->comment, FORMAT_PLAIN),
                array('class' => 'observation-completion-comment-print', 'observation-item-id' => $item->id));
            $cellcontent .= html_writer::end_tag('div');

            //TODO FILE UPLOADS FOR EXTERNAL USERS
            // if (($evaluate && $item->allowfileuploads)
            //     || ($userobservation->userid == $USER->id && $item->allowselffileuploads))
            // {
            $itemfilesurl = new moodle_url('/mod/observation/uploadfile.php',
                array('userid' => $userobservation->userid, 'tiid' => $item->id));
            $cellcontent .= $this->output->single_button($itemfilesurl,
                get_string('updatefiles', 'observation'),
                'get', ['disabled' => true]);
            // }

            $cellcontent .= html_writer::tag('div',
                observation::get_modifiedstr_email($item->completion->timemodified, $item->completion->observeremail),
                array('class' => 'mod-observation-modifiedstr', 'observation-item-id' => $item->id));

            $row[] = html_writer::tag('div', $cellcontent, array('class' => 'observation-completion'));

            $completionicon = $item->completion->status == completion::STATUS_COMPLETE
                ? 'completion-manual-y'
                : 'completion-manual-n';
            $cellcontent = $this->output->flex_icon($completionicon, ['classes' => 'observation-completion-toggle']);

            $row[] = html_writer::tag('div', $cellcontent, array('class' => 'observation-item-witness'));

            $table->data[] = $row;
        }

        $out .= html_writer::table($table);

        // "Submit observation" button
        $submitbutton = new single_button(new moodle_url('#'), 'Submit Observation');
        $submitbutton->formid = 'submit';
        $submitbutton->disabled = true;
        $out .= html_writer::tag(
            'div',
            $this->output->render($submitbutton),
            array('class' => 'observation-submit'));

        // Topic comments
        $out .= $this->get_topic_comments($topic);

        // close topic container
        $out .= html_writer::end_tag('div');  // mod-observation-topic

        return $out;
    }

    /**
     * @param user_topic $topic
     * @return string icon html
     * @throws coding_exception
     */
    public function get_completion_icon(user_topic $topic): string
    {
        switch ($topic->completion_status)
        {
            case completion::STATUS_COMPLETE:
                $completionicon = 'check-success';
                break;
            case completion::STATUS_REQUIREDCOMPLETE:
                $completionicon = 'check-warning';
                break;
            default:
                $completionicon = 'times-danger';
        }
        if (!empty($completionicon))
        {
            $completionicon = $this->output->flex_icon($completionicon,
                ['alt' => get_string('completionstatus' . $topic->completion_status, 'observation')]);
        }
        $completionicon = html_writer::tag('span', $completionicon, ['class' => 'observation-topic-status']);

        return $completionicon;
    }

    /**
     * @param string $observation_name
     * @param string $username
     * @return string
     * @throws coding_exception
     */
    function get_print_button(string $observation_name, string $username)
    {
        global $OUTPUT;

        $out = '';

        $out .= html_writer::start_tag('a', array('href' => 'javascript:window.print()', 'class' => 'evalprint'));
        $out .= html_writer::empty_tag('img',
            array(
                'src'   => $OUTPUT->pix_url('t/print'),
                'alt'   => get_string('printthisobservation', 'observation'),
                'class' => 'icon'));
        $out .= get_string('printthisobservation', 'observation');
        $out .= html_writer::end_tag('a');
        $out .= $OUTPUT->heading(get_string('observationxforx', 'observation',
            (object) array(
                'observation'  => format_string($observation_name),
                'user' => $username)));

        return $out;
    }

    public function display_userview_header($user)
    {
        global $USER;

        $header = '';
        if ($USER->id != $user->id)
        {
            $picture = $this->output->user_picture($user);
            $name = fullname($user);
            $url = new moodle_url('/user/profile.php', array('id' => $user->id));
            $link = html_writer::link($url, $name);
            $viewstr = html_writer::tag('strong', get_string('viewinguserxfeedback360', 'totara_feedback360', $link));

            $header = html_writer::tag('div', $picture . ' ' . $viewstr,
                array('class' => "plan_box notifymessage totara-feedback360-head-relative", 'id' => 'feedbackhead'));
        }

        return $header;
    }

    /**
     * Display feedback header.
     *
     * @param email_assignment $email_assignment
     * @param stdClass         $subjectuser The subject of the feedback.
     * @return string HTML
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function display_feedback_header(email_assignment $email_assignment, $subjectuser)
    {
        global $CFG, $USER;

        // The heading.
        $a = new stdClass();
        $a->username = fullname($subjectuser);
        $a->userid = $subjectuser->id;
        $a->site = $CFG->wwwroot;
        $a->profileurl = "{$CFG->wwwroot}/user/profile.php?id={$subjectuser->id}";

        $anonmessage = false;
        if ($subjectuser->id == $USER->id)
        {
            $titlestr = 'userownheaderfeedback';
        }
        else
        {
            $a->responder = $email_assignment->email;
            $titlestr = 'userheaderfeedbackbyemail';
            $anonmessage = true;
        }

        $message = html_writer::tag('p', get_string($titlestr, 'totara_feedback360', $a));

        if ($anonmessage)
        {
            $anonmessage = get_string('feedbacknotanonymous', 'totara_feedback360');
            $message .= html_writer::tag('p', $anonmessage);
        }

        $content = $this->output->user_picture($subjectuser, array('link' => false)) . $message;

        if (!$email_assignment->timecompleted)
        {
            $savebutton = new single_button(new moodle_url('#'), get_string('saveprogress', 'totara_feedback360'));
            $savebutton->formid = 'saveprogress';
            $save = html_writer::tag('div', $this->output->render($savebutton), array('class' => 'observation-save'));
            $content = $save . $content;
        }

        $out = html_writer::tag('div', '', array('class' => "empty", 'id' => 'feedbackhead-anchor'));

        // HACK ALERT: Notifications by default put the content through clean text.
        // This won't work for the above save progress button, but because they clean it in code the template doesn't attempt to
        // clean it itself.
        // We can get around this by using the template directly and making sure that we clean ourselves.
        $context = array('message' => $content);
        $out .= html_writer::tag(
            'div',
            $this->render_from_template('core/notification_info', $context),
            array('id' => 'feedbackhead'));

        return $out;
    }

    /**
     * returns the html for a user item with delete button.
     *
     * @param email_assignment $assignment The associated email assignment.
     */
    public function external_user_record($assignment)
    {
        $out = '';

        $completestr = get_string('alreadyreplied', 'totara_feedback360');

        // No JS deletion url
        //$deleteparams = array('assignid' => $assignment->id, 'email' => $assignment->email);
        $deleteurl = '#';//new moodle_url('/mod/observation/ext_request/delete.php', $deleteparams);

        $out .= html_writer::start_tag('div',
            array('id' => "external_user_{$assignment->email}", 'class' => 'external_record'));
        $out .= $assignment->email;

        if (!empty($assignment->timecompleted))
        {
            $out .= $this->output->pix_icon('/t/delete_gray', $completestr);
        }
        else
        {
            $removestr = get_string('removeuserfromrequest', 'totara_feedback360', $assignment->email);
            $out .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $removestr), null,
                array('class' => 'external_record_del', 'id' => $assignment->email));
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }

    protected function list_topic_item_files($contextid, $userid, $topicitemid)
    {
        $out = array();

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'mod_observation', 'topicitemfiles' . $topicitemid, $userid,
            'itemid, filepath, filename', false);

        foreach ($files as $file)
        {
            $filename = $file->get_filename();
            $url =
                moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $out[] = html_writer::link($url, $filename);
        }
        $br = html_writer::empty_tag('br');

        return implode($br, $out);
    }

    /**
     * TODO CHECK IF TOKEN ALWAYS REQUIRED
     *
     * @param int         $observationid
     * @param int         $userid
     * @param string|null $token email assignment token for external evaluations
     * @return array [args[], jsmodule[]]
     */
    public function get_evaluation_js_args(int $observationid, int $userid, string $token = null)
    {
        $args     = array(
            'args' =>
                '{ "observationid":' . $observationid .
                ', "userid":' . $userid .
                ', "token":"' . $token . '"' .
                ', "Observation_COMPLETE":' . completion::STATUS_COMPLETE .
                ', "Observation_REQUIREDCOMPLETE":' . completion::STATUS_REQUIREDCOMPLETE .
                ', "Observation_INCOMPLETE":' . completion::STATUS_INCOMPLETE .
                '}');
        $jsmodule = array(
            'name'     => 'mod_observation_evaluate',
            'fullpath' => '/mod/observation/js/evaluate.js',
            'requires' => array('json')
        );

        return array($args, $jsmodule);
    }

    private function get_topic_submission_status_icon(user_topic $topic)
    {
        $date_str = get_string('nosubmissiondate', 'mod_observation');
        $icon_id = 'square-o';

        if (!is_null($topic->external_request))
        {
            $date = $topic->external_request->get_first_email_assign_date();
            if ($date > 0)
            {
                $date_str = get_string('submissiondate', 'mod_observation', userdate($date));
                $icon_id = 'check-square-o';
            }
        }

        return $this->output->flex_icon($icon_id, ['alt' => $date_str]);
    }

    /**
     * @param user_topic $topic
     * @return string
     * @throws coding_exception
     */
    private function get_topic_start(user_topic $topic)
    {
        $out = '';

        $out .= html_writer::start_tag('div',  array('class' => 'mod-observation-topic', 'id' => "observation-topic-{$topic->id}"));
        $completionicon = $this->get_completion_icon($topic);
        $completionicon = html_writer::tag('span', $completionicon, array('class' => 'observation-topic-status'));
        $optionalstr = $topic->completionreq == completion::REQ_OPTIONAL
            ? html_writer::tag('em', ' (' . get_string('optional', 'observation') . ')')
            : '';
        $out .= html_writer::tag('div', format_string($topic->name) . $optionalstr . $completionicon,
            array('class' => 'mod-observation-topic-heading expanded'));

        return $out;
    }

    /**
     * @param user_observation   $userobservation
     * @param user_topic $topic
     * @param            $evaluate
     * @param bool       $submitted
     * @return string
     * @throws coding_exception
     */
    private function get_topic_table(user_observation $userobservation, user_topic $topic, $evaluate, bool $submitted): string
    {
        $out   = '';
        $table = new html_table();

        $table->attributes['class'] = 'mod-observation-topic-items generaltable';

        if ($userobservation->itemwitness)
        {
            $table->head = array('', '', get_string('witnessed', 'mod_observation'));
        }

        if (!count($topic->topic_items))
        {
            $out .= html_writer::span('No topic items created...<br/>');
        }
        else
        {
            $table->data = array();
            foreach ($topic->topic_items as $item)
            {
                $table->data[] = $this->get_topic_item_table_row($item, $submitted, $evaluate);
            }

            $out .= html_writer::table($table);
        }
        return $out;
    }

    /**
     * @param user_topic_item $item
     * @param bool            $submitted
     * @param bool            $evaluate
     * @return array
     */
    private function get_topic_item_table_row(user_topic_item $item, bool $submitted, bool $evaluate)
    {
        global $USER;

        // COLUMN 1: Topic Item Title
        $row = array();
        $optionalstr = $item->completionreq == completion::REQ_OPTIONAL ?
            html_writer::tag('em', ' (' . get_string('optional', 'observation') . ')') : '';
        $row[] = format_string($item->name) . $optionalstr;

        // COLUMN 2: Topic Item Content (conversation & files)
        $cellcontent = $this->get_conversation_history($item);

        if ($evaluate)
        {
            $cellcontent .= html_writer::start_tag('div', array('class' => 'observation-eval-actions', 'observation-item-id' => $item->id));
            $cellcontent .= html_writer::tag('textarea', $item->completion->comment,
                array(
                    'name'        => 'comment-' . $item->id,
                    'rows'        => 4,
                    'class'       => 'observation-completion-comment',
                    'observation-item-id' => $item->id,
                    'disabled' => true
                ));
            $cellcontent .= html_writer::tag('div', format_text($item->completion->comment, FORMAT_PLAIN),
                array('class' => 'observation-completion-comment-print', 'observation-item-id' => $item->id));
            $cellcontent .= html_writer::end_tag('div');
        }
        else
        {
            $cellcontent .= format_text($item->completion->comment, FORMAT_PLAIN);
            $cellcontent .= html_writer::start_tag('div', array('class' => 'observation-submission-actions', 'observation-item-id' => $item->id));

            $args = array(
                'name'        => 'submission-' . $item->id,
                'rows'        => 4,
                'class'       => 'observation-completion-submission',
                'observation-item-id' => $item->id,
                'placeholder' => '');
            if ($submitted || $evaluate)
                $args += ['disabled' => true];

            $cellcontent .= html_writer::tag('textarea', '', $args);

            $cellcontent .= html_writer::tag('div', format_text('', FORMAT_PLAIN),
                array('class' => 'observation-completion-submission-print', 'observation-item-id' => $item->id));
            $cellcontent .= html_writer::end_tag('div');
        }

        if ($item->allowfileuploads || $item->allowselffileuploads)
        {
            $cellcontent .= html_writer::tag(
                'div',
                $this->list_topic_item_files($this->get_context()->id, $item->userid, $item->id),
                array('class' => 'mod-observation-topicitem-files'));

            if ($item->userid == $USER->id && $item->allowselffileuploads)
            {
                $itemfilesurl = new moodle_url('/mod/observation/uploadfile.php',
                    array('userid' => $item->userid, 'tiid' => $item->id));
                $cellcontent .= $this->output->single_button(
                    $itemfilesurl,
                    get_string('updatefiles', 'observation'),
                    'get',
                    ($submitted || $evaluate ? ['disabled' => true] : [])
                );
            }
        }

        $row[] = html_writer::tag('div', $cellcontent, array('class' => 'observation-completion'));

        // COLUMN 3: Topic Item Completion
        $completionicon = $item->completion->status == completion::STATUS_COMPLETE
            ? 'completion-manual-y'
            : 'completion-manual-n';
        $cellcontent = $this->output->flex_icon($completionicon, ['classes' => 'observation-completion-status']);
        $cellcontent .= html_writer::tag('div', observation::get_modifiedstr_email($item->completion->timemodified, $item->completion->observeremail),
            array('class' => 'mod-observation-modifiedstr', 'observation-item-id' => $item->id));
        $row[] = html_writer::tag('div', $cellcontent, array('class' => 'observation-item-witness'));

        // if ($userobservation->itemwitness)
        // {
        //     $cellcontent = '';
        //     if ($itemwitness)
        //     {
        //         $witnessicon = $item->witness->witnessedby ? 'completion-manual-y' : 'completion-manual-n';
        //         $cellcontent .= html_writer:: start_tag('span',
        //             array('class' => 'observation-witness-item', 'observation-item-id' => $item->id));
        //         $cellcontent .= $this->output->flex_icon($witnessicon, ['classes' => 'observation-witness-toggle']);
        //         $cellcontent .= html_writer::end_tag('span');
        //     }
        //     else
        //     {
        //         // Show static witness info
        //         if (!empty($item->witness->witnessedby))
        //         {
        //             $cellcontent .= $this->output->flex_icon('check-success',
        //                 ['alt' => get_string('witnessed', 'observation')]);
        //         }
        //         else
        //         {
        //             $cellcontent .= $this->output->flex_icon('times-danger',
        //                 ['alt' => get_string('notwitnessed', 'observation')]);
        //         }
        //     }
        //
        //     $userobj = new stdClass();
        //     $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'itemwitness');
        //     $cellcontent .= html_writer::tag('div', observation::get_modifiedstr_user($item->witness->timewitnessed, $userobj),
        //         array('class' => 'mod-observation-witnessedstr', 'observation-item-id' => $item->id));
        //
        //     $row[] = html_writer::tag('div', $cellcontent, array('class' => 'observation-item-witness'));
        // }

        return $row;
    }

    /**
     * @param user_topic $topic
     * @return string
     * @throws coding_exception
     * @throws comment_exception
     */
    private function get_topic_comments(user_topic $topic): string
    {
        global $CFG;

        $out = '';

        if ($topic->allowcomments)
        {
            $out .= $this->output->heading(get_string('topiccomments', 'observation'), 4);
            require_once($CFG->dirroot . '/comment/lib.php');
            comment::init();
            $options            = new stdClass();
            $options->area      = 'observation_topic_item_' . $topic->id;
            $options->context   = $this->get_context();
            $options->itemid    = $topic->userid;
            $options->showcount = true;
            $options->component = 'observation';
            $options->autostart = true;
            $options->notoggle  = true;

            $comment = new comment($options);
            $out     .= $comment->output(true);
        }

        return $out;
    }
    private function get_conversation_history(user_topic_item $topic_item)
    {
        $attempts = user_attempt::get_user_attempts($topic_item->id, $topic_item->userid);

        // $data = new stdClass();
        // $data->attemptnumber = $attempt->sequence;
        // $data->timemodified = $attempt->timemodified;
        // $data-> = $attempt->;
        // $data-> = $attempt->;
        // $data-> = $attempt->;
        // $data-> = $attempt->;
        // $data-> = $attempt->;

        return $this->render_from_template('mod_observation/topic_item_conversation',
            ['attempts' => (array)$attempts]);
    }
}

