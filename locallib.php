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

use coding_exception;
use mod_observation\interfaces\templateable;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

// common methods here
class lib
{
    /**
     * Returns observation activity status string for ANY class
     *
     * @param string|null $status
     * @return string|null if null is passed, null is returned
     * @throws \coding_exception
     */
    public static function get_status_string(?string $status): ?string
    {
        if (!$status)
        {
            return null;
        }

        return get_string(sprintf('status:%s', $status), OBSERVATION);
    }

    /**
     * Find first object in associative array based on single key-value pair
     *
     * @param array $input input assoc array
     * @param mixed $key key to lookup by
     * @param mixed $value value to match
     *
     * @return mixed FIRST matched entry in array
     * @throws \coding_exception
     */
    public static function find_in_assoc_array_key_value_or_null($input, $key, $value)
    {
        if (is_array($input))
        {
            foreach ($input as $entry)
            {
                // get value to compare
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
                    $compare_to = $entry->{$key};
                }
                else
                {
                    throw new \coding_exception(sprintf('Unsupported object type "%s" in array', gettype($entry)));
                }

                // perform the actual comparison
                if ($value == $compare_to) // has to be == and not ===
                {
                    return $entry;
                }
            }
        }

        return null;
    }

    /**
     * Find first matching object in associative array based on multiple criteria
     *
     * @param array $input input assoc array
     * @param array $criteria ['key' => 'value']
     * @return mixed FIRST matched entry in array
     * @throws \coding_exception
     */
    public static function find_in_assoc_array_criteria_or_null($input, array $criteria)
    {
        // sometimes $input can be null, we want to keep this flexibility by not adding a type constraint
        if (is_array($input) && !empty($criteria))
        {
            $values = array_values($criteria);
            foreach ($input as $entry)
            {
                $compare_to = [];

                // get values to compare
                foreach (array_keys($criteria) as $key)
                {
                    if ($entry instanceof db_model_base)
                    {
                        $compare_to[] = $entry->get($key);
                    }
                    else if (is_array($entry))
                    {
                        $compare_to[] = $entry[$key];
                    }
                    else if (is_object($entry))
                    {
                        $compare_to[] = $entry->{$key};
                    }
                    else
                    {
                        throw new \coding_exception(sprintf('Unsupported object type "%s" in array', gettype($entry)));
                    }
                }

                // perform the actual comparison
                if ($values == $compare_to) // has to be == and not ===
                {
                    return $entry;
                }
            }
        }

        return null;
    }

    /**
     * Sort provided array by it's sequence number in specified order
     *
     * @param array  $array_to_sort
     * @param string $field_to_sort_by
     * @param string $asc_or_desc "asc"|"desc" sort in ascending (asc) or descending (desc) order
     * @return array sorted array with array keys preserved
     * @throws \coding_exception
     */
    public static function sort_by_field(
        array $array_to_sort, string $field_to_sort_by, string $asc_or_desc = 'asc'): array
    {
        $asc_or_desc = strtolower($asc_or_desc);
        if (!in_array($asc_or_desc, ['asc', 'desc']))
        {
            throw new \coding_exception("Cannot sort in '$asc_or_desc' order. Valid options are 'asc' or 'desc'");
        }

        if (count($array_to_sort) <= 1)
        {
            return $array_to_sort;
        }

        uasort(
            $array_to_sort, function ($a, $b) use ($field_to_sort_by, $asc_or_desc)
        {
            if ($a[$field_to_sort_by] === $b[$field_to_sort_by])
            {
                debugging(
                    sprintf(
                        'Identical values detected when sorting by "%s" <pre>%s</pre> ID\'s [%b, %b]',
                        $field_to_sort_by, print_r(array_keys($a), true), $a['id'], $b['id']), DEBUG_DEVELOPER);
            }

            return $asc_or_desc == 'asc' ? ($a[$field_to_sort_by] <=> $b[$field_to_sort_by])
                : ($b[$field_to_sort_by] <=> $a[$field_to_sort_by]);
        });

        return $array_to_sort;
    }

    public static function get_editor_file_options($context)
    {
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean'  => true,
            'context'  => $context,
            'subdirs'  => false
        ];
    }

    /**
     * @param string $class object::class string
     * @return array ['input_base_name', 'input_format', 'input_class']
     * @throws \coding_exception
     * TODO: this would probably be better as an interface
     */
    public static function get_editor_attributes_for_class(string $class): array
    {
        // even though all fields are named the same at the moment, their names might change
        // in the future via a refactoring therefore we still check for each class here
        switch ($class)
        {
            case learner_attempt::class:
                $column_name = learner_attempt::COL_TEXT;
                break;
            case observer_feedback::class:
                $column_name = observer_feedback::COL_TEXT;
                break;
            case assessor_feedback::class:
                $column_name = assessor_feedback::COL_TEXT;
                break;
            default:
                throw new \coding_exception('Unsupported classname - cannot retrieve input name');
        }

        $clean_classname = lib::remove_namespace_from_classname($class);
        $base = "${clean_classname}_${column_name}";
        return
            [
                $base, // used to retrieve values
                "${base}[text]",
                "${base}[format]"
            ];
    }

    /**
     * Remove namespace from object::class string
     *
     * https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace
     * @param string $class
     * @return false|string
     */
    public static function remove_namespace_from_classname(string $class)
    {
        return (substr(
            $class,
            strrpos($class, '\\') + 1));
    }

    /**
     * Simple convenience method that iterates over each object in array and exports template data
     *
     * @param templateable[] $objects
     * @return array
     */
    public static function export_template_data_from_array(array $objects): array
    {
        $result = [];
        foreach ($objects as $object)
        {
            if ($object instanceof templateable)
            {
                $result[] = $object->export_template_data();
            }
            else
            {
                debugging(
                    sprintf('Unsupported object passed to %s', __METHOD__),
                    DEBUG_DEVELOPER,
                    debug_backtrace());
            }
        }

        return $result;
    }

    public static function save_files(int $draftitemid, int $contextid, string $file_area, int $itemid)
    {
        file_save_draft_area_files(
            $draftitemid,
            $contextid,
            \OBSERVATION_MODULE,
            $file_area,
            $itemid);
    }

    /**
     * @param \stored_file[] $stored_files
     * @return array ['filename' => string, 'url' => string]
     */
    public static function get_downloads_from_stored_files(array $stored_files)
    {
        $attachments = [];
        foreach ($stored_files as $file)
        {
            if ($file->get_filename() == '.')
            {
                // this is the root directory
                continue;
            }

            $url = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                '/',
                $file->get_filename(),
                true);

            $attachments[] = [
                'filename' => $file->get_filename(),
                'url'      => $url->out(false)
            ];
        }

        return $attachments;
    }

    /**
     * Workaround for moodle not being able to precess nested arrays from required_param...<br>
     * See {@link required_param_array()} for details
     */
    public static function required_param_array($parname, $type)
    {
        if (func_num_args() != 2 or empty($parname) or empty($type))
        {
            throw new coding_exception(
                'required_param_array() requires $parname and $type to be specified (parameter: ' . $parname . ')');
        }
        // POST has precedence.
        if (isset($_POST[$parname]))
        {
            $param = $_POST[$parname];
        }
        else if (isset($_GET[$parname]))
        {
            $param = $_GET[$parname];
        }
        else
        {
            print_error('missingparam', '', '', $parname);
        }
        if (!is_array($param))
        {
            print_error('missingparam', '', '', $parname);
        }

        $result = array();
        foreach ($param as $key => $value)
        {
            if (!preg_match('/^[a-z0-9_-]+$/i', $key))
            {
                debugging('Invalid key name in required_param_array() detected: ' . $key . ', parameter: ' . $parname);
                continue;
            }
            $result[$key] = self::clean_param_array($value, $type);
        }

        return $result;
    }

    /**
     * Used by {@link optional_param()} and {@link required_param()} to
     * clean the variables and/or cast to specific types, based on
     * an options field.
     * <code>
     * $course->format = clean_param($course->format, PARAM_ALPHA);
     * $selectedgradeitem = clean_param($selectedgradeitem, PARAM_INT);
     * </code>
     *
     * @param mixed  $param the variable we are cleaning
     * @param string $type expected format of param after cleaning.
     * @return mixed
     * @throws coding_exception
     */
    public static function clean_param_array($param, $type)
    {
        global $CFG;

        if (is_array($param))
        {
            $param = clean_param_array($param, PARAM_RAW, true);
        }
        else if (is_object($param))
        {
            if (method_exists($param, '__toString'))
            {
                $param = $param->__toString();
            }
            else
            {
                throw new coding_exception(
                    'clean_param() can not process objects, please use clean_param_array() instead.');
            }
        }

        switch ($type)
        {
            case PARAM_RAW:
                // No cleaning at all.
                $param = fix_utf8($param);
                return $param;

            default:
                throw new \coding_exception('We only support RAW params');
        }
    }

    public static function validate_prop(
        string $property_name, $current_value, $new_value, array $allowed_values, bool $allow_same_value)
    {
        if (!in_array($new_value, $allowed_values))
        {
            throw new coding_exception(
                sprintf("'$new_value' is not a valid value for property '%s' in '%s'", $property_name, static::class));
        }
        if ($current_value === $new_value)
        {
            if ($allow_same_value)
            {
                debugging(
                    sprintf(
                        '"%s"->"%s" is already "%s". This should not normally happen',
                        self::class,
                        $property_name,
                        $new_value),
                    DEBUG_DEVELOPER,
                    debug_backtrace());
            }
            else
            {
                throw new coding_exception(
                    sprintf(
                        'Cannot set property "%s" - value is already "%s" in %s',
                        $property_name,
                        $current_value,
                        static::class));
            }
        }
    }
}