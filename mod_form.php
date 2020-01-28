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
 * The main observation configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_observation_mod_form extends moodleform_mod
{

    /**
     * Defines forms elements
     */
    public function definition()
    {

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('observationname', 'observation'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) // 'Remove HTML tags from all activity names'
        {
            $mform->setType('name', PARAM_TEXT);
        }
        else
        {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'observationname', 'observation');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        $mform->addElement('header', 'intro_defaults', get_string('intro_defaults', OBSERVATION));

        $mform->addElement(
            'editor',
            'intro_assign_observer',
            get_string('intro_assign_observer', OBSERVATION),
            ['rows' => 10],
            ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context, 'subdirs' => false]);
        $mform->addHelpButton('intro_assign_observer', 'intro_assign_observer', OBSERVATION);
        $mform->setType('intro_assign_observer', PARAM_RAW); // no XSS prevention here, users must be trusted

        $mform->addElement(
            'editor',
            'default_intro_observer',
            get_string('default_intro_observer', OBSERVATION),
            ['rows' => 10],
            ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context, 'subdirs' => false]);
        $mform->addHelpButton('default_intro_observer', 'default_intro_observer', OBSERVATION);
        $mform->setType('default_intro_observer', PARAM_RAW); // no XSS prevention here, users must be trusted

        $mform->addElement(
            'editor',
            'default_intro_assessor',
            get_string('default_intro_assessor', OBSERVATION),
            ['rows' => 10],
            ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context, 'subdirs' => false]);
        $mform->addHelpButton('default_intro_assessor', 'default_intro_assessor', OBSERVATION);
        $mform->setType('default_intro_assessor', PARAM_RAW); // no XSS prevention here, users must be trusted

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    function add_completion_rules()
    {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completion_tasks', '', get_string('completion_tasks', 'observation'));
        $mform->setDefault('completion_tasks', true);
        return array('completion_tasks');
    }

    function completion_rule_enabled($data)
    {
        return !empty($data['completion_tasks']);
    }

}
