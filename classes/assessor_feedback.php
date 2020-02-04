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

use mod_observation\interfaces\templateable;

class assessor_feedback_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_assessor_feedback';

    public const COL_ASSESSOR_SUBMISSIONID = 'assessor_submissionid';
    public const COL_TEXT                  = 'text';
    public const COL_TEXT_FORMAT           = 'text_format';
    public const COL_TIMESUBMITTED         = 'timesubmitted';

    /**
     * @var int
     */
    protected $assessor_submissionid;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var int
     */
    protected $text_format;
    /**
     * @var int
     */
    protected $timesubmitted;
}

class assessor_feedback extends assessor_feedback_base implements templateable
{
    public function __construct($id_or_record)
    {
        parent::__construct($id_or_record);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        return [
            self::COL_ID            => $this->id,
            self::COL_TEXT          => $this->text,
            self::COL_TIMESUBMITTED => usertime($this->timesubmitted),
        ];
    }
}