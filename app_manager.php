<?php
use core\output\notification;
use mod_observation\learner_attempt_base;
use mod_observation\learner_task_submission;
use mod_observation\lib;
use mod_observation\observer;
use mod_observation\observer_base;
use mod_observation\task;
use mod_observation\observation;
use mod_observation\submission;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('lib.php');
require_once('forms.php');


$cmid = required_param('cmid', PARAM_INT);
$learnerid = required_param('learnerid', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($cmid);


$submission = submission::get_submission_for_learner_or_null($cm->instance, $learnerid);

$submission->delete();
?>