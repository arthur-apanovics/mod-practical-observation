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

class criteria_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_criteria';

    public const COL_TASKID             = 'taskid';
    public const COL_NAME               = 'name';
    public const COL_DESCRIPTION        = 'description';
    public const COL_DESCRIPTION_FORMAT = 'description_format';
    public const COL_FEEDBACK_REQUIRED  = 'feedback_required';
    public const COL_SEQUENCE           = 'sequence';

    /**
     * @var int
     */
    protected $taskid;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var int
     */
    protected $description_format;
    /**
     * @var bool
     */
    protected $feedback_required;
    /**
     * sequence number in task
     *
     * @var int
     */
    protected $sequence;

    public function get_formatted_name()
    {
        return format_string($this->name);
    }

    /**
     * Mainly used when updating criteria from moodle form
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

    public function get_last_sequence_number_in_task()
    {
        global $DB;

        if (empty($this->taskid))
        {
            throw new \coding_exception('Task ID missing or un-initialized class instance');
        }

        $sql = 'SELECT max(c.sequence)
                FROM {' . self::TABLE . '} c
                JOIN {' . task::TABLE . '} t ON t.id = c.taskid
                WHERE c.taskid = :taskid';
        return $DB->get_field_sql($sql, ['taskid' => $this->taskid,]);
    }

    public function get_next_sequence_number_in_task()
    {
        return $this->get_last_sequence_number_in_task() + 1;
    }

    public function update_sequence_and_save(int $new_sequence)
    {
        // only update if new order differs
        if ($this->sequence != $new_sequence)
        {
            $old_sequence = $this->sequence;
            $related_criteria = criteria::read_by_condition(
                [self::COL_TASKID => $this->taskid, self::COL_SEQUENCE => $new_sequence],
                true);

            // move related criteria
            $related_criteria->sequence = $old_sequence;
            // move task in question
            $this->sequence = $new_sequence;

            $related_criteria->update();
            $this->update();
        }

        return $this;
    }

    public function delete()
    {
        $sql = 'SELECT * 
                FROM {' . self::TABLE . '}
                WHERE taskid = :taskid
                AND sequence > :deleted_sequence';
        $to_update = criteria_base::read_all_by_sql(
            $sql,
            [
                'taskid'           => $this->taskid,
                'deleted_sequence' => $this->sequence,
            ]);

        $result = parent::delete();

        // update sequence number for related records
        foreach ($to_update as $criteria)
        {
            $updated_sequence = $criteria->get($criteria::COL_SEQUENCE) - 1;
            $criteria->set(criteria::COL_SEQUENCE, $updated_sequence);

            $criteria->update();
        }

        return $result;
    }

    public function get_moodle_form_data()
    {
        return [
            self::COL_TASKID            => $this->taskid,
            self::COL_NAME              => $this->name,
            self::COL_SEQUENCE          => $this->sequence,
            self::COL_FEEDBACK_REQUIRED => $this->feedback_required,
            self::COL_DESCRIPTION       => [
                'text'   => $this->description,
                'format' => $this->description_format,
            ]
        ];
    }
}
