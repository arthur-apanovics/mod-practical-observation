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

namespace mod_observation\db_model\obsolete;

use mod_observation\db_model\db_model_base;

class learner_attempt_model extends db_model_base
{
    protected const TABLE = 'learner_attempt';

    /**
     * @var int
     */
    protected $learner_submission;
    /**
     * @var bigint
     */
    protected $timestarted;
    /**
     * @var bigint
     */
    protected $timesubmitted;
    /**
     * @var longtext
     */
    protected $text;
    /**
     * @var int2
     */
    protected $text_format;
    /**
     * attempt number in order of sequence.
     * todo: redundant?
     *
     * @var int
     */
    protected $attempt_number;
}