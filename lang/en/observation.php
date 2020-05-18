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
$string['calendarstart'] = '{$a} (Observation opens)';
$string['calendarend'] = '{$a} (Observation closes)';
$string['timing:notopen'] = 'This observation activity will not be available until {$a}';
$string['timing:closed'] = 'This observation activity closed on {$a}';
$string['timing:available_until'] = 'This observation activity is available until {$a}';

// GENERAL
$string['back_to_activity'] =  'Back to activity';
$string['observation'] = 'Observation';
$string['no_learners_in_group'] = 'There are no learners in this group';

// CAPABILITIES
$string['observation:addinstance'] = 'Add instance';
$string['observation:view'] = 'View';
$string['observation:submit'] = 'Submit';
$string['observation:viewsubmissions'] = 'View submissions';
$string['observation:assess'] = 'Assess';
$string['observation:manage'] = 'Manage';

// SETTINGS FORM
$string['observation:addinstance'] = 'Add instance';
$string['observation:evaluate'] = 'Evaluate';
$string['observation:evaluateself'] = 'Evaluate self';
$string['observation:manage'] = 'Manage';
$string['observation:view'] = 'View';

$def_task_template = 'Default task instructions - %s (optional)';
$def_task_help_template = 'If set, this will be the default %s instruction for all new Tasks.<br>Use this to include content that applies to all Tasks or if instructions are the same for all Tasks.<br>Note: the default instruction can still be fully edited for each task.';

$string['name'] = 'Observation name';
$string['name_help'] = 'The title of your Observation activity.';
$string['intro_defaults'] = 'Instructions';
$string['def_i_task_learner'] = sprintf($def_task_template, 'Learner');
$string['def_i_task_learner_help'] = sprintf($def_task_help_template, 'Learner');
$string['def_i_task_observer'] = sprintf($def_task_template, 'Observer');
$string['def_i_task_observer_help'] = sprintf($def_task_help_template, 'Observer');
$string['def_i_task_assessor'] = sprintf($def_task_template, 'Assessor');
$string['def_i_task_assessor_help'] = sprintf($def_task_help_template, 'Assessor');
$string['def_i_ass_obs_learner'] = 'Default observer requirements - Learner (optional)';
$string['def_i_ass_obs_learner_help'] = 'If set, this will be the default content that will appear on "Assign observer" page for a learner.<br>Note: the default instruction can still be fully edited for each task.';
$string['def_i_ass_obs_observer'] = 'Default observer requirements - Observer (optional)';
$string['def_i_ass_obs_observer_help'] = 'If set, this will be the default criteria that an observer has to confirm they meet before they\'re able to observe a task. <br>Note: the default instruction can still be fully edited for each task.';
$string['completion_tasks'] = 'All Tasks are observed and complete';
$string['timeopen'] = 'Date open';
$string['timeclose'] = 'Date closed';

// TASK FORM
$task_template = 'Task instructions - <b>%s</b>';
$task_help_template = '<b>%s</b> instructions for this Task';

$string['task_name'] ='Task name';
$string['intro_learner'] = sprintf($task_template, 'Learner');
$string['intro_learner_help'] = sprintf($task_help_template, 'Learner');
$string['intro_observer'] = sprintf($task_template, 'Observer');
$string['intro_observer_help'] = sprintf($task_help_template, 'Observer');
$string['intro_assessor'] = sprintf($task_template, 'Assessor');
$string['intro_assessor_help'] = sprintf($task_help_template, 'Assessor');
$string['int_assign_obs_learner'] ='Observer requirements - <b>Learner</b>';
$string['int_assign_obs_learner_help'] = 'Content that will appear on "Assign observer" page for a learner.';
$string['int_assign_obs_observer'] ='Observer requirements - <b>Observer</b>';
$string['int_assign_obs_observer_help'] = 'Criteria that an observer has to confirm they meet before they\'re able to observe a task.';

// CRITERIA FORM
$string['criteria_name'] ='Criteria name';
$string['description'] ='Description';
$string['description_help'] ='Description of this criteria that will appear on the Task view for the Learner, Observer and Assessor';
$string['feedback_required'] ='Feedback required';
$string['feedback_required_help'] ='If checked, observers will have to provide textual feedback for this criteria';

// LEARNER SUBMISSION STATUS STRINGS
$string['status:not_started'] = 'Not started';
$string['status:learner_in_progress'] = 'In progress';
$string['status:learner_pending'] = 'Learner pending';
$string['status:observation_pending'] = 'Observation pending';
$string['status:observation_in_progress'] = 'Observation in progress';
$string['status:observation_incomplete'] = 'Observation not complete';
$string['status:assessment_pending'] = 'Assessment pending';
$string['status:assessment_in_progress'] = 'Assessment in progress';
$string['status:assessment_incomplete'] = 'Assessment not complete';
$string['status:complete'] = 'Complete';

// MANAGE SPECIFIC
$string['manage:no_tasks'] = 'This activity does not have any tasks, use button below to add new tasks.';
$string['manage:missing_criteria'] = 'One or more tasks in this activity are missing completion criteria. Please correct this by using the "Edit tasks" section.';
$string['manage:empty'] = 'No tasks yet';
$string['manage:edit_tasks'] = 'Edit tasks';
$string['manage:editing_danger'] = 'Warning:<br>Some learners have viewed this activity but not have attempted to make submissions yet. Once a submission has been made, editing will be disabled.<br>Please proceed with caution.';
$string['manage:editing_disabled'] = 'Prohibited after submissions have been made';

// TASK SPECIFIC
$string['add_task'] = 'Add task';
$string['edit_task'] = 'Edit task';
$string['delete_task'] = 'Delete task';
$string['confirm_delete_task'] = 'Are you sure you want to delete task "{$a}"?<br>Warning: this operation is not reversable!';
$string['deleted_task'] = 'Task "{$a}" deleted';
$string['no_criteria'] = 'Task has no criteria';

$string['preview_task'] = 'Preview';
$string['review_task'] = 'Review';
$string['start_task'] = 'Start';
$string['assess_task'] = 'Assess';
$string['observe_task'] = 'Observe';
$string['view_task'] = 'View';

$string['request_observation'] = 'Request observation';
$string['attempt_for'] = 'Attempt #{$a}';
$string['attempt_for_preview'] = 'Example attempt';
$string['feedback_for_no_text'] = '<strong>{$a->fullname}</strong> has marked attempt #{$a->attempt_number} as <span class="outcome-inline">{$a->outcome}</span> {$a->timesubmitted}';
$string['feedback_for'] = 'Feedback for attempt #{$a}';
$string['preview_editor_text'] = 'Example learner attempt text';

// CRITERIA SPECIFIC
$string['add_criteria'] = 'Add criteria';
$string['edit_criteria'] = 'Edit criteria';
$string['delete_criteria'] = 'Delete criteria';
$string['confirm_delete_criteria'] = 'Are you sure you want to delete criteria "{$a}"?<br>Warning: this operation is not reversable!';
$string['deleted_criteria'] = 'Criteria "{$a}" deleted';

$string['assessstudents'] = 'Assess students';
$string['criteriadeleted'] = 'Criteria deleted';

$string['no_tasks'] = 'There are currently no tasks in this activity.';

$string['report'] = 'Report';

// ASSIGN OBSERVER
$string['assign_observer:page_title'] = 'Assign observer - {$a}';
$string['assign_observer:table_title'] = 'Assign from history';
$string['assign_observer:table_title_help'] = 'You can assign any observer you\'ve used in this module or a new observer';
$string['assign_observer:header'] = 'Assign observer';
$string['assign_observer:phone_validation_message'] = 'Please enter a valid NZ phone number';
$string['assign_observer:email_validation_message'] = 'Please enter a valid email address';
$string['assign_observer:confirm_change'] = 'Current observer for this task is <strong>{$a->current}</strong>.<br>Are you sure you want to assign <strong>{$a->new}</strong> as the observer for task "{$a->task}"?';
$string['assign_observer:input_prompt'] = 'Briefly explain this change';

$string['assign_observer:assigned_observer'] = 'Assigned observer';
$string['assign_observer:change_observer'] = 'change observer';

// OBSERVER
$string['fullname'] = 'Full name';
$string['phone'] = 'Phone';
$string['email'] = 'Email';
$string['position_title'] = 'Position title';
$string['message'] = 'Message';
$string['message_placeholder'] = 'Optional message to appear in observer email';
// observation page
$string['accept'] = 'Accept';
$string['decline'] = 'Decline';
$string['confirm_decline'] = 'Are you sure you want to decline this observation?';
$string['observer_page_title'] = 'Observation for {$a}\'s attempt';

$string['send'] = 'Send';
$string['edit_lowercase'] = 'edit';
$string['observer_requirements'] = 'External observer requirements';
$string['observer_requirements_acknowledge'] = 'I acknowledge that I meet the required criteria to observe this learner';

$string['outcome:choose'] = 'Choose...';
$string['outcome:complete'] = 'Complete';
$string['outcome:not_complete'] = 'Not complete';

// ASSESSOR
$string['assess:observer_meets_criteria'] = 'Observer meets required criteria';
$string['assess:last_assigned'] = 'last assigned';
$string['assess:release_grade'] = 'Release';
$string['assess:release_grade_title'] = 'All tasks have to be assessed before releasing';

// NOTIFICATIONS
$string['notification:observation_request_sent'] = 'Observation request sent to {$a}, please be patient while your observer reviews your submission';
$string['notification:observer_assigned_no_change'] = '<strong>{$a->email}</strong> is already the assigned observer for task <i>{$a->task}</i>';
$string['notification:observer_assigned_same'] = 'Observation request for <i>{$a->task}</i> sent to <strong>{$a->email}</strong>';
$string['notification:observer_assigned_new'] = 'Observer successfully changed and observation request sent to <strong>{$a->email}</strong> for <i>{$a->task}</i>';
$string['notification:observer_declined_observation'] = '{$a->observer} has declined your request to observe "{$a->task_name}".<br>Please assign a different observer by clicking {$a->assign_url_with_text}';
$string['notification:observation_pending_or_in_progress'] = 'Observation for this task is not yet complete';
$string['notification:assessment_released'] = 'Assessment for <i>{$a}</i>\'s submission succsesfully released';
$string['notification:submission_pending_or_in_progress'] = 'Learner has not yet submitted an attempt for this task';
$string['notification:activity_wait_for_observers'] = 'You have requested observations for all tasks, please be patient while observers review your submissions';
$string['notification:activity_wait_for_mixed'] = 'Please pe patient while your submissions are observed and assessed';
$string['notification:activity_complete'] = 'Congratulations, you have completed this activity!';
$string['notification:activity_observation_not_complete'] = 'Observation was unsuccessful, please submit new attempt';
$string['notification:activity_assessment_not_complete'] = 'Assessment was unsuccessful, please submit new attempt';
$string['notification:previewing_activity'] = 'You are in preview mode - you can view each task in this activity but you will not be able to make submissions.';

// ERRORS
$string['error:invalid_token'] = 'The link you are trying to access is incorrect. Please contact Akatoi support if you think this is a mistake.';
$string['error:not_active_observer'] = 'This link is no longer valid. If you have received a newer observation email, please use the link in that email instead.';
// $string['error:observation_declined'] = 'This link is no longer valid because you have declined to observe {$a}\'s submission. Please get in touch with Careerfoce for further support.';
$string['error:observation_complete'] = 'This observation has already been completed.';

// EMAILS
$string['email:observer_assigned_subject'] = 'Observation request from {$a->{learner_fullname}}';
$string['email:observer_assigned_body'] = 'Hello {$a->observer_fullname}.
{$a->learner_fullname} has requested an observation for task "{$a->task_name}" in "{$a->activity_name}.
Please follow this link to proceed or decline observation:
{$a->observe_url}"';
$string['email:observer_assigned_body_with_user_message'] = 'Hello {$a->observer_fullname}.
{$a->learner_fullname} has requested an observation for task "{$a->task_name}" in "{$a->activity_name}.

Message from {$a->learner_fullname}:
<q>{$a->learner_message}</q>

Please follow this link to proceed or decline observation:
{$a->observe_url}"';

$string['email:observer_observation_declined_subject'] = 'observer_observation_declined_subject';
$string['email:observer_observation_declined_body'] = 'observer_observation_declined_body';

$string['email:learner_observation_declined_subject'] = 'learner_observation_declined_subject';
$string['email:learner_observation_declined_body'] = 'learner_observation_declined_body';

$string['email:observer_observation_complete_subject'] = 'observer_observation_complete_subject';
$string['email:observer_observation_complete_body'] = 'observer_observation_complete_body';

$string['email:learner_observation_complete_subject'] = 'learner_observation_complete_subject';
$string['email:learner_observation_complete_body'] = 'learner_observation_complete_body';

$string['email:learner_assessment_released_subject'] = 'learner_assessment_released_subject';
$string['email:learner_assessment_released_body'] = 'learner_assessment_released_body';

