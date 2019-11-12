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

/**
 * Upload a file to a observation topic item
 */

use mod_observation\models\observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once(dirname(__FILE__) . '/forms.php');
require_once('lib.php');

require_login();

$userid      = required_param('userid', PARAM_INT);
$topicitemid = required_param('tiid', PARAM_INT);

$sql = "SELECT b.*, ti.allowfileuploads, ti.allowselffileuploads
    FROM {observation_topic_item} ti
    JOIN {observation_topic} t ON ti.topicid = t.id
    JOIN {observation} b ON t.observationid = b.id
    WHERE ti.id = ?";
if (!$observation = $DB->get_record_sql($sql, array($topicitemid)))
{
    print_error('observation not found');
}
$course     = $DB->get_record('course', array('id' => $observation->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('observation', $observation->id, $course->id, false, MUST_EXIST);
$modcontext = context_module::instance($cm->id);

// Check access
if (!($observation->allowfileuploads || $observation->allowselffileuploads))
{
    print_error('files cannot be uploaded for this topic item');
}
// Only users with evaluate perm or evaluateself that's also the observation user should be able to upload a file (if config allows)
// Also allow observation owners to upload files, if configured
$canevaluate   = observation::can_evaluate($userid, $modcontext);
$canselfupload = $observation->allowselffileuploads && $userid == $USER->id;
if (!($canevaluate || $canselfupload))
{
    print_error('access denied');
}

require_login($course, true, $cm);

if ($canevaluate)
{
    $returnurl = new moodle_url('/mod/observation/evaluate.php', array('userid' => $userid, 'bid' => $observation->id));
}
else
{
    $returnurl = new moodle_url('/mod/observation/view.php', array('id' => $cm->id));
}

$PAGE->set_pagelayout('noblocks');
$PAGE->set_url('/mod/observation/uploadfile.php', array('tiid' => $topicitemid, 'userid' => $userid));

if (!$user = $DB->get_record('user', array('id' => $userid)))
{
    print_error('user not found');
}

$fileoptions             = $FILEPICKER_OPTIONS;
$fileoptions['maxfiles'] = 10;

$item              = new stdClass();
$item->topicitemid = $topicitemid;
$item->userid      = $userid;
$item              = file_prepare_standard_filemanager($item, 'topicitemfiles',
    $fileoptions, $modcontext, 'mod_observation', "topicitemfiles{$topicitemid}", $userid);

$mform = new observation_topicitem_files_form(
    null,
    array(
        'topicitemid' => $topicitemid,
        'userid'      => $userid,
        'fileoptions' => $fileoptions
    )
);
$mform->set_data($item);

if ($data = $mform->get_data())
{
    // process files, update the data record
    $data = file_postupdate_standard_filemanager($data, 'topicitemfiles',
        $fileoptions, $modcontext, 'mod_observation', "topicitemfiles{$topicitemid}", $userid);

    totara_set_notification(get_string('filesupdated', 'observation'), $returnurl, array('class' => 'notifysuccess'));
}
else if ($mform->is_cancelled())
{
    redirect($returnurl);
}

//TODO BREADCRUMBS FOR EXTERNAL  USER
//TODO REDIRECT AFTER UPLOAD
$strheading = get_string('updatefiles', 'observation');
$PAGE->navbar->add(get_string('evaluate', 'observation'));
$PAGE->navbar->add(fullname($user),
    new moodle_url('/mod/observation/evaluate.php', array('userid' => $userid, 'bid' => $observation->id)));
$PAGE->navbar->add(get_string('updatefiles', 'observation'));
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

echo $OUTPUT->header();

echo $OUTPUT->heading($strheading, 1);

$mform->display();

echo $OUTPUT->footer();
