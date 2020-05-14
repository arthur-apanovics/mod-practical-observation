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

use coding_exception;

class task_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_task';

    public const COL_OBSERVATIONID         = 'observationid';
    public const COL_NAME                  = 'name';
    public const COL_INTRO_LEARNER         = 'intro_learner';
    public const COL_INTRO_LEARNER_FORMAT  = 'intro_learner_format';
    public const COL_INTRO_OBSERVER        = 'intro_observer';
    public const COL_INTRO_OBSERVER_FORMAT = 'intro_observer_format';
    public const COL_INTRO_ASSESSOR        = 'intro_assessor';
    public const COL_INTRO_ASSESSOR_FORMAT = 'intro_assessor_format';
    /** @var string column - intro, assign observation - learner */
    public const COL_INT_ASSIGN_OBS_LEARNER = 'int_assign_obs_learner';
    /** @var string column - intro, assign observation - learner_format */
    public const COL_INT_ASSIGN_OBS_LEARNER_FORMAT = 'int_assign_obs_learner_format';
    /** @var string column - intro, assign observation - observer */
    public const COL_INT_ASSIGN_OBS_OBSERVER = 'int_assign_obs_observer';
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

    /**
     * Mainly used when updating task from moodle form
     *
     * @return int
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_current_sequence_number(): int
    {
        global $DB;

        if (is_null($this->id) || $this->id < 1)
        {
            throw new \coding_exception(sprintf('Cannot get sequence number of un-initialized class %s', self::class));
        }

        return $DB->get_field(self::TABLE, self::COL_SEQUENCE, [self::COL_ID => $this->id], MUST_EXIST);
    }

    public function get_next_sequence_number_in_activity(): int
    {
        return $this->get_last_sequence_number_in_activity() + 1;
    }

    /**
     * @return int 0 if no tasks found
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_last_sequence_number_in_activity()
    {
        global $DB;

        if (empty($this->observationid))
        {
            throw new \coding_exception('Instance ID missing or un-initialized class instance');
        }

        $sql = 'SELECT max(sequence)
                FROM {' . self::TABLE . '} c
                WHERE ' . self::COL_OBSERVATIONID . ' = :observationid';
        $num = $DB->get_field_sql($sql, ['observationid' => $this->observationid]);

        return $num != false ? $num : 0;
    }

    public function update_sequence_and_save(int $new_sequence)
    {
        // only update if new order differs
        if ($this->sequence != $new_sequence)
        {
            $old_order = $this->sequence;
            $related_task = task::read_by_condition_or_null(
                [self::COL_OBSERVATIONID => $this->observationid, self::COL_SEQUENCE => $new_sequence],
                true);

            // move related task
            $related_task->sequence = $old_order;
            // move task in question
            $this->sequence = $new_sequence;

            $related_task->update();
            $this->update();
        }

        return $this;
    }

    /**
     * @return observation_base
     * @throws \coding_exception
     * @throws \dml_missing_record_exception
     */
    public function get_observation_base(): observation_base
    {
        return new observation_base($this->observationid);
    }

    public function delete()
    {
        $sql = 'SELECT * 
                FROM {' . self::TABLE . '}
                WHERE ' . self::COL_OBSERVATIONID . ' = :observationid
                AND ' . self::COL_SEQUENCE . ' > :deleted_sequence';
        $to_update = task_base::read_all_by_sql(
            $sql,
            [
                'task_table'       => self::TABLE,
                'observationid'    => $this->observationid,
                'deleted_sequence' => $this->sequence,
            ]);

        $result = parent::delete();

        // update sequence number for related records
        foreach ($to_update as $task_base)
        {
            $updated_sequence = $task_base->get($task_base::COL_SEQUENCE) - 1;
            $task_base->set(task::COL_SEQUENCE, $updated_sequence);

            $task_base->update();
        }

        return $result;
    }

    /**
     * @param int $userid
     * @return learner_task_submission_base|null
     * @throws coding_exception
     */
    public function get_learner_task_submission_or_null(int $userid)
    {
        return learner_task_submission_base::read_by_condition_or_null(
            [learner_task_submission::COL_USERID => $userid, learner_task_submission::COL_TASKID => $this->id]);
    }

    /**
     * Checks if task has been completed (observed & assessed) for given user id
     *
     * @param int $userid
     * @return bool
     * @throws coding_exception
     */
    public function is_complete(int $userid): bool
    {
        if ($submission = $this->get_learner_task_submission_or_null($userid))
        {
            return $submission->is_assessment_complete();
        }

        return false;
    }

    public function has_submissions(int $userid = null): bool
    {
        global $DB;

        if (!is_null($userid))
        {
            $count = $DB->count_records(learner_task_submission::TABLE, [
                learner_task_submission::COL_TASKID => $this->id,
                learner_task_submission::COL_USERID => $this->$userid
            ]);
        }
        else
        {
            $count = $DB->count_records(learner_task_submission::TABLE, [
                learner_task_submission::COL_TASKID => $this->id
            ]);
        }

        return ($count !== false && $count > 0);
    }

    public function get_moodle_form_data()
    {
        return [
            self::COL_OBSERVATIONID => $this->observationid,
            self::COL_NAME          => $this->name,
            self::COL_SEQUENCE      => $this->sequence,

            // editors
            self::COL_INTRO_LEARNER => [
                'text'   => $this->intro_learner,
                'format' => $this->intro_learner_format
            ],

            self::COL_INTRO_OBSERVER => [
                'text'   => $this->intro_observer,
                'format' => $this->intro_observer_format
            ],

            self::COL_INTRO_ASSESSOR => [
                'text'   => $this->intro_assessor,
                'format' => $this->intro_assessor_format
            ],

            self::COL_INT_ASSIGN_OBS_LEARNER => [
                'text'   => $this->int_assign_obs_learner,
                'format' => $this->int_assign_obs_learner_format
            ],

            self::COL_INT_ASSIGN_OBS_OBSERVER => [
                'text'   => $this->int_assign_obs_observer,
                'format' => $this->int_assign_obs_observer_format
            ],
        ];
    }

    public function get_criteria_count(): int
    {
        global $DB;

        $count = $DB->count_records(criteria::TABLE, [criteria::COL_TASKID => $this->id]);
        return $count !== false ? $count : 0;
    }
}
