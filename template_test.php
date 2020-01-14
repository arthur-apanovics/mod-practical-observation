<?php

use mod_observation\event\course_module_viewed;
use mod_observation\db_model\obsolete\observation;
use mod_observation\user_observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/mod/observation/lib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login($PAGE->course, false);

// Print the page header.
$PAGE->set_url('/mod/observation/' . __FILE__);
$PAGE->set_title(format_string('Template test page'));
$PAGE->set_heading(format_string('Template test'));

// Output starts here.
/* @var $renderer mod_observation_renderer */
$renderer = $PAGE->get_renderer('observation');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Template test'));

// template name must include component name, e.g. 'component/template_name'
$templatename = 'observation/topic_item_conversation';
// declare any data your template might need here
$context_data = [];
// this method renders the template via mustache engine
echo $renderer->render_from_template($templatename, $context_data);

// Finish the page.
echo $OUTPUT->footer();
