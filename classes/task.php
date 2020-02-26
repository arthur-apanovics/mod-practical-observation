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

        $this->criteria = criteria::read_all_by_condition([criteria::COL_TASKID => $this->id], criteria::COL_SEQUENCE);

        $this->learner_submissions = learner_submission::read_all_by_condition(
            [learner_submission::COL_TASKID => $this->id, learner_submission::COL_USERID => $userid]);
    }

    public function has_submission()
    {
        return (bool) count($this->learner_submissions);
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
            // has to be a simple array otherwise mustache won't loop over
            $criteria_data[] = $criteria->export_template_data();
        }
        // sort by sequence again, just in case
        $criteria_data = lib::sort_by_field($criteria_data, criteria::COL_SEQUENCE);

        $learner_submissions_data = [];
        foreach ($this->learner_submissions as $learner_submission)
        {
            $learner_submissions_data[] = $learner_submission->export_template_data();
        }

        return [
            self::COL_ID             => $this->id,
            self::COL_OBSERVATIONID  => $this->observationid,
            self::COL_NAME           => $this->name,
            self::COL_INTRO_LEARNER  => $this->intro_learner,
            self::COL_INTRO_OBSERVER => $this->intro_observer,
            self::COL_INTRO_ASSESSOR => $this->intro_assessor,
            self::COL_SEQUENCE       => $this->sequence,

            'criteria'            => $criteria_data,
            'learner_submissions' => $learner_submissions_data,

            // other data
            'has_submission'      => $this->has_submission(),
        ];
    }

    /**
     * @return criteria[]|null Can be null if no criteria in task!
     */
    public function get_criteria()
    {
        return $this->criteria;
    }

    public function has_criteria()
    {
        return (bool) count($this->criteria);
    }
}
