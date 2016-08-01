<?php

require_once($CFG->libdir . "/externallib.php");
require_once("../../config.php");

class local_get_user_courses_grades_external extends external_api
{     

        public static function get_user_courses_grades_parameters()
        {
                return new external_function_parameters(
                        array('userid' => new external_value(PARAM_INT, 'User id', VALUE_DEFAULT, 0))
                );
        }

        public static function get_user_courses_grades($userid)
        {       
           global $CFG, $DB;
	   require_once("../../config.php");
	   require_once($CFG->dirroot.'/mod/scorm/locallib.php');
	   require_once($CFG->dirroot.'/mod/scorm/lib.php');
               
                $params = self::validate_parameters(self::get_user_courses_grades_parameters(), array('userid' => $userid));
                $userid = (int) $params['userid'];              
               
      		$sql = 
                       "SELECT 
			gh.finalgrade as 'finalgrade', 
			gih.courseid as 'courseid', 
			c.fullname as 'coursename',cc.id, c.shortname as 'goodcode', 
			cc.name AS 'categoryname', 
			gh.timemodified,
			gh.userid,                  
                        
                        (select round(max({grade_grades_history}.finalgrade))
			FROM {grade_grades_history} 
			JOIN {grade_items} ON {grade_items}.id={grade_grades_history}.itemid
                        where {grade_items}.courseid = c.id
			and {grade_grades_history}.userid =$userid
			group by c.id) as highestscore
				
                        
			FROM {grade_grades_history} gh
			JOIN {grade_items} gih ON gih.id=gh.itemid
			JOIN {course} c ON gih.courseid = c.id
			JOIN {course_categories} cc ON c.category = cc.id
			JOIN {user} u on u.id=gh.userid
			WHERE 
			gih.itemtype = 'course' AND u.id=".$userid."  GROUP BY c.id, u.id
			ORDER BY gih.courseid, gh.timemodified desc";
              
                $results = $DB->get_recordset_sql($sql);

                $all_courses_grades = array();

                foreach ($results as $result) {
                        $grades = array();
                        $grades['id']                   = $result->id;
                        $grades['fullname']             = $result->coursename;
                        $grades['courseid']             = $result->courseid;
                        $grades['goodcode']             = $result->goodcode;                     
                        $grades['userid']               = $result->userid;
                        $grades['finalgrade']           = $result->highestscore;
                        $grades['timemodified']         = $result->timemodified;
                        $all_courses_grades[]           = $grades;
                }

                $results->close();

                return $all_courses_grades;
        }

        public static function get_user_courses_grades_returns()
        {
                return new external_multiple_structure(
                        new external_single_structure(
                        array(
                                'id'                    => new external_value(PARAM_INT, 'id'),
                                'fullname'              => new external_value(PARAM_TEXT, 'fullname'),
				'goodcode'              => new external_value(PARAM_TEXT, 'goodcode'),
                                'courseid'              => new external_value(PARAM_INT, 'course id'),
                                'userid'                => new external_value(PARAM_INT, 'userid'),                                              
                                'finalgrade'            => new external_value(PARAM_INT, 'finalgrade'),                                
                                'timemodified'          => new external_value(PARAM_INT, 'Time Modified '),
                        )
                    )
                );
        }

}
       
