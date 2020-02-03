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

namespace mod_observation;

// avoid classloading exceptions
include_once('interface/crud.php');

use cm_info;
use coding_exception;
use dml_exception;
use dml_missing_record_exception;
use mod_observation\interfaces\templateable;
use totara_userdata\local\count;

class observation_base extends db_model_base
{
    // DATABASE CONSTANTS:

    public const TABLE = 'observation';

    public const COL_COURSE                        = 'course';
    public const COL_NAME                          = 'name';
    public const COL_INTRO                         = 'intro';
    public const COL_INTROFORMAT                   = 'introformat';
    public const COL_TIMEOPEN                      = 'timeopen';
    public const COL_TIMECLOSE                     = 'timeclose';
    public const COL_TIMECREATED                   = 'timecreated';
    public const COL_TIMEMODIFIED                  = 'timemodified';
    public const COL_LASTMODIFIEDBY                = 'lastmodifiedby';
    public const COL_DELETED                       = 'deleted';
    public const COL_DEF_I_TASK_OBSERVER           = 'def_i_task_observer';
    public const COL_DEF_I_TASK_OBSERVER_FORMAT    = 'def_i_task_observer_format';
    public const COL_DEF_I_TASK_ASSESSOR           = 'def_i_task_assessor';
    public const COL_DEF_I_TASK_ASSESSOR_FORMAT    = 'def_i_task_assessor_format';
    public const COL_DEF_I_ASS_OBS_LEARNER         = 'def_i_ass_obs_learner';
    public const COL_DEF_I_ASS_OBS_LEARNER_FORMAT  = 'def_i_ass_obs_learner_format';
    public const COL_DEF_I_ASS_OBS_OBSERVER        = 'def_i_ass_obs_observer';
    public const COL_DEF_I_ASS_OBS_OBSERVER_FORMAT = 'def_i_ass_obs_observer_format';
    public const COL_COMPLETION_TASKS              = 'completion_tasks';

    // ACTIVITY CONSTANTS:

    public const CAP_ADDINSTANCE     = 'mod/observation:addinstance';
    public const CAP_VIEW            = 'mod/observation:view';
    public const CAP_SUBMIT          = 'mod/observation:submit';
    public const CAP_VIEWSUBMISSIONS = 'mod/observation:viewsubmissions';
    public const CAP_ASSESS          = 'mod/observation:assess';
    public const CAP_MANAGE          = 'mod/observation:manage';

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
}

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

        $this->tasks = task::to_class_instances(
            task::read_all_by_condition([task::COL_OBSERVATIONID => $this->cm->instance]));
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

    public function get_formatted_name()
    {
        return format_string($this->name);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $tasks = [];
        foreach ($this->tasks as $task)
        {
            $tasks[] = $task->export_template_data();
        }

        return [
            'id'     => $this->id,
            'course' => $this->course,
            'name'   => $this->name,
            'intro'  => $this->intro,

            'tasks' => $tasks,
        ];
    }
}