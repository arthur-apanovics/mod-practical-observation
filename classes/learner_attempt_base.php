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

class learner_attempt_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_learner_attempt';

    public const COL_LEARNER_TASK_SUBMISSIONID = 'learner_task_submissionid';
    public const COL_TIMESTARTED               = 'timestarted';
    public const COL_TIMESUBMITTED        = 'timesubmitted';
    public const COL_TEXT                 = 'text';
    public const COL_TEXT_FORMAT          = 'text_format';
    public const COL_ATTEMPT_NUMBER       = 'attempt_number';

    /**
     * @var int
     */
    protected $learner_task_submissionid;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * @var int
     */
    protected $timesubmitted;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var int
     */
    protected $text_format;
    /**
     * attempt number in order of sequence.
     *
     * @var int
     */
    protected $attempt_number;

    /**
     * Performs basic validation before marking attempt as submitted
     *
     * @param learner_task_submission_base $task_submission
     * @return $this
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function submit(learner_task_submission_base $task_submission)
    {
        $this->validate($task_submission);

        if ($this->is_submitted())
        {
            throw new \coding_exception("Attempt with id '$this->id' is already submitted");
        }

        $this->set(learner_attempt::COL_TIMESUBMITTED, time(), true);

        return $this;
    }

    public function save(learner_task_submission_base $task_submission = null): self
    {
        if (is_null($task_submission))
        {
            $task_submission = learner_task_submission_base::read_or_null($this->learner_task_submissionid);
        }

        $this->validate($task_submission);

        return $this->update();
    }

    /**
     * @param learner_task_submission_base|null $task_submission
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function validate(learner_task_submission_base $task_submission = null): void
    {
        if (is_null($task_submission))
        {
            $task_submission = learner_task_submission_base::read_or_null($this->learner_task_submissionid);
        }

        $error_message = null;
        if (empty($this->get(learner_attempt::COL_TEXT)))
        {
            $error_message = sprintf(
                'learner attempt with id "%s" has no text',
                $this->get_id_or_null());
        }
        else if ($task_submission->get(learner_task_submission::COL_STATUS)
            != learner_task_submission::STATUS_LEARNER_IN_PROGRESS)
        {
            $error_message = sprintf(
                'learner task submission with id "%s" has invalid "%s" value',
                $this->get_id_or_null(),
                learner_task_submission::COL_STATUS);
        }
        // check and throw (todo: this will only throw the last error message - not ideal)
        if (!is_null($error_message))
        {
            throw new coding_exception($error_message);
        }
    }

    public function get_attempt_number(): int
    {
        return $this->attempt_number;
    }

    public function get_next_attemptnumber_in_submission(): int
    {
        return $this->get_last_attemptnumber_in_submission() + 1;
    }

    /**
     * @return int 0 if no attempts found
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_last_attemptnumber_in_submission(): int
    {
        global $DB;

        if (empty($this->learner_task_submissionid))
        {
            throw new \coding_exception('submission id missing or un-initialized class instance');
        }

        $sql = 'SELECT max(' . self::COL_ATTEMPT_NUMBER . ')
                FROM {' . self::TABLE . '} a
                WHERE ' . self::COL_LEARNER_TASK_SUBMISSIONID . ' = ?';
        $num = $DB->get_field_sql($sql, [$this->learner_task_submissionid]);

        return $num != false ? $num : 0;
    }

    public function get_assessor_feedback_or_null(): ?assessor_feedback_base
    {
        return assessor_feedback_base::read_by_condition_or_null(
            [assessor_feedback::COL_ATTEMPTID => $this->id]);
    }

    public function get_moodle_form_data()
    {
        return [
            self::COL_LEARNER_TASK_SUBMISSIONID => $this->learner_task_submissionid,
            self::COL_TIMESTARTED               => $this->timestarted,
            self::COL_TIMESUBMITTED             => $this->timesubmitted,
            self::COL_TEXT                      => [
                'text'   => $this->text,
                'format' => $this->text_format
            ],
            self::COL_ATTEMPT_NUMBER            => $this->attempt_number,
        ];
    }

    public function is_submitted()
    {
        return $this->timesubmitted != 0;
    }
}
