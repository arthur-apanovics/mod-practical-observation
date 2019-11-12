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

defined('MOODLE_INTERNAL') || die;

class backup_observation_activity_structure_step extends backup_activity_structure_step
{

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure()
    {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the observation instance.
        $observation = new backup_nested_element('observation', array('id'), array(
            'name',
            'intro',
            'introformat',
            'grade',
            'managersignoff',
            'itemwitness',
            'completiontopics'));

        $topics = new backup_nested_element('topics');
        $topic  = new backup_nested_element('topic', array('id'), array(
            'name',
            'completionreq'));

        $items = new backup_nested_element('items');
        $item  = new backup_nested_element('item', array('id'), array(
            'name',
            'completionreq',
            'allowfileuploads',
            'allowselffileuploads'));

        $completions = new backup_nested_element('completions');
        $completion  = new backup_nested_element('completion', array('id'), array(
            'userid',
            'type',
            'topicid',
            'topicitemid',
            'status',
            'comment',
            'timemodified',
            'modifiedby'));

        $topic_signoffs = new backup_nested_element('topic_signoffs');
        $topic_signoff  = new backup_nested_element('topic_signoff', array('id'), array(
            'userid',
            'topicid',
            'signedoff',
            'comment',
            'timemodified',
            'modifiedby'));

        $item_witnesses = new backup_nested_element('item_witnesses');
        $item_witness   = new backup_nested_element('item_witness', array('id'), array(
            'userid',
            'topicitemid',
            'witnessedby',
            'timewitnessed'));

        // Build the tree
        $observation->add_child($topics);
        $topics->add_child($topic);
        $topic->add_child($items);
        $items->add_child($item);

        $observation->add_child($completions);
        $completions->add_child($completion);

        $observation->add_child($topic_signoffs);
        $topic_signoffs->add_child($topic_signoff);

        $observation->add_child($item_witnesses);
        $item_witnesses->add_child($item_witness);

        // Define data sources.
        $observation->set_source_table('observation', array('id' => backup::VAR_ACTIVITYID));

        $topic->set_source_sql('
            SELECT *
              FROM {observation_topic}
             WHERE observationid = ?',
            array(backup::VAR_PARENTID));

        $item->set_source_sql('
            SELECT *
              FROM {observation_topic_item}
             WHERE topicid = ?',
            array(backup::VAR_PARENTID));

        if ($userinfo)
        {
            $completion->set_source_sql('
                SELECT *
                  FROM {observation_completion}
                 WHERE observationid = ?',
                array(backup::VAR_ACTIVITYID));

            $topic_signoff->set_source_sql('
                SELECT *
                  FROM {observation_topic_signoff}
                  WHERE topicid IN (
                      SELECT id
                      FROM {observation_topic}
                      WHERE observationid = ?
                  )',
                array(backup::VAR_ACTIVITYID));

            $item_witness->set_source_sql('
                SELECT *
                  FROM {observation_item_witness}
                  WHERE topicitemid IN (
                      SELECT ti.id
                      FROM {observation_topic} t
                      JOIN {observation_topic_item} ti ON t.id = ti.topicid
                      WHERE t.observationid = ?
                  )',
                array(backup::VAR_ACTIVITYID));

        }

        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.
        $completion->annotate_ids('user', 'userid');
        $completion->annotate_ids('user', 'modifiedby');

        $topic_signoff->annotate_ids('user', 'userid');
        $topic_signoff->annotate_ids('user', 'modifiedby');

        $item_witness->annotate_ids('user', 'userid');

        // Define file annotations (we do not use itemid in this example).
        $observation->annotate_files('mod_observation', 'intro', null);

        // Return the root element (observation), wrapped into standard activity structure.
        return $this->prepare_activity_structure($observation);
    }
}
