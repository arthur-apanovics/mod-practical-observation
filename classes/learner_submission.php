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
use mod_observation\interfaces\templateable;

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

    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);

        $this->learner_attempts = learner_attempt::read_all_by_condition(
            [learner_attempt::COL_LEARNER_SUBMISSIONID => $this->id]);

        $this->observer_assignments = observer_assignment::read_all_by_condition(
            [observer_assignment::COL_LEARNER_SUBMISSIONID => $this->id]);

        $this->assessor_submission = assessor_submission::read_by_condition_or_null(
            [assessor_submission::COL_LEARNER_SUBMISSIONID => $this->id],
            $this->is_observation_complete() // must exist if observation complete
        );
    }

    public function is_observation_complete()
    {
        $this->validate_status();

        // if status is either one of these, then observation is complete
        $complete_statuses = [
            self::STATUS_ASSESSMENT_PENDING,
            self::STATUS_ASSESSMENT_IN_PROGRESS,
            self::STATUS_ASSESSMENT_INCOMPLETE,
        ];

        return in_array($this->status, $complete_statuses);
    }

    private function validate_status(): void
    {
        if (empty($this->status))
        {
            throw new coding_exception(
                sprintf('Accessing observation status on an uninitialized %s class', self::class));
        }
    }

    public function is_assessment_complete()
    {
        $this->validate_status();

        return $this->status === self::STATUS_COMPLETE;
    }

    public function learner_can_attempt()
    {
        switch ($this->status)
        {
            // this status gets set when no attempts have been made
            // or an observer or assessor has requested new attempt
            case self::STATUS_LEARNER_PENDING:
                if (!$this->has_attempts())
                {
                    // no attempts ever - ok
                    $this->create_new_attempt(true);

                    return true;
                }
                else
                {
                    // find latest attempt and check stuff
                    $attempt = $this->get_latest_attempt_or_null();

                    if ($attempt->get(learner_attempt::COL_TIMESUBMITTED) == 0)
                    {
                        // attempt exists and is not yet submitted - NOT OK!

                        // learner_pending status is set AFTER an attempt has been marked
                        // by an observer or assessor. This indicates a problem in our logic

                        // update to correct status
                        $this->set(self::COL_STATUS, self::STATUS_LEARNER_IN_PROGRESS, true);
                        // let a dev know if he/she is watching
                        debugging(
                            sprintf(
                                'learner submission status is set to "pending", however, an attempt already exists for submission id %n!',
                                $this->id));

                        return true;
                    }
                    else
                    {
                        // latest attempt has been submitted and status indicates that
                        // another attempt has to be made by learner
                        $this->create_new_attempt(true);

                        return true;
                    }
                }
                break;

            case self::STATUS_LEARNER_IN_PROGRESS:
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
     * @param bool $update_submission_state if true, will set submission state to {@link learner_submission::STATUS_LEARNER_IN_PROGRESS}
     * @return learner_attempt_base
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     * @throws coding_exception
     */
    public function create_new_attempt(bool $update_submission_state = true)
    {
        $attempt = new learner_attempt_base();

        // set defaults
        $attempt->set(learner_attempt::COL_LEARNER_SUBMISSIONID, $this->id);
        $attempt->set(learner_attempt::COL_TIMESTARTED, time());
        $attempt->set(learner_attempt::COL_TIMESUBMITTED, 0);
        $attempt->set(learner_attempt::COL_TEXT, '');
        $attempt->set(learner_attempt::COL_TEXT_FORMAT, editors_get_preferred_format());
        $attempt->set(learner_attempt::COL_ATTEMPT_NUMBER, $attempt->get_next_attemptnumber_in_submission());

        $this->learner_attempts[] = $attempt->create();

        if ($update_submission_state)
        {
            $this->set(self::COL_STATUS, self::STATUS_LEARNER_IN_PROGRESS, true);
        }

        return $attempt;
    }

    /**
     * @return learner_attempt|null null if no current attempt
     * @throws coding_exception
     */
    public function get_latest_attempt_or_null()
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
     * Assigns submitted observer to this learner submission.
     *
     * @param observer_base $submitted_observer
     * @param string        $message message to include in email for observer
     * @param string|null   $explanation if learner is switching observers, he/she must explain why
     * @return observer_assignment
     * @throws \dml_exception
     * @throws coding_exception
     * @throws \moodle_exception
     */
    public function assign_observer(
        observer_base $submitted_observer, string $message, string $explanation = null): observer_assignment
    {
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

                // new observer, set current assignment as 'not active'
                $current_assignment->set(observer_assignment::COL_ACTIVE, false, true);

                // create assignment for new observer
                $assignment = observer_assignment::create_assignment(
                    $this->id, $submitted_observer->get_id_or_null(), $explanation);
            }
        }
        else
        {
            // no assignment yet, create
            $assignment = observer_assignment::create_assignment($this->id, $submitted_observer->get_id_or_null());
        }

        // TODO: EVENT

        // TODO: SEND EMAIL AND NOTIFICATION
        $review_url = new \moodle_url(null, []);
        // send email
        // send notification

        return $assignment;
    }

    /**
     * Fetches currently active observer assignment or null if one does not exist
     *
     * @return observer_assignment|null null if no record found
     * @throws coding_exception
     */
    public function get_active_observer_assignment_or_null()
    {
        return lib::find_in_assoc_array_criteria_or_null(
            $this->observer_assignments,
            [
                observer_assignment::COL_LEARNER_SUBMISSIONID => $this->id,
                observer_assignment::COL_ACTIVE               => true
            ]);
    }

    /**
     * Used for 'assign observer from history table' to display all observers assigned by user in activity
     *
     * @return observer_assignment[]
     * @throws \dml_exception
     */
    public function get_course_level_observer_assignments(): array
    {
        global $DB;

        // get course this submission is in
        //  SELECT o.course
        //  FROM mdl_observation o
        //  JOIN mdl_observation_task t on t.observationid = o.id
        //  JOIN mdl_observation_learner_submission ls ON ls.taskid = t.id
        //  WHERE ls.id = 6

        $sql = 'SELECT o.' . observation::COL_COURSE . '
                FROM {' . observation::TABLE . '} o
                    JOIN {' . task::TABLE . '} t on t.' . task::COL_OBSERVATIONID . ' = o.id
                    JOIN {' . learner_submission::TABLE . '} ls ON ls.' . learner_submission::COL_TASKID . ' = t.id
                WHERE ls.id = ?';
        $courseid = $DB->get_field_sql($sql, [$this->id], MUST_EXIST);

        // get all assignments in course for user:
        //   SELECT oa.*
        //   FROM mdl_observation_observer_assignment oa
        //            INNER JOIN (SELECT x.token
        //                             , x.active
        //                        FROM mdl_observation_observer_assignment x
        //                        WHERE x.active = (SELECT max(active)
        //                                          FROM mdl_observation_observer_assignment
        //                                          WHERE x.observerid = observerid)
        //                        GROUP BY x.observerid) g
        //                       ON g.token = oa.token AND g.active = oa.active
        //            JOIN mdl_observation_learner_submission ls
        //                 ON ls.id = oa.learner_submissionid
        //            JOIN mdl_observation_task t ON t.id = ls.taskid
        //            JOIN mdl_observation o ON o.id = t.observationid
        //   WHERE ls.userid = 130
        //       AND o.course = 381

        // TODO: This might be easier to do in PHP
        $sql = 'SELECT oa.*
                FROM {' . observer_assignment::TABLE . '} oa
                     INNER JOIN (SELECT x.' . observer_assignment::COL_TOKEN . '
                          , x.' . observer_assignment::COL_ACTIVE . '
                     FROM mdl_observation_observer_assignment x
                     WHERE x.' . observer_assignment::COL_ACTIVE . ' = (SELECT max(' . observer_assignment::COL_ACTIVE . ')
                                       FROM {' . observer_assignment::TABLE . '}
                                       WHERE x.' . observer_assignment::COL_OBSERVERID . ' = '
            . observer_assignment::COL_OBSERVERID . ')
                     GROUP BY x.' . observer_assignment::COL_OBSERVERID . ') g
                    ON g.' . observer_assignment::COL_TOKEN . ' = oa.' . observer_assignment::COL_TOKEN
            . ' AND g.' . observer_assignment::COL_ACTIVE . ' = oa.' . observer_assignment::COL_ACTIVE . '
                    JOIN {' . learner_submission::TABLE . '} ls 
                        ON ls.id = oa.' . observer_assignment::COL_LEARNER_SUBMISSIONID . '
                    JOIN {' . task::TABLE . '} t ON t.id = ls.' . learner_submission::COL_TASKID . '
                    JOIN {' . observation::TABLE . '} o ON o.id = t.' . task::COL_OBSERVATIONID . '
                WHERE ls.' . learner_submission::COL_USERID . ' = ?
                AND o.' . observation::COL_COURSE . ' = ?';

        return observer_assignment::read_all_by_sql($sql, [$this->userid, $courseid]);
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

        $assessor_submission_data = null;
        if (!is_null($this->assessor_submission))
        {
            $assessor_submission_data = $this->assessor_submission->export_template_data();
        }

        return [
            self::COL_ID            => $this->id,
            self::COL_TIMESTARTED   => userdate($this->timestarted),
            self::COL_TIMECOMPLETED => userdate($this->timecompleted),

            'learner_attempts'     => $learner_attempts_data,
            'observer_assignments' => $observer_assignments_data,
            'assessor_submission'  => $assessor_submission_data
        ];
    }
}
