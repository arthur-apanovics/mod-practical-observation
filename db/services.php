<?php
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

/**
 * observation external functions and service definitions.
 *
 * @package    observation
 */

use mod_observation\observation;

defined('MOODLE_INTERNAL') || die;

$services = [
    'observation service' => [
        'functions' => [
            'mod_observation_task_update_sequence',
            'mod_observation_criteria_update_sequence',
            'mod_observation_observer_update_details',
        ],
        'enabled'   => true
    ]
];

// the web service functions to install.
$functions = [
    'mod_observation_task_update_sequence'     => [
        'classname'    => 'mod_observation_external',
        'methodname'   => 'task_update_sequence',
        'description'  => 'saves observation task data',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => observation::CAP_MANAGE
    ],
    'mod_observation_criteria_update_sequence' => [
        'classname'    => 'mod_observation_external',
        'methodname'   => 'criteria_update_sequence',
        'description'  => 'saves observation criteria data',
        'type'         => 'write',
        'ajax'         => true,
        'capabilities' => observation::CAP_MANAGE
    ],
    'mod_observation_observer_update_details'  => [
        'classname'     => 'mod_observation_external',
        'methodname'    => 'observer_update_details',
        'description'   => 'updates observer details',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => false
    ],
];
