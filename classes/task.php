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

class task extends db_model_base
{
    public const TABLE = OBSERVATION . '_task';

    public const COL_OBSERVATION           = 'observation';
    public const COL_INTRO_LEARNER         = 'intro_learner';
    public const COL_INTRO_LEARNER_FORMAT  = 'intro_learner_format';
    public const COL_INTRO_OBSERVER        = 'intro_observer';
    public const COL_INTRO_OBSERVER_FORMAT = 'intro_observer_format';
    public const COL_INTRO_ASSESSOR        = 'intro_assessor';
    public const COL_INTRO_ASSESSOR_FORMAT = 'intro_assessor_format';

    /**
     * @var int
     */
    protected $observation;
    /**
     * @var string
     */
    protected $intro_learner;
    /**
     * @var string
     */
    protected $intro_observer;
    /**
     * @var string
     */
    protected $intro_assessor;
}