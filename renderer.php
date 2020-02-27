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

defined('MOODLE_INTERNAL') || die();

use core\notification;
use mod_observation\lib;
use mod_observation\observation;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// require_once($CFG->dirroot . '/mod/observation/lib.php');

class mod_observation_renderer extends plugin_renderer_base
{
    public function __construct(moodle_page $page, $target)
    {
        parent::__construct($page, $target);
    }

    // /**
    //  * @param user_topic $topic
    //  * @return string icon html
    //  * @throws coding_exception
    //  */
    // public function get_completion_icon(user_topic $topic): string
    // {
    //     switch ($topic->completion_status)
    //     {
    //         case completion::STATUS_COMPLETE:
    //             $completionicon = 'check-success';
    //             break;
    //         case completion::STATUS_REQUIREDCOMPLETE:
    //             $completionicon = 'check-warning';
    //             break;
    //         default:
    //             $completionicon = 'times-danger';
    //     }
    //     if (!empty($completionicon))
    //     {
    //         $completionicon = $this->output->flex_icon($completionicon,
    //             ['alt' => get_string('completionstatus' . $topic->completion_status, 'observation')]);
    //     }
    //     $completionicon = html_writer::tag('span', $completionicon, ['class' => 'observation-topic-status']);
    //
    //     return $completionicon;
    // }

    // /**
    //  * @param string $observation_name
    //  * @param string $username
    //  * @return string
    //  * @throws coding_exception
    //  */
    // function get_print_button(string $observation_name, string $username)
    // {
    //     global $OUTPUT;
    //
    //     $out = '';
    //
    //     $out .= html_writer::start_tag('a', array('href' => 'javascript:window.print()', 'class' => 'evalprint'));
    //     $out .= html_writer::empty_tag('img',
    //         array(
    //             'src'   => $OUTPUT->pix_url('t/print'),
    //             'alt'   => get_string('printthisobservation', 'observation'),
    //             'class' => 'icon'));
    //     $out .= get_string('printthisobservation', 'observation');
    //     $out .= html_writer::end_tag('a');
    //     $out .= $OUTPUT->heading(get_string('observationxforx', 'observation',
    //         (object) array(
    //             'observation'  => format_string($observation_name),
    //             'user' => $username)));
    //
    //     return $out;
    // }

    // protected function list_topic_item_files($contextid, $userid, $topicitemid)
    // {
    //     $out = array();
    //
    //     $fs = get_file_storage();
    //     $files = $fs->get_area_files($contextid, 'mod_observation', 'topicitemfiles' . $topicitemid, $userid,
    //         'itemid, filepath, filename', false);
    //
    //     foreach ($files as $file)
    //     {
    //         $filename = $file->get_filename();
    //         $url =
    //             moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
    //                 $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    //         $out[] = html_writer::link($url, $filename);
    //     }
    //     $br = html_writer::empty_tag('br');
    //
    //     return implode($br, $out);
    // }

    // /**
    //  * @param int         $observationid
    //  * @param int         $userid
    //  * @param string|null $token email assignment token for external evaluations
    //  * @return array [args[], jsmodule[]]
    //  */
    // public function get_evaluation_js_args(int $observationid, int $userid, string $token = null)
    // {
    //     $args     = array(
    //         'args' =>
    //             '{ "observationid":' . $observationid .
    //             ', "userid":' . $userid .
    //             ', "token":"' . $token . '"' .
    //             ', "Observation_COMPLETE":' . completion::STATUS_COMPLETE .
    //             ', "Observation_REQUIREDCOMPLETE":' . completion::STATUS_REQUIREDCOMPLETE .
    //             ', "Observation_INCOMPLETE":' . completion::STATUS_INCOMPLETE .
    //             '}');
    //     $jsmodule = array(
    //         'name'     => 'mod_observation_evaluate',
    //         'fullpath' => '/mod/observation/js/evaluate.js',
    //         'requires' => array('json')
    //     );
    //
    //     return array($args, $jsmodule);
    // }

    // private function get_topic_submission_status_icon(user_topic $topic)
    // {
    //     $date_str = get_string('nosubmissiondate', 'mod_observation');
    //     $icon_id = 'square-o';
    //
    //     if (!is_null($topic->external_request))
    //     {
    //         $date = $topic->external_request->get_first_email_assign_date();
    //         if ($date > 0)
    //         {
    //             $date_str = get_string('submissiondate', 'mod_observation', userdate($date));
    //             $icon_id = 'check-square-o';
    //         }
    //     }
    //
    //     return $this->output->flex_icon($icon_id, ['alt' => $date_str]);
    // }

    /**
     * Sets 'confirm' as a boolean in GET request to check result
     *
     * @param string     $confirmation_text
     * @param array|null $additional_params
     * @throws coding_exception
     */
    public function echo_confirmation_page_and_die(string $confirmation_text, array $additional_params = null)
    {
        $confirm_url = $this->page->url;
        $confirm_url->params(['confirm' => 1, 'sesskey' => sesskey()] + $additional_params);

        echo $this->output->header();
        echo $this->output->confirm($confirmation_text, $confirm_url, $this->page->url);
        echo $this->output->footer();

        die();
    }

    /**
     * Render main activity view
     *
     * @param observation $observation
     * @return string
     * @throws moodle_exception
     */
    public function activity_view(observation $observation)
    {
        $template_data = $observation->export_template_data();
        $caps = $template_data['capabilities'];
        $out = '';

        $out .= $this->activity_header($template_data);

        // assessor view
        if ($caps['can_assess'] || $caps['can_viewsubmissions'])
        {
            // TODO: ASSESSOR TABLE
            $out .= $this->render_from_template('/assessor_table', $template_data);
        }
        // learner view or preview
        else if ($caps['can_submit'] || $caps['can_view'])
        {
            // submission/preview logic in template
            $out .= $this->render_from_template('/activity_view', $template_data);
        }

        // validation for 'managers'
        if ($caps['can_manage'])
        {
            // check all tasks have criteria
            if (!$observation->all_tasks_have_criteria())
            {
                notification::warning(get_string('manage:missing_criteria', 'observation'));
            }

            $out .= $this->render_from_template('/part_edit_tasks_button', $template_data);
        }

        return $out;
    }

    /**
     * Renders activity header with title and description
     *
     * @param array $template_data has to contain ['name' => string, 'intro' => string], everything else will be ignored
     * @return string html
     */
    private function activity_header(array $template_data): string
    {
        return $this->render_from_template(
            '/part_activity_header',
            [
                observation::COL_NAME  => $template_data[observation::COL_NAME],
                observation::COL_INTRO => $template_data[observation::COL_INTRO],
            ]);
    }

    public function render_from_template($templatename, $context)
    {
        if (strpos($templatename, OBSERVATION_MODULE) === false)
        {
            $templatename = sprintf('%s/%s', OBSERVATION_MODULE, $templatename);
        }

        return parent::render_from_template($templatename, $context); // TODO: Change the autogenerated stub
    }

    public function manage_view(observation $observation)
    {
        $template_data = $observation->export_template_data();
        $out = '';

        $out .= $this->render_from_template('/manage_tasks_view', $template_data);

        return $out;
    }

    public function task_learner_view(observation $observation, task $task)
    {
        global $USER;

        $template_data = $observation->export_template_data();
        $task_template_data = lib::find_in_assoc_array_key_value_or_null(
            $template_data['tasks'],
            task::COL_ID,
            $task->get_id_or_null());
        $caps = $template_data['capabilities'];
        $out = '';

        if ($caps['can_submit'])
        {
            // learner submission
            $submission = $task->get_current_learner_submission_or_create($USER->id);

            if ($submission->learner_can_attempt())
            {
                $attemp = $submission->get_latest_attempt_or_null();

                // get editor // TODO !!!!!!!!!!
                $editor_form = new observation_learner_editor_form();
                $editor_form->set_data($attemp->get_moodle_form_data());
                $task_template_data['extra']['editor_html'] = $editor_form->render();
            }

            // TODO: STATUS FAKE BLOCK

            $out .= $this->render_from_template('task_view_learner', $task_template_data);
        }
        else if ($caps['can_view'])
        {
            // preview
            $out .= $this->render_from_template('/task_view_preview', $task_template_data);
        }
        else
        {
            print_error('Sorry, you don\'t have permission to view this page');
            return $out;
        }

        return $out;
    }

    /**
     * Renders specified template with an activity header
     *
     * @param string     $templatename template name
     * @param array|null $context
     * @return string html
     */
    private function render_from_template_with_header(string $templatename, array $context = null)
    {
        return $this->activity_header($context) . $this->render_from_template($templatename, $context);
    }
}

