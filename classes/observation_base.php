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

class observation_base extends db_model_base
{
    // DATABASE CONSTANTS:

    public const TABLE = 'observation';

    public const COL_COURSE           = 'course';
    public const COL_NAME             = 'name';
    public const COL_INTRO            = 'intro';
    public const COL_INTROFORMAT      = 'introformat';
    public const COL_TIMEOPEN         = 'timeopen';
    public const COL_TIMECLOSE        = 'timeclose';
    public const COL_TIMECREATED      = 'timecreated';
    public const COL_TIMEMODIFIED     = 'timemodified';
    public const COL_LASTMODIFIEDBY   = 'lastmodifiedby';
    public const COL_DELETED          = 'deleted';
    public const COL_COMPLETION_TASKS = 'completion_tasks';
    // default intro's:
    public const COL_DEF_I_TASK_LEARNER     = 'def_i_task_learner';
    public const COL_DEF_I_TASK_OBSERVER    = 'def_i_task_observer';
    public const COL_DEF_I_TASK_ASSESSOR    = 'def_i_task_assessor';
    public const COL_DEF_I_ASS_OBS_LEARNER  = 'def_i_ass_obs_learner';
    public const COL_DEF_I_ASS_OBS_OBSERVER = 'def_i_ass_obs_observer';
    // formats for default intro's:
    public const COL_DEF_I_TASK_LEARNER_FORMAT     = 'def_i_task_learner_format';
    public const COL_DEF_I_TASK_OBSERVER_FORMAT    = 'def_i_task_observer_format';
    public const COL_DEF_I_TASK_ASSESSOR_FORMAT    = 'def_i_task_assessor_format';
    public const COL_DEF_I_ASS_OBS_LEARNER_FORMAT  = 'def_i_ass_obs_learner_format';
    public const COL_DEF_I_ASS_OBS_OBSERVER_FORMAT = 'def_i_ass_obs_observer_format';

    // ACTIVITY CONSTANTS:

    /**
     * Can add new activity instances
     */
    public const CAP_ADDINSTANCE = 'mod/observation:addinstance';
    /**
     * Can view activity (read only)
     */
    public const CAP_VIEW = 'mod/observation:view';
    /**
     * Can make submissions (learner)
     */
    public const CAP_SUBMIT = 'mod/observation:submit';
    /**
     * Can view a list of all submissions
     */
    public const CAP_VIEWSUBMISSIONS = 'mod/observation:viewsubmissions';
    /**
     * Can assess observed activities (trainer)
     */
    public const CAP_ASSESS = 'mod/observation:assess';
    /**
     * Can make changes to activity, e.g. settings, topics (editing trainer)
     */
    public const CAP_MANAGE = 'mod/observation:manage';

    public const FILE_AREA_INTRO    = 'observation_intro';
    public const FILE_AREA_TRAINEE  = 'learner_attachments';
    public const FILE_AREA_OBSERVER = 'observer_attachments';
    public const FILE_AREA_ASSESSOR = 'assessor_attachments';

    // DATABASE PROPERTIES

    /**
     * @var int
     */
    protected $course; // fk course
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $intro;
    /**
     * @var int
     */
    protected $introformat;
    /**
     * @var int
     */
    protected $timeopen;
    /**
     * @var int
     */
    protected $timeclose;
    /**
     * @var int
     */
    protected $timecreated;
    /**
     * @var int
     */
    protected $timemodified;
    /**
     * @var int
     */
    protected $lastmodifiedby; // fk user
    /**
     * @var bool
     */
    protected $deleted;
    /**
     * default intro
     * @var string
     */
    protected $def_i_task_learner;
    /**
     * @var int
     */
    protected $def_i_task_learner_format;
    /**
     * default intro
     * @var string
     */
    protected $def_i_task_observer;
    /**
     * @var int
     */
    protected $def_i_task_observer_format;
    /**
     * default intro
     * @var string
     */
    protected $def_i_task_assessor;
    /**
     * @var int
     */
    protected $def_i_task_assessor_format;
    /**
     * default intro
     * @var string
     */
    protected $def_i_ass_obs_learner;
    /**
     * @var int
     */
    protected $def_i_ass_obs_learner_format;
    /**
     * default intro
     * @var string
     */
    protected $def_i_ass_obs_observer;
    /**
     * @var int
     */
    protected $def_i_ass_obs_observer_format;
    /**
     * @var bool
     */
    protected $completion_tasks;

    public function get_formatted_name()
    {
        return format_string($this->name);
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
        global $USER;

        if ($USER->id == 0)
        {
            // external observer
            return null;
        }

        $context = context_module::instance($this->get_cm()->id);

        return [
            'can_view'            => has_capability(self::CAP_VIEW, $context, $userid),
            'can_submit'          => has_capability(self::CAP_SUBMIT, $context, $userid),
            'can_viewsubmissions' => has_capability(self::CAP_VIEWSUBMISSIONS, $context, $userid),
            'can_assess'          => has_capability(self::CAP_ASSESS, $context, $userid),
            'can_manage'          => has_capability(self::CAP_MANAGE, $context, $userid),
        ];
    }

    public function get_cm(): cm_info
    {
        if (is_null($this->id))
        {
            throw new coding_exception('Cannot get course module for uninitialized observation activity class');
        }

        return cm_info::create(
            get_coursemodule_from_instance(OBSERVATION, $this->id, $this->course, false, MUST_EXIST));
    }

    public function get_tasks()
    {
        return task_base::read_all_by_condition([task::COL_OBSERVATIONID => $this->id]);
    }

    public function get_task_ids()
    {
        global $DB;

        // using $DB will be faster in this case
        $ids = $DB->get_fieldset_select(
            task::TABLE, 'id',
            sprintf('%s = ?', task::COL_OBSERVATIONID), [$this->id]);

        return empty($ids) ? [] : $ids;
    }

    public function get_task_count()
    {
        global $DB;

        return $DB->count_records(task::TABLE, [task::COL_OBSERVATIONID => $this->id]);
    }

    /**
     * @param int $userid
     * @return learner_task_submission_base[]
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_learner_task_submissions(int $userid): array
    {
        $res = [];
        foreach ($this->get_tasks() as $task)
        {
            $res[] = learner_task_submission_base::read_by_condition_or_null(
                [learner_task_submission::COL_TASKID => $task->id, learner_task_submission::COL_USERID => $userid]);
        }

        return $res;
    }

    /**
     * @return learner_task_submission_base[]
     * @throws \dml_exception
     * @throws coding_exception
     * TODO: use submision class instead of looping over tasks
     */
    public function get_all_task_submisisons(): array
    {
        $submisisons = [];
        foreach ($this->get_task_ids() as $id)
        {
            foreach (learner_task_submission_base::read_all_by_condition(
                [learner_task_submission::COL_TASKID => $id]) as $submisison)
            {
                $submisisons[] = $submisison;
            }
        }

        return $submisisons;
    }

    /**
     * @return submission[]
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_all_submissions(): array
    {
        return submission::read_all_by_condition([submission::COL_OBSERVATIONID => $this->id]);
    }

    /**
     * Checks if all criteria for completing this observation are complete
     * @param int $userid
     * @return bool complete or not
     */
    public function is_activity_complete(int $userid): bool
    {
        $tasks = $this->get_tasks();
        if (empty($tasks))
        {
            // no tasks = not complete
            return false;
        }

        foreach ($tasks as $task)
        {
            if (!$task->is_complete($userid))
            {
                // return early as all tasks have to be complete
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $userid
     * @return bool
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function is_observed(int $userid): bool
    {
        $observed = 0;
        foreach ($this->get_learner_task_submissions($userid) as $learner_task_submission)
        {
            $observed += $learner_task_submission->is_observation_complete();
        }

        return ($observed == $this->get_task_count());
    }

    public function is_observed_as_incomplete(int $learnerid): bool
    {
        $incomplete = 0;
        foreach ($this->get_learner_task_submissions($learnerid) as $learner_task_submission)
        {
            $incomplete += $learner_task_submission->is_observation_incomplete();
        }

        return ($incomplete == $this->get_task_count());
    }

    public function is_assessed_as_incomplete($learnerid)
    {
        $incomplete = 0;
        foreach ($this->get_learner_task_submissions($learnerid) as $learner_task_submission)
        {
            $incomplete += $learner_task_submission->is_assessment_incomplete();
        }

        return ($incomplete == $this->get_task_count());
    }

    public function is_all_tasks_no_learner_action_required(int $userid): bool
    {
        foreach ($this->get_tasks() as $task)
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

    public function all_tasks_observation_pending_or_in_progress(int $userid): bool
    {
        foreach ($this->get_tasks() as $task)
        {
            if ($task_submission = $task->get_learner_task_submission_or_null($userid))
            {
                // has a task_submission
                if ($task_submission->get_active_observer_assignment_or_null()
                    && $task_submission->is_observation_pending_or_in_progress())
                {
                    // has observer assigned and observation pending/in progress
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * @return array ['column_name' =>
     *                 [
     *                  'text' => 'default_value',
     *                  'format' => 'default_value'
     *                  ]]
     */
    public function get_form_defaults_for_new_task()
    {
        return [
            task::COL_INTRO_LEARNER => [
                'text'   => $this->def_i_task_learner,
                'format' => $this->def_i_task_learner_format
            ],

            task::COL_INTRO_OBSERVER => [
                'text'   => $this->def_i_task_observer,
                'format' => $this->def_i_task_observer_format
            ],

            task::COL_INTRO_ASSESSOR => [
                'text'   => $this->def_i_task_assessor,
                'format' => $this->def_i_task_assessor_format
            ],

            task::COL_INT_ASSIGN_OBS_LEARNER => [
                'text'   => $this->def_i_ass_obs_learner,
                'format' => $this->def_i_ass_obs_learner_format
            ],

            task::COL_INT_ASSIGN_OBS_OBSERVER => [
                'text'   => $this->def_i_ass_obs_observer,
                'format' => $this->def_i_ass_obs_observer_format
            ],
        ];
    }
}
