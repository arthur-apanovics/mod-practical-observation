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

namespace mod_observation;

use cm_info;
use coding_exception;
use context_module;
use core_user;
use dml_exception;
use dml_missing_record_exception;
use mod_observation\event\activity_started;
use mod_observation\interfaces\templateable;
use moodle_url;
use observation_task_form;

/**
 * An instance of the observation activity with all related data
 *
 * @package mod_observation
 */
class observation extends observation_base implements templateable
{
    /**
     * @var submission[]
     */
    private $submisisons;
    /**
     * Tasks in this observation, sorted by sequence
     * @var task[]
     */
    private $tasks;

    /**
     * True if observation is filtered by userid or taskid
     *
     * @var bool
     */
    private $is_filtered = false;

    /**
     * observation_instance constructor.
     *
     * @param object|cm_info $cm_or_cm_info
     * @param int            $userid
     * @param int            $taskid
     * @throws dml_missing_record_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws \ReflectionException
     */
    public function __construct($cm_or_cm_info, int $userid = null, int $taskid = null)
    {
        if (!$cm_or_cm_info instanceof cm_info)
        {
            $cm_or_cm_info = cm_info::create($cm_or_cm_info);
        }

        $this->cm = $cm_or_cm_info;

        parent::__construct($this->cm->instance, $cm_or_cm_info);

        // get submissions
        $args = [submission::COL_OBSERVATIONID => $this->id];
        if (!is_null($userid))
        {
            $args += [submission::COL_USERID => $userid];
        }
        $this->submisisons = submission::read_all_by_condition($args);

        // get task records
        if (!is_null($taskid))
        {
            $tasks = task_base::read_all_by_condition([task::COL_ID => $taskid]);
        }
        else
        {
            $tasks = task_base::read_all_by_condition(
                [task::COL_OBSERVATIONID => $this->id],
                sprintf('`%s` ASC', task::COL_SEQUENCE));
        }
        // initialise task objects
        if (!empty($tasks))
        {
            foreach ($tasks as $task)
            {
                // assign tasks to field
                $this->tasks[] = new task($task, $userid);
            }
        }
        else
        {
            // avoid 'invalid argument' warnings
            $this->tasks = [];
        }

        $this->is_filtered = (!is_null($userid) || !is_null($taskid));
    }

    public function get_cm(): cm_info
    {
        return $this->cm;
    }

    /**
     * Removes observation and all related records
     *
     * @return bool
     * @throws dml_exception
     * @throws coding_exception
     */
    public function delete()
    {
        foreach ($this->submisisons as $submisison)
        {
            $submisison->delete();
        }

        foreach ($this->tasks as $task)
        {
            $task->delete();
        }

        return parent::delete();
    }

    /**
     * @return task[]
     */
    public function get_tasks()
    {
        return $this->tasks;
    }

    /**
     * @param int $userid
     * @return learner_task_submission[]
     * @throws coding_exception
     */
    public function get_learner_task_submissions(int $userid): array
    {
        $submissions = [];
        foreach ($this->tasks as $task)
        {
            $submissions[] = $task->get_learner_task_submission_or_null($userid);
        }

        return $submissions;
    }

    /**
     * @param array|null $userids specify user id's to get submissions for
     * @return submission[]
     * @throws \ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_all_submissions(array $userids = null): array
    {
        global $DB;

        if (is_null($userids) || empty($userids))
        {
            // get all submissions
            if ($this->is_filtered)
            {
                return parent::get_all_submissions();
            }
            else
            {
                return $this->submisisons;
            }
        }
        else
        {
            list($in_sql, $in_params) = $DB->get_in_or_equal($userids);
            $sql = "SELECT *
                    FROM {" . submission::TABLE . "}
                    WHERE " . submission::COL_USERID . " $in_sql
                    AND " . submission::COL_OBSERVATIONID . " = ?";
            $in_params[] = $this->id;
            return submission::to_class_instances(
                $DB->get_records_sql($sql, $in_params));
        }
    }

    /**
     * null NOT included
     *
     * @return learner_task_submission[]
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_all_task_submisisons(): array
    {
        // double check user has required capabilities
        $context = context_module::instance($this->get_cm()->id);
        if (!has_any_capability([self::CAP_VIEWSUBMISSIONS, self::CAP_ASSESS], $context))
        {
            throw new coding_exception('You are not permitted to view all submissions');
        }

        $submissions = [];
        if ($this->is_filtered)
        {
            $submissions = learner_task_submission::to_class_instances(
                parent::get_all_task_submisisons());
        }
        else
        {
            foreach ($this->tasks as $task)
            {
                $submissions[] = $task->get_learner_task_submissions();
            }
        }

        return $submissions;
    }

    /**
     * Checks if all criteria for completing this observation are complete
     * @param int $userid
     * @return bool complete or not
     * @throws coding_exception
     */
    public function is_activity_complete(int $userid): bool
    {
        if (empty($this->tasks))
        {
            // no tasks = not complete
            return false;
        }

        foreach ($this->tasks as $task)
        {
            if (!$task->is_complete($userid))
            {
                // return early as all tasks have to be complete
                return false;
            }
        }

        return true;
    }

    public function can_assess(int $learnerid)
    {
        $submission = $this->get_submission_or_null($learnerid);

        return $submission->is_assessment_pending() || $submission->is_assessment_in_progress();
    }

    /**
     * @inheritDoc
     */
    public function is_observed(int $userid): bool
    {
        if (empty($this->tasks))
        {
            // nothing to observe
            return false;
        }

        foreach ($this->tasks as $task)
        {
            if (!$task->is_observed($userid))
            {
                return false;
            }
        }

        return true;
    }

    public function all_tasks_observation_pending_or_in_progress(int $userid): bool
    {
        $complete = 0;
        foreach ($this->tasks as $task)
        {
            if ($task_submission = $task->get_learner_task_submission_or_null($userid))
            {
                // has a submission
                if ($task_submission->get_active_observer_assignment_or_null()
                    && ($task_submission->is_observation_pending_or_in_progress()
                        || $task_submission->is_assessment_complete()))
                {
                    // keep track of completed tasks
                    $complete += $task_submission->is_assessment_complete();
                    // has observer assigned and observation pending/in progress
                    continue;
                }
            }

            return false;
        }

        if ($complete == count($this->tasks))
        {
            // all tasks are complete - nothing to observe
            return false;
        }
        else
        {
            // some tasks are complete but rest are awaiting observation
            return true;
        }
    }

    /**
     * Get task by id from instantiated Observation class. WILL THROW EXCEPTION if task not found
     *
     * @param $taskid
     * @return mixed
     * @throws coding_exception
     */
    public function get_task($taskid): task
    {
        if (!$task = lib::find_in_assoc_array_by_key_value_or_null($this->tasks, task::COL_ID, $taskid))
        {
            throw new coding_exception(
                sprintf('Task with id %d does not exist in observation with id %d', $taskid, $this->id));
        }

        return $task;
    }

    public function get_submission_or_null(int $learnerid): ?submission
    {
        return submission::read_by_condition_or_null(
            [submission::COL_OBSERVATIONID => $this->id, submission::COL_USERID => $learnerid]);
    }

    public function get_submission_or_create(int $learnerid): submission
    {
        if (!$submission = $this->get_submission_or_null($learnerid))
        {
            $submission = new submission_base();
            $submission->set(submission::COL_OBSERVATIONID, $this->id);
            $submission->set(submission::COL_USERID, $learnerid);
            $submission->set(submission::COL_STATUS, submission::STATUS_LEARNER_PENDING);
            $submission->set(submission::COL_ATTEMPTS_ASSESSMENT, 0);
            $submission->set(submission::COL_TIMESTARTED, time());
            $submission->set(submission::COL_TIMECOMPLETED, 0);

            $submission = new submission($submission->create());

            // trigger event
            $event = activity_started::create(
                [
                    'context'  => \context_module::instance($this->get_cm()->id),
                    'objectid' => $submission->get_id_or_null(),
                    'userid'   => $learnerid,
                ]);
            $event->trigger();
        }

        return $submission;
    }

    public function create_task(task_base $task)
    {
        if (!$task->get_id_or_null())
        {
            $task->create();
        }
        // task already created in db, make sure it hasn't been added to observation already
        else if ($this->tasks[$task->get_id_or_null()])
        {
            throw new coding_exception(
                sprintf(
                    'Task "%s" (id %d) already exists in observation %s (id %d)',
                    $task->get(task::COL_NAME),
                    $task->get_id_or_null(),
                    $this->get_formatted_name(),
                    $this->get_id_or_null()));
        }
        else if ($task->get(task::COL_OBSERVATIONID !== $this->get_id_or_null()))
        {
            throw new coding_exception(
                sprintf(
                    'Task "%s" (id %d) does not belong to observation %s (id %d)',
                    $task->get(task::COL_NAME),
                    $task->get_id_or_null(),
                    $this->get_formatted_name(),
                    $this->get_id_or_null()));
        }

        $this->tasks[] = new task($task);
    }

    /**
     * Checks if all tasks in this observation instance contain at least one criteria
     *
     * @return bool
     */
    public function all_tasks_have_criteria()
    {
        foreach ($this->tasks as $task)
        {
            if (!$task->has_criteria())
            {
                return false;
            }
        }

        // if we got here, then all tasks have at least one criteria
        return true;
    }

    public function is_all_tasks_no_learner_action_required(int $userid): bool
    {
        foreach ($this->tasks as $task)
        {
            if ($submission = $task->get_learner_task_submission_or_null($userid))
            {
                // has a submission
                if ($submission->is_learner_action_required())
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if all tasks have been graded for learner
     *
     * @param int $learnerid
     * @return bool
     */
    public function can_release_grade(int $learnerid): bool
    {
        $submitted = 0;
        foreach ($this->get_learner_task_submissions($learnerid) as $learner_task_submission)
        {
            $assessor_feedback = $learner_task_submission
                ->get_latest_learner_attempt_or_null()
                ->get_assessor_feedback_or_null();

            if (!is_null($assessor_feedback))
            {
                $submitted += $assessor_feedback->is_submitted();
            }
        }

        return ($submitted == $this->get_task_count());
    }

    public function export_submissions_summary_template_data(array $userids = null): array
    {
        $data = [];
        foreach ($this->get_all_submissions($userids) as $submission)
        {
            $userid = $submission->get_userid();
            $total = $this->get_task_count();
            $observed = $submission->get_observed_task_count();
            $learner_url = (new moodle_url('/user/view.php', ['id' => $userid, 'course' => $this->course]));
            $assessment_attempts = $submission->get(submission::COL_ATTEMPTS_ASSESSMENT);

            $observation_attempt_summary = [];
            foreach ($submission->get_task_observation_attempts() as $task_id => $attempts)
            {
                /** @var $task task */
                $task = lib::find_in_assoc_array_by_key_value_or_null($this->get_tasks(), 'id', $task_id);
                $observation_attempt_summary[] = ['task' => $task->get_formatted_name(), 'attempts' => $attempts];
            }

            $data[] = [
                'userid'                       => $userid,
                'learner'                      => fullname(core_user::get_user($userid)),
                'learner_profile_url'          => $learner_url,
                'has_attempts_summary'         => !empty($observation_attempt_summary),
                'attempts_observation_summary' => $observation_attempt_summary,
                'attempt_number_assessment'    => $assessment_attempts == 0 ? '-' : $assessment_attempts,
                'observed_count_formatted'     => sprintf('%d/%d', $observed, $total),
                'is_complete'                  => $submission->is_assessment_complete(),
                'submission_status'            => lib::get_status_string($submission->get(submission::COL_STATUS))
            ];
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $tasks = [];
        foreach ($this->tasks as $task)
        {
            // has to be a simple array otherwise mustache won't loop over
            $tasks[] = $task->export_template_data();
        }
        // sort tasks based on sequence (tasks should be sorted in constructor but need to be sure)
        $tasks = lib::sort_by_field($tasks, task::COL_SEQUENCE);

        $context = context_module::instance($this->cm->id);
        return [
            self::COL_ID             => $this->id,
            self::COL_COURSE         => $this->course,
            self::COL_NAME           => $this->name,
            self::COL_INTRO          => lib::format_intro(self::COL_INTRO, $this->intro, $context),
            self::COL_LASTMODIFIEDBY => fullname(core_user::get_user($this->lastmodifiedby)),

            'tasks'       => $tasks,

            // other data
            'module_root' => (new moodle_url(sprintf('/mod/%s', OBSERVATION)))->out(false),
            'courseid'    => $this->course,
            'cmid'        => $this->cm->id,

            'capabilities'    => $this->export_capabilities(),
            'has_submissions' => $this->has_submissions()
        ];
    }

    public function get_all_observers() : array {
        global $DB;
        $sql = "SELECT e.* "

        . " FROM {" . observer_assignment::TABLE . "} a "
    
        . "INNER JOIN {" . learner_task_submission::TABLE . "} b ON\n"
    
        . "    a.learner_task_submissionid = b.id\n"
    
        . "INNER JOIN {" . observer::TABLE . "} e ON\n"
    
        . "	a.observerid = e.id\n"
    
        . "INNER JOIN {" . task::TABLE . "} c ON\n"
    
        . "    b.taskid = c.id\n"
    
        . "INNER JOIN  {" . observation::TABLE . "} d ON\n"
    
        . "    c.observationid = d.id\n"
    
        . "WHERE\n"
    
        . "    observationid = 1";

        return observer::read_all_by_sql($sql);
    }
}