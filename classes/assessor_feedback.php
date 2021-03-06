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

class assessor_feedback extends assessor_feedback_base implements templateable
{
    /**
     * assessor_feedback constructor.
     * @param                          $id_or_record
     * @throws \coding_exception
     * @throws \dml_missing_record_exception
     */
    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $assessor_task_submission = assessor_task_submission_base::read_or_null(
            $this->assessor_task_submissionid, true);
        $learner_task_submisison = $assessor_task_submission->get_learner_task_submission();
        $learner_attempt = $learner_task_submisison->get_learner_attempt_or_null($this->attemptid);
        $outcome = $assessor_task_submission->get(assessor_task_submission::COL_OUTCOME);

        return [
            self::COL_ID                          => $this->id,
            self::COL_TEXT                        => format_text($this->text, FORMAT_HTML, ['trusted' => false]),
            self::COL_TIMESUBMITTED               => userdate($this->timesubmitted),

            // extra
            'feedback_type'                       => 'assessor', // feedback css class
            observer_task_submission::COL_OUTCOME => is_null($outcome) ? 'pending' : $outcome,
            learner_attempt::COL_ATTEMPT_NUMBER   => $learner_attempt->get_attempt_number(),
            'fullname'                            => fullname(
                \core_user::get_user($assessor_task_submission->get(assessor_task_submission::COL_ASSESSORID))),
        ];
    }
}