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

use mod_observation\criteria;
use mod_observation\external_request;
use mod_observation\lib;
use mod_observation\observer;
use mod_observation\task;
use mod_observation\user_external_request;

if (!defined('MOODLE_INTERNAL'))
{
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/uploadlib.php');
require_once($CFG->dirroot . '/mod/observation/locallib.php');

class observation_task_form extends moodleform
{
    function definition()
    {
        $mform =& $this->_form;
        $cmid = $this->_customdata['cmid'];
        $taskid = $this->_customdata['taskid'];
        $cm = get_coursemodule_from_id(OBSERVATION, $cmid);
        $context = context_module::instance($cm->id);

        $element = task::COL_NAME;
        $mform->addElement('text', $element, get_string('task_name', OBSERVATION));
        $mform->setType($element, PARAM_TEXT);
        $mform->addRule($element, null, 'required', null, 'client');

        $task_intros = [
            mod_observation\task::COL_INTRO_LEARNER,
            mod_observation\task::COL_INTRO_OBSERVER,
            mod_observation\task::COL_INTRO_ASSESSOR,
            mod_observation\task::COL_INT_ASSIGN_OBS_LEARNER,
            mod_observation\task::COL_INT_ASSIGN_OBS_OBSERVER,
        ];
        foreach ($task_intros as $element)
        {
            $mform->addElement(
                'editor',
                $element,
                get_string($element, OBSERVATION),
                ['rows' => 10],
                lib::get_editor_file_options($context));
            $mform->addHelpButton($element, $element, OBSERVATION);
            $mform->addRule($element, get_string('required'), 'required', null);
            $mform->setType($element, PARAM_RAW);// no XSS prevention here, users must be trusted
        }

        // CMID
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $cmid);
        // TASK ID
        $mform->addElement('hidden', 'taskid');
        $mform->setType('taskid', PARAM_INT);
        $mform->setDefault('taskid', $taskid);

        $this->add_action_buttons(true);
    }
}

class observation_criteria_form extends moodleform
{
    function definition()
    {
        $mform =& $this->_form;
        $cmid = $this->_customdata['cmid'];
        $taskid = $this->_customdata['taskid'];
        $criteriaid = $this->_customdata['criteriaid'];
        $cm = get_coursemodule_from_id(OBSERVATION, $cmid);
        $context = context_module::instance($cm->id);

        // name
        $element = criteria::COL_NAME;
        $mform->addElement('text', $element, get_string('criteria_name', OBSERVATION));
        $mform->setType($element, PARAM_TEXT);
        $mform->addRule($element, null, 'required', null, 'client');

        // description
        $element = criteria::COL_DESCRIPTION;
        $mform->addElement(
            'editor',
            $element,
            get_string($element, OBSERVATION),
            ['rows' => 10],
            lib::get_editor_file_options($context));
        $mform->addHelpButton($element, $element, OBSERVATION);
        $mform->addRule($element, get_string('required'), 'required', null);
        $mform->setType($element, PARAM_RAW);// no XSS prevention here, users must be trusted

        // feedback required
        $element = criteria::COL_FEEDBACK_REQUIRED;
        $mform->addElement('advcheckbox', $element, get_string($element, 'observation'));
        $mform->addHelpButton($element, $element, OBSERVATION);
        $mform->setDefault($element, false);

        // CMID
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $cmid);
        // TASK ID
        $mform->addElement('hidden', 'taskid');
        $mform->setType('taskid', PARAM_INT);
        $mform->setDefault('taskid', $taskid);
        // CRITERIA ID
        $mform->addElement('hidden', 'criteriaid');
        $mform->setType('criteriaid', PARAM_INT);
        $mform->setDefault('criteriaid', $criteriaid);

        $this->add_action_buttons(true);
    }
}


class observation_assign_observer_form extends moodleform
{
    function definition()
    {
        $mform =& $this->_form;
        $mform->updateAttributes(['id' => 'assign-observer-form']);

        $cmid = $this->_customdata['id'];
        $learner_task_submission_id = $this->_customdata['learner_task_submission_id'];

        $element = 'assign_observer:header';
        $mform->addElement('header', $element, get_string('assign_observer:header', OBSERVATION));
        $mform->setExpanded($element);

        // name
        $element = observer::COL_FULLNAME;
        $mform->addElement('text', $element, get_string($element, OBSERVATION));
        $mform->setType($element, PARAM_TEXT);
        $mform->addRule($element, null, 'required', null, 'client');

        // phone
        $element = observer::COL_PHONE;
        $mform->addElement('text', $element, get_string($element, OBSERVATION));
        $mform->setType($element, PARAM_TEXT);
        $mform->addRule($element, null, 'required', null, 'client');
        $mform->addRule(
            $element,
            get_string('assign_observer:phone_validation_message', 'observation'),
            'regex',
            '/(0|(\+64(\s|-)?)){1}(21|22|27){1}(\s|-)?\d{3}(\s|-)?\d{4}/',
            'client');

        // email
        $element = observer::COL_EMAIL;
        $mform->addElement('text', $element, get_string($element, OBSERVATION));
        $mform->setType($element, PARAM_EMAIL);
        $mform->addRule($element, null, 'required', null, 'client');
        $mform->addRule(
            $element,
            get_string('assign_observer:email_validation_message', 'observation'),
            'regex',
            '/^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i',
            'client');

        // position
        $element = observer::COL_POSITION_TITLE;
        $mform->addElement('text', $element, get_string($element, OBSERVATION));
        $mform->setType($element, PARAM_TEXT);
        $mform->addRule($element, null, 'required', null, 'client');

        // message
        $element = 'message';
        $mform->addElement(
            'textarea', $element, get_string($element, OBSERVATION),
            [
                'rows'        => 4,
                'cols'        => 60,
                'placeholder' => get_string('message_placeholder', 'observation')
            ]);
        $mform->setType($element, PARAM_TEXT);

        // CMID
        $element = 'id';
        $mform->addElement('hidden', $element);
        $mform->setType($element, PARAM_INT);
        $mform->setDefault($element, $cmid);
        // submission id
        $element = 'learner_task_submission_id';
        $mform->addElement('hidden', $element);
        $mform->setType($element, PARAM_INT);
        $mform->setDefault($element, $learner_task_submission_id);

        $this->add_action_buttons(false, get_string('send', 'observation'));
    }
}
