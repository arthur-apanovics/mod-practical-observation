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

class observation extends db_model_base
{
    // DATABASE CONSTANTS:

    public const TABLE = 'observation';

    public const COL_COURSE                        = 'course';
    public const COL_NAME                          = 'name';
    public const COL_INTRO                         = 'intro';
    public const COL_INTRO_FORMAT                  = 'intro_format';
    public const COL_TIMEOPEN                      = 'timeopen';
    public const COL_TIMECLOSE                     = 'timeclose';
    public const COL_TIMECREATED                   = 'timecreated';
    public const COL_TIMEMODIFIED                  = 'timemodified';
    public const COL_LASTMODIFIEDBY                = 'lastmodifiedby';
    public const COL_DELETED                       = 'deleted';
    public const COL_DEFAULT_INTRO_OBSERVER        = 'default_intro_observer';
    public const COL_DEFAULT_INTRO_OBSERVER_FORMAT = 'default_intro_observer_format';
    public const COL_DEFAULT_INTRO_ASSIGN          = 'default_intro_assign';
    public const COL_DEFAULT_INTRO_ASSIGN_FORMAT   = 'default_intro_assign_format';
    public const COL_COMPLETIONTOPICS              = 'completiontopics';

    // ACTIVITY CONSTANTS:

    public const CAP_ADDINSTANCE     = 'mod/observation:addinstance';
    public const CAP_VIEW            = 'mod/observation:view';
    public const CAP_SUBMIT          = 'mod/observation:submit';
    public const CAP_VIEWSUBMISSIONS = 'mod/observation:viewsubmissions';
    public const CAP_ASSESS          = 'mod/observation:assess';
    public const CAP_MANAGE          = 'mod/observation:manage';

    public const FILE_AREA_INTRO    = 'observation_intro';
    public const FILE_AREA_TRAINEE  = 'learner_attachments';
    public const FILE_AREA_OBSERVER = 'observer_attachments';
    public const FILE_AREA_ASSESSOR = 'assessor_attachments';

    /**
     * @var int
     */
    protected $course; // fk course
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $intro;
    /**
     * @var int
     */
    protected $intro_format;
    /**
     * @var int
     */
    protected $timeopen;
    /**
     * @var int
     */
    protected $timeclose;
    /**
     * @var int
     */
    protected $timecreated;
    /**
     * @var int
     */
    protected $timemodified;
    /**
     * @var int
     */
    protected $lastmodifiedby; // fk user
    /**
     * @var bool
     */
    protected $deleted;
    /**
     * @var string
     */
    protected $default_intro_observer;
    /**
     * @var int
     */
    protected $default_intro_observer_format;
    /**
     * @var string
     */
    protected $default_intro_assign;
    /**
     * @var int
     */
    protected $default_intro_assign_format;
    /**
     * @var bool
     */
    protected $completiontopics;


    /**
     * Marks as deleted and updates db record
     *
     * @return observation|bool
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function delete()
    {
        $this->deleted = true;

        return $this->update();
    }

    /**
     * Checks if all criteria for completing this observation are complete
     *
     * @return bool
     */
    public function is_activity_complete(): bool // TODO perform completion check
    {
        throw new \coding_exception(__METHOD__ . ' not defined');
    }
}