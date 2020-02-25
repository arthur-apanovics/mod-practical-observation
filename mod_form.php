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
 * The main observation configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 */

use mod_observation\lib;

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
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $element = mod_observation\observation::COL_NAME;
        // Adding the standard "name" field.
        $mform->addElement('text', $element, get_string($element, OBSERVATION), array('size' => '64'));
        // 'Remove HTML tags from all activity names'?
        $type = !empty($CFG->formatstringstriptags)
            ? PARAM_TEXT
            : PARAM_CLEAN;
        $mform->setType($element, $type);
        $mform->addRule($element, null, 'required', null, 'client');
        $mform->addRule($element, get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton($element, $element, OBSERVATION);

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        $mform->addElement('header', 'intro_defaults', get_string('intro_defaults', OBSERVATION));
        $mform->setExpanded('intro_defaults', true);

        $default_intros = [
            mod_observation\observation::COL_DEF_I_TASK_LEARNER,
            mod_observation\observation::COL_DEF_I_TASK_OBSERVER,
            mod_observation\observation::COL_DEF_I_TASK_ASSESSOR,
            mod_observation\observation::COL_DEF_I_ASS_OBS_LEARNER,
            mod_observation\observation::COL_DEF_I_ASS_OBS_OBSERVER,
        ];
        foreach ($default_intros as $element)
        {
            $mform->addElement(
                'editor',
                $element,
                get_string($element, OBSERVATION),
                ['rows' => 10],
                lib::get_editor_file_options($this->context));
            $mform->addHelpButton($element, $element, OBSERVATION);
            $mform->setType($element, PARAM_RAW);// no XSS prevention here, users must be trusted
        }

        //------------------------------------AVAILABILITY-------------------------------------------
        $mform->addElement('header', 'availabilityhdr', get_string('availability'));

        // date open
        $element = mod_observation\observation::COL_TIMEOPEN;
        $mform->addElement(
            'date_time_selector',
            $element,
            get_string($element, OBSERVATION),
            array('optional' => true));

        //date closed
        $element = mod_observation\observation::COL_TIMECLOSE;
        $mform->addElement(
            'date_time_selector',
            $element,
            get_string($element, OBSERVATION),
            array('optional' => true));

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

        $element = mod_observation\observation::COL_COMPLETION_TASKS;
        $mform->addElement('advcheckbox', $element, '', get_string($element, OBSERVATION));
        $mform->setDefault('completion_tasks', true);
        return array('completion_tasks');
    }

    function completion_rule_enabled($data)
    {
        return !empty($data['completion_tasks']);
    }

}
