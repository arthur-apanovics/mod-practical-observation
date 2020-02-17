<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once $CFG->libdir . "/externallib.php";
require_once $CFG->dirroot . '/mod/observation/locallib.php';

/**
 * observation external functions
 *
 * @package    mod_observation
 * @category   external
 * @since      Moodle 3.0
 */
class mod_observation_external extends external_api
{
    public static function save_example($observationid, $attempt)
    {
        global $USER;

        $extracted_params = self::validate_parameters(self::save_example_parameters(), [/*parameters here*/]);

        // LOGIC GOES HERE

        return self::clean_returnvalue(self::save_example_returns(), [/*parameters here*/]);
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function save_example_parameters()
    {
        return new external_function_parameters(
            [
                'id'            => new external_value(PARAM_INT, 'id parameter description here'),
                'example_array' => new external_single_structure(
                    [
                        'id'    => new external_value(
                            PARAM_INT,
                            'description here (I think only used for exception messages)',
                            VALUE_REQUIRED), // << note the required option
                        'other' => new external_value(
                            PARAM_RAW,
                            'description here (I think only used for exception messages or /OPTIONS HTTP requests)'),
                        'stuff' => new external_value(
                            PARAM_INT,
                            'description here (I think only used for exception messages)')
                    ])
            ]);
    }

    /**
     * Expose to ajax
     * @return boolean
     */
    public static function save_example_is_allowed_from_ajax()
    {
        // have no idea if we actually need to specify this or not, maybe 'true' is returned by default if method not set?
        return true;
    }

    /**
     * Returns description of method return values
     *
     * @return external_single_structure
     */
    public static function save_example_returns()
    {
        return new external_single_structure(
            [
                'example_1'     => new external_value(PARAM_INT, 'example description', VALUE_DEFAULT, null),
                'example_2' => new external_value(PARAM_INT, 'example description', VALUE_DEFAULT, null),
            ]);
    }
}
