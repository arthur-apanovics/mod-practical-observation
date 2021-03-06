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
use mod_observation\event\activity_assessing;
use mod_observation\event\attempt_started;
use mod_observation\event\observer_assigned;
use mod_observation\interfaces\templateable;

class learner_task_submission extends learner_task_submission_base implements templateable
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
     * @var assessor_task_submission
     */
    private $assessor_task_submission;

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->learner_attempts = learner_attempt::read_all_by_condition(
            [learner_attempt::COL_LEARNER_TASK_SUBMISSIONID => $this->id]);

        $this->observer_assignments = observer_assignment::read_all_by_condition(
            [observer_assignment::COL_LEARNER_TASK_SUBMISSIONID => $this->id]);

        $this->assessor_task_submission = assessor_task_submission::read_by_condition_or_null(
            [assessor_task_submission::COL_LEARNER_TASK_SUBMISSIONID => $this->id],
            $this->is_assessment_in_progress() // must exist if observation complete
        );
    }

    public function learner_can_attempt_or_create()
    {
        switch ($this->status)
        {
            // this status gets set when no attempts have been made
            // or an observer or assessor has requested new attempt
            case self::STATUS_LEARNER_PENDING:
                if (!$this->has_attempts())
                {
                    // no attempts, ever - ok
                    $this->create_new_attempt(true);

                    return true;
                }
                else
                {
                    // find latest attempt and check stuff
                    $attempt = $this->get_latest_attempt_or_null();

                    if (!$attempt->is_submitted())
                    {
                        // attempt exists and is not yet submitted - NOT OK!

                        // learner_pending status is set AFTER an attempt has been marked
                        // by an observer or assessor. This indicates a problem in our logic

                        // update to correct status
                        $this->update_status_and_save(self::STATUS_LEARNER_IN_PROGRESS);
                        // let a dev know if he/she is watching
                        debugging(
                            sprintf(
                                'learner task submission status is set to "pending", however, an attempt already exists for submission id %d!',
                                $this->id), DEBUG_DEVELOPER, debug_backtrace());

                        return true;
                    }
                    else
                    {
                        // NOT OK
                        // latest attempt has been submitted and status indicates that
                        // another attempt has to be made by learner
                        debugging(
                            'attempt submitted and observed but status is set to pending, creating new attempt',
                            DEBUG_DEVELOPER, debug_backtrace());

                        $this->create_new_attempt(true);

                        return true;
                    }
                }
                break;

            case self::STATUS_LEARNER_IN_PROGRESS:
                $attempt = $this->get_latest_attempt_or_null();
                if (!$attempt)
                {
                    // NOT OK
                    debugging(
                        'learner task submission is set to "learner in progress" but no attempt exists',
                        DEBUG_DEVELOPER, debug_backtrace());

                    $this->create_new_attempt(false);
                }

                if ($attempt->is_submitted())
                {
                    // can happen when observation request has been declined (we set status to 'in progress' in that case)
                    return false;
                }

                return true;
                break;

            case self::STATUS_OBSERVATION_INCOMPLETE:
            case self::STATUS_ASSESSMENT_INCOMPLETE:
                $this->create_new_attempt(true);

                return true;
                break;


            default:
                return false;
        }
    }

    /**
     * Checks if learner has made any attempts for this submission
     *
     * @return bool
     */
    public function has_attempts()
    {
        return (bool) count($this->learner_attempts);
    }

    /**
     * @param bool $update_submission_status if true, will set submission and task submission state to {@link STATUS_LEARNER_IN_PROGRESS}
     * @return learner_attempt_base
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public function create_new_attempt(bool $update_submission_status)
    {
        $attempt = new learner_attempt_base();

        // set defaults
        $attempt->set(learner_attempt::COL_LEARNER_TASK_SUBMISSIONID, $this->id);
        $attempt->set(learner_attempt::COL_TIMESTARTED, time());
        $attempt->set(learner_attempt::COL_TIMESUBMITTED, 0);
        $attempt->set(learner_attempt::COL_TEXT, '');
        $attempt->set(learner_attempt::COL_TEXT_FORMAT, editors_get_preferred_format());
        $attempt->set(learner_attempt::COL_ATTEMPT_NUMBER, $attempt->get_next_attemptnumber_in_submission());

        $this->learner_attempts[] = new learner_attempt($attempt->create());

        if ($update_submission_status)
        {
            // task submission
            $this->update_status_and_save(self::STATUS_LEARNER_IN_PROGRESS);

            // activity submission
            $submission = $this->get_submission();
            if ($submission->get(submission::COL_STATUS) !== submission::STATUS_LEARNER_IN_PROGRESS)
            {
                $submission->update_status_and_save(submission::STATUS_LEARNER_IN_PROGRESS, true);
            }

            // trigger event
            $event = attempt_started::create(
                [
                    'context'  => \context_module::instance(
                        $this->get_submission()
                            ->get_observation()
                            ->get_cm()
                            ->id),
                    'objectid' => $attempt->get_id_or_null(),
                    'userid'   => $this->userid,
                    'other'    => [
                        'taskid' => $this->taskid,
                    ]
                ]);
            $event->trigger();
        }

        return $attempt;
    }

    /**
     * @return learner_attempt|null null if no current attempt
     * @throws coding_exception
     */
    public function get_latest_attempt_or_null(): ?learner_attempt
    {
        if (empty($this->learner_attempts))
        {
            return null;
        }

        $attempts_sorted = lib::sort_by_field(
            $this->learner_attempts,
            learner_attempt::COL_ATTEMPT_NUMBER,
            'desc');

        return array_values($attempts_sorted)[0];
    }

    /**
     * Assigns submitted observer to this learner task submission.
     *
     * @param observer_base $submitted_observer
     * @param string|null   $explanation if learner is switching observers, he/she must explain why
     * @return observer_assignment
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_missing_record_exception
     */
    public function assign_observer(
        observer_base $submitted_observer, string $explanation = null): observer_assignment
    {
        if (!$submitted_observer->get_id_or_null())
        {
            throw new coding_exception('Observer must exist in database before assigning to submission');
        }

        if ($current_assignment = $this->get_active_observer_assignment_or_null())
        {
            // we have an existing assignment

            //TODO: CHECK IF observation_accepted = 0,
            // MEANING OBSERVER HAS DECLINED THIS REQUEST PREVIOUSLY

            // assignment for this submission exists, check if same observer submitted
            $current_observer = $current_assignment->get_observer();
            if ($current_observer->get_id_or_null() != $submitted_observer->get_id_or_null())
            {
                if (is_null($explanation))
                {
                    throw new coding_exception('Explanation cannot be NULL when switching observers');
                }
            }

            // set current assignment as 'not active'
            $current_assignment->set(observer_assignment::COL_ACTIVE, false, true);

            // create new assignment
            $assignment = observer_assignment::create_assignment(
                $this->id, $submitted_observer->get_id_or_null(), $explanation);
        }
        else
        {
            // no assignment yet, create
            $assignment = observer_assignment::create_assignment($this->id, $submitted_observer->get_id_or_null());
        }

        // trigger event
        $submisison = $this->get_submission();
        $observation = $submisison->get_observation();
        $event = observer_assigned::create(
            [
                'context'  => \context_module::instance($observation->get_cm()->id),
                'objectid' => $assignment->get_id_or_null(),
                'other'    => [
                    'observerid'                => $assignment->get_observer()->get_id_or_null(),
                    'learner_task_submissionid' => $this->get_id_or_null(),
                ]
            ]);
        $event->trigger();

        return $assignment;
    }

    /**
     * Check if the latest observation request has been declined.
     *
     * @return bool false if no assignments exist
     */
    public function is_observation_declined()
    {
        if ($assignment = $this->get_latest_observer_assignment_or_null())
        {
            if ($assignment->is_declined())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetches currently active observer assignment or null if one does not exist
     *
     * @return observer_assignment|null null if no record found
     * @throws coding_exception
     */
    public function get_active_observer_assignment_or_null(): ?observer_assignment_base
    {
        return lib::find_in_assoc_array_by_criteria_or_null(
            $this->observer_assignments,
            [
                observer_assignment::COL_LEARNER_TASK_SUBMISSIONID => $this->id,
                observer_assignment::COL_ACTIVE                    => true
            ]);
    }


    /**
     * Returns all existing observer assignments for this task
     *
     * @return observer_assignment[]
     */
    public function get_observer_assignments(): array
    {
        return $this->observer_assignments;
    }

    /**
     * @return assessor_task_submission|null
     */
    public function get_assessor_task_submission_or_null()
    {
        return $this->assessor_task_submission;
    }

    /**
     * @return assessor_feedback[]
     */
    public function get_all_assessor_feedback(): array
    {
        $feedback = [];
        if ($assessor_task_submission = $this->get_assessor_task_submission_or_null())
        {
            $feedback = $assessor_task_submission->get_all_feedback();
        }

        return $feedback;
    }

    public function get_assessor_task_submission_or_create(int $assessorid = null): assessor_task_submission
    {
        global $USER;

        if (!$assessor_task_submission = $this->get_assessor_task_submission_or_null())
        {
            $userid = is_null($assessorid) ? $USER->id : $assessorid;

            $assessor_task_submission = new assessor_task_submission_base();
            $assessor_task_submission->set(assessor_task_submission::COL_ASSESSORID, $userid);
            $assessor_task_submission->set(assessor_task_submission::COL_LEARNER_TASK_SUBMISSIONID, $this->id);
            // COL_OUTCOME intentionally null

            $assessor_task_submission = new assessor_task_submission($assessor_task_submission->create());
        }
        else if (!is_null($assessorid)
            && $assessor_task_submission->get(assessor_task_submission::COL_ASSESSORID) != $assessorid)
        {
            debugging(
                sprintf(
                    'Provided assessor id "%d" does not match existing assessor id "%d" in assessor_task_submission with id "%d"',
                    $assessorid,
                    $assessor_task_submission->get(assessor_task_submission::COL_ASSESSORID),
                    $assessor_task_submission->get_id_or_null()
                ));
        }

        // trigger event
        $submisison = $this->get_submission();
        $observation = $submisison->get_observation();
        $event = activity_assessing::create(
            [
                'context'       => \context_module::instance($observation->get_cm()->id),
                'objectid'      => $submisison->get_id_or_null(),
                'relateduserid' => $this->userid,
                'other'         => [
                    'assessor_submissionid' => $assessor_task_submission->get_id_or_null(),
                ]
            ]);
        $event->trigger();

        return $assessor_task_submission;
    }

    /**
     * Used for 'assign observer from history table' to display all observers assigned by user in course for
     * this activity type
     *
     * @return observer_assignment[]
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_course_level_observer_assignments(): array
    {
        $sql =
            'SELECT oa.*
             FROM mdl_observation_observer_assignment oa
                 JOIN {' . learner_task_submission::TABLE . '} lts 
                    ON lts.id = oa. ' . observer_assignment::COL_LEARNER_TASK_SUBMISSIONID . '
                 JOIN {' . task::TABLE . '} t ON t.id = lts.taskid
                 JOIN {' . observation::TABLE . '} o ON o.id = t.' . task::COL_OBSERVATIONID . '
             WHERE o.' . observation::COL_COURSE . ' = ?
             AND lts.' . learner_task_submission::COL_USERID . ' = ?
             ORDER BY ' . observer_assignment::COL_ACTIVE . ' DESC';
        $assignments = observer_assignment::read_all_by_sql($sql, [$this->get_course_id(), $this->userid]);

        // get_records_sql returns assoc array indexed by db id's so we need to sort again...
        usort($assignments, function ($item1, $item2) {
            return $item2->get('active') <=> $item1->get('active');
        });

        // filter out assignments for this task submission
        $current_submission_assignments = array_filter(
            $assignments, function ($assignment, $key) use (&$assignments)
        {
            if ($assignment->get(observer_assignment::COL_LEARNER_TASK_SUBMISSIONID) === $this->id)
            {
                /** @var $key int */
                unset($assignments[$key]);

                return true;
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);

        // combine arrays with current task submission assignments on top
        $assignments = array_merge($current_submission_assignments, $assignments);

        $unique_assignments = [];
        foreach ($assignments as $assignment)
        {
            $observer_id = $assignment->get(observer_assignment::COL_OBSERVERID);
            if (!array_key_exists($observer_id, $unique_assignments))
            {
                if ($assignment->is_active())
                {
                    if ($this->id !== $assignment->get(observer_assignment::COL_LEARNER_TASK_SUBMISSIONID))
                    {
                        // active assignment for a different task - set as non active to display properly in table
                        $assignment->set(observer_assignment::COL_ACTIVE, false, false);
                    }
                }

                $unique_assignments[$observer_id] = $assignment;
            }
        }

        return $unique_assignments;
    }

    /**
     * @return int
     * @throws dml_exception
     */
    private function get_course_id(): int
    {
        global $DB;

        // get course this submission is in
        //  SELECT o.course
        //  FROM mdl_observation o
        //  JOIN mdl_observation_task t on t.observationid = o.id
        //  JOIN mdl_observation_learner_task_submission ls ON ls.taskid = t.id
        //  WHERE ls.id = 6

        $sql = 'SELECT o.' . observation::COL_COURSE . '
                FROM {' . observation::TABLE . '} o
                    JOIN {' . task::TABLE . '} t on t.' . task::COL_OBSERVATIONID . ' = o.id
                    JOIN {' . learner_task_submission::TABLE . '} ls ON ls.' . learner_task_submission::COL_TASKID . ' = t.id
                WHERE ls.id = ?';

        return $DB->get_field_sql($sql, [$this->id], MUST_EXIST);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        global $USER;

        $learner_attempts_data = [];
        foreach ($this->learner_attempts as $learner_attempt)
        {
            if ($learner_attempt->is_submitted())
            {
                $learner_attempts_data[] = $learner_attempt->export_template_data();
            }
        }

        $observer_assignments_data = [];
        foreach ($this->observer_assignments as $observer_assignment)
        {
            $observer_assignments_data[] = $observer_assignment->export_template_data();
        }

        $assessor_task_submission_data = null;
        if (!is_null($this->assessor_task_submission))
        {
            $assessor_task_submission_data = $this->assessor_task_submission->export_template_data();
        }

        $is_admin = is_siteadmin($USER);
        $review_url = null;
        if ($is_admin && $this->is_observation_pending_or_in_progress())
        {
            if ($observer_assignment = $this->get_active_observer_assignment_or_null())
            {
                $review_url = $observer_assignment->get_review_url();
            }
        }

        return [
            self::COL_ID            => $this->id,
            self::COL_TIMESTARTED   => userdate($this->timestarted),
            self::COL_TIMECOMPLETED => $this->timecompleted != 0 ? userdate($this->timecompleted) : null,

            'learner_attempts'         => $learner_attempts_data,
            'observer_assignments'     => $observer_assignments_data,
            'assessor_task_submission' => $assessor_task_submission_data,

            // extra
            'is_admin'                 => $is_admin,
            'review_url'               => $review_url,
        ];
    }

    public function delete()
    {
        foreach ($this->learner_attempts as $learner_task_attempt) {
            $learner_task_attempt->delete();
        }

        foreach ($this->observer_assignments as $observer_assignment_task) {
            $observer_assignment_task->delete();
        }

        if (!is_null($this->assessor_task_submission)) {
            $this->assessor_task_submission->delete();
        }
        parent::delete();
    }
}
