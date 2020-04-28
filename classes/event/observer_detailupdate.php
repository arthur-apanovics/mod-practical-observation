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

namespace mod_observation\event;

use mod_observation\observer;

defined('MOODLE_INTERNAL') || die();

class observer_detailupdate extends \core\event\base {

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Observer with id '$this->objectid' has updated their details
        in observation with course module id '$this->contextinstanceid' (updated details attached to this log in database).";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return 'Observer details updated';
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = observer::TABLE; // db table for objectid in question (e.g. learner_submission)
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        // $additional = [];
        // foreach ($additional as $key)
        // {
        //     if (!isset($this->data['other'][$key]))
        //     {
        //         throw new \coding_exception("Event requires '$key' to be set in 'data['other']'");
        //     }
        // }
    }

    // public static function get_objectid_mapping() {
    //     return array('db' => 'observation_learner_attempt', 'restore' => 'learner_attempt');
    // }
}
