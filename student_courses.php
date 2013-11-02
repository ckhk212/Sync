<?php
$db2_sql = "SELECT
TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) AS COURSE,
PERSON_ID
FROM
SISR.STUDENT_ACTIVITIES
WHERE
CURRENT_STS='APP'";

$sql = "SELECT
CONCAT(TRIM(code),TRIM(section),TRIM(session)) AS code,
course_id
FROM
org_courses_temp";
	//The where clause here exists because we want to only consider LEC/SEM types for now
	//This will probably have to be modified in the future to include LAB types as well
$courses = $sync->mysql_query($sql); 



$sql = "SELECT
student_id
FROM
org_students_temp";
$students = $sync->mysql_query($sql);

$sql = "DROP TABLE IF EXISTS `org_student_courses_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_student_courses_temp` (
	`student_id` int(11) NOT NULL,
	`course_id` int(11) NOT NULL,
	`last_updated` datetime NOT NULL,
	PRIMARY KEY (`student_id`,`course_id`),
	KEY `fk_uottawa_student_courses_uottawa_students_idx` (`student_id`),
	KEY `fk_uottawa_student_courses_uottawa_courses_idx` (`course_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$sync->mysql_query($sql);


$mysql_template = "INSERT INTO 
org_student_courses_temp (
	course_id,
	student_id,
	last_updated
	)
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
student_id = student_id,
course_id = VALUES(course_id),
last_updated = VALUES(last_updated)";

 $sync->db2_query_rolling_insert($db2_sql, $mysql_template, 'prep');


function prep($data) {

	global $courses, $students, $sync;

	$data = $sync->join_results($data, $courses, 'COURSE','code','course_id');
	$data = $sync->join_results($data, $students, 'PERSON_ID', 'student_id', 'student_id');

	return $data;

}
?>
