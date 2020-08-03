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

/**
 * Define all the restore steps that will be used by the restore_observation_activity_task
 */

use mod_observation\assessor_feedback;
use mod_observation\assessor_task_submission;
use mod_observation\criteria;
use mod_observation\learner_attempt;
use mod_observation\learner_task_submission;
use mod_observation\observation;
use mod_observation\observer;
use mod_observation\observer_assignment;
use mod_observation\observer_feedback;
use mod_observation\observer_task_submission;
use mod_observation\submission;
use mod_observation\task;

global $CFG;
require_once($CFG->dirroot . '/mod/observation/locallib.php');

/**
 * Structure step to restore one observation activity
 */
class restore_observation_activity_structure_step extends restore_activity_structure_step
{
    protected function define_structure()
    {
        $paths = array();
        $base_path = '/activity/observation';

        $paths[] = new restore_path_element(observation::TABLE, $base_path);

        $path = "{$base_path}/tasks/task";
        $paths[] = new restore_path_element(task::TABLE, $path);

        $path = "{$path}/criterias/criteria";
        $paths[] = new restore_path_element(criteria::TABLE, $path);

        if ($this->get_setting_value('userinfo'))
        {
            $path = "{$base_path}/observers/observer";
            $paths[] = new restore_path_element(observer::TABLE, $path);

            $path = "{$base_path}/submissions/submission";
            $paths[] = new restore_path_element(submission::TABLE, $path);

            $path_learner_task_sub = "{$path}/learner_task_submissions/learner_task_submission";
            $path = "{$path}/learner_task_submissions/learner_task_submission";
            $paths[] = new restore_path_element(learner_task_submission::TABLE, $path);

            $path = "{$path}/assessor_task_submissions/assessor_task_submission";
            $paths[] = new restore_path_element(assessor_task_submission::TABLE, $path);

            $path = "{$path_learner_task_sub}/learner_attempts/learner_attempt";
            $paths[] = new restore_path_element(learner_attempt::TABLE, $path);

            $path = "{$path}/assessor_feedbacks/assessor_feedback";
            $paths[] = new restore_path_element(assessor_feedback::TABLE, $path);

            $path = "{$path_learner_task_sub}/observer_assignments/observer_assignment";
            $paths[] = new restore_path_element(observer_assignment::TABLE, $path);

            $path = "{$path}/observer_task_submissions/observer_task_submission";
            $paths[] = new restore_path_element(observer_task_submission::TABLE, $path);

            $path = "{$path}/observer_feedbacks/observer_feedback";
            $paths[] = new restore_path_element(observer_feedback::TABLE, $path);
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function after_execute()
    {
        global $DB;

        // Add observation related files, no need to match by itemname (just internally handled context)
        $this->add_related_files(OBSERVATION_MODULE, observation::FILE_AREA_INTRO, null);
        // $this->add_related_files(OBSERVATION_MODULE, observation::FILE_AREA_GENERAL, null);
    }

    protected function process_observation($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the observation record
        $newitemid = $DB->insert_record(observation::TABLE, $data);

        $this->add_related_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_INTRO,
            observation::TABLE,
            $this->get_task()->get_old_contextid(),
            $oldid);

        // adds all task intro files that have a custom mapping
        $this->add_related_files(OBSERVATION_MODULE, observation::FILE_AREA_GENERAL, null);

        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_observation_task($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{task::COL_OBSERVATIONID} = $this->get_new_parentid(\OBSERVATION);

        $newitemid = $DB->insert_record(task::TABLE, $data);
        $this->set_mapping(task::TABLE, $oldid, $newitemid, true);

        // rewrite file itemid's as we use some prefixes in file itemid's to identify type of intro
        $contextid = $this->get_task()->get_contextid();
        $sql = "SELECT *
                FROM {files}
                WHERE contextid = :contextid
                AND component = :component
                AND filearea = :filearea
                AND itemid REGEXP :rgex";
        $files = $DB->get_records_sql(
            $sql,
            [
                'contextid' => $contextid,
                'component' => OBSERVATION_MODULE,
                'filearea' => observation::FILE_AREA_GENERAL,
                'rgex' => sprintf('^[0-9]%s$', $oldid)
            ]);
        foreach ($files as $file)
        {
            // prefix + taskid
            $prefix = substr($file->itemid, 0, 1);
            $new_file_itemid = (int) sprintf('%d%d', $prefix, $newitemid);
            $file->itemid = $new_file_itemid;
            // we MUST update the path hash or files will not be served later on
            $file->pathnamehash = file_storage::get_pathname_hash(
                $contextid,
                OBSERVATION_MODULE,
                observation::FILE_AREA_GENERAL,
                $file->itemid,
                $file->filepath,
                $file->filename);

            $DB->update_record('files', $file);
        }
    }

    protected function process_observation_criteria($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{criteria::COL_TASKID} = $this->get_new_parentid(task::TABLE);

        $newitemid = $DB->insert_record(criteria::TABLE, $data);
        $this->set_mapping(criteria::TABLE, $oldid, $newitemid, true);

        $this->add_related_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_GENERAL,
            criteria::TABLE,
            $this->get_task()->get_old_contextid(),
            $oldid);
    }

    protected function process_observation_submission($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{submission::COL_OBSERVATIONID} = $this->get_new_parentid(observation::TABLE);
        $data->{submission::COL_USERID} = $this->get_mappingid('user', $data->{submission::COL_USERID});

        $newitemid = $DB->insert_record(submission::TABLE, $data);
        $this->set_mapping(submission::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_learner_task_submission($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->{learner_task_submission::COL_SUBMISISONID} = $this->get_new_parentid(submission::TABLE);

        $data->{learner_task_submission::COL_TASKID} =
            $this->get_mappingid(task::TABLE, $data->{learner_task_submission::COL_TASKID});

        $data->{learner_task_submission::COL_USERID} =
            $this->get_mappingid('user', $data->{learner_task_submission::COL_USERID});

        $newitemid = $DB->insert_record(learner_task_submission::TABLE, $data);
        $this->set_mapping(learner_task_submission::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_learner_attempt($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{learner_attempt::COL_LEARNER_TASK_SUBMISSIONID} =
            $this->get_new_parentid(learner_task_submission::TABLE);

        $newitemid = $DB->insert_record(learner_attempt::TABLE, $data);
        $this->set_mapping(learner_attempt::TABLE, $oldid, $newitemid, true);

        $this->add_related_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_TRAINEE,
            learner_attempt::TABLE,
            $this->get_task()->get_old_contextid(),
            $oldid);
    }

    protected function process_observation_observer($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{observer::COL_ADDED_BY} = $this->get_mappingid('user', $data->{observer::COL_ADDED_BY});
        $data->{observer::COL_MODIFIED_BY} = $this->get_mappingid('user', $data->{observer::COL_MODIFIED_BY});

        $params = [
            observer::COL_FULLNAME       => $data->{observer::COL_FULLNAME},
            observer::COL_PHONE          => $data->{observer::COL_PHONE},
            observer::COL_EMAIL          => $data->{observer::COL_EMAIL},
            observer::COL_POSITION_TITLE => $data->{observer::COL_POSITION_TITLE},
        ];
        // skip observer if already exists
        if (!$newitemid = $DB->get_field(observer::TABLE, 'id', $params))
        {
            $newitemid = $DB->insert_record(observer::TABLE, $data);
        }

        $this->set_mapping(observer::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_observer_assignment($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->{observer_assignment::COL_LEARNER_TASK_SUBMISSIONID} =
            $this->get_new_parentid(learner_task_submission::TABLE);

        $data->{observer_assignment::COL_OBSERVERID} =
            $this->get_mappingid(observer::TABLE, $data->{observer_assignment::COL_OBSERVERID});

        $newitemid = $DB->insert_record(observer_assignment::TABLE, $data);
        $this->set_mapping(observer_assignment::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_observer_task_submission($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{observer_task_submission::COL_OBSERVER_ASSIGNMENTID} =
            $this->get_new_parentid(observer_assignment::TABLE);

        $newitemid = $DB->insert_record(observer_task_submission::TABLE, $data);
        $this->set_mapping(observer_task_submission::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_observer_feedback($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->{observer_feedback::COL_OBSERVER_SUBMISSIONID} = $this->get_new_parentid(submission::TABLE);

        $data->{observer_feedback::COL_ATTEMPTID} =
            $this->get_mappingid(learner_attempt::TABLE, $data->{observer_feedback::COL_ATTEMPTID});

        $data->{observer_feedback::COL_CRITERIAID} =
            $this->get_mappingid(criteria::TABLE, $data->{observer_feedback::COL_CRITERIAID});

        $newitemid = $DB->insert_record(observer_feedback::TABLE, $data);
        $this->set_mapping(observer_feedback::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_assessor_task_submission($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->{assessor_task_submission::COL_ASSESSORID} =
            $this->get_mappingid('user', $data->{assessor_task_submission::COL_ASSESSORID});
        $data->{assessor_task_submission::COL_LEARNER_TASK_SUBMISSIONID} =
            $this->get_new_parentid(learner_task_submission::TABLE);

        $newitemid = $DB->insert_record(assessor_task_submission::TABLE, $data);
        $this->set_mapping(assessor_task_submission::TABLE, $oldid, $newitemid);
    }

    protected function process_observation_assessor_feedback($data)
    {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->{assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID} = $this->get_mappingid(
            assessor_task_submission::TABLE,
            $data->{assessor_feedback::COL_ASSESSOR_TASK_SUBMISSIONID});

        $data->{assessor_feedback::COL_ATTEMPTID} = $this->get_new_parentid(learner_attempt::TABLE);

        $newitemid = $DB->insert_record(assessor_feedback::TABLE, $data);
        $this->set_mapping(assessor_feedback::TABLE, $oldid, $newitemid);

        $this->add_related_files(
            OBSERVATION_MODULE,
            observation::FILE_AREA_ASSESSOR,
            assessor_feedback::TABLE,
            $this->get_task()->get_old_contextid(),
            $oldid);
    }
}
