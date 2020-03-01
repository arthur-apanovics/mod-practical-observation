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

        $this->tasks = task::read_all_by_condition(
            [task::COL_OBSERVATIONID => $this->cm->instance],
            sprintf('`%s` ASC', task::COL_SEQUENCE));
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

    public function get_tasks()
    {
        return $this->tasks;
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

    /**
     * Check every {@link observation_base} capability for specified user
     *
     * @param int|null $userid if null, global $USER will be used
     * @return array ['capability' => bool]
     * @throws coding_exception
     */
    public function export_capabilities(int $userid = null)
    {
        $context = context_module::instance($this->cm->id);

        return [
            'can_view'            => has_capability(self::CAP_VIEW, $context, $userid),
            'can_submit'          => has_capability(self::CAP_SUBMIT, $context, $userid),
            'can_viewsubmissions' => has_capability(self::CAP_VIEWSUBMISSIONS, $context, $userid),
            'can_assess'          => has_capability(self::CAP_ASSESS, $context, $userid),
            'can_manage'          => has_capability(self::CAP_MANAGE, $context, $userid),
        ];
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