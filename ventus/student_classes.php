<?php
// @author Kelvin Chan
// @date 2014-01-09
// @purpose queries to fetch students' courses data from DB2, and insert into ventus DB
require_once ("/var/www/html/sass/sync/SyncObject.php");
$sync = new SyncObject();

define('INCREMENT_SIZE', '300000');

$sql = "SELECT teaching_method FROM org_course_classes GROUP BY teaching_method";

/* execute query to  */
$teach_methods = $sync->mysql_query($sql);

/* 	
	@purpose: 
	prepare query with the different types of teach methods
	$teach_methods could include 'LEC', 'TUT', 'DGD', 'LAB'... etc
*/
	if (is_array($teach_methods)){
		foreach($teach_methods as $method){
			/* DGD and LAB are the exception becuase query is structured differently than others. See below */
			if ($method['teaching_method'] != "DGD" || $method['teaching_method'] != "LAB"){
				/* prepare db2 query */
				$db2_sql .= "TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || '".strtoupper($method['teaching_method'])."' AS ".strtoupper($method['teaching_method']).",";
				/* prepare mysql query */
				$ventus_sql .= "CASE WHEN A.teaching_method = '".strtoupper($method['teaching_method'])."' THEN CONCAT(B.code, B.section, B.session, A.teaching_method) END AS ".strtolower($method['teaching_method']).",";
			}
		}
	}

	/* get all classes  */
	$sql = "SELECT 
	".$ventus_sql."
	CASE WHEN A.teaching_method = 'DGD' THEN CONCAT(B.code, B.section, B.session, A.teaching_method, A.teaching_method_meet) ELSE '' END AS dgd,
	CASE WHEN A.teaching_method = 'LAB' THEN CONCAT(B.code, B.section, B.session, A.teaching_method, A.teaching_method_meet) ELSE '' END AS lab,
	A.class_id
	FROM
	org_course_classes A
	LEFT JOIN
	org_courses B
	ON A.course_id = B.course_id";

	$courses = $sync->mysql_query($sql);

	$sql = "SELECT MAX(student_id) FROM org_students";

	$numOfStudents = $sync->mysql_query($sql);

	$numOfStudents = $numOfStudents[0]["MAX(student_id)"];

	/* sql template query for inserting into table */
	$mysql_template = "INSERT INTO 
	org_student_classes (
		student_id,
		class_id,
		last_updated)
	VALUES {DATA}
	ON DUPLICATE KEY UPDATE 
	student_id = student_id,
	class_id = VALUES(class_id),
	last_updated = VALUES(last_updated)";

printf("Total number of students are: %d\n", $numOfStudents);

for ($count = 0;$count<$numOfStudents;$count+=INCREMENT_SIZE){
	incrementalInsertion($count, $count+INCREMENT_SIZE, $db2_sql, $mysql_template);
}

function incrementalInsertion($startID, $endID, $db2_sql, $mysql_template){
	global $sync;

	$db2_sql = "SELECT
	PERSON_ID,
	".$db2_sql."
	CASE WHEN DGD_CHOICE = 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'DGD' || 1 
	WHEN DGD_CHOICE > 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'DGD' || DGD_CHOICE 
	ELSE '' END AS DGD,
	CASE WHEN LAB_CHOICE = 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'LAB' || 1 
	WHEN LAB_CHOICE > 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'LAB' || LAB_CHOICE 
	ELSE '' END AS LAB
	FROM
	SISR.STUDENT_COURSES_SESSION
	WHERE
	CURRENT_STS='APP'
	AND PERSON_ID >= '$startID'
	AND PERSON_ID < '$endID'";

	printf("Starting from %d to %d at %s\n", $startID, $endID, date('Y-m-d h:i:s'));

	/* execute query */
	$db2_result = $sync->db2_query($db2_sql);

	$data = prep($db2_result);

	$sync->mysql_insert($data, $mysql_template);
}

/* END of the TEST_student_courses.php */

function prep($data) {

	global $courses, $students, $sync, $teach_methods;

	if (is_array($teach_methods)){
		foreach($teach_methods as $method){
			$data = join_results($data, $courses, strtoupper($method['teaching_method']),strtolower($method['teaching_method']),'class_id', FALSE);	
		}
	}
	
	/* initialize a data structure to store the final result, and a count variabe to label where to insert */
	$final_data = array();
	$count = 0;
	/* flatten the muti dimensional array structure and insert into DB */
	foreach ($data as $row){
		/* foreach each student's courses */
		foreach ($row as $field){
			if(is_array($field)){
				/* if there is only one type of class per course */
				if(count($field) === 1){
					$final_data[$count][] = $row['PERSON_ID'];
					$final_data[$count][] = $field[0]; // only one type of class, so just getting first element of array is fine
					$count++;
				}
				/* when there are mutiple classes for each course, ex: LEC, DGD, LAB...etc */
				else{
					foreach($field as $c=>$element){
						$final_data[$count][] = $row['PERSON_ID'];
						$final_data[$count][] = $element;
						$count++;
					}
				}
			}
		}	
	}
	return $final_data;
}

function join_results(&$result_left, &$result_right, $left, $right, $value, $unset=TRUE) {
	$lookup = array();		
	foreach($result_right as $row){
		/* get $result_right array each $value and store it in $lookup with field name becomes a muti dimensional array */
		$lookup[$row[$right]][] = $row[$value];
	}
	foreach($result_left as $key=>$left_row) {
		if(isset($lookup[$left_row[$left]]) && !empty($result_left[$key][$left])){
			$result_left[$key][$left] = $lookup[$left_row[$left]];
		}
		else {
			if($unset){
				unset($result_left[$key]);
			}
		}
	}
	return $result_left;
}
?>
