<?php


namespace mod_observation\traits;

// class autoloader cannot find interface on it's own...
global $CFG;
require_once($CFG->dirroot . '/mod/observation/classes/interface/learner_submission_statuses.php');

use coding_exception;
use mod_observation\db_model_base;
use mod_observation\interfaces\learner_submission_statuses;
use mod_observation\lib;

abstract class submission_status_store extends db_model_base implements learner_submission_statuses
{
    /**
     * @var string one of {@link learner_submission_statuses}
     */
    protected $status;

    public function is_observation_complete(bool $include_incomplete_assessment = false)
    {
        $this->validate_status();

        // if status is either one of these, then observation is complete
        $complete_statuses = [
            self::STATUS_ASSESSMENT_PENDING,
            self::STATUS_ASSESSMENT_IN_PROGRESS,
            self::STATUS_COMPLETE
        ];

        if ($include_incomplete_assessment)
        {
            $complete_statuses += [self::STATUS_ASSESSMENT_INCOMPLETE];
        }

        return in_array($this->status, $complete_statuses);
    }

    public function is_assessment_started_inprogress_or_complete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS
            || $this->status === self::STATUS_ASSESSMENT_INCOMPLETE
            || $this->status === self::STATUS_COMPLETE;
    }

    public function is_assessment_in_progress_or_complete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS
            || $this->status === self::STATUS_COMPLETE;
    }

    public function is_assessment_in_progress_or_incomplete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS
            || $this->status === self::STATUS_ASSESSMENT_INCOMPLETE;
    }

    public function is_assessment_in_progress()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_IN_PROGRESS;
    }

    public function is_assessment_pending()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_PENDING;
    }

    public function is_assessment_complete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_COMPLETE;
    }

    public function is_assessment_incomplete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_ASSESSMENT_INCOMPLETE;
    }

    public function is_assessment_complete_or_incomplete()
    {
        $this->validate_status();
        return $this->status === self::STATUS_COMPLETE
            || $this->status === self::STATUS_ASSESSMENT_INCOMPLETE;
    }

    public function is_observation_pending_or_in_progress()
    {
        $this->validate_status();
        return in_array($this->status, [self::STATUS_OBSERVATION_PENDING, self::STATUS_OBSERVATION_IN_PROGRESS]);
    }

    public function is_observation_in_progress()
    {
        $this->validate_status();
        return $this->status == self::STATUS_OBSERVATION_IN_PROGRESS;
    }

    public function is_observation_incomplete()
    {
        $this->validate_status();
        return $this->status == self::STATUS_OBSERVATION_INCOMPLETE;
    }

    public function is_observation_pending()
    {
        $this->validate_status();
        return $this->status == self::STATUS_OBSERVATION_PENDING;
    }

    public function is_learner_pending()
    {
        $this->validate_status();
        return $this->status == self::STATUS_LEARNER_PENDING;
    }

    public function is_learner_in_progress()
    {
        $this->validate_status();
        return $this->status == self::STATUS_LEARNER_IN_PROGRESS;
    }

    public function is_learner_action_required()
    {
        $this->validate_status();
        $yes = [
            self::STATUS_LEARNER_PENDING,
            self::STATUS_LEARNER_IN_PROGRESS,
            self::STATUS_OBSERVATION_INCOMPLETE,
            self::STATUS_ASSESSMENT_INCOMPLETE,
        ];

        return in_array($this->status, $yes);
    }

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == static::COL_STATUS)
        {
            // validate status is correctly set
            $allowed = [
                self::STATUS_LEARNER_PENDING,
                self::STATUS_LEARNER_IN_PROGRESS,
                self::STATUS_OBSERVATION_PENDING,
                self::STATUS_OBSERVATION_IN_PROGRESS,
                self::STATUS_OBSERVATION_INCOMPLETE,
                self::STATUS_ASSESSMENT_PENDING,
                self::STATUS_ASSESSMENT_IN_PROGRESS,
                self::STATUS_ASSESSMENT_INCOMPLETE,
                self::STATUS_COMPLETE,
            ];
            lib::validate_prop(static::COL_STATUS, $this->status, $value, $allowed, true);
        }

        return parent::set($prop, $value, $save);
    }

    private function validate_status(): void
    {
        if (empty($this->status))
        {
            throw new coding_exception(
                sprintf('Accessing observation status on an uninitialized %s class', self::class));
        }
    }
}
