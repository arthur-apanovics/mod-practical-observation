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

use mod_observation\external_request;
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
        global $CFG;

        $mform =& $this->_form;
        $cmid = $this->_customdata['id'];
        $cm = get_coursemodule_from_id(OBSERVATION, $cmid);
        $context = context_module::instance($cm->id);

        $mform->addElement('text', 'name', get_string('task_name', OBSERVATION));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $task_intros = [
            mod_observation\task::COL_INTRO_ASSESSOR,
            mod_observation\task::COL_INTRO_LEARNER,
            mod_observation\task::COL_INTRO_OBSERVER,
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
                [
                    'maxfiles' => EDITOR_UNLIMITED_FILES,
                    'noclean'  => true,
                    'context'  => $context,
                    'subdirs'  => false
                ]);
            $mform->addHelpButton($element, $element, OBSERVATION);
            $mform->setType($element, PARAM_RAW);// no XSS prevention here, users must be trusted
        }

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $cmid);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true);
    }
}
