<?php
/** @noinspection PhpUnused */
/*
 * Copyright (C) 2015 onwards Catalyst IT
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
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Library of interface functions and constants for module observation
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the observation specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 */

require_once('classes/observation.php');

use mod_observation\observation;
use \mod_observation\observation_base;

defined('MOODLE_INTERNAL') || die();

define('OBSERVATION', 'observation');
define('OBSERVATION_MODULE', 'mod_observation');
define('OBSERVATION_MODULE_PATH', '/mod/converse/');

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function observation_supports($feature)
{
    switch ($feature)
    {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GROUPS:
            // case FEATURE_GRADE_HAS_GRADE:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the observation into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass                 $observation Submitted data from the form in mod_form.php
 * @param mod_observation_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted observation record
 * @throws dml_exception
 * @throws coding_exception
 */
function observation_add_instance(stdClass $observation, mod_observation_mod_form $mform = null)
{
    global $USER;
    
    $data = (array)$observation;
    $now        = time();
    $base = new  observation_base();

    $base->set($base::COL_COURSE, $data[$base::COL_COURSE]);
    $base->set($base::COL_NAME, $data[$base::COL_NAME]);
    $base->set($base::COL_INTRO, $data[$base::COL_INTRO]);
    $base->set($base::COL_INTROFORMAT, $data[$base::COL_INTROFORMAT]);

    $base->set($base::COL_TIMEOPEN, $data[$base::COL_TIMEOPEN]);
    $base->set($base::COL_TIMECLOSE, $data[$base::COL_TIMECLOSE]);

    $base->set($base::COL_TIMECREATED, $now);
    $base->set($base::COL_TIMEMODIFIED, $now);
    $base->set($base::COL_LASTMODIFIEDBY, $USER->id);

    $base->set($base::COL_DEF_I_TASK_OBSERVER, $data[$base::COL_DEF_I_TASK_OBSERVER]['text']);
    $base->set($base::COL_DEF_I_TASK_OBSERVER_FORMAT, $data[$base::COL_DEF_I_TASK_OBSERVER]['format']);
    $base->set($base::COL_DEF_I_TASK_ASSESSOR, $data[$base::COL_DEF_I_TASK_ASSESSOR]['text']);
    $base->set($base::COL_DEF_I_TASK_ASSESSOR_FORMAT, $data[$base::COL_DEF_I_TASK_ASSESSOR]['format']);
    $base->set($base::COL_DEF_I_ASS_OBS_LEARNER, $data[$base::COL_DEF_I_ASS_OBS_LEARNER]['text']);
    $base->set($base::COL_DEF_I_ASS_OBS_LEARNER_FORMAT, $data[$base::COL_DEF_I_ASS_OBS_LEARNER]['format']);
    $base->set($base::COL_DEF_I_ASS_OBS_OBSERVER, $data[$base::COL_DEF_I_ASS_OBS_OBSERVER]['text']);
    $base->set($base::COL_DEF_I_ASS_OBS_OBSERVER_FORMAT, $data[$base::COL_DEF_I_ASS_OBS_OBSERVER]['format']);

    $base->set($base::COL_COMPLETION_TASKS, $data[$base::COL_COMPLETION_TASKS]);

    $base->set($base::COL_DELETED, 0);

    $base->create();

    return $base->get_id_or_null();//todo test if create() updated object reference
}

/**
 * Updates an instance of the observation in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass                 $observation An object from the form in mod_form.php
 * @param mod_observation_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 * @throws dml_exception
 * @throws coding_exception
 */
function observation_update_instance(stdClass $observation, mod_observation_mod_form $mform = null)
{
    global $USER;

    $observation = new  observation_base($observation);
    $observation->set( observation_base::COL_TIMEMODIFIED, time());
    $observation->set( observation_base::COL_LASTMODIFIEDBY, $USER->id);
    // $observation->id             = $observation->instance; // ?!

    // You may have to add extra stuff in here.

    $observation->update();

    return true;
}

/**
 * Removes an instance of the observation from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 * @throws coding_exception
 * @throws dml_exception
 */
function observation_delete_instance($id)
{
    try
    {
        $observation = new  observation_base($id);
    }
    catch (dml_missing_record_exception $ex)
    {
        return false;
    }

    // $transaction = $DB->start_delegated_transaction();

    // Normally, this is where all data related to activity instance is deleted,
    // however, we will simply mark the instance as deleted instead.
    $observation->delete();

    // $transaction->allow_commit();

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass         $course The course record
 * @param stdClass         $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass         $observation The observation instance record
 * @return stdClass|null
 */
function observation_user_outline($course, $user, $mod, $observation) // TODO: User outline?
{
    $return       = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info  $mod course module info
 * @param stdClass $observation the module instance record
 */
function observation_user_complete($course, $user, $mod, $observation)
{
}

/**
 * Obtains the specific requirements for completion.
 *
 * @param object $cm Course-module
 * @return array Requirements for completion
 * @throws coding_exception
 * @throws dml_exception
 */
function observation_get_completion_requirements($cm)
{
    $observation = new  observation_base($cm->instance);

    $result = array();

    if ($observation->get( observation_base::COL_COMPLETION_TASKS))
    {
        $result[] = get_string('completion_tasks', 'observation');
    }

    return $result;
}

/**
 * Obtains the completion progress.
 *
 * @param object $cm Course-module
 * @param int    $userid User ID
 * @return array The current status of completion for the user
 * @throws coding_exception
 * @throws dml_exception
 */
function observation_get_completion_progress($cm, $userid)
{
    // Get observation details.
    $observation = new  observation_base($cm->instance);

    $result = array();

    if ($observation->get( observation_base::COL_COMPLETION_TASKS))
    {
        if ($observation->is_activity_complete())
        {
            $result[] = get_string('completion_tasks', 'observation');
        }
    }

    return $result;
}


/**
 * Obtains the automatic completion state for this observation activity based on any conditions
 * in observation settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int    $userid User ID
 * @param bool   $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 * @throws dml_exception
 * @throws coding_exception
 * @throws ReflectionException
 */
function observation_get_completion_state($course, $cm, $userid, $type)
{
    // Get observation.
    $observation = new  observation($cm);

    // This means that if only view is required we don't end up with a false state.
    if (empty($observation->get( observation_base::COL_COMPLETION_TASKS)))
    {
        return $type;
    }

    return $observation->is_activity_complete($userid);

}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link observation_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int   $index the index in the $activities to use for the next record
 * @param int   $timestart append activity since this time
 * @param int   $courseid the id of the course we produce the report for
 * @param int   $cmid course module id
 * @param int   $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int   $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function observation_get_recent_mod_activity(
    &$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0)
{
}

/**
 * Prints single activity item prepared by {@link observation_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int      $courseid the id of the course we produce the report for
 * @param bool     $detail print detailed report
 * @param array    $modnames as returned by {@link get_module_types_names()}
 * @param bool     $viewfullnames display users' full names
 */
function observation_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames)
{
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * @return boolean
 */
function observation_cron()
{
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function observation_get_extra_capabilities()
{
    return [];
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 * @throws coding_exception
 */
function observation_get_file_areas($course, $cm, $context)
{
    $areas = [];

    $areas[observation_base::FILE_AREA_TRAINEE]  = get_string( observation_base::FILE_AREA_TRAINEE, OBSERVATION);
    $areas[observation_base::FILE_AREA_OBSERVER] = get_string( observation_base::FILE_AREA_OBSERVER, OBSERVATION);
    $areas[observation_base::FILE_AREA_ASSESSOR] = get_string( observation_base::FILE_AREA_ASSESSOR, OBSERVATION);

    return $areas;
}

/**
 * File browsing support for observation file areas
 *
 * @param file_browser $browser
 * @param array        $areas
 * @param stdClass     $course
 * @param stdClass     $cm
 * @param context      $context
 * @param string       $file_area
 * @param int          $itemid
 * @param string       $file_path
 * @param string       $file_name
 * @return file_info instance or null if not found
 * @throws coding_exception
 * @package mod_observation
 * @category files
 *
 */
function observation_get_file_info(
    $browser, $areas, $course, $cm, $context, $file_area, $itemid, $file_path, $file_name)
{
    global $CFG;
    require_once($CFG->dirroot . OBSERVATION_MODULE_PATH . 'locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE)
    {
        return null;
    }

    $url_base  = $CFG->wwwroot . '/pluginfile.php';
    $fs        = get_file_storage();
    $file_path = is_null($file_path)
        ? '/'
        : $file_path;
    $file_name = is_null($file_name)
        ? '.'
        : $file_name;

    if ($file_area ===  observation_base::FILE_AREA_INTRO)
    {
        if (!has_capability('moodle/course:managefiles', $context))
        {
            // Students not allowed
            return null;
        }

        if (!($stored_file = $fs->get_file($context->id, 'mod_converse', $file_area, 0, $file_path, $file_name)))
        {
            return null;
        }

        return new file_info_stored(
            $browser, $context, $stored_file, $url_base, $file_area, $itemid, true, true, false);
    }
    else
    {
        return null;
    }
}

/**
 * Serves the files from the observation file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param context  $context the observation's context
 * @param string   $filearea the name of the file area
 * @param array    $args extra arguments (itemid, path)
 * @param bool     $forcedownload whether or not force download
 * @param array    $options additional options affecting the file serving
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @category files
 *
 * @package mod_observation
 */
function observation_pluginfile(
    $course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array())
{
    global $USER;

    if ($context->contextlevel != CONTEXT_MODULE)
    {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    $user_id = $args[0];
    if (!(has_capability( observation_base::CAP_MANAGE, $context) || $user_id == $USER->id))
    {
        // Only evaluators and/or owners have access to files
        return false;
    }

    $fs            = get_file_storage();
    $relative_path = implode('/', $args);
    $full_path     = "/$context->id/" . OBSERVATION_MODULE . "/$filearea/$relative_path";
    if ((!$file = $fs->get_file_by_hash(sha1($full_path))) || $file->is_directory())
    {
        send_file_not_found();
        return false;
    }
    else
    {
        // finally send the file
        send_stored_file($file, null, 0, $forcedownload, $options);
        return true;
    }
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding observation nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the observation module instance
 * @param stdClass        $course current course record
 * @param stdClass        $module current observation instance record
 * @param cm_info         $cm course module information
 */
function observation_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm)
{
    // $context = context_module::instance($cm->id);
    // if (has_capability(observation::CAP_ASSESS, $context))
    // {
    //     $link = new moodle_url('/mod/observation/report.php', array('cmid' => $cm->id));
    //     $node = $navref->add(get_string('evaluatestudents', 'observation'), $link, navigation_node::TYPE_SETTING);
    //     $node->mainnavonly = true;
    // }
}

/**
 * Extends the settings navigation with the observation settings
 *
 * This function is called when the context for the page is a observation module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node     $observationnode observation administration node
 */
function observation_extend_settings_navigation(
    settings_navigation $settingsnav, navigation_node $observationnode = null)
{
    // global $PAGE;
    //
    // if (has_capability('', $PAGE->cm->context))
    // {
    //     $link = new moodle_url('/mod/observation/report.php', array('cmid' => $PAGE->cm->id));
    //     $node = navigation_node::create(
    //         get_string('', OBSERVATION),
    //         new moodle_url('/mod/observation/report.php', array('cmid' => $PAGE->cm->id)),
    //         navigation_node::TYPE_SETTING,
    //         null,
    //         'mod_observation_evaluate',
    //         new pix_icon('i/valid', ''));
    //     $observationnode->add_node($node);
    // }
}
