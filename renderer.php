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
use mod_ojt\models\ojt;
use mod_ojt\user_ojt;
use mod_ojt\user_topic;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/ojt/lib.php');

class mod_ojt_renderer extends plugin_renderer_base
{

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
            $out         .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic'));
            $out         .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-heading'));
            $optionalstr =
                $topic->completionreq == completion::REQ_OPTIONAL ? ' (' . get_string('optional', 'ojt') . ')' : '';
            $out         .= format_string($topic->name) . $optionalstr;
            if ($config)
            {
                $additemurl = new moodle_url('/mod/ojt/topicitem.php', array('bid' => $ojt->id, 'tid' => $topic->id));
                $out        .= $this->output->action_icon($additemurl,
                    new flex_icon('plus', ['alt' => get_string('additem', 'ojt')]));
                $editurl    = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id));
                $out        .= $this->output->action_icon($editurl,
                    new flex_icon('edit', ['alt' => get_string('edittopic', 'ojt')]));
                $deleteurl  =
                    new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id, 'delete' => 1));
                $out        .= $this->output->action_icon($deleteurl,
                    new flex_icon('delete', ['alt' => get_string('deletetopic', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');

            $out .= $this->config_topic_items($ojt->id, $topic->id, $config);
            $out .= html_writer::end_tag('div');
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    function config_topic_items($ojtid, $topicid, $config = true)
    {
        global $DB;

        $out = '';

        $items = $DB->get_records('ojt_topic_item', array('topicid' => $topicid), 'id');

        $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-items'));
        foreach ($items as $item)
        {
            $out         .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-item'));
            $optionalstr =
                $item->completionreq == completion::REQ_OPTIONAL ? ' (' . get_string('optional', 'ojt') . ')' : '';
            $out         .= format_string($item->name) . $optionalstr;
            if ($config)
            {
                $editurl   = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id));
                $out       .= $this->output->action_icon($editurl,
                    new flex_icon('edit', ['alt' => get_string('edititem', 'ojt')]));
                $deleteurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id, 'delete' => 1));
                $out       .= $this->output->action_icon($deleteurl,
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
            $topic_data                         = [];
            $topic_data['id']                   = $topic->id;
            $topic_data['title']                = $topic->name;
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
        $out = html_writer::start_tag('div', array('id' => 'mod-ojt-user-ojt'));

        foreach ($userojt->topics as $topic)
            $out .= $this->user_topic($userojt, $topic, $evaluate, $signoff, $itemwitness);

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
        global $CFG, $DB, $USER, $PAGE;

        $course  = $DB->get_record('course', array('id' => $userojt->course), '*', MUST_EXIST);
        $cm      = get_coursemodule_from_instance('ojt', $userojt->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $out = '';

        $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic', 'id' => "ojt-topic-{$topic->id}"));
        $completionicon = $this->get_completion_icon($topic);
        $completionicon = html_writer::tag('span', $completionicon,
            array('class' => 'ojt-topic-status'));
        $optionalstr    = $topic->completionreq == completion::REQ_OPTIONAL ?
            html_writer::tag('em', ' (' . get_string('optional', 'ojt') . ')') : '';
        $out            .= html_writer::tag('div', format_string($topic->name) . $optionalstr . $completionicon,
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
            $row         = array();
            $optionalstr = $item->completionreq == completion::REQ_OPTIONAL ?
                html_writer::tag('em', ' (' . get_string('optional', 'ojt') . ')') : '';
            $row[]       = format_string($item->name) . $optionalstr;

            if ($evaluate)
            {
                $completionicon =
                    $item->completion->status == completion::STATUS_COMPLETE ? 'completion-manual-y' : 'completion-manual-n';
                $cellcontent    =
                    html_writer::start_tag('div', array('class' => 'ojt-eval-actions', 'ojt-item-id' => $item->id));
                $cellcontent    .= $this->output->flex_icon($completionicon,
                    ['classes' => 'ojt-completion-toggle']);
                $cellcontent    .= html_writer::tag('textarea', $item->completion->comment,
                    array('name'        => 'comment-' . $item->id,
                          'rows'        => 3,
                          'class'       => 'ojt-completion-comment',
                          'ojt-item-id' => $item->id));
                $cellcontent    .= html_writer::tag('div', format_text($item->completion->comment, FORMAT_PLAIN),
                    array('class' => 'ojt-completion-comment-print', 'ojt-item-id' => $item->id));
                $cellcontent    .= html_writer::end_tag('div');
            }
            else
            {
                // Show static stuff.
                $cellcontent = '';
                if ($item->completion->status == completion::STATUS_COMPLETE)
                {
                    $cellcontent .= $this->output->flex_icon('check-success',
                        ['alt' => get_string('completionstatus' . completion::STATUS_COMPLETE, 'ojt')]);
                }
                else
                {
                    $cellcontent .= $this->output->flex_icon('times-danger',
                        ['alt' => get_string('completionstatus' . completion::STATUS_INCOMPLETE, 'ojt')]);
                }

                $cellcontent .= format_text($item->completion->comment, FORMAT_PLAIN);
            }
            $userobj     = new stdClass();
            $userobj     = username_load_fields_from_object($userobj, $item, $prefix = 'modifier');
            $cellcontent .= html_writer::tag('div', ojt::get_modifiedstr($item->completion->timemodified, $userobj),
                array('class' => 'mod-ojt-modifiedstr', 'ojt-item-id' => $item->id));

            if ($item->allowfileuploads || $item->allowselffileuploads)
            {
                $cellcontent .= html_writer::tag('div',
                    $this->list_topic_item_files($context->id, $userojt->userid, $item->id),
                    array('class' => 'mod-ojt-topicitem-files'));

                if (($evaluate && $item->allowfileuploads) ||
                    ($userojt->userid == $USER->id && $item->allowselffileuploads))
                {
                    $itemfilesurl = new moodle_url('/mod/ojt/uploadfile.php',
                        array('userid' => $userojt->userid, 'tiid' => $item->id));
                    $cellcontent  .= $this->output->single_button($itemfilesurl, get_string('updatefiles', 'ojt'),
                        'get');
                }
            }

            $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-completion'));

            if ($userojt->itemwitness)
            {
                $cellcontent = '';
                if ($itemwitness)
                {
                    $witnessicon = $item->witness->witnessedby ? 'completion-manual-y' : 'completion-manual-n';
                    $cellcontent .= html_writer:: start_tag('span',
                        array('class' => 'ojt-witness-item', 'ojt-item-id' => $item->id));
                    $cellcontent .= $this->output->flex_icon($witnessicon, ['classes' => 'ojt-witness-toggle']);
                    $cellcontent .= html_writer::end_tag('div');

                }
                else
                {
                    // Show static witness info
                    if (!empty($item->witness->witnessedby))
                    {
                        $cellcontent .= $this->output->flex_icon('check-success',
                            ['alt' => get_string('witnessed', 'ojt')]);
                    }
                    else
                    {
                        $cellcontent .= $this->output->flex_icon('times-danger',
                            ['alt' => get_string('notwitnessed', 'ojt')]);
                    }
                }

                $userobj     = new stdClass();
                $userobj     = username_load_fields_from_object($userobj, $item, $prefix = 'itemwitness');
                $cellcontent .= html_writer::tag('div', ojt::get_modifiedstr($item->witness->timewitnessed, $userobj),
                    array('class' => 'mod-ojt-witnessedstr', 'ojt-item-id' => $item->id));

                $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-item-witness'));
            }

            $table->data[] = $row;
        }

        $out .= html_writer::table($table);

        // Topic signoff
        if ($userojt->managersignoff)
        {
            $out .= html_writer::start_tag('div',
                array('class' => 'mod-ojt-topic-signoff', 'ojt-topic-id' => $topic->id));
            $out .= get_string('managersignoff', 'ojt');
            if ($signoff)
            {
                $out .= $this->output->flex_icon($topic->signoff->signedoff ? 'completion-manual-y' : 'completion-manual-n',
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
            $out     .= html_writer::tag('div', ojt::get_modifiedstr($topic->signoff->timemodified, $userobj),
                array('class' => 'mod-ojt-topic-modifiedstr'));
            $out     .= html_writer::end_tag('div');
        }

        // Topic comments
        if ($topic->allowcomments)
        {
            $out .= $this->output->heading(get_string('topiccomments', 'ojt'), 4);
            require_once($CFG->dirroot . '/comment/lib.php');
            comment::init();
            $options            = new stdClass();
            $options->area      = 'ojt_topic_item_' . $topic->id;
            $options->context   = $context;
            $options->itemid    = $userojt->userid;
            $options->showcount = true;
            $options->component = 'ojt';
            $options->autostart = true;
            $options->notoggle  = true;
            $comment            = new comment($options);
            $out                .= $comment->output(true);
        }


        $out .= html_writer::end_tag('div');  // mod-ojt-topic

        return $out;
    }

    protected function list_topic_item_files($contextid, $userid, $topicitemid)
    {
        $out = array();

        $fs    = get_file_storage();
        $files = $fs->get_area_files($contextid, 'mod_ojt', 'topicitemfiles' . $topicitemid, $userid,
            'itemid, filepath, filename', false);

        foreach ($files as $file)
        {
            $filename = $file->get_filename();
            $url      =
                moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $out[]    = html_writer::link($url, $filename);
        }
        $br = html_writer::empty_tag('br');

        return implode($br, $out);
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
        $completionicon = html_writer::tag('span', $completionicon,  ['class' => 'ojt-topic-status']);

        return $completionicon;
    }

    public function display_userview_header($user) {
        global $USER;

        $header = '';
        if ($USER->id != $user->id) {
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
     * returns the html for a user item with delete button.
     *
     * @param email_assignment $assignment The associated email assignment.
     */
    public function external_user_record($assignment)
    {
        $out = '';

        $completestr  = get_string('alreadyreplied', 'totara_feedback360');

        // No JS deletion url
        //$deleteparams = array('assignid' => $assignment->id, 'email' => $assignment->email);
        $deleteurl    = '#';//new moodle_url('/mod/ojt/ext_request/delete.php', $deleteparams);

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
            $out       .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $removestr), null,
                array('class' => 'external_record_del', 'id' => $assignment->email));
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }
}

