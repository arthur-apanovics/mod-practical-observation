<?php

namespace mod_observation\interfaces;

interface learner_submission_statuses {
    // learner statuses
    public const STATUS_LEARNER_PENDING     = 'learner_pending';
    public const STATUS_LEARNER_IN_PROGRESS = 'learner_in_progress';

    // observer statuses
    public const STATUS_OBSERVATION_PENDING     = 'observation_pending';
    public const STATUS_OBSERVATION_IN_PROGRESS = 'observation_in_progress';
    public const STATUS_OBSERVATION_INCOMPLETE  = 'observation_incomplete';

    // assessor statuses
    public const STATUS_ASSESSMENT_PENDING     = 'assessment_pending';
    public const STATUS_ASSESSMENT_IN_PROGRESS = 'assessment_in_progress';
    public const STATUS_ASSESSMENT_INCOMPLETE  = 'assessment_incomplete';

    public const STATUS_COMPLETE = 'complete';
}