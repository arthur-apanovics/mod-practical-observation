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

namespace mod_ojt;

use coding_exception;
use dml_exception;
use mod_ojt\models\completion;
use mod_ojt\models\ojt;
use mod_ojt\traits\record_mapper;

class user_ojt extends ojt
{
    use record_mapper;

    /**
     * @var int
     */
    public $userid;

    /**
     * @var user_topic[]
     */
    public $topics;


    public function __construct($id_or_record, $userid)
    {
        parent::__construct($id_or_record);

        $this->userid = $userid;
        $this->topics = user_topic::get_user_topics($this->id, $this->userid);
    }

    public function get_topic_by_id(int $topicid)
    {
        return $this->topics[$topicid];
    }
}