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

    /**
     * Find object in associative array based on criteria
     *
     * @param array $input input assoc array
     * @param mixed $key key to lookup by
     * @param mixed $value value to match
     *
     * @return mixed matched entry in array
     */
    public static function find_in_assoc_array_or_null(array $input, $key, $value)
    {
        foreach ($input as $entry)
        {
            if ($entry instanceof db_model_base)
            {
                $compare_to = $entry->get($key);
            }
            else if (is_array($entry))
            {
                $compare_to = $entry[$key];
            }
            else if (is_object($entry))
            {
                $compare_to = $entry->$key;
            }
            else
            {
                throw new \coding_exception(sprintf('Unsupported object type "%s" in array', gettype($entry)));
            }

            if ($value === $compare_to)
            {
                return $entry;
            }
        }

        return null;
    }
}