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

use mod_observation\interfaces\templateable;

class task_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_task';

    public const COL_OBSERVATIONID                  = 'observationid';
    public const COL_NAME                           = 'name';
    public const COL_INTRO_LEARNER                  = 'intro_learner';
    public const COL_INTRO_LEARNER_FORMAT           = 'intro_learner_format';
    public const COL_INTRO_OBSERVER                 = 'intro_observer';
    public const COL_INTRO_OBSERVER_FORMAT          = 'intro_observer_format';
    public const COL_INTRO_ASSESSOR                 = 'intro_assessor';
    public const COL_INTRO_ASSESSOR_FORMAT          = 'intro_assessor_format';
    /** @var string column - intro, assign observation - learner */
    public const COL_INT_ASSIGN_OBS_LEARNER         = 'int_assign_obs_learner';
    /** @var string column - intro, assign observation - learner_format */
    public const COL_INT_ASSIGN_OBS_LEARNER_FORMAT  = 'int_assign_obs_learner_format';
    /** @var string column - intro, assign observation - observer */
    public const COL_INT_ASSIGN_OBS_OBSERVER        = 'int_assign_obs_observer';
    /** @var string column - intro, assign observation - observer_format */
    public const COL_INT_ASSIGN_OBS_OBSERVER_FORMAT = 'int_assign_obs_observer_format';
    public const COL_SEQUENCE                       = 'sequence';

    /**
     * @var int
     */
    protected $observationid;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $intro_learner;
    /**
     * @var int
     */
    protected $intro_learner_format;
    /**
     * @var string
     */
    protected $intro_observer;
    /**
     * @var int
     */
    protected $intro_observer_format;
    /**
     * @var string
     */
    protected $intro_assessor;
    /**
     * @var int
     */
    protected $intro_assessor_format;
    /**
     * @var string
     */
    protected $int_assign_obs_learner;
    /**
     * @var int
     */
    protected $int_assign_obs_learner_format;
    /**
     * @var string
     */
    protected $int_assign_obs_observer;
    /**
     * @var int
     */
    protected $int_assign_obs_observer_format;
    /**
     * sequence number in activity
     *
     * @var int
     */
    protected $sequence;

    public function get_formatted_name()
    {
        return format_string($this->name);
    }
}

class task extends task_base implements templateable
{
    /**
     * @var criteria[]
     */
    private $criteria;
    /**
     * @var learner_submission[]
     */
    private $learner_submissions;

    public function __construct($id_or_record, int $userid = null)
    {
        parent::__construct($id_or_record);

        $this->criteria = criteria::to_class_instances(
            criteria::read_all_by_condition([criteria::COL_TASKID => $this->id]));

        $this->learner_submissions = learner_submission::to_class_instances(
            learner_submission::read_all_by_condition(
                [learner_submission::COL_TASKID => $this->id, learner_submission::COL_USERID => $userid]));
    }

    /**
     * Checks if task has been observed for given userid
     *
     * @param int $userid
     */
    public function is_observed(int $userid)
    {
        // todo: implement method
        throw new \coding_exception(__METHOD__ . ' not implemented');
    }

    /**
     * Checks if task has been completed (observed & assessed) for given user id
     *
     * @param int $userid
     */
    public function is_complete(int $userid)
    {
        // todo: implement method
        return false;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $criteria_data = [];
        foreach ($this->criteria as $criteria)
        {
            $criteria_data[] = $criteria->export_template_data();
        }

        $learner_submissions_data = [];
        foreach ($this->learner_submissions as $learner_submission)
        {
            $learner_submissions_data[] = $learner_submission->export_template_data();
        }

        return [
            self::COL_ID             => $this->id,
            self::COL_NAME           => $this->name,
            self::COL_INTRO_LEARNER  => $this->intro_learner,
            self::COL_INTRO_OBSERVER => $this->intro_observer,
            self::COL_INTRO_ASSESSOR => $this->intro_assessor,
            self::COL_SEQUENCE       => $this->sequence,

            'criteria'            => $criteria_data,
            'learner_submissions' => $learner_submissions_data,
        ];
    }

    public function get_criteria()
    {
        return $this->criteria;
    }

    public function update_sequence_and_save(int $new_order)
    {
        // only update if new order differs
        if ($this->sequence != $new_order)
        {
            $old_order = $this->sequence;
            $related_task = task::read_by_condition(
                [self::COL_OBSERVATIONID => $this->observationid, self::COL_SEQUENCE => $new_order],
                true);

            // move related task
            $related_task->sequence = $old_order;
            // move task in question
            $this->sequence = $new_order;

            $related_task->update();
            $this->update();
        }

        return $this;
    }
}
