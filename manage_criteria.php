<?php
/*
 * Copyright (C) 2020 onwards Like-Minded Learning
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
 * @author  Arthur Apanovics <arthur.a@likeminded.co.nz>
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_observation\criteria;
use mod_observation\criteria_base;
use mod_observation\observation;
use mod_observation\observation_base;

global $DB;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('forms.php');

$cmid = required_param('id', PARAM_INT);
$taskid = required_param('taskid', PARAM_INT); // not really required, only when adding new criteria...
$criteriaid = optional_param('criteriaid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);

list($course, $cm) = get_course_and_cm_from_cmid($cmid, OBSERVATION);
$context = context_module::instance($cm->id);

$observation = new observation_base($cm->instance); // we don't need full instance here
$manage_url = new moodle_url(OBSERVATION_MODULE_PATH . 'manage.php', array('id' => $cm->id));

require_login($course, false, $cm);
require_capability(observation::CAP_MANAGE, $context);

// Print the page header.
$PAGE->set_url(
    OBSERVATION_MODULE_PATH . 'manage_criteria.php',
    array('id' => $cm->id, 'taskid' => $taskid, 'criteriaid' => $criteriaid));

if ($delete)
{
    $criteria = new criteria_base($criteriaid);

    $confirm = optional_param('confirm', 0, PARAM_BOOL);
    if (!$confirm)
    {
        /* @var $renderer mod_observation_renderer */
        $renderer = ($PAGE->get_renderer(OBSERVATION));
        $renderer->echo_confirmation_page_and_die(
            get_string('confirm_delete_criteria', 'observation', $criteria->get_formatted_name()),
            ['delete' => 1]);
    }
    else
    {
        $criteria->delete();
        totara_set_notification(
            get_string('deleted_criteria', \OBSERVATION, $criteria->get_formatted_name()),
            $manage_url,
            ['class' => 'notifysuccess']);
    }
}

$form = new observation_criteria_form(null, ['cmid' => $cm->id, 'taskid' => $taskid, 'criteriaid' => $criteriaid]);
if ($form->is_cancelled())
{
    redirect($manage_url);
}
if ($data = $form->get_data())
{
    // data is being posted
    $criteria = new criteria_base();
    $criteria->set(criteria::COL_TASKID, $taskid);
    $criteria->set(criteria::COL_NAME, $data->{criteria::COL_NAME});
    $criteria->set(criteria::COL_DESCRIPTION, $data->{criteria::COL_DESCRIPTION}['text']);
    $criteria->set(criteria::COL_DESCRIPTION_FORMAT, $data->{criteria::COL_DESCRIPTION}['format']);
    $criteria->set(criteria::COL_FEEDBACK_REQUIRED, $data->{criteria::COL_FEEDBACK_REQUIRED});

    if (empty($data->criteriaid))
    {
        // create
        $criteria->set($criteria::COL_SEQUENCE, $criteria->get_next_sequence_number_in_task());

        $criteria->create();
    }
    else
    {
        // update

        // make sure there's no mischief
        if ($observation->has_submissions() && !$observation->has_submissions(true))
        {
            // check feedback requirement hasn't changed as it is not allowed to edit this setting after submissions made
            $current_value = $DB->get_field(
                criteria::TABLE, criteria::COL_FEEDBACK_REQUIRED, ['id' => $data->criteriaid]);
            if ($current_value != $criteria->get(criteria::COL_FEEDBACK_REQUIRED))
            {
                throw new coding_exception(
                    sprintf('Not allowed to change "%s" after submissions made', criteria::COL_FEEDBACK_REQUIRED));
            }
        }

        $criteria->set(criteria::COL_ID, $data->criteriaid);
        $criteria->set($criteria::COL_SEQUENCE, $criteria->get_current_sequence_number());

        $criteria->update();
    }

    redirect($manage_url);
}

if (!empty($criteriaid))
{
    $criteria = new criteria_base($criteriaid);
    $form->set_data($criteria->get_moodle_form_data());
}

$observation_title = $observation->get_formatted_name();
$actionstr = empty($criteriaid)
    ? get_string('add_criteria', \OBSERVATION)
    : get_string('edit_criteria', \OBSERVATION);
$PAGE->set_title($observation_title);
$PAGE->set_heading(sprintf('%s - %s', $observation_title, $actionstr));

$PAGE->add_body_class('observation-manage-criteria');

$PAGE->navbar->add(get_string('breadcrumb:manage', \OBSERVATION),
    new moodle_url(OBSERVATION_MODULE_PATH . 'manage.php', array('id' => $cmid)));

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->heading);

// Display
$form->display();

// Finish the page.
echo $OUTPUT->footer();
