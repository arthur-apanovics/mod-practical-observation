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

class assessor_submission extends assessor_submission_base implements templateable
{
    /**
     * @var assessor_feedback[]
     */
    private $feedback;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->feedback = assessor_feedback::read_all_by_condition(
            [assessor_feedback::COL_ASSESSOR_SUBMISSIONID => $this->id]);
    }

    /**
     * @return assessor_feedback[]
     */
    public function get_all_feedback(): array
    {
        return $this->feedback;
    }

    /**
     * @return assessor_feedback
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function get_latest_feedback_or_create(): assessor_feedback
    {
        if (empty($this->get_all_feedback()))
        {
            $feedback = new assessor_feedback_base();
            $feedback->set(assessor_feedback::COL_ASSESSOR_SUBMISSIONID, $this->id);
            $feedback->set(assessor_feedback::COL_TEXT, '');
            $feedback->set(assessor_feedback::COL_TEXT_FORMAT, editors_get_preferred_format());
            $feedback->set(assessor_feedback::COL_TIMESUBMITTED, 0);

            $this->feedback[] = new assessor_feedback($feedback->create());
        }

        return $this->feedback[count($this->feedback)];
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
