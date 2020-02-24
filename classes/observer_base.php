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

class observer_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer';

    public const COL_FULLNAME       = 'fullname';
    public const COL_PHONE          = 'phone';
    public const COL_EMAIL          = 'email';
    public const COL_POSITION_TITLE = 'position_title';
    public const COL_ADDED_BY       = 'added_by';
    public const COL_TIMEADDED      = 'timeadded';
    public const COL_MODIFIED_BY    = 'modified_by';
    public const COL_TIMEMODIFIED   = 'timemodified';

    /**
     * @var string
     */
    protected $fullname;
    /**
     * @var string
     */
    protected $phone;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $position_title;
    /**
     * User id of learner that added this observer into system
     *
     * @var int
     */
    protected $added_by;
    /**
     * @var int
     */
    protected $timeadded;
    /**
     * Id of user that last modified observer record
     *
     * @var int
     */
    protected $modified_by;
    /**
     * @var int
     */
    protected $timemodified;
}
