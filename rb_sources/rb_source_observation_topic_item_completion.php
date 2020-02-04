<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Arthur Apanovics <arthur.a@likeminded.co.nz>
 * @package mod
 * @subpackage observation
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_observation_topic_item_completion extends rb_base_source
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

        $this->base            = '{observation_completion}';
        $this->joinlist        = $this->define_joinlist();
        $this->columnoptions   = $this->define_columnoptions();
        $this->filteroptions   = $this->define_filteroptions();
        $this->contentoptions  = $this->define_contentoptions();
        $this->paramoptions    = $this->define_paramoptions();
        $this->defaultcolumns  = $this->define_defaultcolumns();
        $this->defaultfilters  = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle     = get_string('observationtopicitemcompletion', 'rb_source_observation_topic_item_completion');
        $this->sourcewhere     = 'base.type = ' . completion::COMP_TYPE_TOPICITEM; //TODO

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
                'observation_topic_item',
                'LEFT',
                '{observation_topic_item}',
                'base.topicitemid = observation_topic_item.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'modifyuser',
                'LEFT',
                '{user}',
                'base.modifiedby = modifyuser.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        // include some standard joins
        $this->add_user_table_to_joinlist($joinlist, 'base', 'userid');
        $this->add_course_table_to_joinlist($joinlist, 'observation', 'course');
        // requires the course join
        $this->add_course_category_table_to_joinlist($joinlist,
            'course', 'category');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'base', 'userid');
        $this->add_core_tag_tables_to_joinlist('core', 'course', $joinlist, 'observation', 'course');
        $this->add_cohort_course_tables_to_joinlist($joinlist, 'observation', 'course');

        return $joinlist;
    }

    protected function define_columnoptions()
    {
        global $DB;

        $columnoptions = array(
            new rb_column_option(
                'observation',
                'name',
                get_string('observation', 'rb_source_observation_topic_item_completion'),
                'observation.name',
                array('joins'       => 'observation',
                      'displayfunc' => 'observation_link',
                      'extrafields' => array('userid' => 'base.userid', 'observationid' => 'base.observationid'))
            ),
            new rb_column_option(
                'observation',
                'evaluatelink',
                get_string('evaluatelink', 'rb_source_observation_topic_item_completion'),
                'observation.name',
                array('joins'       => 'observation',
                      'displayfunc' => 'observation_evaluate_link',
                      'extrafields' => array('userid' => 'base.userid', 'observationid' => 'base.observationid'))
            ),
            new rb_column_option(
                'observation_topic',
                'name',
                get_string('topic', 'rb_source_observation_topic_item_completion'),
                'observation_topic.name',
                array('joins' => 'observation_topic')
            ),
            new rb_column_option(
                'observation_topic_item',
                'name',
                get_string('topicitem', 'rb_source_observation_topic_item_completion'),
                'observation_topic_item.name',
                array('joins' => 'observation_topic_item')
            ),
            new rb_column_option(
                'base',
                'status',
                get_string('completionstatus', 'rb_source_observation_topic_item_completion'),
                'base.status',
                array('displayfunc' => 'observation_completion_status')
            ),
            new rb_column_option(
                'base',
                'timemodified',
                get_string('timemodified', 'rb_source_observation_topic_item_completion'),
                'base.timemodified',
                array('displayfunc' => 'nice_datetime')
            ),
            new rb_column_option(
                'base',
                'modifiedby',
                get_string('modifiedby', 'rb_source_observation_topic_item_completion'),
                $DB->sql_fullname("modifyuser.firstname", "modifyuser.lastname"),
                array(
                    'joins'       => 'modifyuser',
                    'displayfunc' => 'link_user',
                    'extrafields' => array('user_id' => "modifyuser.id"),
                )
            ),
            new rb_column_option(
                'base',
                'comment',
                get_string('comment', 'rb_source_observation_topic_item_completion'),
                'base.comment'
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
                get_string('observationname', 'rb_source_observation_topic_item_completion'),
                'text'
            ),
            new rb_filter_option(
                'observation_topic',
                'name',
                get_string('topicname', 'rb_source_observation_topic_item_completion'),
                'text'
            ),
            new rb_filter_option(
                'observation_topic_item',
                'name',
                get_string('topicitemname', 'rb_source_observation_topic_item_completion'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'comment',
                get_string('comment', 'rb_source_observation_topic_item_completion'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'timemodified',
                get_string('timemodified', 'rb_source_observation_topic_item_completion'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'status',
                get_string('completionstatus', 'rb_source_observation_topic_item_completion'),
                'select',
                array(
                    'selectfunc' => 'observation_completion_status_list',
                )
            ),
        );

        // include some standard filters
        $this->add_user_fields_to_filters($filteroptions);
        $this->add_course_fields_to_filters($filteroptions);
        $this->add_course_category_fields_to_filters($filteroptions);
        $this->add_job_assignment_fields_to_filters($columnoptions);
        $this->add_core_tag_fields_to_filters('core', 'course', $filteroptions);
        $this->add_cohort_course_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions()
    {
        $contentoptions = array(
            new rb_content_option(
                'current_pos',
                get_string('currentpos', 'totara_reportbuilder'),
                'position.path',
                'position'
            ),
            new rb_content_option(
                'current_org',
                get_string('currentorg', 'totara_reportbuilder'),
                'organisation.path',
                'organisation'
            ),
            new rb_content_option(
                'user',
                get_string('user', 'rb_source_observation_topic_item_completion'),
                array(
                    'userid'      => 'base.userid',
                    'managerid'   => 'position_assignment.managerid',
                    'managerpath' => 'position_assignment.managerpath',
                    'postype'     => 'position_assignment.type',
                ),
                'position_assignment'
            ),
        );
        return $contentoptions;
    }

    protected function define_paramoptions()
    {
        $paramoptions = array();

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
                'type'  => 'observation_topic_item',
                'value' => 'name',
            ),
            array(
                'type'  => 'base',
                'value' => 'status',
            ),
            array(
                'type'  => 'base',
                'value' => 'comment',
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
                'type'  => 'observation_topic_item',
                'value' => 'name',
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
        $requiredcolumns = array(
            new rb_column(
                'observation_topic_item',
                'id',
                '',
                'observation_topic_item.id',
                array('joins' => 'observation_topic_item')
            ),
        );

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
            return get_string('completionstatus' . completion::STATUS_INCOMPLETE, 'observation');
        }
        else
        {
            return get_string('completionstatus' . $status, 'observation');
        }
    }

    function rb_display_observation_link($observationname, $row, $isexport)
    {
        return html_writer::link(new moodle_url('/mod/observation/evaluate.php',
            array('userid' => $row->userid, 'bid' => $row->observationid)), $observationname);

    }

    function rb_display_observation_evaluate_link($observationname, $row, $isexport)
    {
        return html_writer::link(new moodle_url('/mod/observation/evaluate.php',
            array('userid' => $row->userid, 'bid' => $row->observationid)),
            get_string('evaluate', 'rb_source_observation_topic_item_completion'));

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

} // class

