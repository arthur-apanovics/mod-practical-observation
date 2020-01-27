<?php
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

namespace mod_observation;

use mod_observation;

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
}

class learner_attempt extends learner_attempt_base
{
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);
    }
}
