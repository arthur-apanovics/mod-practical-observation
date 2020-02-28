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

class learner_attempt_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_learner_attempt';

    public const COL_LEARNER_SUBMISSIONID = 'learner_submissionid';
    public const COL_TIMESTARTED          = 'timestarted';
    public const COL_TIMESUBMITTED        = 'timesubmitted';
    public const COL_TEXT                 = 'text';
    public const COL_TEXT_FORMAT          = 'text_format';
    public const COL_ATTEMPT_NUMBER       = 'attempt_number';

    /**
     * @var int
     */
    protected $learner_submissionid;
    /**
     * @var int
     */
    protected $timestarted;
    /**
     * @var int
     */
    protected $timesubmitted;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var int
     */
    protected $text_format;
    /**
     * attempt number in order of sequence.
     *
     * @var int
     */
    protected $attempt_number;

    public function get_next_attemptnumber_in_submission(): int
    {
        return $this->get_last_attemptnumber_in_submission() + 1;
    }

    /**
     * @return int 0 if no attempts found
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_last_attemptnumber_in_submission(): int
    {
        global $DB;

        if (empty($this->learner_submissionid))
        {
            throw new \coding_exception('submission id missing or un-initialized class instance');
        }

        $sql = 'SELECT max(' . self::COL_ATTEMPT_NUMBER . ')
                FROM {' . self::TABLE . '} a
                WHERE ' . self::COL_LEARNER_SUBMISSIONID . ' = :learner_submissionid';
        $num = $DB->get_field_sql($sql, ['learner_submissionid' => $this->learner_submissionid]);

        return $num != false ? $num : 0;
    }

    public function get_moodle_form_data()
    {
        return [
            self::COL_LEARNER_SUBMISSIONID => $this->learner_submissionid,
            self::COL_TIMESTARTED          => $this->timestarted,
            self::COL_TIMESUBMITTED        => $this->timesubmitted,
            self::COL_TEXT                 => [
                'text'   => $this->text,
                'format' => $this->text_format
            ],
            self::COL_ATTEMPT_NUMBER       => $this->attempt_number,
        ];
    }
}
