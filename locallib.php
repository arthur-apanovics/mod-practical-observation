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
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Internal library of functions for module ojt
 *
 * All the ojt specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 */

use mod_ojt\models\ojt;

defined('MOODLE_INTERNAL') || die();


/**
 * Check ojt id when initializing page
 *
 * @param int $cmid
 * @param int $instanceid
 * @return array [mod_ojt\model\ojt, stdClass course, stdClass cm]
 * @throws coding_exception
 * @throws dml_exception
 */
function ojt_check_page_id_params_and_init(int $cmid, int $instanceid){
    global $DB;

    if ($cmid)
    {
        $cm           = get_coursemodule_from_id('ojt', $cmid, 0, false, MUST_EXIST);
        $course       = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $ojt          = new ojt($cm->instance);//$DB->get_record('ojt', array('id' => $cm->instance), '*', MUST_EXIST);
    }
    else if ($instanceid)
    {
        $ojt          = new ojt($instanceid);
        $course       = $DB->get_record('course', array('id' => $ojt->course), '*', MUST_EXIST);
        $cm           = get_coursemodule_from_instance('ojt', $ojt->id, $course->id, false, MUST_EXIST);
    }
    else
    {
        throw new coding_exception('You must specify a course_module ID or an instance ID');
    }

    return [$ojt, $course, $cm];
}