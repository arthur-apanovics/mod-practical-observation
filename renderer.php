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
use mod_observation\learner_attempt;
use mod_observation\learner_submission;
use mod_observation\lib;
use mod_observation\observation;
use mod_observation\task;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('forms.php');

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
            $out .= $this->render_from_template('part-assessor_table', $template_data);
        }
        // learner view or preview
        else if ($caps['can_submit'] || $caps['can_view'])
        {
            // submission/preview logic in template
            $out .= $this->render_from_template('view-activity', $template_data);
        }

        // validation for 'managers'
        if ($caps['can_manage'])
        {
            // check all tasks have criteria
            if (!$observation->all_tasks_have_criteria())
            {
                notification::warning(get_string('manage:missing_criteria', 'observation'));
            }

            $out .= $this->render_from_template('part-edit_tasks_button', $template_data);
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
            'part-activity_header', [
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

        $out .= $this->render_from_template('view-manage_tasks', $template_data);

        return $out;
    }

    public function task_learner_view(observation $observation, int $taskid)
    {
        global $USER;

        $task = new task($taskid, $USER->id);

        $task_template_data = $task->export_template_data();
        $caps = $observation->export_capabilities();
        $out = '';

        if ($caps['can_submit'])
        {
            // learner submission
            $submission = $task->get_current_learner_submission_or_create($USER->id);

            if ($submission->learner_can_attempt())
            {
                $attempt = $submission->get_latest_attempt_or_null();
                $context = context_module::instance($observation->get_cm()->id);

                // text editor
                $task_template_data['extra']['editor_html'] = $this->text_editor(
                    $attempt->get(learner_attempt::COL_TEXT),
                    $attempt->get(learner_attempt::COL_TEXT_FORMAT),
                    $context);

                // file manager
                $task_template_data['extra']['filemanager_html'] =
                    $this->files_input($attempt->get_id_or_null(), observation::FILE_AREA_TRAINEE, $context);

                // id's
                $task_template_data['extra']['learner_submission_id'] = $submission->get_id_or_null();
                $task_template_data['extra']['attempt_id'] = $attempt->get_id_or_null();

                // tell template that there will be a new attempt
                $task_template_data['extra']['is_submission'] = true;
                // save time later by including cmid while whe have it easily available
                $task_template_data['extra']['cmid'] = $observation->get_cm()->id;
            }

            // TODO: STATUS FAKE BLOCK

            $out .= $this->render_from_template('view-task_learner', $task_template_data);
        }
        else if ($caps['can_view'])
        {
            // preview
            $out .= $this->render_from_template('task_view_preview', $task_template_data);
        }
        else
        {
            print_error('Sorry, you don\'t have permission to view this page');
            return $out;
        }

        return $out;
    }

    public function text_editor(string $text, int $format, context $context): string
    {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');

        $input_name = lib::get_input_field_name_from_class(learner_attempt::class);
        $id = $input_name . '_id';
        $output = '';

        // get available formats
        $response_format = $format;
        $editor = editors_get_preferred_editor($response_format);
        $formats = $editor->get_supported_formats();

        $str_formats = format_text_menu();
        foreach ($formats as $fid)
        {
            $formats[$fid] = $str_formats[$fid];
        }

        // set existing text
        $editor->set_text($text);

        $editor->use_editor(
            $id, ['context' => $context, 'autosave' => true], ['return_types' => FILE_INTERNAL | FILE_EXTERNAL]);

        // editor wrapper
        $output .= html_writer::start_tag('div', ['class' => 'attempt-response']);
        // editor textarea
        $output .= html_writer::tag(
            'div', html_writer::tag(
            'textarea', s($text), [
            'id'   => $id,
            'name' => $input_name,
            'rows' => 10,
            'cols' => 50
        ]));

        // format wrapper
        $output .= html_writer::start_tag('div');
        if (count($formats) == 1)
        {
            // format id
            reset($formats);
            $output .= html_writer::empty_tag(
                'input', array(
                'type'  => 'hidden',
                'name'  => $input_name . '_format',
                'value' => key($formats)
            ));
        }
        else
        {
            // format selector for plain text editors
            $output .= html_writer::label(
                get_string('format'), 'menu' . $input_name . 'format', false);
            $output .= ' ';
            $output .= html_writer::select($formats, $input_name . 'format', $response_format, '');
        }

        // /editor wrapper
        $output .= html_writer::end_tag('div');
        // /format wrapper
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * @param int     $itemid for learner - attempt id, for observer & assessor - feedback id
     * @param context $context
     * @param int     $max_files
     * @return string
     * @throws coding_exception
     */
    private function files_input(int $itemid, string $file_area, context $context, int $max_files = 10): string
    {
        global $CFG;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $picker_options = new stdClass();
        $picker_options->mainfile = null;
        $picker_options->maxfiles = $max_files;
        $picker_options->context = $context;
        $picker_options->return_types = FILE_INTERNAL;

        $picker_options->itemid = $this->prepare_response_files_draft_itemid($file_area, $context->id, $itemid);

        // render
        $files_renderer = $this->page->get_renderer('core', 'files');
        $fm = new form_filemanager($picker_options);
        $out = '';

        $out .= $files_renderer->render($fm);
        $out .= html_writer::empty_tag(
            'input', array(
            'type'  => 'hidden',
            'name'  => 'attachments_itemid',
            'value' => $picker_options->itemid
        ));

        return $out;
    }

    /**
     * @param string $file_area
     * @param int    $contextid
     * @param int    $itemid
     * @return int the draft itemid.
     */
    public function prepare_response_files_draft_itemid(string $file_area, int $contextid, int $itemid = null)
    {
        $draftid = 0; // Will be filled in by file_prepare_draft_area.

        // if files exist for this itemid they will be automatically copied over
        file_prepare_draft_area(
            $draftid, $contextid, \OBSERVATION, $file_area, $itemid);

        return $draftid;
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

