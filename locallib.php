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
use context;
use context_module;
use context_user;
use file_storage;
use grade_scale;
use mod_observation\interfaces\templateable;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

// common methods here
class lib
{
    /**
     * @param int            $current_group call {@link groups_get_activity_group} to get currently active group
     * @param context_module $context cm
     * @param string         $capability capability that the user needs to have
     * @return array|null
     * @throws \dml_exception
     */
    public static function get_usersids_in_group_or_null(
        int $current_group, context_module $context, string $capability)
    {
        global $DB;

        $userids = null;
        if (!empty($current_group))
        {
            // We have a currently selected group.
            $groupstudentsjoins = get_enrolled_with_capabilities_join(
                $context, '', $capability, $current_group);

            if (!empty($groupstudentsjoins->joins))
            {
                $sql = "SELECT DISTINCT u.id
                        FROM {user} u
                        $groupstudentsjoins->joins
                        WHERE $groupstudentsjoins->wheres";
                $userids = $DB->get_fieldset_sql($sql, $groupstudentsjoins->params);
            }
        }

        return !empty($userids) ? $userids : null;
    }

    /**
     * Fetches a binary type scale id or creates one if not found.
     * Required to ensure gradebook supports our grade type.
     *
     * @return int scale id
     */
    public static function get_binary_scaleid_or_create()
    {
        global $CFG, $USER;
        require_once($CFG->libdir . '/grade/grade_scale.php');
        require_once($CFG->libdir . '/grade/constants.php');

        // check for a binary scale
        $scale = grade_scale::fetch(['scale' => 'Not yet competent,Competent']);

        if (!$scale)
        {
            // binary scale absent, create our own
            $params = [];
            $params['courseid'] = 0; // not tying scale to a course makes it available globally
            $params['userid'] = $USER->id;
            $params['name'] = 'Binary competence scale';
            $params['scale'] = 'Not yet competent,Competent';
            $params['description'] = 'A binary rating scale that provides no further information beyond whether someone has demonstrated proficiency or not. Created & required by "Converse" activity';
            $params['descriptionformat'] = 0;
            $params['timemodified'] = time();

            $scale = new grade_scale($params);
            $scale->insert();
        }

        return $scale->id;
    }

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
     * @param string      $type observer|assessor
     * @param string|null $outcome
     * @return string|null
     * @throws coding_exception
     */
    public static function get_outcome_string(string $type, ?string $outcome): ?string
    {
        if (!$outcome)
        {
            return null;
        }
        else if (!in_array($type, ['observer','assessor']))
        {
            throw new coding_exception(sprintf('Invalid "type" argument - "%s"', $type));
        }

        return get_string(sprintf('outcome:%s_%s', $type, $outcome), OBSERVATION);
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
    public static function find_in_assoc_array_by_key_value_or_null($input, $key, $value)
    {
        if (is_array($input) && !empty($input))
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
    public static function find_in_assoc_array_by_criteria_or_null($input, array $criteria)
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
     * @param string $sort_direction "asc"|"desc" sort in ascending (asc) or descending (desc) order
     * @return array sorted array with array keys preserved
     * @throws \coding_exception
     */
    public static function sort_by_field(
        array $array_to_sort, string $field_to_sort_by, string $sort_direction = 'asc'): array
    {
        $sort_direction = strtolower($sort_direction);
        if (!in_array($sort_direction, ['asc', 'desc']))
        {
            throw new \coding_exception("Cannot sort in '$sort_direction' order. Valid options are 'asc' or 'desc'");
        }

        if (count($array_to_sort) <= 1)
        {
            return $array_to_sort;
        }

        uasort(
            $array_to_sort, function ($a, $b) use ($field_to_sort_by, $sort_direction)
        {
            $mapped = array_map(
                function ($el) use ($field_to_sort_by)
                {
                    $val = null;
                    if ($el instanceof db_model_base)
                    {
                        $val = $el->get($field_to_sort_by);
                    }
                    else if (is_object($el))
                    {
                        $val = $el->{$field_to_sort_by};
                    }
                    else if (is_array($el))
                    {
                        $val = $el[$field_to_sort_by];
                    }
                    else
                    {
                        throw new coding_exception(sprintf('Unsupported value type "%s" passed to sort', gettype($el)));
                    }

                    return $val;
                }, [$a, $b]);

            if ($mapped[0] === $mapped[1])
            {
                debugging(
                    sprintf(
                        'Identical values detected when sorting by "%s"', $field_to_sort_by),
                    DEBUG_DEVELOPER, debug_backtrace());
            }

            return $sort_direction == 'asc'
                ? ($mapped[0] <=> $mapped[1])
                : ($mapped[1] <=> $mapped[0]);
        });

        return $array_to_sort;
    }

    public static function get_editor_file_options()
    {
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean'  => true,
            'subdirs'  => true
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
     * Same as {@link file_prepare_draft_area()} but for users who do not have a local account (anonymous/guest).
     *
     * @param int    $draftitemid the id of the draft area to use, or 0 to create a new one, in which case this parameter is updated.
     * @param int    $contextid This parameter and the next two identify the file area to copy files from.
     * @param string $component
     * @param string $filearea helps indentify the file area.
     * @param int    $itemid helps identify the file area. Can be null if there are no files yet.
     * @param array  $options text and file options ('subdirs'=>false, 'forcehttps'=>false)
     * @param string $text some html content that needs to have embedded links rewritten to point to the draft area.
     * @return string|null returns string if $text was passed in, the rewritten $text is returned. Otherwise NULL.
     */
    public static function file_prepare_anonymous_draft_area(
        &$draftitemid, $contextid, $component, $filearea, $itemid, array $options = null, $text = null)
    {
        global $CFG;

        $options = (array) $options;
        if (!isset($options['subdirs']))
        {
            $options['subdirs'] = false;
        }
        if (!isset($options['forcehttps']))
        {
            $options['forcehttps'] = false;
        }

        $usercontext = context_user::instance($CFG->siteguest);
        $fs = get_file_storage();

        if (empty($draftitemid))
        {
            // create a new area and copy existing files into
            $draftitemid = self::file_get_unused_draft_itemid_allow_guest_and_set_global();
            $file_record = array(
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $draftitemid
            );
            if (!is_null($itemid) and $files = $fs->get_area_files($contextid, $component, $filearea, $itemid))
            {
                foreach ($files as $file)
                {
                    if ($file->is_directory() and $file->get_filepath() === '/')
                    {
                        // we need a way to mark the age of each draft area,
                        // by not copying the root dir we force it to be created automatically with current timestamp
                        continue;
                    }
                    if (!$options['subdirs'] and ($file->is_directory() or $file->get_filepath() !== '/'))
                    {
                        continue;
                    }
                    $draftfile = $fs->create_file_from_storedfile($file_record, $file);
                    // XXX: This is a hack for file manager (MDL-28666)
                    // File manager needs to know the original file information before copying
                    // to draft area, so we append these information in mdl_files.source field
                    // {@link file_storage::search_references()}
                    // {@link file_storage::search_references_count()}
                    $sourcefield = $file->get_source();
                    $newsourcefield = new stdClass;
                    $newsourcefield->source = $sourcefield;
                    $original = new stdClass;
                    $original->contextid = $contextid;
                    $original->component = $component;
                    $original->filearea = $filearea;
                    $original->itemid = $itemid;
                    $original->filename = $file->get_filename();
                    $original->filepath = $file->get_filepath();
                    $newsourcefield->original = file_storage::pack_reference($original);
                    // Check we can read the file before we update it.
                    if ($fs->content_exists($file->get_contenthash()))
                    {
                        $draftfile->set_source(serialize($newsourcefield));
                    }
                    // End of file manager hack
                }
            }
            if (!is_null($text))
            {
                // at this point there should not be any draftfile links yet,
                // because this is a new text from database that should still contain the @@pluginfile@@ links
                // this happens when developers forget to post process the text
                $text =
                    str_replace("\"$CFG->httpswwwroot/draftfile.php", "\"$CFG->httpswwwroot/brokenfile.php#", $text);
            }
        }

        if (is_null($text))
        {
            return null;
        }

        // relink embedded files - editor can not handle @@PLUGINFILE@@ !
        return file_rewrite_pluginfile_urls(
            $text, 'draftfile.php', $usercontext->id, 'user', 'draft', $draftitemid, $options);
    }

    /**
     * {@link file_get_unused_draft_itemid())
     *
     * @return int a random but available draft itemid that can be used to create a new draft
     * file area.
     */
    public static function file_get_unused_draft_itemid_allow_guest_and_set_global()
    {
        global $CFG, $USER;

        // TODO: most likely will have to create a local user with limited permissions to avoid guest limitations...
        $guest = \core_user::get_user($CFG->siteguest);
        $USER = $guest; // this will be needed further down the line

        $contextid = context_user::instance($guest->id)->id;

        $fs = get_file_storage();
        $draftitemid = rand(1, 999999999);
        while ($files = $fs->get_area_files($contextid, 'user', 'draft', $draftitemid))
        {
            $draftitemid = rand(1, 999999999);
        }

        return $draftitemid;
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
        string $property_name, $current_value, $new_value, array $allowed_values, bool $allow_same_value): bool
    {
        if (!in_array($new_value, $allowed_values))
        {
            throw new coding_exception(
                sprintf("'$new_value' is not a valid value for property '%s'", $property_name));
        }
        if ($current_value === $new_value)
        {
            if ($allow_same_value)
            {
                debugging(
                    sprintf(
                        '"%s" is already "%s". This should not normally happen',
                        $property_name,
                        $new_value),
                    DEBUG_DEVELOPER,
                    debug_backtrace());

                return false;
            }
            else
            {
                throw new coding_exception(
                    sprintf(
                        'Cannot set property "%s" - value is already "%s"',
                        $property_name,
                        $current_value));
            }
        }

        return true;
    }

    /**
     * @param string $to email
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public static function email_external(string $to, string $subject, string $message): bool
    {
        // create a fake user to bypass moodle checks
        $fake_user = new stdClass();
        $fake_user->id = PHP_INT_MAX; // make sure we don't clash with an existing user
        $fake_user->email = $to;
        $fake_user->deleted = false;
        $fake_user->suspended = false;
        $fake_user->auth = 'manual';
        $fake_user->mailformat = FORMAT_HTML;

        $from = \core_user::get_noreply_user();
        return email_to_user($fake_user, $from, $subject, $message);
    }

    public static function email_user(stdClass $user, string $subject, string $message)
    {
        $from = \core_user::get_noreply_user();

        // send email
        return email_to_user($user, $from, $subject, $message);
    }

    /**
     * @param stdClass[] $users
     * @param string     $subject
     * @param string     $message
     * @return array ['userid' => bool] array with results for each userid
     */
    public static function email_users(array $users, string $subject, string $message): array
    {
        $from = \core_user::get_noreply_user();

        // send email
        $results = [];
        foreach ($users as $user)
        {
            $results[$user->id] = email_to_user($user, $from, $subject, $message);
        }

        return $results;
    }

    public static function get_activity_timing_error_string(observation_base $observation)
    {
        if (!$observation->is_activity_open())
        {
            $date = userdate($observation->get(observation::COL_TIMEOPEN));
            return get_string('timing:notopen', \OBSERVATION, $date);
        }
        else if ($observation->is_activity_closed())
        {
            $date = userdate($observation->get(observation::COL_TIMECLOSE));
            return get_string('timing:closed', \OBSERVATION, $date);
        }
        else
        {
            throw new coding_exception('Requested timing error string when activity is fully available');
        }
    }

    // /**
    //  * Gets learner and assessor status description string for tasks
    //  *
    //  * @param string $status
    //  * @return string|null
    //  * @throws coding_exception
    //  */
    // public static function get_status_description_string(string $status): ?string
    // {
    //    switch ($status)
    //    {
    //        // learner
    //        case learner_task_submission::STATUS_OBSERVATION_PENDING:
    //        case learner_task_submission::STATUS_OBSERVATION_INCOMPLETE:
    //        // assessor
    //        case assessor_task_submission::OUTCOME_COMPLETE:
    //        case assessor_task_submission::OUTCOME_NOT_COMPLETE:
    //            return get_string("{$status}-description", OBSERVATION);
    //        default:
    //            return null;
    //    }
    // }

    public static function get_intro_itemid_mappings(bool $flip_mapping = false)
    {
        // files stored in same filearea need to have a unique item id,
        // therefore we need to provide a dummy 'unique' id
        $mappings = [
            // task intro defaults
            observation::COL_DEF_I_TASK_LEARNER     => 0,
            observation::COL_DEF_I_ASS_OBS_LEARNER  => 1,
            observation::COL_DEF_I_TASK_OBSERVER    => 2,
            observation::COL_DEF_I_ASS_OBS_OBSERVER => 3,
            observation::COL_DEF_I_TASK_ASSESSOR    => 4,
            // task intros
            task::COL_INTRO_LEARNER                 => 5,
            task::COL_INT_ASSIGN_OBS_LEARNER        => 6,
            task::COL_INTRO_OBSERVER                => 7,
            task::COL_INT_ASSIGN_OBS_OBSERVER       => 8,
            task::COL_INTRO_ASSESSOR                => 9,
            // criteria
            criteria::COL_DESCRIPTION               => null, // criteria id itself will suffice
            // special cases
            observation::COL_INTRO                  => null
        ];

        return $flip_mapping ? array_flip($mappings) : $mappings;
    }


    public static function get_intro_itemid_prefix(string $intro): ?int
    {
        return self::get_intro_itemid_mappings()[$intro];
    }

    /**
     * Some intros share fileareas, therefore we need to give each intro a unique itemid to save files
     *
     * @param string   $intro
     * @param int|null $itemid must be provided for all editors
     * except {@link observation::get_intro_fields()}
     * @return array
     * @throws coding_exception
     */
    public static function get_filearea_and_itemid_for_intro(string $intro, int $itemid = null): array
    {
        $prefix = self::get_intro_itemid_prefix($intro);

        switch ($intro){
            case observation::COL_DEF_I_TASK_LEARNER:
            case observation::COL_DEF_I_ASS_OBS_LEARNER:
            case observation::COL_DEF_I_TASK_OBSERVER:
            case observation::COL_DEF_I_ASS_OBS_OBSERVER:
            case observation::COL_DEF_I_TASK_ASSESSOR:
                // general file area with just the item id prefix
                return [observation::FILE_AREA_GENERAL, $prefix];

            case observation::COL_INTRO:
                return [observation::FILE_AREA_INTRO, $prefix];

            default:
            {
                // if we got here then an itemid had to be provided
                if (is_null($itemid))
                {
                    throw new coding_exception('"itemid" must be provided for current filearea');
                }
                // check intro name is valid
                if (!in_array($intro, array_keys(self::get_intro_itemid_mappings())))
                {
                    throw new coding_exception("unknown intro '{$intro}'");
                }

                // general file area with modified itemid
                return [
                    observation::FILE_AREA_GENERAL,
                    (int) sprintf('%d%d', $prefix, $itemid)
                ];
            }
        }
    }

    public static function format_intro(string $intro_name, string $content, context $context, $itemid = null)
    {
        list($area, $itemid) = lib::get_filearea_and_itemid_for_intro($intro_name, $itemid);

        return trim(
            format_text(
                file_rewrite_pluginfile_urls(
                    $content, 'pluginfile.php', $context->id, OBSERVATION_MODULE, $area, $itemid)));
    }

    /**
     * Prepare intro for editing in a form
     *
     * @param string   $intro
     * @param int      $format
     * @param string   $text
     * @param context  $context
     * @param int|null $itemid if known
     * @return array 'text', 'format', 'itemid' in array
     * @throws coding_exception
     */
    public static function prepare_intro(
        string $intro, int $format, string $text, context $context, int $itemid = null): array
    {
        list($area, $itemid) = lib::get_filearea_and_itemid_for_intro($intro, $itemid);
        // $itemid = !is_null($itemid) ? $itemid : $id;

        $draftid = file_get_submitted_draft_itemid($intro);
        $text = file_prepare_draft_area(
            $draftid,                                       // Draftid.
            $context->id,                                              // Context.
            OBSERVATION_MODULE,                              // Component.
            $area,                                                     // Filarea.
            $itemid,                                                   // Itemid.
            self::get_editor_file_options(),
            $text                                                       // Text.
        );

        return [
            'text'   => $text,
            'format' => $format,
            'itemid' => $draftid,
        ];
    }

    public static function save_intro(
        array $editor, string $filearea, int $itemid, context $context): string
    {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        return file_save_draft_area_files(
            $editor['itemid'],
            $context->id,
            OBSERVATION_MODULE,
            $filearea,
            $itemid,
            lib::get_editor_file_options(),
            $editor['text']);
    }
}
