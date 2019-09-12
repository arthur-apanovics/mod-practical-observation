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

namespace mod_ojt\models;


use coding_exception;
use dml_exception;
use mod_ojt\interfaces\crud;
use mod_ojt\traits\db_record_base;
use mod_ojt\traits\record_mapper;
use stdClass;

class item_witness extends db_record_base
{
    protected const TABLE = 'ojt_item_witness';

    /**
     * @var int
     */
    public $userid;

    /**
     * @var int
     */
    public $topicitemid;

    /**
     * @var int if of system user that witnessed
     */
    public $witnessedby;

    // incavtive for now, might change name of column
    // /**
    //  * @var string email address of external user if item was witnessed externally
    //  */
    // public $witnessedbyexternal;

    /**
     * @var int timestamp
     */
    public $timewitnessed;


    /**
     * @param int $topicitemid
     * @param int $userid
     * @return item_witness|null null if record not found
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function get_user_item_witness(int $topicitemid, int $userid)
    {
        global $DB;
        $rec = $DB->get_record('ojt_item_witness', ['topicitemid' => $topicitemid, 'userid' => $userid]);
        return $rec ? new self($rec) : null;
    }
}