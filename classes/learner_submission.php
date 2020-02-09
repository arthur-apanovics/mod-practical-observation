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
use dml_exception;
use dml_missing_record_exception;
use mod_observation\interfaces\templateable;

class learner_submission_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_learner_submission';

    public const COL_USERID        = 'userid';
    public const COL_TASKID        = 'taskid';
    public const COL_STATUS        = 'status';
    public const COL_TIMESTARTED   = 'timestarted';
    public const COL_TIMECOMPLETED = 'timecompleted';

    public const STATUS_NOT_STARTED             = 'not_started';
    public const STATUS_LEARNER_IN_PROGRESS     = 'learner_in_progress';
    public const STATUS_OBSERVATION_PENDING     = 'observation_pending';
    public const STATUS_OBSERVATION_IN_PROGRESS = 'observation_in_progress';
    public const STATUS_OBSERVATION_INCOMPLETE  = 'observation_incomplete';
    public const STATUS_ASSESSMENT_PENDING      = 'assessment_pending';
    public const STATUS_ASSESSMENT_IN_PROGRESS  = 'assessment_in_progress';
    public const STATUS_ASSESSMENT_INCOMPLETE   = 'assessment_incomplete';
    public const STATUS_COMPLETE                = 'complete';

    /**
     * @var int
     */
    protected $userid;
    /**
     * @var int
     */
    protected $taskid;
    /**
     * ENUM ('not_started', 'learner_in_progress', 'observation_pending', 'observation_in_progress',
     * 'observation_incomplete', 'assessment_pending', 'assessment_in_progress', 'assessment_incomplete', 'complete')
     *
     * @var string
     */
    protected $status;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * @var int
     */
    protected $timecompleted;

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_STATUS)
        {
            // validate status is correctly set
            $allowed = [
                self::STATUS_NOT_STARTED,
                self::STATUS_LEARNER_IN_PROGRESS,
                self::STATUS_OBSERVATION_PENDING,
                self::STATUS_OBSERVATION_IN_PROGRESS,
                self::STATUS_OBSERVATION_INCOMPLETE,
                self::STATUS_ASSESSMENT_PENDING,
                self::STATUS_ASSESSMENT_IN_PROGRESS,
                self::STATUS_ASSESSMENT_INCOMPLETE,
                self::STATUS_COMPLETE,
            ];
            if (!in_array($value, $allowed))
            {
                throw new coding_exception(
                    sprintf("'$value' is not a valid value for '%s' in '%s'", self::COL_STATUS, get_class($this)));
            }
        }

        return parent::set($prop, $value, $save);
    }

    // /**
    //  * @param int $id observation id
    //  * @param int $userid
    //  * @param int $taskid
    //  * @return learner_submission_base[]
    //  * @throws \dml_exception
    //  */
    // public static function get_submissions(int $id, int $userid = null, int $taskid = null): array
    // {
    //     // TODO check for multiple records if taskid provided and throw
    //     return self::read_all_by_condition(
    //         [self::COL_ID => $id, self::COL_USERID => $userid, self::COL_TASKID => $taskid]);
    // }
}

class learner_submission extends learner_submission_base implements templateable
{
    /**
     * @var learner_attempt[]
     */
    private $learner_attempts;
    /**
     * @var observer_assignment[]
     */
    private $observer_assignments;
    /**
     * @var assessor_submission
     */
    private $assessor_submission;

    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);

        $this->learner_attempts = learner_attempt::to_class_instances(
            learner_attempt::read_all_by_condition([learner_attempt::COL_LEARNER_SUBMISSIONID => $this->id]));

        $this->observer_assignments = observer_assignment::to_class_instances(
            observer_assignment::read_all_by_condition([observer_assignment::COL_LEARNER_SUBMISSIONID => $this->id]));

        $this->assessor_submission = assessor_submission::read_by_condition(
            [assessor_submission::COL_LEARNER_SUBMISSIONID => $this->id],
            $this->is_observation_complete() // must exist if observation complete
        );
    }

    public function is_observation_complete(bool $validate = false)
    {
        // todo: implement method
        throw new \coding_exception(__METHOD__ . ' not implemented');

        $result = false;

        if ($validate)
        {
            // todo: implement method
            throw new \coding_exception(__METHOD__ . ' - validation not implemented');

            if ($observer_assignment = self::get_active_observer_assignment_or_null($this->id))
            {
                $observer_feedback = $observer_assignment->get_observer_submission()->get_observer_feedback();


                foreach ($observer_feedback as $feedback)
                {

                }
            }
        }
        else
        {
            $complete_statuses = [
                self::STATUS_ASSESSMENT_PENDING,
                self::STATUS_ASSESSMENT_IN_PROGRESS,
                self::STATUS_ASSESSMENT_INCOMPLETE,
            ];

            $result = in_array($this->status, $complete_statuses);
        }

        return $result;
    }

    /**
     * Fetches currently active observer assignment or null if one does not exist
     *
     * @param int $learner_submissionid
     * @return observer_assignment|null null if no record found
     * @throws dml_exception
     * @throws dml_missing_record_exception
     * @throws coding_exception
     */
    public static function get_active_observer_assignment_or_null(int $learner_submissionid)
    {
        $assignment = observer_assignment::read_by_condition(
            [
                observer_assignment::COL_LEARNER_SUBMISSIONID => $learner_submissionid,
                observer_assignment::COL_ACTIVE               => true
            ]);

        return !empty($assignment->id)
            ? $assignment
            : null;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $learner_attempts_data = [];
        foreach ($this->learner_attempts as $learner_attempt)
        {
            $learner_attempts_data[] = $learner_attempt->export_template_data();
        }

        $observer_assignments_data = [];
        foreach ($this->observer_assignments as $observer_assignment)
        {
            $observer_assignments_data[] = $observer_assignment->export_template_data();
        }

        return [
            self::COL_ID            => $this->id,
            self::COL_TIMESTARTED   => userdate($this->timestarted),
            self::COL_TIMECOMPLETED => userdate($this->timecompleted),

            'learner_attempts'     => $learner_attempts_data,
            'observer_assignments' => $observer_assignments_data,
            'assessor_submission'  => $this->assessor_submission->export_template_data()
        ];
    }
}
