<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use mod_observation\criteria;
use mod_observation\criteria_base;
use mod_observation\observer;
use mod_observation\observer_assignment;
use mod_observation\task;
use mod_observation\task_base;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once $CFG->libdir . "/externallib.php";
require_once $CFG->dirroot . '/mod/observation/locallib.php';

/**
 * observation external functions
 *
 * @package    mod_observation
 * @category   external
 * @since      Moodle 3.0
 */
class mod_observation_external extends external_api
{
    public static function task_update_sequence($taskid, $newsequence)
    {
        $clean_params = self::validate_parameters(
            self::task_update_sequence_parameters(),
            [
                'taskid'      => $taskid,
                'newsequence' => $newsequence
            ]);

        $task = new task_base($taskid);
        $task->update_sequence_and_save($newsequence);

        return self::clean_returnvalue(
            self::task_update_sequence_returns(),
            [
                'taskid'      => $task->get_id_or_null(),
                'newsequence' => $task->get(task::COL_SEQUENCE)
            ]);
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function task_update_sequence_parameters()
    {
        return new external_function_parameters(
            [
                'taskid'      => new external_value(
                    PARAM_INT, 'task id', true, null, NULL_NOT_ALLOWED),
                'newsequence' => new external_value(
                    PARAM_INT, 'new order for observation task', true, null, NULL_NOT_ALLOWED),
            ]);
    }

    /**
     * Returns description of method return values
     *
     * @return external_single_structure
     */
    public static function task_update_sequence_returns()
    {
        return new external_single_structure(
            [
                'taskid'      => new external_value(PARAM_INT, 'task id', true, null, NULL_NOT_ALLOWED),
                'newsequence' => new external_value(
                    PARAM_INT, 'new order of task', VALUE_DEFAULT, null, NULL_NOT_ALLOWED),
            ]);
    }

    public static function criteria_update_sequence($criteriaid, $newsequence)
    {
        $clean_params = self::validate_parameters(
            self::criteria_update_sequence_parameters(),
            [
                'criteriaid'  => $criteriaid,
                'newsequence' => $newsequence
            ]);

        $criteria = new criteria_base($criteriaid);
        $criteria->update_sequence_and_save($newsequence);

        return self::clean_returnvalue(
            self::criteria_update_sequence_returns(),
            [
                'criteriaid'  => $criteria->get_id_or_null(),
                'newsequence' => $criteria->get(criteria::COL_SEQUENCE)
            ]);
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function criteria_update_sequence_parameters()
    {
        return new external_function_parameters(
            [
                'criteriaid'  => new external_value(
                    PARAM_INT, 'criteria id', true, null, NULL_NOT_ALLOWED),
                'newsequence' => new external_value(
                    PARAM_INT, 'new order for task criteria', true, null, NULL_NOT_ALLOWED),
            ]);
    }

    /**
     * Returns description of method return values
     *
     * @return external_single_structure
     */
    public static function criteria_update_sequence_returns()
    {
        return new external_single_structure(
            [
                'criteriaid'  => new external_value(PARAM_INT, 'criteria id', true, null, NULL_NOT_ALLOWED),
                'newsequence' => new external_value(
                    PARAM_INT, 'new order of criteria', VALUE_DEFAULT, null, NULL_NOT_ALLOWED),
            ]);
    }

    public static function observer_update_details($token, $observerid, $fullname, $phone, $position_title)
    {
        $params = self::validate_parameters(
            self::observer_update_details_parameters(),
            [
                'token'          => $token,
                'observerid'     => $observerid,
                'fullname'       => $fullname,
                'phone'          => $phone,
                'position_title' => $position_title
            ]);

        // validate token
        if (!$observer_assignment = observer_assignment::read_by_token_or_null($token))
        {
            throw new \coding_exception('Invalid token provided when updating observer details');
        }

        $observer_before = $observer_assignment->get_observer();
        $observer_after = observer::update_from_ajax(
            $params['token'],
            $params['observerid'],
            $params['fullname'],
            $params['phone'],
            $params['position_title']
        );

        // trigger event
        $observation = $observer_assignment
            ->get_learner_task_submission_base()
            ->get_submission()
            ->get_observation();
        $event = \mod_observation\event\observer_detailupdate::create(
            [
                'context'  => \context_module::instance($observation->get_cm()->id),
                'objectid' => $observer_after->get_id_or_null(),
                'other'    => [
                    'observer_before' => $observer_before,
                    'observer_after' => $observer_after,
                ]
            ]);
        $event->trigger();

        return self::clean_returnvalue(
            self::observer_update_details_returns(),
            [
                'observerid' => $observer_after->get_id_or_null(),
                'fullname' => $observer_after->get(observer::COL_FULLNAME),
                'phone' => $observer_after->get(observer::COL_PHONE),
                'position_title' => $observer_after->get(observer::COL_POSITION_TITLE)
            ]);
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function observer_update_details_parameters()
    {
        return new external_function_parameters(
            [
                'token'          => new external_value(PARAM_TEXT, 'token from observer assignment', true, null, NULL_NOT_ALLOWED),
                'observerid'     => new external_value(PARAM_INT, 'observer id', true, null, NULL_NOT_ALLOWED),
                'fullname'       => new external_value(PARAM_TEXT, 'name', true, null, NULL_NOT_ALLOWED),
                'phone'          => new external_value(PARAM_TEXT, 'phone number', true, null, NULL_NOT_ALLOWED),
                'position_title' => new external_value(PARAM_TEXT, 'position title', true, null, NULL_NOT_ALLOWED),
            ]);
    }

    /**
     * Returns description of method return values
     *
     * @return external_single_structure
     */
    public static function observer_update_details_returns()
    {
        return new external_single_structure(
            [
                'observerid'     => new external_value(PARAM_INT, 'observer id', true, null, NULL_NOT_ALLOWED),
                'fullname'       => new external_value(PARAM_TEXT, 'name', true, null, NULL_NOT_ALLOWED),
                'phone'          => new external_value(PARAM_TEXT, 'phone number', true, null, NULL_NOT_ALLOWED),
                'position_title' => new external_value(PARAM_TEXT, 'position title', true, null, NULL_NOT_ALLOWED),
            ]);
    }
}
