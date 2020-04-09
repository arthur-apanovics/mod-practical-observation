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
use mod_observation\interfaces\templateable;
use moodle_url;

/**
 * An instance of the observation activity with all related data
 *
 * @package mod_observation
 */
class observation extends observation_base implements templateable
{
    /**
     * @var cm_info
     */
    private $cm;
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
        parent::__construct($this->cm->instance);

        // get task records
        if (!is_null($taskid))
        {
            $tasks = task_base::read_all_by_condition([task::COL_ID => $taskid]);
        }
        else
        {
            $tasks = task_base::read_all_by_condition(
                [task::COL_OBSERVATIONID => $this->cm->instance],
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
     * Marks as deleted and updates db record
     *
     * @return self
     * @throws dml_exception
     * @throws coding_exception
     */
    public function delete()
    {
        $this->deleted = true;

        return $this->update();
    }

    public function get_tasks()
    {
        return $this->tasks;
    }

    /**
     * @param int $userid
     * @return learner_submission[]
     * @throws coding_exception
     */
    public function get_learner_submissions(int $userid): array
    {
        $submissions = [];
        foreach ($this->tasks as $task)
        {
            $submissions[] = $task->get_current_learner_submission_or_null($userid);
        }

        return $submissions;
    }

    /**
     * null NOT included
     *
     * @return learner_submission[]
     */
    public function get_all_submisisons(): array
    {
        global $USER;

        // double check user has required capabilities
        $context = context_module::instance($this->get_cm()->id);
        if (!has_any_capability([self::CAP_VIEWSUBMISSIONS, self::CAP_ASSESS], $context))
        {
            throw new coding_exception('You are not permitted to view all submissions');
        }

        $submissions = [];
        if ($this->is_filtered)
        {
            $submissions = learner_submission::to_class_instances(
                parent::get_all_submisisons());
        }
        else
        {
            foreach ($this->tasks as $task)
            {
                $submissions[] = $task->get_learner_submissions();
            }
        }

        return $submissions;
    }

    /**
     * Checks if all criteria for completing this observation are complete
     * @param int $userid
     * @return bool complete or not
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

    public function all_tasks_observation_pending_or_in_progress(int $userid)
    {
        foreach ($this->tasks as $task)
        {
            if ($submission = $task->get_current_learner_submission_or_null($userid))
            {
                // has a submission
                if ($submission->get_active_observer_assignment_or_null()
                    && $submission->is_observation_pending_or_in_progress())
                {
                    // has observer assigned and observation pending/in progress
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    public function all_tasks_no_learner_action_required(int $userid)
    {
        foreach ($this->tasks as $task)
        {
            if ($submission = $task->get_current_learner_submission_or_null($userid))
            {
                // has a submission
                if ($submission->is_learner_action_required())
                {
                    // nothing for learner to do
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    public function export_submissions_summary_template_data(): array
    {
        $data = [];
        foreach ($this->get_all_submisisons() as $submisison)
        {
            $attempt = $submisison->get_latest_attempt_or_null();
            $total = $this->get_task_count();
            $observed = array_reduce(
                $this->tasks, function (int $carry, task $task) use ($submisison)
            {
                return ($carry += $task->is_observed($submisison->get_userid()));
            }, 0);

            $data[] = [
                'userid'   => $submisison->get_userid(),
                'name'     => fullname(core_user::get_user($submisison->get_userid())),
                'attempt'  => !is_null($attempt) ? $attempt->get_last_attemptnumber_in_submission() : '-',
                'observed' => "$observed/$total",
                'complete' => $submisison->is_assessment_complete(),
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

        return [
            self::COL_ID             => $this->id,
            self::COL_COURSE         => $this->course,
            self::COL_NAME           => $this->name,
            self::COL_INTRO          => $this->intro,
            self::COL_LASTMODIFIEDBY => fullname(core_user::get_user($this->lastmodifiedby)),

            'tasks'       => $tasks,

            // other data
            'module_root' => (new moodle_url(sprintf('/mod/%s', OBSERVATION)))->out(false),
            'courseid'    => $this->course,
            'cmid'        => $this->cm->id,

            'capabilities' => $this->export_capabilities()
        ];
    }
}