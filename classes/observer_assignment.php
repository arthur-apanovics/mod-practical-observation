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

class observer_assignment extends observer_assignment_base implements templateable
{
    /**
     * @var observer
     */
    private $observer;
    /**
     * @var observer_task_submission
     */
    private $observer_task_submission;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->observer = new observer($this->observerid);

        $this->observer_task_submission = observer_task_submission::read_by_condition_or_null(
            [observer_task_submission::COL_OBSERVER_ASSIGNMENTID => $this->id],
            (bool) $this->observation_accepted // must exist if observation has been accepted
        );
    }

    /**
     * Used when an observer is accessing the external observation page
     *
     * @param string $token
     * @param bool   $create_submission if no observer submission exists, should one be created
     * @return observer_assignment|null
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public static function read_by_token_or_null(string $token, bool $create_submission = false): ?self
    {
        if ($assignment = self::read_by_condition_or_null([self::COL_TOKEN => $token]))
        {
            if ($create_submission)
            {
                $assignment->get_observer_submission_or_create();
            }
        }

        return $assignment;
    }

    public function get_observer_submission_or_create(): observer_task_submission
    {
        if (!$observer_submission = $this->get_observer_submission_or_null())
        {
            $observer_submission = $this->create_observer_submission();
        }

        return $observer_submission;
    }

    public function get_observer_submission_or_null(): ?observer_task_submission
    {
        return $this->observer_task_submission;
    }

    /**
     * @return observer_task_submission
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public function create_observer_submission(): observer_task_submission
    {
        $submission = new observer_task_submission_base();
        $submission->set(observer_task_submission::COL_OBSERVER_ASSIGNMENTID, $this->get_id_or_null());
        $submission->set(observer_task_submission::COL_TIMESTARTED, time());
        // $submission->set(observer_submission::COL_STATUS, null); // already null
        $submission->set(observer_task_submission::COL_TIMESUBMITTED, 0);

        return ($this->observer_task_submission =
            new observer_task_submission($submission->create()));
    }

    public function is_observation_complete()
    {
        return $this->observer_task_submission->is_complete();
    }

    /**
     * @return observer
     */
    public function get_observer(): observer
    {
        return $this->observer;
    }

    /**
     * Create assignment and sets it as the active one
     *
     * @param int    $learner_task_submissionid
     * @param int    $observer_id
     * @param string $explanation if learner is switching observers, he/she must explain why
     * @return observer_assignment
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public static function create_assignment(
        int $learner_task_submissionid, int $observer_id, string $explanation = null): observer_assignment
    {
        $assignment = new observer_assignment_base();
        $assignment->set(observer_assignment::COL_LEARNER_TASK_SUBMISSIONID, $learner_task_submissionid);
        $assignment->set(observer_assignment::COL_OBSERVERID, $observer_id);
        $assignment->set(observer_assignment::COL_CHANGE_EXPLAIN, $explanation);
        $assignment->set(observer_assignment::COL_OBSERVATION_ACCEPTED, null);
        $assignment->set(observer_assignment::COL_TIMEASSIGNED, time());
        $assignment->set(observer_assignment::COL_TOKEN, self::create_requestertoken());
        $assignment->set(observer_assignment::COL_ACTIVE, true);

        return new self($assignment->create());
    }

    /**
     * Creates a random, unique 40 character sha1 hash to be used as the 'requestertoken'.
     * Taken from {@link \feedback360_responder}
     *
     * @return string the requester token (a 40 character sha1 hash).
     */
    private static function create_requestertoken()
    {
        $stringtohash = 'requester' . time() . random_string() . get_site_identifier();

        return sha1($stringtohash);
    }

    public function get_task_base(): task_base
    {
        $taskid = $this->get_learner_task_submission_base()
            ->get(learner_task_submission::COL_TASKID);

        return task_base::read_or_null($taskid);
    }

    public function get_learner_task_submission_base(): learner_task_submission_base
    {
        return learner_task_submission_base::read_or_null($this->learner_task_submissionid);
    }

    public function accept()
    {
        // update task submission
        $task_submission = $this->get_learner_task_submission_base();
        $task_submission->update_status_and_save(learner_task_submission::STATUS_OBSERVATION_IN_PROGRESS);

        // update observer assignment
        $this->set(self::COL_OBSERVATION_ACCEPTED, true);
        $this->set(self::COL_TIMEACCEPTED, time());

        return $this->update();
    }

    /**
     * Observer declined observation
     *
     * @return $this
     * @throws coding_exception
     * @throws dml_exception
     */
    public function decline(): self
    {
        // set as declined
        $this->set(self::COL_OBSERVATION_ACCEPTED, false);
        // make sure observer is not set as active
        $this->set(self::COL_ACTIVE, false);
        // set time
        $this->set(self::COL_TIMEACCEPTED, time());

        return $this->update();
    }

    public function get_observer_feedback_or_create(
        criteria_base $criteria, learner_attempt $attempt): observer_feedback
    {
        $feedback = observer_feedback::read_by_condition_or_null(
            [
                observer_feedback::COL_OBSERVER_SUBMISSIONID => $this->observer_task_submission->get_id_or_null(),
                observer_feedback::COL_CRITERIAID            => $criteria->get_id_or_null(),
                observer_feedback::COL_ATTEMPTID             => $attempt->get_id_or_null()
            ]);
        if (is_null($feedback))
        {
            // create feedback
            $feedback = observer_feedback::create_new_feedback($this->observer_task_submission, $criteria, $attempt);
        }

        return $feedback;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        $observer_submission = !is_null($this->observer_task_submission)
            ? $this->observer_task_submission->export_template_data()
            : null;

        return [
            self::COL_ID                   => $this->id,
            self::COL_CHANGE_EXPLAIN       => $this->change_explain,
            self::COL_TIMEASSIGNED         => usertime($this->timeassigned),
            self::COL_OBSERVATION_ACCEPTED => $this->observation_accepted,
            self::COL_TIMEACCEPTED         => !is_null($this->timeaccepted) ? usertime($this->timeaccepted) : null,
            self::COL_ACTIVE               => $this->active,

            'observer'            => $this->observer->export_template_data(),
            'observer_submission' => $observer_submission
        ];
    }
}
