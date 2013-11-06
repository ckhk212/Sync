<?php
require 'SyncObject.php';
$sync = new SyncObject();
define ("DB2_EXAM_TABLE", "exams");
define ("VENTUS_EXAM_TABLE", "ventus_professor_exam_requests"); /* ventus exam table */

require_once('/var/www/html/sass/apps/ventus/includes/php/config.php');
require_once(FS_PROFESSOR . '/models/professor.php');
require_once(FS_FACULTY . '/models/faculty.php');

$professor = new RequestForm();
$faculty = new Faculty();

/* Query to find entries that are not exisit on VENTUS_EXAM_TABLE */
$sql="SELECT `session`, `course_code`, `course_section`, `exam_type`, `exam_date`, `exam_duration`, `exam_alternate_special`, 
`contact_name`, `requestor_email`, `confirmation_key` as cid, `is_confirmed`,`imported_automatically`, 
`inserted_on`
FROM `org_".DB2_EXAM_TABLE."` new WHERE NOT EXISTS (SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type AND
	new.deleted = 0 AND
	new.imported_automatically = 1 )";
$result = $sync->mysql_query($sql);

echo "INSERT\n\n";
var_dump($result); 
/* insert into VENTUS_EXAM_TABLE if $result is found */

if (is_array($result) && !is_object($result)){
	$sql="INSERT INTO `".VENTUS_EXAM_TABLE."` (`session`, `course_code`, `course_section`, `exam_type`, `exam_date`, `exam_duration`, `exam_alternate_special`, 
		`contact_name`, `requestor_email`, `confirmation_key`, `is_confirmed`, `imported_automatically`, 
		`inserted_on`, `updated_on`) VALUES {DATA}";
	$sync->mysql_insert($result,$sql);
	foreach ($result as $row){
		$row['source'] = "prof";	
		$professor->reminderToAccessServiceStudents($row); /* send email to students */
	}
}


/* Query to find entries need update on VENTUS_EXAM_TABLE */
$sql = "SELECT new.* FROM `org_".DB2_EXAM_TABLE."` new
RIGHT JOIN `".VENTUS_EXAM_TABLE."` old
ON 
new.session = old.session AND
new.course_code = old.course_code AND
new.course_section = old.course_section AND
new.exam_type = old.exam_type AND
old.deleted = 0 AND
old.imported_automatically = 1
WHERE 
new.exam_date != old.exam_date 
OR new.exam_duration != old.exam_duration";
$result = $sync->mysql_query($sql);
echo "UDPATE\n\n";
var_dump($result);

/* update VENTUS_EXAM_TABLE entries if $result is found */
if (is_array($result) && !is_object($result)){
	foreach ($result as $row){

		$sql ="SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE old.session = '".$row[session]."' AND
		old.course_code = '".$row[course_code]."' AND
		old.course_section = '".$row[course_section]."' AND
		old.exam_type = '".$row[exam_type]."' AND
		old.imported_automatically = '".$row[imported_automatically]."'";

		$old_result = $sync->mysql_query($sql);

		$faculty->updateRequestDetails($old_result[0]['exam_request_id'], $row);
	}
}

/* Query to find entries need to be deleted on VENTUS_EXAM_TABLE */
$sql = "SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE NOT EXISTS (SELECT * FROM `org_".DB2_EXAM_TABLE."` new WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type) AND old.session='20139' AND old.exam_type='final' AND old.imported_automatically=1 AND old.deleted = 0";
$result = $sync->mysql_query($sql);
echo "DELETE\n\n";
var_dump($result);

/* delete VENTUS_EXAM_TABLE entries if $result is found */
if (is_array($result) && !is_object($result)){
	foreach ($result as $row){
		$faculty->deleteRequest($row['exam_request_id']);
	}
}

echo "===================================== END ======================================\n\n";
?>
