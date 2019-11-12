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
 * @package mod_observation
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one observation activity
 */
class restore_observation_activity_structure_step extends restore_activity_structure_step
{

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure()
    {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        $paths   = array();
        $paths[] = new restore_path_element('observation', '/activity/observation');
        $paths[] = new restore_path_element('observation_topic', '/activity/observation/topics/topic');
        $paths[] = new restore_path_element('observation_topic_item', '/activity/observation/topics/topic/items/item');
        if ($userinfo)
        {
            $paths[] = new restore_path_element('observation_completion', '/activity/observation/completions/completion');
            $paths[] = new restore_path_element('observation_topic_signoff', '/activity/observation/topic_signoffs/topic_signoff');
            $paths[] = new restore_path_element('observation_item_witness', '/activity/observation/item_witnesses/item_witness');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data for the observation activity
     *
     * @param array $data parsed element data
     */
    protected function process_observation($data)
    {
        global $DB;

        $data               = (object) $data;
        $oldid              = $data->id;
        $data->course       = $this->get_courseid();
        $data->timecreated  = time();
        $data->timemodified = time();

        // Create the observation instance.
        $newitemid = $DB->insert_record('observation', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the given restore path element data for observation topics
     *
     * @param array $data parsed element data
     */
    protected function process_observation_topic($data)
    {
        global $DB;

        $data        = (object) $data;
        $oldid       = $data->id;
        $data->observationid = $this->get_new_parentid('observation');

        // Add observation topic.
        $newitemid = $DB->insert_record('observation_topic', $data);
        $this->set_mapping('observation_topic', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data for observation topic items
     *
     * @param array $data parsed element data
     */
    protected function process_observation_topic_item($data)
    {
        global $DB;

        $data          = (object) $data;
        $oldid         = $data->id;
        $data->topicid = $this->get_new_parentid('observation_topic');

        // Add observation topic.
        $newitemid = $DB->insert_record('observation_topic_item', $data);
        $this->set_mapping('observation_topic_item', $oldid, $newitemid);
    }

    /**
     * Process the given restore path element data for observation completion
     *
     * @param array $data parsed element data
     */
    protected function process_observation_completion($data)
    {
        global $DB;

        $data              = (object) $data;
        $oldid             = $data->id;
        $data->userid      = $this->get_mappingid('user', $data->userid);
        $data->observationid       = $this->get_new_parentid('observation');
        $data->topicid     = $this->get_mappingid('observation_topic', $data->topicid);
        $data->topicitemid = $this->get_mappingid('observation_topic_item', $data->topicitemid);
        $data->modifiedby  = $this->get_mappingid('user', $data->userid);

        // Add observation topic.
        $newitemid = $DB->insert_record('observation_completion', $data);
    }

    /**
     * Process the given restore path element data for observation topic signoffs
     *
     * @param array $data parsed element data
     */
    protected function process_observation_topic_signoff($data)
    {
        global $DB;

        $data             = (object) $data;
        $oldid            = $data->id;
        $data->userid     = $this->get_mappingid('user', $data->userid);
        $data->topicid    = $this->get_mappingid('observation_topic', $data->topicid);
        $data->modifiedby = $this->get_mappingid('user', $data->userid);

        // Add observation topic.
        $newitemid = $DB->insert_record('observation_topic_signoff', $data);
    }

    /**
     * Process the given restore path element data for observation item completion witnesses
     *
     * @param array $data parsed element data
     */
    protected function process_observation_item_witness($data)
    {
        global $DB;

        $data              = (object) $data;
        $oldid             = $data->id;
        $data->userid      = $this->get_mappingid('user', $data->userid);
        $data->topicitemid = $this->get_mappingid('observation_topic_item', $data->topicitemid);
        $data->witnessedby = $this->get_mappingid('user', $data->witnessedby);

        // Add observation topic.
        $newitemid = $DB->insert_record('observation_item_witness', $data);
    }


    /**
     * Post-execution actions
     */
    protected function after_execute()
    {
        // Add observation related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_observation', 'intro', null);
    }
}
