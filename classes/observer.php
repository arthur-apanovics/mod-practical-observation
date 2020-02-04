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

use core_user;
use mod_observation\interfaces\templateable;

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

class observer extends observer_base implements templateable
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
        $added_by  = fullname(core_user::get_user($this->added_by));
        $timeadded = usertime($this->timeadded);

        $modified_by  = null;
        $timemodified = null;
        if (!is_null($this->modified_by) && !is_null($this->timemodified))
        {
            $modified_by  = fullname(core_user::get_user($this->modified_by));
            $timemodified = usertime($this->timemodified);
        }

        return [
            self::COL_ID             => $this->id,
            self::COL_FULLNAME       => $this->fullname,
            self::COL_PHONE          => $this->phone,
            self::COL_EMAIL          => $this->email,
            self::COL_POSITION_TITLE => $this->position_title,

            self::COL_ADDED_BY     => $added_by,
            self::COL_TIMEADDED    => $timeadded,
            self::COL_MODIFIED_BY  => $modified_by,
            self::COL_TIMEMODIFIED => $timemodified,
        ];
    }
}
