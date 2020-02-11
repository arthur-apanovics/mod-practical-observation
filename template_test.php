<?php

use mod_observation\event\course_module_viewed;
use mod_observation\observation_base;
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

$PAGE->requires->js_call_amd(OBSERVATION_MODULE . '/developer_view', 'init', ['test', 'param', 123]);
$PAGE->requires->css('/mod/observation/styles_temp.css');
$PAGE->requires->js('/mod/observation/js/observer.js');


echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Template test'));

// template name must include component name, e.g. 'component/template_name'
//$templatename = 'observation/activity_view';
//$templatename = 'observation/task_observer_view';
//$templatename = 'observation/task_view';
//$templatename = 'observation/change_observer_view';
$templatename = 'observation/comp_observation_activity_task_list';

// declare any data your template might need here
$context_data = [];
$context_data['log_entries'] = ['value' => 'test value1','value' => 'test value2'];
// this method renders the template via mustache engine
echo $renderer->render_from_template($templatename, $context_data);

// Finish the page.
echo $OUTPUT->footer();
