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

namespace mod_observation;

use dml_exception;

class observer_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer';

    public const COL_FULLNAME       = 'fullname';
    public const COL_PHONE          = 'phone';
    public const COL_EMAIL          = 'email';
    public const COL_POSITION_TITLE = 'position_title';
    public const COL_ADDED_BY       = 'added_by';
    public const COL_TIMEADDED      = 'timeadded';
    public const COL_MODIFIED_BY    = 'modified_by';
    public const COL_TIMEMODIFIED   = 'timemodified';

    /**
     * @var string
     */
    protected $fullname;
    /**
     * @var string
     */
    protected $phone;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $position_title;
    /**
     * User id of learner that added this observer into system
     *
     * @var int
     */
    protected $added_by;
    /**
     * @var int
     */
    protected $timeadded;
    /**
     * user id that last modified this record. <b>-1 means that the observer himself modified the details</b>
     *
     * @var int
     */
    protected $modified_by;
    /**
     * @var int
     */
    protected $timemodified;

    public function get_formatted_name()
    {
        return format_string($this->fullname);
    }

    public function get_email()
    {
        if (is_null($this->id))
        {
            throw new \coding_exception('observer object not initialised');
        }

        return $this->email;
    }

    /**
     * Used when an observer updates their details
     *
     * @param int    $id
     * @param string $fullname
     * @param string $phone
     * @param string $position_title
     * @return observer_base
     * @throws \coding_exception
     * @throws \dml_missing_record_exception
     * @throws dml_exception
     */
    public static function update_from_ajax(int $id, string $fullname, string $phone, string $position_title)
    {
        foreach (func_get_args() as $value)
        {
            if (empty($value))
            {
                throw new \coding_exception('Empty argument passed to ' . __METHOD__);
            }
        }

        $to_update = new self($id);
        $to_update->set(self::COL_FULLNAME, $fullname);
        $to_update->set(self::COL_PHONE, $phone);
        $to_update->set(self::COL_POSITION_TITLE, $position_title);

        // check if anything changed
        $original = new self($id);
        $should_update = false;
        $keys = [self::COL_ID, self::COL_FULLNAME, self::COL_PHONE, self::COL_POSITION_TITLE,];
        foreach ($keys as $key)
        {
            if ($original->get($key) != $to_update->get($key))
            {
                $should_update = true;
                break;
            }
        }

        if ($should_update)
        {
            $to_update->set(self::COL_MODIFIED_BY, -1);
            $to_update->set(self::COL_TIMEMODIFIED, time());

            return $to_update->update();
        }
        else
        {
            return $original;
        }
    }

    public static function update_or_create(observer_base $submitted_observer)
    {
        global $USER;

        // try match by email
        if ($id = self::try_get_id_for_observer($submitted_observer))
        {
            // update existing record
            $existing = new self($id);
            $existing->fullname = $submitted_observer->fullname;
            $existing->phone = $submitted_observer->phone;
            // skipping email
            $existing->position_title = $submitted_observer->position_title;
            $existing->timemodified = time();
            $existing->modified_by = $USER->id;

            return $existing->update();
        }
        else
        {
            // create new record
            $submitted_observer->added_by = $submitted_observer->modified_by = $USER->id;
            $submitted_observer->timeadded = $submitted_observer->timemodified = time();

            return $submitted_observer->create();
        }
    }

    /**
     * Checks if an observer exists based on provided data.
     * Used when checking existing observer assignments during observation requesting.
     *
     * @param observer_base $observer
     * @return false|int false if not matching record found, id if found
     * @throws dml_exception
     */
    public static function try_get_id_for_observer(observer_base $observer)
    {
        global $DB;

        //TODO perform more and smarter checks

        return $DB->get_field(self::TABLE, self::COL_ID, [self::COL_EMAIL => $observer->email]);
    }
}
