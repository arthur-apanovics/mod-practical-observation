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

use context_module;
use mod_observation\event\activity_observed;
use mod_observation\event\attempt_observed;

class observer_task_submission_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer_task_submission';

    public const COL_OBSERVER_ASSIGNMENTID = 'observer_assignmentid';
    public const COL_TIMESTARTED           = 'timestarted';
    public const COL_OUTCOME               = 'outcome';
    public const COL_TIMESUBMITTED         = 'timesubmitted';

    public const OUTCOME_NOT_COMPLETE = 'not_complete';
    public const OUTCOME_COMPLETE     = 'complete';

    /**
     * @var int
     */
    protected $observer_assignmentid;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * Outcome of observation, NULL if not submitted yet.
     * Possible values: null, {@link OUTCOME_NOT_COMPLETE}, {@link OUTCOME_COMPLETE}
     * @var string|null
     */
    protected $outcome;
    /**
     * @var int
     */
    protected $timesubmitted;

    /**
     * @param string $outcome {@link outcome}
     * @return observer_task_submission_base
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_missing_record_exception
     */
    public function submit(string $outcome): self
    {
        global $USER;

        // setting outcome early also validates that correct value has been passed
        $this->set(self::COL_OUTCOME, $outcome);
        $this->set(self::COL_TIMESUBMITTED, time());

        $observer_assignment = $this->get_observer_assignment_base();
        $learner_task_submission = $observer_assignment->get_learner_task_submission_base();

        $new_task_status = ($outcome == self::OUTCOME_COMPLETE)
            ? learner_task_submission::STATUS_ASSESSMENT_PENDING
            : learner_task_submission::STATUS_OBSERVATION_INCOMPLETE;
        $learner_task_submission->update_status_and_save($new_task_status);

        // update activity submission status if needed
        $submission = $learner_task_submission->get_submission();
        $observation = $submission->get_observation();

        $assessment_needed = false;
        if ($submission->is_observed())
        {
            // all tasks observed, assessment needed
            $assessment_needed = true;
            // increment assessment attempt count
            $submission->increment_assessment_attempt_number(false);
            // set new status and update db
            $submission->update_status_and_save(submission::STATUS_ASSESSMENT_PENDING);
        }
        else if ($submission->is_observed_as_incomplete())
        {
            // all observations marked as not complete
            $submission->update_status_and_save(submission::STATUS_OBSERVATION_INCOMPLETE);
        }
        else if (!$submission->is_all_tasks_no_learner_action_required())
        {
            // user input needed for some task(s)
            $submission->update_status_and_save(submission::STATUS_LEARNER_PENDING, true);
        }
        else
        {
            // not observed, not observed as incomplete, no learner input needed
            if ($submission->get(submission::COL_STATUS) === submission::STATUS_OBSERVATION_PENDING)
            {
                // observation started, update status to 'in progress'
                $submission->update_status_and_save(submission::STATUS_OBSERVATION_IN_PROGRESS);
            }
        }

        // save
        $this->update();

        $cm = $observation->get_cm();
        // trigger event
        $event = attempt_observed::create(
            [
                'context'  => \context_module::instance($cm->id),
                'objectid' => $learner_task_submission->get_id_or_null(),
                'userid'   => $learner_task_submission->get_userid(),
                'other'    => [
                    'admin_observing'       => is_siteadmin($USER),
                    'observerid'            => $observer_assignment->get_observer()->get_id_or_null(),
                    'attemptid'             => $learner_task_submission->get_latest_learner_attempt_or_null()->get_id_or_null(),
                    'observer_submissionid' => $this->get_id_or_null()
                ]
            ]);
        $event->trigger();

        // trigger another event if all tasks have been observed and are ready for assessment
        if ($assessment_needed)
        {
            // check if groups enabled
            if (groups_get_activity_groupmode($cm))
            {
                $course = $observation->get_course();
                $lang_data = [
                    'learner_fullname'  => fullname(\core_user::get_user($submission->get_userid())),
                    'assessment_url'    => $submission->get_assess_url($observation),
                    'observer_fullname' => $observer_assignment->get_observer()->get_formatted_name_or_null(),
                    'task_name'         => $learner_task_submission->get_task_base()->get_formatted_name(),
                    'activity_name'     => $observation->get_formatted_name(),
                    'activity_url'      => $observation->get_url(),
                    'course_fullname'   => $course->fullname,
                    'course_shortname'  => $course->shortname,
                    'course_url'        => new \moodle_url('/course/view.php', ['id' => $course->id]),
                ];

                $context = context_module::instance($cm->id);
                $learner_groups = groups_get_user_groups($cm->course, $submission->get_userid());
                foreach($learner_groups[0] as $learner_group)
                {
                    // get assessors in this group
                    $assessors_in_group = lib::get_usersids_in_group_or_null(
                        $learner_group, $context, observation::CAP_ASSESS);
                    if ($assessors_in_group)
                    {
                        // map userid's to user objects
                        $assessors_in_group = array_map(
                            function ($assessor)
                            {
                                return \core_user::get_user($assessor);
                            }, $assessors_in_group);

                        $message_body = get_string('email:assessor_assessment_pending_body', OBSERVATION, $lang_data);
                        lib::email_users(
                            $assessors_in_group,
                            get_string('email:assessor_assessment_pending_subject', OBSERVATION),
                            $message_body);
                    }
                }
            }

            // trigger event
            $event = activity_observed::create(
                [
                    'context'  => \context_module::instance($observation->get_cm()->id),
                    'objectid' => $submission->get_id_or_null(),
                    'relateduserid' => $submission->get_userid(),
                ]);
            $event->trigger();
        }

        return $this;
    }

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_OUTCOME)
        {
            // validate status is correctly set
            $allowed = [self::OUTCOME_NOT_COMPLETE, self::OUTCOME_COMPLETE];
            lib::validate_prop(self::COL_OUTCOME, $this->outcome, $value, $allowed, false);
        }

        return parent::set($prop, $value, $save);
    }

    public function is_complete(): bool
    {
        return $this->outcome == self::OUTCOME_COMPLETE;
    }

    public function is_submitted(): bool
    {
        return !is_null($this->outcome);
    }

    public function get_observer_assignment_base()
    {
        return observer_assignment_base::read_by_condition_or_null(
            [observer_assignment::COL_ID => $this->observer_assignmentid], true);
    }
}
