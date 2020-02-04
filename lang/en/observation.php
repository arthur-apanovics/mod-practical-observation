<?php /** @noinspection SpellCheckingInspection */
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


/**
 * English strings for observation
 */

defined('MOODLE_INTERNAL') || die();

// MOODLE & PLUGIN SPECIFIC
$string['modulename'] = 'Observation';
$string['modulenameplural'] = 'Observations';
$string['modulename_help'] = 'The Observation module allows for student evaluation based on pre-configured Observation tasks and criterias.';
$string['pluginadministration'] = 'Observation administration';
$string['pluginname'] = 'Observation';
$string['observation'] = 'Observation';
$string['accessdenied'] = 'Access denied';

// SETTINGS FORM
$string['observation:addinstance'] = 'Add instance';
$string['observation:evaluate'] = 'Evaluate';
$string['observation:evaluateself'] = 'Evaluate self';
$string['observation:manage'] = 'Manage';
$string['observation:view'] = 'View';

$string['name'] = 'Observation name';
$string['name_help'] = 'The title of your Observation activity.';
$string['intro_defaults'] = 'Instructions';
$string['def_i_task_observer'] = 'Default observer requirements - Learner (optional)';
$string['def_i_task_observer_help'] = 'If set, this will be the default content that will appear on "Assign observer" page for a learner.<br>Note: the default instruction can still be fully edited for each task.';
$string['def_i_task_assessor'] = 'Default observer requirements - Observer (optional)';
$string['def_i_task_assessor_help'] = 'If set, this will be the default criteria that an observer has to confirm they meet before they\'re able to observe a task. <br>Note: the default instruction can still be fully edited for each task.';
$string['def_i_ass_obs_learner'] = 'Default task instructions - Observer (optional)';
$string['def_i_ass_obs_learner_help'] = 'If set, this will be the default Observer instruction for all new Tasks.<br>Use this to include content that applies to all Tasks or if instructions are the same for all Tasks.<br>Note: the default instruction can still be fully edited for each task.';
$string['def_i_ass_obs_observer'] = 'Default task instructions - Assessor (optional)';
$string['def_i_ass_obs_observer_help'] = 'If set, this will be the default Assessor instruction for all new Tasks.<br>Use this to include content that applies to all Tasks or if instructions are the same for all Tasks.<br>Note: the default instruction can still be fully edited for each task.';
$string['completion_tasks'] = 'All Tasks are observed and complete';
$string['timeopen'] = 'Date open';
$string['timeclose'] = 'Date closed';

$string['observation'] = 'Observation';
// $string['observationfieldset'] = 'Custom example fieldset';
// $string['competencies'] = 'Competencies';
// $string['competencies_help'] = 'Here you can select which of the assigned course competencies should be marked as proficient upon completion of this task.
//
// Multiple competencies can be selected by holding down \<CTRL\> and and selecting the criterias.';

// STATUS STRINGS
$string['status:not_complete'] = 'Not complete';
$string['status:complete'] = 'Complete';
$string['status:not_started'] = 'Not started';
$string['status:learner_in_progress'] = 'In progress';
$string['status:observation_pending'] = 'Observation pending';
$string['status:observation_in_progress'] = 'Observation in progress';
$string['status:observation_incomplete'] = 'Observation not complete';
$string['status:assessment_pending'] = 'Assessment pending';
$string['status:assessment_in_progress'] = 'Assessment in progress';
$string['status:assessment_incomplete'] = 'Assessment not complete';

// MANAGE SPECIFIC
$string['manage:no_tasks'] = 'This activity does not have any tasks, use button below to add new tasks.';
$string['manage:edit_tasks'] = 'Edit tasks';

// $string['confirmtaskdelete'] = 'Are you sure you want to delete this task?';
// $string['confirmcriteriadelete'] = 'Are you sure you want to delete this criteria?';
// $string['deletecriteria'] = 'Delete criteria';
// $string['deletetask'] = 'Delete task';
// $string['taskdeleted'] = 'Task deleted';
// $string['edittask'] = 'Edit task';
// $string['error:observationnotfound'] = 'Observation not found';
$string['assessstudents'] = 'Assess students';
$string['criteriadeleted'] = 'Criteria deleted';
// $string['manage'] = 'Manage';

$string['no_tasks'] = 'There are currently no tasks in this activity.';


// $string['printthisobservation'] = 'Print this Observation';
$string['report'] = 'Report';

// $string['submissiondate'] = 'Observation submitted on {$a}';
// $string['nosubmissiondate'] = 'Observation has not been submitted yet';

// EMAIL
// $string['requests_disabled:title'] = 'You cannot assign new emails at the moment';
// $string['requestobservation'] = 'Request observation';
// $string['userxfeedback'] = '{$a}\'s Feedback';
