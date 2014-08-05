<?php
// @author Kelvin Chan
// @date 2014-05-09
// @purpose queries to fetch students' courses data from DB2, and insert into ventus DB
// @version 1.3

$sql = "SELECT teaching_method FROM org_".COURSE_CLASSES_TABLE."_temp WHERE teaching_method != '' GROUP BY teaching_method";

/* execute query to  */
$this->teach_methods = $this->mysql_query($sql);

$sql = "SELECT student_id FROM org_".STUDENTS_TABLE."_temp order by student_id ASC";

/* execute query to  */
$this->students = $this->mysql_query($sql);
$this->numOfStudent = count($this->students);

/* 	
	@purpose: 
	prepare query with the different types of teach methods
	$teach_methods could include 'LEC', 'TUT', 'DGD', 'LAB'... etc
*/
	if (is_array($this->teach_methods)){
		foreach($this->teach_methods as $method){
			/* DGD and LAB are the exception becuase query is structured differently than others. See below */
			if ($method['teaching_method'] !== "DGD" || $method['teaching_method'] !== "LAB"){
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
	org_".COURSE_CLASSES_TABLE."_temp A
	LEFT JOIN
	org_".COURSES_TABLE."_temp B
	ON A.course_id = B.course_id";

	$this->courseClasses = $this->mysql_query($sql);

	/* sql template query for inserting into table */
	$mysql_template = "INSERT INTO 
	org_".STUDENT_COURSE_CLASSES_TABLE."_temp (
		student_id,
		class_id,
		last_updated)
		VALUES {DATA}
		ON DUPLICATE KEY UPDATE 
		student_id = student_id,
		class_id = VALUES(class_id),
		last_updated = VALUES(last_updated)";

	printf("Total number of students are: %d\n", $this->numOfStudent);

	// for ($count = 0;$count<$this->maxStudentNum;$count+=STUDENT_INCREMENT_SIZE){
		// incrementalInsertion($count, $count+STUDENT_INCREMENT_SIZE, $db2_sql, $mysql_template, $this);

	$chuckOfStudents = array_chunk($this->students, 1000, true);
	$numOfChucks = count($chuckOfStudents);

	printf("There are %d of chucks\n", $numOfChucks);

	for ($count = 0;$count<$numOfChucks;++$count){
		printf("Starting chuck %d\n", $count);
		incrementalInsertion($count, $db2_sql, $mysql_template, $this, $chuckOfStudents[$count]);
	}

	// unset the variables to prevent memory lost
	unset($ventus_sql, $sql, $db2_sql, $mysql_template, $count);

// function incrementalInsertion($startID, $endID, $sql, $mysql_template, $sync){
	function incrementalInsertion($count, $sql, $mysql_template, $sync, $smallerChuckOfStudents){

	// $student = $sync->students[$count]['student_id'];

	// $db2_sql = "SELECT
	// PERSON_ID,
	// ".$sql."
	// CASE WHEN DGD_CHOICE = 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'DGD' || 1 
	// WHEN DGD_CHOICE > 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'DGD' || DGD_CHOICE 
	// ELSE '' END AS DGD,
	// CASE WHEN LAB_CHOICE = 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'LAB' || 1 
	// WHEN LAB_CHOICE > 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'LAB' || LAB_CHOICE 
	// ELSE '' END AS LAB
	// FROM
	// ".DB2_STUDENT_COURSES."
	// WHERE
	// CURRENT_STS='APP'
	// AND PERSON_ID = '$student'
	// -- AND PERSON_ID >= '$startID'
	// -- AND PERSON_ID < '$endID'";

	// printf("Starting from %d to %d at %s\n", $startID, $endID, date('Y-m-d h:i:s'));

	$db2_sql = "SELECT
	PERSON_ID,
	".$sql."
	CASE WHEN DGD_CHOICE = 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'DGD' || 1 
	WHEN DGD_CHOICE > 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'DGD' || DGD_CHOICE 
	ELSE '' END AS DGD,
	CASE WHEN LAB_CHOICE = 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'LAB' || 1 
	WHEN LAB_CHOICE > 0 THEN TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) || 'LAB' || LAB_CHOICE 
	ELSE '' END AS LAB
	FROM
	".DB2_STUDENT_COURSES."
	WHERE
	CURRENT_STS='APP' AND (";

	foreach ($smallerChuckOfStudents as $key => $student) {
		$db2_sql .= "PERSON_ID = '".$student['student_id']."' OR ";
	}

	$db2_sql = substr($db2_sql, 0, -4);

	$db2_sql.=")";

	/* execute query */
	$db2_result = $sync->db2_query($db2_sql);

	$data = prepare($db2_result, $sync);

	$sync->mysql_insert($data, $mysql_template, count($data));

	unset($db2_result, $data, $db2_sql);
}

/* overwrite some functions from SyncObject */

function prepare($student_data, $sync) {

	if (is_array($sync->teach_methods)){
		foreach($sync->teach_methods as $method){
			$student_data = concat_results($student_data, $sync->courseClasses, strtoupper($method['teaching_method']),strtolower($method['teaching_method']),'class_id', FALSE);	
		}
	}
	/* initialize a data structure to store the final result, and a count variabe to label where to insert */
	$final_data = array();
	$count = 0;

	/* flatten the muti dimensional array structure and insert into DB */
	foreach ($student_data as $row){
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
	unset($student_data);
	return $final_data;
}

function concat_results(&$result_left, &$result_right, $left, $right, $value, $unset=TRUE) {

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
