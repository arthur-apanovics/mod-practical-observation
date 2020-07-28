<?php

use core\output\notification;
use mod_observation\submission;

require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once 'lib.php';
require_once 'forms.php';

$cmid = required_param('cmid', PARAM_INT);
$learnerid = required_param('learnerid', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($cmid);

require_login($course, false, $cm);
if (!is_siteadmin($USER->id)) {
    throw new coding_exception('User needs to be a Site Administrtor to access this feature.');
}

$submission = submission::get_submission_for_learner_or_null($cm->instance, $learnerid);

$submission->delete();

redirect(
    new moodle_url(OBSERVATION_MODULE_PATH.'view.php', ['id' => $cmid]),
    get_string('notification:submission_deleted', 'observation'),
    null,
    notification::NOTIFY_SUCCESS
);
?>