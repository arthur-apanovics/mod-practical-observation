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

defined('MOODLE_INTERNAL') || die();

use core\output\flex_icon;
use mod_ojt\models\completion;
use mod_ojt\models\email_assignment;
use mod_ojt\models\external_request;
use mod_ojt\models\ojt;
use mod_ojt\user_external_request;
use mod_ojt\user_ojt;
use mod_ojt\user_topic;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/ojt/lib.php');

class mod_ojt_renderer extends plugin_renderer_base
{
    /**
     * Topic configuration page
     *
     * @param      $ojt
     * @param bool $config
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function config_topics($ojt, $config = true)
    {
        global $DB;

        $out = '';
        $out .= html_writer::start_tag('div', array('id' => 'config-mod-ojt-topics'));

        $topics = $DB->get_records('ojt_topic', array('ojtid' => $ojt->id), 'id');
        if (empty($topics))
        {
            return html_writer::tag('p', get_string('notopics', 'ojt'));
        }
        foreach ($topics as $topic)
        {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic'));
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-heading'));
            $optionalstr =
                $topic->completionreq == completion::REQ_OPTIONAL ? ' (' . get_string('optional', 'ojt') . ')' : '';
            $out .= format_string($topic->name) . $optionalstr;
            if ($config)
            {
                $additemurl = new moodle_url('/mod/ojt/topicitem.php', array('bid' => $ojt->id, 'tid' => $topic->id));
                $out .= $this->output->action_icon($additemurl,
                    new flex_icon('plus', ['alt' => get_string('additem', 'ojt')]));
                $editurl = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id));
                $out .= $this->output->action_icon($editurl,
                    new flex_icon('edit', ['alt' => get_string('edittopic', 'ojt')]));
                $deleteurl =
                    new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl,
                    new flex_icon('delete', ['alt' => get_string('deletetopic', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');

            $out .= $this->config_topic_items($ojt->id, $topic->id, $config);
            $out .= html_writer::end_tag('div');
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Topic Item configuration page
     *
     * @param      $ojtid
     * @param      $topicid
     * @param bool $config
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function config_topic_items($ojtid, $topicid, $config = true)
    {
        global $DB;

        $out = '';

        $items = $DB->get_records('ojt_topic_item', array('topicid' => $topicid), 'id');

        $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-items'));
        foreach ($items as $item)
        {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-item'));
            $optionalstr =
                $item->completionreq == completion::REQ_OPTIONAL ? ' (' . get_string('optional', 'ojt') . ')' : '';
            $out .= format_string($item->name) . $optionalstr;
            if ($config)
            {
                $editurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id));
                $out .= $this->output->action_icon($editurl,
                    new flex_icon('edit', ['alt' => get_string('edititem', 'ojt')]));
                $deleteurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl,
                    new flex_icon('delete', ['alt' => get_string('deleteitem', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * @param user_ojt $user_ojt
     * @param stdClass $cm course module
     * @return string html
     * @throws coding_exception
     * @throws moodle_exception
     */
    function userojt_topic_summary(user_ojt $user_ojt, stdClass $cm)
    {
        $out = '';
        $data = [];
        $data['has_topics'] = !empty($user_ojt->topics);

        foreach ($user_ojt->topics as $topic)
        {
            $topic_data = [];
            $topic_data['id'] = $topic->id;
            $topic_data['title'] = $topic->name;
            $topic_data['submission_status_icon'] = $this->get_topic_submission_status_icon($topic);
            $topic_data['completion_icon_html'] = $this->get_completion_icon($topic);

            $topic_data['url'] = new moodle_url('/mod/ojt/viewtopic.php',
                ['id' => $cm->id, 'topic' => $topic->id]);
            $topic_data['manage_url'] = new moodle_url('/mod/ojt/request.php',
                ['cmid' => $cm->id, 'topicid' => $topic->id, 'userid' => $user_ojt->userid]);

            $data['topics'][] = $topic_data;
        }

        $out .= $this->render_from_template('mod_ojt/topic_summary', $data);

        return $out;
    }

    /**
     * Renders all ojt topic content
     *
     * @param user_ojt $userojt
     * @param bool     $evaluate
     * @param bool     $signoff
     * @param bool     $itemwitness
     * @return string html
     * @throws coding_exception
     * @throws comment_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function user_ojt(user_ojt $userojt, $evaluate = false, $signoff = false, $itemwitness = false)
    {
        $out = '';
        $out .= html_writer::start_tag('div', array('id' => 'mod-ojt-user-ojt'));

        foreach ($userojt->topics as $topic)
        {
            $out .= $this->user_topic($userojt, $topic, $evaluate, $signoff, $itemwitness);
        }

        $out .= html_writer::end_tag('div');  // mod-ojt-user-ojt

        return $out;
    }

    /**
     * @param user_ojt   $userojt
     * @param user_topic $topic
     * @param bool       $evaluate
     * @param bool       $signoff
     * @param bool       $itemwitness
     * @return string html
     * @throws coding_exception
     * @throws comment_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function user_topic(user_ojt $userojt, user_topic $topic, $evaluate = false, $signoff = false, $itemwitness = false)
    {
        global $CFG, $DB, $USER;

        $course = $DB->get_record('course', array('id' => $userojt->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('ojt', $userojt->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $out = '';

        $out .= html_writer::start_tag('div',
            array('class' => 'mod-ojt-topic', 'id' => "ojt-topic-{$topic->id}"));
        $completionicon = $this->get_completion_icon($topic);
        $completionicon = html_writer::tag('span', $completionicon,
            array('class' => 'ojt-topic-status'));
        $optionalstr = $topic->completionreq == completion::REQ_OPTIONAL ?
            html_writer::tag('em', ' (' . get_string('optional', 'ojt') . ')') : '';
        $out .= html_writer::tag('div', format_string($topic->name) . $optionalstr . $completionicon,
            array('class' => 'mod-ojt-topic-heading expanded'));

        $table = new html_table();
        $table->attributes['class'] = 'mod-ojt-topic-items generaltable';

        if ($userojt->itemwitness)
        {
            $table->head = array('', '', get_string('witnessed', 'mod_ojt'));
        }
        $table->data = array();

        foreach ($topic->topic_items as $item)
        {
            // COLUMN 1: Topic Item Title
            $row = array();
            $optionalstr = $item->completionreq == completion::REQ_OPTIONAL ?
                html_writer::tag('em', ' (' . get_string('optional', 'ojt') . ')') : '';
            $row[] = format_string($item->name) . $optionalstr;

            // COLUMN 2: Topic Item Content (conversation & files)
            if ($evaluate)
            {
                $cellcontent = html_writer::start_tag('div', array('class' => 'ojt-eval-actions', 'ojt-item-id' => $item->id));
                $cellcontent .= html_writer::tag('textarea', $item->completion->comment,
                    array(
                        'name'        => 'comment-' . $item->id,
                        'rows'        => 4,
                        'class'       => 'ojt-completion-comment',
                        'ojt-item-id' => $item->id));
                $cellcontent .= html_writer::tag('div', format_text($item->completion->comment, FORMAT_PLAIN),
                    array('class' => 'ojt-completion-comment-print', 'ojt-item-id' => $item->id));
                $cellcontent .= html_writer::end_tag('div');
            }
            else
            {
                // Show static stuff.

                // red cross or blue tick before text:
                // if ($item->completion->status == completion::STATUS_COMPLETE)
                // {
                //     $cellcontent .= $this->output->flex_icon('check-success',
                //         ['alt' => get_string('completionstatus' . completion::STATUS_COMPLETE, 'ojt')]);
                // }
                // else
                // {
                //     $cellcontent .= $this->output->flex_icon('times-danger',
                //         ['alt' => get_string('completionstatus' . completion::STATUS_INCOMPLETE, 'ojt')]);
                // }
                $cellcontent = format_text($item->completion->comment, FORMAT_PLAIN);

                //TODO USER SUBMISSIONS
                // at the moment entry will not be saved and ispurely for demonstration purposes
                $cellcontent .= html_writer::start_tag('div', array('class' => 'ojt-submission-actions', 'ojt-item-id' => $item->id));
                $cellcontent .= html_writer::tag('textarea', '',
                    array(
                        'name'        => 'sumission-' . $item->id,
                        'rows'        => 4,
                        'class'       => 'ojt-completion-submission',
                        'ojt-item-id' => $item->id,
                        'placeholder' => 'At the moment, text entered here will not be saved'));
                $cellcontent .= html_writer::tag('div', format_text('', FORMAT_PLAIN),
                    array('class' => 'ojt-completion-submission-print', 'ojt-item-id' => $item->id));
                $cellcontent .= html_writer::end_tag('div');
            }

            $userobj = username_load_fields_from_object(new stdClass(), $item, $prefix = 'modifier');
            $cellcontent .= html_writer::tag('div', ojt::get_modifiedstr_user($item->completion->timemodified, $userobj),
                array('class' => 'mod-ojt-modifiedstr', 'ojt-item-id' => $item->id));

            if ($item->allowfileuploads || $item->allowselffileuploads)
            {
                $cellcontent .= html_writer::tag('div',
                    $this->list_topic_item_files($context->id, $userojt->userid, $item->id),
                    array('class' => 'mod-ojt-topicitem-files'));

                if (($evaluate && $item->allowfileuploads)
                    || ($userojt->userid == $USER->id && $item->allowselffileuploads))
                {
                    $itemfilesurl = new moodle_url('/mod/ojt/uploadfile.php',
                        array('userid' => $userojt->userid, 'tiid' => $item->id));
                    $cellcontent .= $this->output->single_button($itemfilesurl, get_string('updatefiles', 'ojt'),
                        'get');
                }
            }

            $row[] = html_writer::tag('div', $cellcontent, array('class' => 'ojt-completion'));

            // COLUMN 3: Topic Item Completion
            $completionicon = $item->completion->status == completion::STATUS_COMPLETE
                ? 'completion-manual-y'
                : 'completion-manual-n';
            $cellcontent = $this->output->flex_icon($completionicon, ['classes' => 'ojt-completion-toggle']);
            $row[] = html_writer::tag('div', $cellcontent, array('class' => 'ojt-item-witness'));

            // if ($userojt->itemwitness)
            // {
            //     $cellcontent = '';
            //     if ($itemwitness)
            //     {
            //         $witnessicon = $item->witness->witnessedby ? 'completion-manual-y' : 'completion-manual-n';
            //         $cellcontent .= html_writer:: start_tag('span',
            //             array('class' => 'ojt-witness-item', 'ojt-item-id' => $item->id));
            //         $cellcontent .= $this->output->flex_icon($witnessicon, ['classes' => 'ojt-witness-toggle']);
            //         $cellcontent .= html_writer::end_tag('span');
            //     }
            //     else
            //     {
            //         // Show static witness info
            //         if (!empty($item->witness->witnessedby))
            //         {
            //             $cellcontent .= $this->output->flex_icon('check-success',
            //                 ['alt' => get_string('witnessed', 'ojt')]);
            //         }
            //         else
            //         {
            //             $cellcontent .= $this->output->flex_icon('times-danger',
            //                 ['alt' => get_string('notwitnessed', 'ojt')]);
            //         }
            //     }
            //
            //     $userobj = new stdClass();
            //     $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'itemwitness');
            //     $cellcontent .= html_writer::tag('div', ojt::get_modifiedstr_user($item->witness->timewitnessed, $userobj),
            //         array('class' => 'mod-ojt-witnessedstr', 'ojt-item-id' => $item->id));
            //
            //     $row[] = html_writer::tag('div', $cellcontent, array('class' => 'ojt-item-witness'));
            // }

            $table->data[] = $row;
        }

        $out .= html_writer::table($table);

        // TOPIC SIGNOFF
        if ($userojt->managersignoff)
        {
            $out .= html_writer::start_tag('div',
                array('class' => 'mod-ojt-topic-signoff', 'ojt-topic-id' => $topic->id));
            $out .= get_string('managersignoff', 'ojt');
            if ($signoff)
            {
                $out .= $this->output->flex_icon($topic->signoff->signedoff ? 'completion-manual-y' :
                    'completion-manual-n',
                    ['classes' => 'ojt-topic-signoff-toggle']);
            }
            else
            {
                if ($topic->signoff->signedoff)
                {
                    $out .= $this->output->flex_icon('check-success', ['alt' => get_string('signedoff', 'ojt')]);
                }
                else
                {
                    $out .= $this->output->flex_icon('times-danger', ['alt' => get_string('notsignedoff', 'ojt')]);
                }
            }
            $userobj = new stdClass();
            $userobj = username_load_fields_from_object($userobj, $topic, $prefix = 'signoffuser');
            $out .= html_writer::tag('div', ojt::get_modifiedstr_user($topic->signoff->timemodified, $userobj),
                array('class' => 'mod-ojt-topic-modifiedstr'));
            $out .= html_writer::end_tag('div');
        }

        // Topic comments
        if ($topic->allowcomments)
        {
            $out .= $this->output->heading(get_string('topiccomments', 'ojt'), 4);
            require_once($CFG->dirroot . '/comment/lib.php');
            comment::init();
            $options = new stdClass();
            $options->area = 'ojt_topic_item_' . $topic->id;
            $options->context = $context;
            $options->itemid = $userojt->userid;
            $options->showcount = true;
            $options->component = 'ojt';
            $options->autostart = true;
            $options->notoggle = true;

            $comment = new comment($options);
            $out .= $comment->output(true);
        }


        $out .= html_writer::end_tag('div');  // mod-ojt-topic

        return $out;
    }

    public function user_topic_external(user_ojt $userojt, user_topic $topic)
    {
        global $CFG, $DB;

        $course = $DB->get_record('course', array('id' => $userojt->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('ojt', $userojt->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $out = '';

        $out .= html_writer::start_tag('div',
            array('class' => 'mod-ojt-topic', 'id' => "ojt-topic-{$topic->id}"));
        $completionicon = $this->get_completion_icon($topic);
        $completionicon = html_writer::tag('span', $completionicon,
            array('class' => 'ojt-topic-status'));
        $optionalstr = $topic->completionreq == completion::REQ_OPTIONAL ?
            html_writer::tag('em', ' (' . get_string('optional', 'ojt') . ')') : '';
        $out .= html_writer::tag('div', format_string($topic->name) . $optionalstr . $completionicon,
            array('class' => 'mod-ojt-topic-heading expanded'));

        $table = new html_table();
        $table->attributes['class'] = 'mod-ojt-topic-items generaltable';

        if ($userojt->itemwitness)
        {
            $table->head = array('', '', get_string('witnessed', 'mod_ojt'));
        }
        $table->data = array();

        foreach ($topic->topic_items as $item)
        {
            $row = array();
            $optionalstr = $item->completionreq == completion::REQ_OPTIONAL ?
                html_writer::tag('em', ' (' . get_string('optional', 'ojt') . ')') : '';
            $row[] = format_string($item->name) . $optionalstr;

            $cellcontent = html_writer::start_tag('div', array('class' => 'ojt-eval-actions', 'ojt-item-id' => $item->id));
            $cellcontent .= html_writer::tag('textarea', $item->completion->comment,
                array(
                    'name'        => 'comment-' . $item->id,
                    'rows'        => 4,
                    'class'       => 'ojt-completion-comment',
                    'ojt-item-id' => $item->id));
            $cellcontent .= html_writer::tag('div', format_text($item->completion->comment, FORMAT_PLAIN),
                array('class' => 'ojt-completion-comment-print', 'ojt-item-id' => $item->id));
            $cellcontent .= html_writer::end_tag('div');

            $cellcontent .= html_writer::tag('div',
                ojt::get_modifiedstr_email($item->completion->timemodified, $item->completion->observeremail),
                array('class' => 'mod-ojt-modifiedstr', 'ojt-item-id' => $item->id));

            $row[] = html_writer::tag('div', $cellcontent, array('class' => 'ojt-completion'));

            $completionicon = $item->completion->status == completion::STATUS_COMPLETE
                ? 'completion-manual-y'
                : 'completion-manual-n';
            $cellcontent = $this->output->flex_icon($completionicon, ['classes' => 'ojt-completion-toggle']);

            $row[] = html_writer::tag('div', $cellcontent, array('class' => 'ojt-item-witness'));

            //TODO FILE UPLOADS FOR EXTERNAL USERS
            // if ($item->allowfileuploads || $item->allowselffileuploads)
            // {
            //     $cellcontent .= html_writer::tag('div',
            //         $this->list_topic_item_files($context->id, $userojt->userid, $item->id),
            //         array('class' => 'mod-ojt-topicitem-files'));
            //
            //     if (($evaluate && $item->allowfileuploads)
            //         || ($userojt->userid == $USER->id && $item->allowselffileuploads))
            //     {
            //         $itemfilesurl = new moodle_url('/mod/ojt/uploadfile.php',
            //             array('userid' => $userojt->userid, 'tiid' => $item->id));
            //         $cellcontent .= $this->output->single_button($itemfilesurl, get_string('updatefiles', 'ojt'),
            //             'get');
            //     }
            // }

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
            array('class' => 'ojt-submit'));

        // Topic comments
        if ($topic->allowcomments)
        {
            $out .= $this->output->heading(get_string('topiccomments', 'ojt'), 4);
            require_once($CFG->dirroot . '/comment/lib.php');
            comment::init();
            $options = new stdClass();
            $options->area = 'ojt_topic_item_' . $topic->id;
            $options->context = $context;
            $options->itemid = $userojt->userid;
            $options->showcount = true;
            $options->component = 'ojt';
            $options->autostart = true;
            $options->notoggle = true;

            $comment = new comment($options);
            $out .= $comment->output(true);
        }

        $out .= html_writer::end_tag('div');  // mod-ojt-topic

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
                ['alt' => get_string('completionstatus' . $topic->completion_status, 'ojt')]);
        }
        $completionicon = html_writer::tag('span', $completionicon, ['class' => 'ojt-topic-status']);

        return $completionicon;
    }

    /**
     * @param string $ojt_name
     * @param string $username
     * @return string
     * @throws coding_exception
     */
    function get_print_button(string $ojt_name, string $username)
    {
        global $OUTPUT;

        $out = '';

        $out .= html_writer::start_tag('a', array('href' => 'javascript:window.print()', 'class' => 'evalprint'));
        $out .= html_writer::empty_tag('img',
            array(
                'src'   => $OUTPUT->pix_url('t/print'),
                'alt'   => get_string('printthisojt', 'ojt'),
                'class' => 'icon'));
        $out .= get_string('printthisojt', 'ojt');
        $out .= html_writer::end_tag('a');
        $out .= $OUTPUT->heading(get_string('ojtxforx', 'ojt',
            (object) array(
                'ojt'  => format_string($ojt_name),
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
            $save = html_writer::tag('div', $this->output->render($savebutton), array('class' => 'ojt-save'));
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
        $deleteurl = '#';//new moodle_url('/mod/ojt/ext_request/delete.php', $deleteparams);

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
        $files = $fs->get_area_files($contextid, 'mod_ojt', 'topicitemfiles' . $topicitemid, $userid,
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
     * @param int         $ojtid
     * @param int         $userid
     * @param string|null $token email assignment token for external evaluations
     * @return array [args[], jsmodule[]]
     */
    public function get_evaluation_js_args(int $ojtid, int $userid, string $token = null)
    {
        $args     = array(
            'args' =>
                '{ "ojtid":' . $ojtid .
                ', "userid":' . $userid .
                ', "token":"' . $token . '"' .
                ', "OJT_COMPLETE":' . completion::STATUS_COMPLETE .
                ', "OJT_REQUIREDCOMPLETE":' . completion::STATUS_REQUIREDCOMPLETE .
                ', "OJT_INCOMPLETE":' . completion::STATUS_INCOMPLETE .
                '}');
        $jsmodule = array(
            'name'     => 'mod_ojt_evaluate',
            'fullpath' => '/mod/ojt/js/evaluate.js',
            'requires' => array('json')
        );

        return array($args, $jsmodule);
    }

    private function get_topic_submission_status_icon(user_topic $topic)
    {
        $date_str = get_string('nosubmissiondate', 'mod_ojt');
        $icon_id = 'square-o';

        if (!is_null($topic->external_request))
        {
            $date = $topic->external_request->get_first_email_assign_date();
            if ($date > 0)
            {
                $date_str = get_string('submissiondate', 'mod_ojt', userdate($date));
                $icon_id = 'check-square-o';
            }
        }

        return $this->output->flex_icon($icon_id, ['alt' => $date_str]);
    }
}

