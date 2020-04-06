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

use coding_exception;

class observer_feedback_base extends db_model_base
{
    public const TABLE = OBSERVATION . '_observer_feedback';

    public const COL_ATTEMPTID             = 'attemptid';
    public const COL_CRITERIAID            = 'criteriaid';
    public const COL_OBSERVER_SUBMISSIONID = 'observer_submissionid';
    public const COL_STATUS                = 'status';
    public const COL_TEXT                  = 'text';
    public const COL_TEXT_FORMAT           = 'text_format';

    public const STATUS_NOT_COMPLETE = 'not_complete';
    public const STATUS_COMPLETE     = 'complete';

    /**
     * @var int
     */
    protected $attemptid;
    /**
     * @var int
     */
    protected $criteriaid;
    /**
     * @var int
     */
    protected $observer_submissionid;
    /**
     * One of:
     * <ul>
     * <li>null (null means no decision has been made yet)</li>
     * <li>{@link STATUS_NOT_COMPLETE},</li>
     * <li>{@link STATUS_COMPLETE}</li>
     *</ul>
     *
     * @var string|null
     */
    protected $status;
    /**
     * null when feedback not requried
     *
     * @var string|null
     */
    protected $text;
    /**
     * null when feedback not requried
     *
     * @var int|null
     */
    protected $text_format;

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_STATUS)
        {
            // validate status is correctly set
            $allowed = [self::STATUS_COMPLETE, self::STATUS_NOT_COMPLETE];
            lib::validate_prop(self::COL_STATUS, $this->status, $value, $allowed, true);
        }

        return parent::set($prop, $value, $save);
    }
}
