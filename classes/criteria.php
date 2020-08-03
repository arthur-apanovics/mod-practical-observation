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

class criteria extends criteria_base implements templateable
{
    /**
     * @var observer_feedback[]
     */
    private $observer_feedback;


    /**
     * @var bool
     */
    private $is_filtered;

    public function __construct($id_or_record, int $userid = null, int $observer_submissionid = null)
    {
        parent::__construct($id_or_record);

        if (!is_null($userid))
        {
            // filter feedback by user
            $sql = 'select f.*
                    from mdl_observation_observer_feedback f
                        join {'.criteria::TABLE.'} c on c.id = f.'.observer_feedback::COL_CRITERIAID.'
                        join {'.observer_task_submission::TABLE.'} os on os.id = f.'.observer_feedback::COL_OBSERVER_SUBMISSIONID.'
                        join {'.observer_assignment::TABLE.'} oa on oa.id = os.'.observer_task_submission::COL_OBSERVER_ASSIGNMENTID.'
                        join {'.learner_task_submission::TABLE.'} ls on ls.id = oa.'.observer_assignment::COL_LEARNER_TASK_SUBMISSIONID.'
                    where c.id = ? and ls.userid = ?';
            $this->observer_feedback = observer_feedback::read_all_by_sql($sql, [$this->id, $userid]);

            $this->is_filtered = true;
        }
        else if (!is_null($observer_submissionid))
        {
            // filter feedback by observer submission (this automatically includes user)
            $this->observer_feedback =  observer_feedback::read_all_by_condition(
                [observer_feedback::COL_OBSERVER_SUBMISSIONID => $observer_submissionid]);

            $this->is_filtered = true;
        }
        else
        {
            // no filter, get all feedback for criteria
            $this->observer_feedback = observer_feedback::read_all_by_condition(
                [observer_feedback::COL_CRITERIAID => $this->id]);
        }
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $observer_feedback_data = [];
        $observer_task_submission = null;
        foreach ($this->observer_feedback as $observer_feedback)
        {
            if (is_null($observer_task_submission)
                || $observer_feedback->get_id_or_null() != $observer_task_submission->get_id_or_null())
            {
                // needed to check if feedback has been submitted
                $observer_task_submission = $observer_feedback->get_observer_task_submission_base();
            }

            if ($observer_task_submission->is_submitted() && $observer_feedback->is_submitted())
            {
                $observer_feedback_data[] = $observer_feedback->export_template_data();
            }
        }

        $outcome = null;
        // set ouctome for this criteria if only single user data present
        if ($this->is_filtered && isset($observer_feedback))
        {
            // use last iteration of observer feedback to determine final outcome for criteria
            $outcome = $observer_feedback->get(observer_feedback::COL_OUTCOME);
        }


        // todo: improve cmid retrieval
        $cmid = (new task_base($this->taskid))->get_observation_base()->get_cm()->id;
        $context = \context_module::instance($cmid);
        return [
            self::COL_ID                => $this->id,
            self::COL_TASKID            => $this->taskid,
            self::COL_NAME              => $this->name,
            self::COL_DESCRIPTION       => lib::format_intro(
                self::COL_DESCRIPTION,
                $this->description,
                $context,
                $this->id),
            self::COL_FEEDBACK_REQUIRED => $this->feedback_required,
            self::COL_SEQUENCE          => $this->sequence,

            'outcome'           => $outcome,
            'observer_feedback' => $observer_feedback_data
        ];
    }
}
