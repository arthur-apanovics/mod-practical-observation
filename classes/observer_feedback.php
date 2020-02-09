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
use mod_observation\interfaces\templateable;

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
     * ENUM ('not_complete', 'complete')
     *
     * @var string
     */
    protected $status;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var int
     */
    protected $text_format;

    public function set(string $prop, $value, bool $save = false): db_model_base
    {
        if ($prop == self::COL_STATUS)
        {
            // validate status is correctly set
            $allowed = [self::STATUS_COMPLETE, self::STATUS_NOT_COMPLETE];
            if (!in_array($value, $allowed))
            {
                throw new coding_exception(
                    sprintf("'$value' is not a valid value for '%s' in '%s'", self::COL_STATUS, get_class($this)));
            }
        }

        return parent::set($prop, $value, $save);
    }
}

class observer_feedback extends observer_feedback_base implements templateable
{
    public function __construct($id_or_record, int $userid)
    {
        parent::__construct($id_or_record);
    }

    /**
     * @inheritDoc
     */
    public function export_template_data(): array
    {
        return [
            self::COL_ID     => $this->id,
            self::COL_STATUS => lib::get_status_string($this->status),
            self::COL_TEXT   => $this->text,
        ];
    }
}
