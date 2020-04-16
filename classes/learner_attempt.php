<?php
/*
 * Copyright (C) 2020 onwards Like-Minded Learning
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
 * @author  Arthur Apanovics <arthur.a@likeminded.co.nz>
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observation;

use coding_exception;
use context_module;
use core_user;
use dml_exception;
use mod_observation\interfaces\templateable;
use stored_file;

class learner_attempt extends learner_attempt_base implements templateable
{
    /**
     * @var learner_task_submission_base
     */
    private $learner_task_submission_base;

    public function __construct($id_or_record, learner_task_submission_base $submission_base = null)
    {
        parent::__construct($id_or_record);

        if ($submission_base)
        {
            $this->learner_task_submission_base = $submission_base;
        }
        else
        {
            $this->get_learner_task_submission_base();
        }
    }

    public function get_learner_task_submission_base()
    {
        if (is_null($this->learner_task_submission_base))
        {
            $this->learner_task_submission_base = learner_task_submission_base::read_by_condition_or_null(
                [learner_task_submission_base::COL_ID => $this->learner_task_submissionid], true);
        }

        return $this->learner_task_submission_base;
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        return [
            self::COL_ID             => $this->id,
            self::COL_TIMESTARTED    => userdate($this->timestarted),
            self::COL_TIMESUBMITTED  => $this->timesubmitted != 0 ? userdate($this->timesubmitted) : null,
            self::COL_TEXT           => format_text($this->text, FORMAT_HTML),
            self::COL_ATTEMPT_NUMBER => $this->attempt_number,

            'name'  => fullname(
                core_user::get_user($this->learner_task_submission_base->get_userid())),
            'files' => lib::get_downloads_from_stored_files($this->get_attached_files())
        ];
    }

    /**
     * @return stored_file[]
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function get_attached_files(): array
    {
        $context = context_module::instance($this->get_cmid());
        $fs = get_file_storage();

        return $fs->get_area_files(
            $context->id, OBSERVATION_MODULE, observation::FILE_AREA_TRAINEE, $this->id, null, false);
    }

    /**
     * Fetches cmid with a DB query
     *
     * @return int
     * @throws dml_exception
     */
    private function get_cmid(): int // do not allow 'false'
    {
        global $DB;

        $sql = 'SELECT cm.id
                FROM {course_modules} cm
                WHERE cm.module = (SELECT id FROM {modules} WHERE name = \'' . OBSERVATION . '\')
                AND instance =
                    (SELECT o.id
                     FROM {' . observation::TABLE . '} o
                        JOIN {' . task::TABLE . '} t ON t.' . task::COL_OBSERVATIONID . ' = o.id
                        JOIN {' . learner_task_submission::TABLE . '} ls ON ls.' . learner_task_submission::COL_TASKID . ' = t.id
                    WHERE ls.id = ?)';

        return $DB->get_field_sql($sql, [$this->learner_task_submission_base->get_id_or_null()], MUST_EXIST);
    }
}
