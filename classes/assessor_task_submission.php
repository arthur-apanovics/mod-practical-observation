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
     * @param int $learner_attempt_id
     * @return assessor_feedback
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function get_feedback_or_create(int $learner_attempt_id): assessor_feedback
    {
        $feedback = lib::find_in_assoc_array_by_key_value_or_null(
            $this->feedbacks, assessor_feedback::COL_ATTEMPTID, $learner_attempt_id);

        if (is_null($feedback))
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

    public function submit(string $outcome, assessor_feedback_base $feedback)
    {
        if (!lib::find_in_assoc_array_by_key_value_or_null($this->feedbacks, 'id', $feedback->get_id_or_null()))
        {
            throw new coding_exception(
                sprintf('%s with id "%d" does not exist in %s with id "%d"',
            assessor_feedback::class, $feedback->get_id_or_null(), self::class, $this->id));
        }
        if (empty($feedback->get(assessor_feedback::COL_TIMESUBMITTED))
            || empty($feedback->get(assessor_feedback::COL_TEXT))
            || empty($feedback->get(assessor_feedback::COL_TEXT_FORMAT)))
        {
            throw new coding_exception('Assessor feedback was not saved correctly');
        }

        // task submission outcome is set during releasing of grade

        // update activity submission
        $learner_task_submission = $this->get_learner_task_submission();
        $submission = $learner_task_submission->get_submission();
        $observation = $submission->get_observation();
        if ($observation->is_activity_complete($learner_task_submission->get_userid()))
        {
            // all marked as complete - activity complete
            $submission->update_status_and_save(submission::STATUS_COMPLETE);
        }
        else if ($observation->is_assessed_as_incomplete($learner_task_submission->get_userid()))
        {
            // all assessments marked as not complete
            $submission->update_status_and_save(submission::STATUS_ASSESSMENT_INCOMPLETE);
        }
        else
        {
            if ($submission->get(submission::COL_STATUS) === submission::STATUS_ASSESSMENT_PENDING)
            {
                $submission->update_status_and_save(submission::STATUS_ASSESSMENT_IN_PROGRESS);
            }
        }

        // TODO notifications
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        return [
            self::COL_ID      => $this->id,
            self::COL_OUTCOME => lib::get_status_string($this->outcome),
        ];
    }
}
