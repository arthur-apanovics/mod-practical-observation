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

use mod_observation\models\topic_item;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/forms.php');

$observationid   = required_param('bid', PARAM_INT); // Observation instance id.
$topicid = required_param('tid', PARAM_INT);  // Topic id.
$itemid  = optional_param('id', 0, PARAM_INT);  // Topic item id.
$delete  = optional_param('delete', 0, PARAM_BOOL);

$observation    = $DB->get_record('observation', array('id' => $observationid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
$cm     = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/observation:manage', $context);

$PAGE->set_url('/mod/observation/topicitem.php', array('bid' => $observationid, 'tid' => $topicid, 'id' => $itemid));

// Handle actions
if ($delete)
{
    $confirm = optional_param('confirm', 0, PARAM_BOOL);
    if (!$confirm)
    {
        echo $OUTPUT->header();
        $confirmurl = $PAGE->url;
        $confirmurl->params(array('delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()));
        echo $OUTPUT->confirm(get_string('confirmitemdelete', 'observation'), $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }

    require_sesskey();

    topic_item::delete_topic_item($itemid, $context);
    $redirecturl = new moodle_url('/mod/observation/manage.php', array('cmid' => $cm->id));
    totara_set_notification(get_string('itemdeleted', 'observation'), $redirecturl, array('class' => 'notifysuccess'));
}

$form = new observation_topic_item_form(null, array('observationid' => $observationid, 'topicid' => $topicid));
if ($data = $form->get_data())
{
    // Save topic
    $topic_item                       = new topic_item();
    $topic_item->topicid              = $data->tid;
    $topic_item->name                 = $data->name;
    $topic_item->completionreq        = $data->completionreq;
    $topic_item->allowfileuploads     = $data->allowfileuploads;
    $topic_item->allowselffileuploads = $data->allowselffileuploads;

    if (empty($data->id))
    {
        // Add
        $topic_item->create();
    }
    else
    {
        // Update
        $topic_item->id = $data->id;
        $topic_item->update();
    }

    redirect(new moodle_url('/mod/observation/manage.php', array('cmid' => $cm->id)));
}

// Print the page header.
$actionstr = empty($itemid)
    ? get_string('additem', 'observation')
    : get_string('edititem', 'observation');
$PAGE->set_title(format_string($observation->name));
$PAGE->set_heading(format_string($observation->name) . ' - ' . $actionstr);

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading($PAGE->heading);

if (!empty($itemid))
{
    $topic_item = new topic_item($itemid);
    $form->set_data((array)$topic_item);
}

// Display
$form->display();

// Finish the page.
echo $OUTPUT->footer();
