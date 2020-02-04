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

/**
 * Internal library of functions for module observation
 *
 * All the observation specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 */
namespace mod_observation;

defined('MOODLE_INTERNAL') || die();

// common methods here
class lib
{
    /**
     * Returns observation activity status string for ANY class
     *
     * @param string $status
     * @return string
     * @throws \coding_exception
     */
    public static function get_status_string(string $status): string
    {
        if (!$status)
        {
            throw new \coding_exception(sprintf('No status provided for %s', __METHOD__));
        }

        return get_string(sprintf('status:%s', $status), OBSERVATION);
    }
}