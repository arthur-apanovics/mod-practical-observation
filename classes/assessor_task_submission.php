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
use context_module;
use mod_observation\interfaces\templateable;

class assessor_task_submission extends assessor_task_submission_base implements templateable
{
    /**
     * @var assessor_feedback[]
     */
    private $feedbacks;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->feedbacks = assessor_feedback::read_all_by_condition(
            [assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID => $this->id]);
    }

    /**
     * @return assessor_feedback[]
     */
    public function get_all_feedback(): array
    {
        return $this->feedbacks;
    }

    /**
     * Used to determine task outcome when submission has not been released yet
     *
     * @return string|null
     * null if no outcome because feedback does not exist yet;
     * {@link assessor_feedback::OUTCOME_COMPLETE};
     * {@link assessor_feedback::OUTCOME_NOT_COMPLETE}
     * @throws coding_exception
     */
    public function get_task_outcome_from_feedback_or_null(int $learner_attempt_id): ?string
    {
        if ($feedback = $this->get_feedback_or_null($learner_attempt_id))
        {
            return $feedback->get(assessor_feedback::COL_OUTCOME);
        }

        return null;
    }

    /**
     * @param int $learner_attempt_id
     * @return assessor_feedback
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_feedback_or_create(int $learner_attempt_id): assessor_feedback
    {
        if (!$feedback = $this->get_feedback_or_null($learner_attempt_id))
        {
            $feedback = new assessor_feedback_base();
            $feedback->set(assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID, $this->id);
            $feedback->set(assessor_feedback::COL_ATTEMPTID, $learner_attempt_id);
            $feedback->set(assessor_feedback::COL_TEXT, '');
            $feedback->set(assessor_feedback::COL_TEXT_FORMAT, editors_get_preferred_format());
            $feedback->set(assessor_feedback::COL_TIMESUBMITTED, 0);

            $feedback = new assessor_feedback($feedback->create());
            $this->feedbacks[] = $feedback;
        }

        return $feedback;
    }

    /**
     * @param int $learner_attempt_id
     * @return assessor_feedback|null
     * @throws coding_exception
     */
    private function get_feedback_or_null(int $learner_attempt_id): ?assessor_feedback
    {
        return lib::find_in_assoc_array_by_key_value_or_null(
            $this->feedbacks, assessor_feedback::COL_ATTEMPTID, $learner_attempt_id);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        return [
            self::COL_ID      => $this->id,
            self::COL_OUTCOME => lib::get_outcome_string($this->outcome),
        ];
    }
}
