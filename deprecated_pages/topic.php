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

use mod_observation\completion;
use mod_observation\topic;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/forms.php');

$observationid   = required_param('bid', PARAM_INT); // Observation instance id.
$topicid = optional_param('id', 0, PARAM_INT);  // Topic id.
$delete  = optional_param('delete', 0, PARAM_BOOL);

$observation    = $DB->get_record('observation', array('id' => $observationid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
$cm     = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/observation:manage', context_module::instance($cm->id));

$PAGE->set_url('/mod/observation/topic.php', array('bid' => $observationid, 'id' => $topicid));

// Handle actions
if ($delete)
{
    $confirm = optional_param('confirm', 0, PARAM_BOOL);
    if (!$confirm)
    {
        echo $OUTPUT->header();
        $confirmurl = $PAGE->url;
        $confirmurl->params(array('delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()));
        echo $OUTPUT->confirm(get_string('confirmtopicdelete', 'observation'), $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }

    topic::delete_topic($topicid);
    $redirecturl = new moodle_url('/mod/observation/manage.php', array('cmid' => $cm->id));
    totara_set_notification(get_string('topicdeleted', 'observation'), $redirecturl, array('class' => 'notifysuccess'));
}

$form = new observation_topic_form(null, array('courseid' => $course->id, 'observationid' => $observationid));
if ($data = $form->get_data())
{
    // Save topic
    $topic                      = new topic();
    $topic->observationid               = $data->bid;
    $topic->name                = $data->name;
    $topic->intro               = $data->intro['text'];
    $topic->introformat         = $data->intro['format'];
    $topic->observerintro       = $data->observerintro['text'];
    $topic->observerintroformat = $data->observerintro['format'];
    $topic->completionreq       = $data->completionreq;
    $topic->competencies        = !empty($data->competencies) ? implode(',', $data->competencies) : '';
    $topic->allowcomments       = $data->allowcomments;

    if (empty($data->id))
    {
        // Add
        $topic->create();
    }
    else
    {
        // Update
        $topic->id = $data->id;

        $transaction = $DB->start_delegated_transaction();
        $topic->update();

        if (!empty($topic->competencies))
        {
            // We need to add 'proficient' competency records for any historical user topic completions
            $topiccompletions = $DB->get_records_select('observation_completion', 'topicid = ? AND type = ? AND status IN(?,?)',
                array(
                    $data->id,
                    completion::COMP_TYPE_TOPIC,
                    completion::STATUS_REQUIREDCOMPLETE,
                    completion::STATUS_COMPLETE));

            foreach ($topiccompletions as $tc)
            {
                topic::update_topic_competency_proficiency($tc->userid, $tc->topicid, $tc->status);
            }
        }

        $transaction->allow_commit();
    }

    redirect(new moodle_url('/mod/observation/manage.php', array('cmid' => $cm->id)));
}

// Print the page header.
$actionstr = empty($topicid) ? get_string('addtopic', 'observation') : get_string('edittopic', 'observation');
$PAGE->set_title(format_string($observation->name));
$PAGE->set_heading(format_string($observation->name) . ' - ' . $actionstr);

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading($PAGE->heading);

if (!empty($topicid))
{
    $topic               = new topic($topicid);
    $topic->competencies = explode(',', $topic->competencies);

    $topic->intro = [
        'text'   => $topic->intro,
        'format' => $topic->introformat
    ];

    $topic->observerintro = [
        'text'   => $topic->observerintro,
        'format' => $topic->observerintroformat
    ];

    $form->set_data((array)$topic);
}

// Display
$form->display();

// Finish the page.
echo $OUTPUT->footer();
