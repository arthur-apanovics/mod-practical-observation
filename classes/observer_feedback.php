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

class observer_feedback extends observer_feedback_base implements templateable
{
    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);
    }

    /**
     * @return observer_task_submission_base
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function get_observer_task_submission_base()
    {
        return observer_task_submission_base::read_or_null($this->observer_submissionid, true);
    }

    public static function create_new_feedback(
        observer_task_submission $observer_submission, criteria_base $criteria, learner_attempt $attempt): self
    {
        $feedback = new observer_feedback_base();
        $feedback->set(self::COL_ATTEMPTID, $attempt->get_id_or_null());
        $feedback->set(self::COL_CRITERIAID, $criteria->get_id_or_null());
        $feedback->set(self::COL_OBSERVER_SUBMISSIONID, $observer_submission->get_id_or_null());
        // (intentionally) null by default:
        // self::COL_STATUS
        // self::COL_TEXT
        // self::COL_TEXT_FORMAT

        return new self($feedback->create());
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $attempt = learner_attempt_base::read_or_null($this->attemptid, true);
        $observer_task_submission = $this->get_observer_task_submission_base();
        $observer = $observer_task_submission->get_observer_assignment_base()->get_observer();

        return [
            self::COL_ID      => $this->id,
            self::COL_OUTCOME => $this->outcome,
            self::COL_TEXT    => format_text($this->text, FORMAT_HTML),

            // extra
            observer::COL_FULLNAME => $observer->get_formatted_name(),
            learner_attempt::COL_ATTEMPT_NUMBER => $attempt->get_attempt_number(),
            observer_task_submission::COL_TIMESUBMITTED => userdate(
                $observer_task_submission->get(observer_task_submission::COL_TIMESUBMITTED)),
        ];
    }
}
