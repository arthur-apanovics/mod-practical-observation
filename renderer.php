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

use core\output\flex_icon;
use mod_observation\observation;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// require_once($CFG->dirroot . '/mod/observation/lib.php');

class mod_observation_renderer extends plugin_renderer_base
{
    public function __construct(moodle_page $page, $target)
    {
        // TODO: REMOVE CSS IMPORT WHEN PORTED TO .less
        echo '<link rel="stylesheet" type="text/css" href="styles_temp.css">';

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

    public function activity_view(observation $observation, context_module $context)
    {
        $template_data = $observation->export_template_data();
        $out           = '';

        $out .= $this->render_from_template(OBSERVATION_MODULE . '/activity_view', $template_data);

        return $out;
    }
}

