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
use mod_observation\observation;
use mod_observation\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot
    . '/mod/observation/backup/moodle2/restore_observation_stepslib.php'); // Because it exists (must)

/**
 * observation restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_observation_activity_task extends restore_activity_task
{

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course()
    {
        $rules = array();

        // Fix old wrong uses (missing extension)
        $rules[] = new restore_log_rule(
            'observation', 'view all', 'index?id={course}', null, null, null, 'index.php?id={course}');

        return $rules;
    }

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings()
    {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps()
    {
        // observation only has one structure step
        $this->add_step(new restore_observation_activity_structure_step('observation_structure', 'observation.xml'));
        // todo: define file post-restore step?
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents()
    {
        $contents = array();

        $contents[] = new restore_decode_content(
            observation::TABLE, [
            observation::COL_INTRO,
            observation::COL_DEF_I_TASK_LEARNER,
            observation::COL_DEF_I_TASK_OBSERVER,
            observation::COL_DEF_I_TASK_ASSESSOR,
            observation::COL_DEF_I_ASS_OBS_LEARNER,
            observation::COL_DEF_I_ASS_OBS_OBSERVER,
        ], null);

        $contents[] = new restore_decode_content(
            task::TABLE, [
            task::COL_INTRO_LEARNER,
            task::COL_INTRO_OBSERVER,
            task::COL_INTRO_ASSESSOR,
            task::COL_INT_ASSIGN_OBS_LEARNER,
            task::COL_INT_ASSIGN_OBS_OBSERVER,
        ], null);

        $contents[] = new restore_decode_content(
            criteria::TABLE, [
            criteria::COL_DESCRIPTION
        ], 'id');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules()
    {
        $rules = array();
        $rules[] = new restore_decode_rule('OBSERVATIONVIEWBYID', '/mod/observation/view.php?id=$1', 'course_module');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * observation logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules()
    {
        $rules = array();
        $rules[] = new restore_log_rule('observation', 'add', 'view.php?id={course_module}', '{observation}');
        $rules[] = new restore_log_rule('observation', 'update', 'view.php?id={course_module}', '{observation}');
        $rules[] = new restore_log_rule('observation', 'view', 'view.php?id={course_module}', '{observation}');

        return $rules;
    }
}
