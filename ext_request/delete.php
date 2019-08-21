<?php
// /*
//  * This file is part of Totara LMS
//  *
//  * Copyright (C) 2010 onwards Totara Learning Solutions LTD
//  *
//  * This program is free software; you can redistribute it and/or modify
//  * it under the terms of the GNU General Public License as published by
//  * the Free Software Foundation; either version 3 of the License, or
//  * (at your option) any later version.
//  *
//  * This program is distributed in the hope that it will be useful,
//  * but WITHOUT ANY WARRANTY; without even the implied warranty of
//  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  * GNU General Public License for more details.
//  *
//  * You should have received a copy of the GNU General Public License
//  * along with this program.  If not, see <http://www.gnu.org/licenses/>.
//  *
//  * @author David Curry <david.curry@totaralms.com>
//  * @package totara
//  * @subpackage totara_feedback360
//  */
//
// use mod_ojt\models\email_assignment;
// use mod_ojt\models\external_request;
//
// require_once(__DIR__ . '/../../../config.php');
//
// require_login();
//
// $assignid = required_param('assignid', PARAM_INT); // Email assignment id
// $email    = optional_param('email', '', PARAM_EMAIL); // The email field to check against.
//
// // Confirmation hash.
// $delete = optional_param('del', '', PARAM_ALPHANUM);
//
// // Set up some variables.
// $strdelrequest = get_string('removerequest', 'totara_feedback360');
//
// $email_assignment = new email_assignment($assignid);
// if (is_null($email_assignment->id))
// {
//     print_error('error:invalidparams');
// }
// if ($email_assignment->email != $email)
// {
//     // There is something wrong here, these should always match.
//     print_error('error:invalidparams');
// }
//
// $external_request =
//     new external_request($email_assignment->externalrequestid); //$DB->get_record('ojt_external_request', ['id' => $email_assignment->externalrequestid]);
// // $user_ojt = new user_ojt($ojtid, $userid);
//
// $usercontext   = context_user::instance($user_assignment->userid);
// $systemcontext = context_system::instance();
//
// // // Check user has permission to request feedback.
// // if ($USER->id == $external_request->userid)
// // {
// //     require_capability('mod/ojt:', $systemcontext);
// // }
// // else
// // {
// //     print_error('error:accessdenied', 'totara_feedback360');
// // }
//
// $returnurl = new moodle_url('/mod/ojt/request.php',
//     array(
//         'action' => 'users',
//         'cmid'   => $external_request->ojtid,
//         'topicid' => $external_request->topicid,
//         'userid' => $external_request->userid
//     ));
//
// // Set up the page.
// $urlparams = array('assignid' => $email_assignment->id, 'email' => $email_assignment->email);
// $PAGE->set_url(new moodle_url('/mod/ojt/ext_request/delete.php'), $urlparams);
// $PAGE->set_context($systemcontext);
// $PAGE->set_pagelayout('admin');
// $PAGE->set_title($strdelrequest);
// $PAGE->set_heading($strdelrequest);
//
// if ($delete && !empty($email_assignment))
// {
//     require_sesskey();
//
//     // Delete.
//     if ($delete != md5($email_assignment->timeassigned))
//     {
//         print_error('error:requestdeletefailure', 'totara_feedback360');
//     }
//
//     if (isset($resp_assignment->feedback360emailassignmentid))
//     {
//         // Delete email.
//         $DB->delete_records('feedback360_email_assignment',
//             array('id' => $resp_assignment->feedback360emailassignmentid));
//     }
//
//     // Then delete the assignment.
//     $DB->delete_records('feedback360_resp_assignment', array('id' => $resp_assignment->id));
//
//     //TODO deleted event
//     // \totara_feedback360\event\request_deleted::create_from_instance($resp_assignment, $user_assignment->userid, $email)
//     //                                          ->trigger();
//
//     totara_set_notification(get_string('feedback360requestdeleted', 'totara_feedback360'), $returnurl,
//         array('class' => 'notifysuccess'));
// }
// else
// {
//     // Display confirmation page.
//     echo $OUTPUT->header();
//     $delete_params = array('respid'  => $respid,
//                            'email'   => $email,
//                            'del'     => md5($resp_assignment->timeassigned),
//                            'sesskey' => sesskey());
//
//     $deleteurl = new moodle_url('/totara/feedback360/request/delete.php', $delete_params);
//     if (!empty($email))
//     {
//         $username = $email;
//     }
//     else
//     {
//         $username = fullname($DB->get_record('user', array('id' => $resp_assignment->userid)));
//     }
//
//     echo $OUTPUT->confirm(get_string('removerequestconfirm', 'totara_feedback360', $username), $deleteurl, $returnurl);
//
//     echo $OUTPUT->footer();
// }
