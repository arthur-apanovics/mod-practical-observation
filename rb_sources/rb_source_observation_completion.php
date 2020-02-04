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

class rb_source_observation_completion extends rb_base_source
{
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null)
    {
        if ($groupid instanceof rb_global_restriction_set)
        {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/observation/lib.php');

        $this->base = "(
            SELECT " . $DB->sql_concat('ub.courseid', "'-'", 'ub.userid', "'-'", 'ub.observationid', "'-'", 'ub.topicid', "'-'",
                'ub.type') . " AS id,
            ub.courseid, ub.userid, ub.observationid, ub.topicid, ub.type, bc.status, bc.timemodified, bc.observeremail
            FROM (
                (SELECT ue.courseid, ue.userid, b.id AS observationid, 0 AS topicid," . completion::COMP_TYPE_Observation . " AS type
                FROM
                    (SELECT distinct courseid, userid
                    FROM {enrol} e
                    JOIN {user_enrolments} ue ON e.id = ue.enrolid) ue
                JOIN {observation} b ON ue.courseid = b.course)
                UNION
                (SELECT ue.courseid, ue.userid, b.id AS observationid, t.id AS topicid," . completion::COMP_TYPE_TOPIC . " AS type
                FROM
                    (SELECT DISTINCT courseid, userid
                    FROM {enrol} e
                    JOIN {user_enrolments} ue ON e.id = ue.enrolid) ue
                JOIN {observation} b ON ue.courseid = b.course
                JOIN {observation_topic} t ON b.id = t.observationid)
            ) AS ub
            LEFT JOIN {observation_completion} bc
                ON bc.userid = ub.userid
                AND bc.observationid = ub.observationid
                AND bc.topicid = ub.topicid
                AND bc.type = ub.type
            ORDER BY courseid, userid, observationid, topicid
        )";

        $this->joinlist        = $this->define_joinlist();
        $this->columnoptions   = $this->define_columnoptions();
        $this->filteroptions   = $this->define_filteroptions();
        $this->contentoptions  = $this->define_contentoptions();
        $this->paramoptions    = $this->define_paramoptions();
        $this->defaultcolumns  = $this->define_defaultcolumns();
        $this->defaultfilters  = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle     = get_string('observationcompletion', 'rb_source_observation_completion');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported()
    {
        return true;
    }


    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist()
    {
        global $CFG;

        // to get access to constants
        require_once($CFG->dirroot . '/mod/observation/lib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');

        $joinlist = array(
            new rb_join(
                'observation',
                'LEFT',
                '{observation}',
                'base.observationid = observation.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'observation_topic',
                'LEFT',
                '{observation_topic}',
                'base.topicid = observation_topic.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'observation_topic_signoff',
                'LEFT',
                '{observation_topic_signoff}',
                'base.topicid = observation_topic_signoff.topicid
                    AND base.userid = observation_topic_signoff.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'modifyuser',
                'LEFT',
                '{user}',
                'base.modifiedby = modifyuser.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'topicsignoffuser',
                'LEFT',
                '{user}',
                'observation_topic_signoff.modifiedby = topicsignoffuser.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'observation_topic_signoff'
            ),
        );

        // include some standard joins
        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_course_table_to_joinlist($joinlist, 'base', 'courseid');
        // requires the course join
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_core_tag_tables_to_joinlist('core', 'course', $joinlist, 'base', 'courseid');
        $this->add_cohort_course_tables_to_joinlist($joinlist, 'base', 'courseid');

        return $joinlist;
    }

    protected function define_columnoptions()
    {
        global $DB;

        $columnoptions = array(
            new rb_column_option(
                'observation',
                'name',
                get_string('observation', 'rb_source_observation_completion'),
                'observation.name',
                array('joins'       => 'observation',
                      'displayfunc' => 'observation_link',
                      'extrafields' => array('userid' => 'base.userid', 'observationid' => 'base.observationid'))
            ),
            new rb_column_option(
                'observation',
                'evaluatelink',
                get_string('evaluatelink', 'rb_source_observation_completion'),
                'observation.name',
                array('joins'       => 'observation',
                      'displayfunc' => 'observation_evaluate_link',
                      'extrafields' => array('userid' => 'base.userid', 'observationid' => 'base.observationid'))
            ),

            new rb_column_option(
                'observation_topic',
                'name',
                get_string('topic', 'rb_source_observation_completion'),
                'observation_topic.name',
                array('joins' => 'observation_topic')
            ),
            new rb_column_option(
                'observation_topic_signoff',
                'signedoff',
                get_string('topicsignedoff', 'rb_source_observation_completion'),
                'observation_topic_signoff.signedoff',
                array('joins' => 'observation_topic_signoff', 'displayfunc' => 'observation_topic_signedoff')
            ),
            new rb_column_option(
                'observation_topic_signoff',
                'timemodified',
                get_string('topicsignedofftime', 'rb_source_observation_completion'),
                'observation_topic_signoff.timemodified',
                array('joins' => 'observation_topic_signoff', 'displayfunc' => 'nice_datetime')
            ),
            new rb_column_option(
                'observation_topic_signoff',
                'topicsignoffuser',
                get_string('topicsignoffuser', 'rb_source_observation_completion'),
                $DB->sql_fullname("topicsignoffuser.firstname", "topicsignoffuser.lastname"),
                array(
                    'joins'       => 'topicsignoffuser',
                    'displayfunc' => 'link_user',
                    'extrafields' => array('user_id' => "topicsignoffuser.id"),
                )

            ),
            new rb_column_option(
                'base',
                'status',
                get_string('completionstatus', 'rb_source_observation_completion'),
                'base.status',
                array('displayfunc' => 'observation_completion_status')
            ),
            new rb_column_option(
                'base',
                'type',
                get_string('type', 'rb_source_observation_completion'),
                'base.type',
                array('displayfunc' => 'observation_type')
            ),
            new rb_column_option(
                'base',
                'timemodified',
                get_string('timemodified', 'rb_source_observation_completion'),
                'base.timemodified',
                array('displayfunc' => 'nice_datetime')
            ),
            new rb_column_option(
                'base',
                'modifiedby',
                get_string('modifiedby', 'rb_source_observation_completion'),
                $DB->sql_fullname("modifyuser.firstname", "modifyuser.lastname"),
                array(
                    'joins'       => 'modifyuser',
                    'displayfunc' => 'link_user',
                    'extrafields' => array('user_id' => "modifyuser.id"),
                )
            ),
        );

        // include some standard columns
        $this->add_user_fields_to_columns($columnoptions);
        $this->add_course_fields_to_columns($columnoptions);
        $this->add_course_category_fields_to_columns($columnoptions);
        $this->add_job_assignment_fields_to_columns($columnoptions);
        $this->add_core_tag_fields_to_columns('core', 'course', $columnoptions);
        $this->add_cohort_course_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions()
    {
        $filteroptions = array(

            new rb_filter_option(
                'observation',
                'name',
                get_string('observationname', 'rb_source_observation_completion'),
                'text'
            ),
            new rb_filter_option(
                'observation_topic',
                'name',
                get_string('topicname', 'rb_source_observation_completion'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'timemodified',
                get_string('timemodified', 'rb_source_observation_completion'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'status',
                get_string('completionstatus', 'rb_source_observation_completion'),
                'select',
                array(
                    'selectfunc' => 'observation_completion_status_list',
                )
            ),
            new rb_filter_option(
                'base',
                'type',
                get_string('type', 'rb_source_observation_completion'),
                'select',
                array(
                    'selectfunc' => 'observation_type_list',
                )
            ),

        );

        // include some standard filters
        $this->add_user_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);
        $this->add_course_category_fields_to_filters($filteroptions);
        $this->add_job_assignment_fields_to_filters($filteroptions);
        $this->add_core_tag_fields_to_filters('core', 'course', $filteroptions);
        $this->add_cohort_course_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions()
    {
        $contentoptions = array();
        $this->add_basic_user_content_options($contentoptions);
        $contentoptions[] = new rb_content_option(
            'observation_completion_type',
            get_string('observationcompletiontype', 'rb_source_observation_completion'),
            'base.type',
            'base'
        );
        return $contentoptions;
    }

    protected function define_paramoptions()
    {
        $paramoptions = array(
            new rb_param_option(
                'observationid',
                'base.observationid'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns()
    {
        $defaultcolumns = array(
            array(
                'type'  => 'user',
                'value' => 'namelink',
            ),
            array(
                'type'  => 'course',
                'value' => 'courselink',
            ),
            array(
                'type'  => 'observation',
                'value' => 'name',
            ),
            array(
                'type'  => 'observation_topic',
                'value' => 'name',
            ),
            array(
                'type'  => 'base',
                'value' => 'type',
            ),
            array(
                'type'  => 'base',
                'value' => 'status',
            ),

        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters()
    {
        $defaultfilters = array(
            array(
                'type'  => 'observation',
                'value' => 'name',
            ),
            array(
                'type'  => 'observation_topic',
                'value' => 'name',
            ),
            array(
                'type'  => 'base',
                'value' => 'type',
            ),
            array(
                'type'  => 'base',
                'value' => 'status',
            ),
            array(
                'type'  => 'user',
                'value' => 'fullname',
            ),
            array(
                'type'     => 'course',
                'value'    => 'fullname',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }

    protected function define_requiredcolumns()
    {
        $requiredcolumns = array();
        return $requiredcolumns;
    }

    //
    //
    // Source specific column display methods
    //
    //

    function rb_display_observation_completion_status($status, $row, $isexport)
    {
        if (empty($status))
        {
            return get_string('completionstatus' . completion::STATUS_INCOMPLETE, 'observation'); //TODO
        }
        else
        {
            return get_string('completionstatus' . $status, 'observation');
        }
    }

    function rb_display_observation_type($type, $row, $isexport)
    {
        return get_string('type' . $type, 'observation');
    }

    function rb_display_observation_link($observationname, $row, $isexport)
    {
        return html_writer::link(new moodle_url('/mod/observation/evaluate.php',
            array('userid' => $row->userid, 'bid' => $row->observationid)), $observationname);

    }

    function rb_display_observation_evaluate_link($observationname, $row, $isexport)
    {
        return html_writer::link(new moodle_url('/mod/observation/evaluate.php',
            array('userid' => $row->userid, 'bid' => $row->observationid)), get_string('evaluate', 'rb_source_observation_completion'));

    }

    function rb_display_observation_topic_signedoff($signedoff, $row, $isexport)
    {

        return !empty($signedoff) ? get_string('yes') : get_string('no');

    }




    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_observation_completion_status_list()
    {
        $statuses   =
            array(completion::STATUS_INCOMPLETE, completion::STATUS_REQUIREDCOMPLETE, completion::STATUS_COMPLETE);
        $statuslist = array();
        foreach ($statuses as $status)
        {
            $statuslist[$status] = get_string('completionstatus' . $status, 'observation');
        }

        return $statuslist;
    }

    function rb_filter_observation_type_list()
    {
        $types    = array(completion::COMP_TYPE_Observation, completion::COMP_TYPE_TOPIC);
        $typelist = array();
        foreach ($types as $type)
        {
            $typelist[$type] = get_string('type' . $type, 'observation');
        }

        return $typelist;
    }

    /**
     * Unit test data
     */

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase)
    {
        global $DB;

        if (!PHPUNIT_TEST)
        {
            throw new coding_exception('phpunit_prepare_test_data() cannot be used outside of unit tests');
        }
        $data = array(
            'observation'             => array(
                array('id' => 1, 'course' => 1, 'name' => 'test observation', 'intro' => '', 'timecreated' => 1)
            ),
            'observation_topic'       => array(
                array('id' => 1, 'observationid' => 1, 'name' => 'test observation topic')
            ),
            'observation_topic_item'  => array(
                array('id' => 1, 'observationid' => 1, 'topicid' => 1, 'name' => 'test observation topic item')
            ),
            'observation_completion'  => array(
                array('id'          => 1,
                      'userid'      => 2,
                      'type'        => 0,
                      'observationid'       => 1,
                      'topicid'     => 0,
                      'topicitemid' => 0,
                      'status'      => 1,
                      'modifiedby'  => 1),
                array('id'          => 2,
                      'userid'      => 2,
                      'type'        => 1,
                      'observationid'       => 1,
                      'topicid'     => 1,
                      'topicitemid' => 0,
                      'status'      => 1,
                      'modifiedby'  => 1),
                array('id'          => 3,
                      'userid'      => 2,
                      'type'        => 2,
                      'observationid'       => 1,
                      'topicid'     => 1,
                      'topicitemid' => 1,
                      'status'      => 1,
                      'modifiedby'  => 1),
            ),
            'user_enrolments' => array(
                array('id' => 1, 'status' => 0, 'enrolid' => 1, 'userid' => 2)
            ),
        );
        foreach ($data as $table => $data)
        {
            foreach ($data as $datarow)
            {
                $DB->import_record($table, $datarow);
            }
            $DB->get_manager()->reset_sequence(new xmldb_table($table));
        }
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption)
    {
        if (!PHPUNIT_TEST)
        {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 2;
    }

} // end of rb_source_course_completion class


/**
 * Restrict content by observation completion type
 *
 * Pass in an integer that represents a observation completion type, e.g Observation_CTYPE_TOPIC
 */
class rb_observation_completion_type_content extends rb_base_content
{

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string  $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/observation/lib.php');

        // remove rb_ from start of classname
        $type     = substr(get_class($this), 3);
        $settings = reportbuilder::get_all_settings($reportid, $type);

        return array('base.type = :crbct', array('crbct' => $settings['completiontype']));
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string  $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid)
    {
        global $DB;

        // remove rb_ from start of classname
        $type     = substr(get_class($this), 3);
        $settings = reportbuilder::get_all_settings($reportid, $type);

        return !empty($settings['completiontype']) ?
            $title . ' - ' . get_string('type' . $settings['completiontype'], 'observation') : '';
    }


    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string  $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title)
    {
        // get current settings
        // remove rb_ from start of classname
        $type           = substr(get_class($this), 3);
        $enable         = reportbuilder::get_setting($reportid, $type, 'enable');
        $completiontype = reportbuilder::get_setting($reportid, $type, 'completiontype');

        $mform->addElement('header', 'observation_completion_type_header',
            get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('observation_completion_type_header');
        $mform->addElement('checkbox', 'observation_completion_type_enable', '',
            get_string('completiontypeenable', 'rb_source_observation_completion'));
        $mform->setDefault('observation_completion_type_enable', $enable);
        $mform->disabledIf('observation_completion_type_enable', 'contentenabled', 'eq', 0);
        $radiogroup   = array();
        $radiogroup[] =& $mform->createElement('radio', 'observation_completion_type_completiontype',
            '', get_string('type' . completion::COMP_TYPE_Observation, 'observation'), completion::COMP_TYPE_Observation);
        $radiogroup[] =& $mform->createElement('radio', 'observation_completion_type_completiontype',
            '', get_string('type' . completion::COMP_TYPE_TOPIC, 'observation'), completion::COMP_TYPE_TOPIC);
        $mform->addGroup($radiogroup, 'observation_completion_type_completiontype_group',
            get_string('includecompltyperecords', 'rb_source_observation_completion'), html_writer::empty_tag('br'), false);
        $mform->setDefault('observation_completion_type_completiontype', $completiontype);
        $mform->disabledIf('observation_completion_type_completiontype_group', 'contentenabled',
            'eq', 0);
        $mform->disabledIf('observation_completion_type_completiontype_group', 'observation_completion_type_enable',
            'notchecked');
    }


    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object  $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform)
    {
        $status = true;
        // remove rb_ from start of classname
        $type = substr(get_class($this), 3);

        // enable checkbox option
        $enable = (isset($fromform->observation_completion_type_enable) &&
                   $fromform->observation_completion_type_enable) ? 1 : 0;
        $status = $status && reportbuilder::update_setting($reportid, $type,
                'enable', $enable);

        // recursive radio option
        $recursive = isset($fromform->observation_completion_type_completiontype) ?
            $fromform->observation_completion_type_completiontype : 0;
        $status    = $status && reportbuilder::update_setting($reportid, $type,
                'completiontype', $recursive);

        return $status;
    }
}

