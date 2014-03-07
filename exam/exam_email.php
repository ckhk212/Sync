<?php
/*
@purpose Script to determine if final exams need to be inserted, updated and/or deleted
@author Kelvin Chan
@date 	2014-02-21
@version 1.1
*/

require '/var/www/html/sass/sync/SyncObject.php';
$sync = new SyncObject();
define ("DB2_EXAM_TABLE", "exams"); // final exam data fetched from DB2
define ("VENTUS_EXAM_TABLE", "ventus_professor_exam_requests"); // Ventus production exam table

/* some esstenial includes from Ventus */
require_once('/var/www/html/sass/apps/ventus/includes/php/config.php');
require_once(FS_PROFESSOR . '/models/professor.php'); 
require_once(FS_FACULTY . '/models/faculty.php');

$professor = new RequestForm();
$faculty = new Faculty();

/* Find exams that are not exisit on VENTUS_EXAM_TABLE */
$sql="SELECT `session`, `course_code`, `course_section`, `exam_type`, `exam_date`, `exam_duration`, `exam_alternate_special`, 
`contact_name`, `requestor_email`, `confirmation_key` as cid, `is_confirmed`,`imported_automatically`, 
`inserted_on`
FROM `org_".DB2_EXAM_TABLE."` new WHERE NOT EXISTS (SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type AND
	new.deleted = old.deleted AND
	new.imported_automatically = old.imported_automatically )";
$result = $sync->mysql_query($sql);

echo "INSERT on ".date("Y-m-d H:i:s")."\n\n";
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


/* Find exams require update on VENTUS_EXAM_TABLE */
$sql = "SELECT new.* FROM `org_".DB2_EXAM_TABLE."` new
RIGHT JOIN `".VENTUS_EXAM_TABLE."` old
ON 
new.session = old.session AND
new.course_code = old.course_code AND
new.course_section = old.course_section AND
new.exam_type = old.exam_type AND
new.deleted = old.deleted AND
new.imported_automatically = old.imported_automatically
WHERE 
new.exam_date != old.exam_date 
OR new.exam_duration != old.exam_duration";
$result = $sync->mysql_query($sql);
echo "UDPATE on ".date("Y-m-d H:i:s")."\n\n";
var_dump($result);

/* Update VENTUS_EXAM_TABLE entries if $result is found */
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

/* 
	@purpose Get current semester final exam fields: session, exam_type, imported_automatically, deleted
			The fields are use for the following query to find which final exams are no longer exist on the Ventus
			production table
*/
$sql = "SELECT session, exam_type, imported_automatically, deleted FROM `org_".DB2_EXAM_TABLE."` ORDER BY session DESC LIMIT 1";
$currentExamProperties = $sync->mysql_query($sql);


/* Find entries need to be deleted on VENTUS_EXAM_TABLE */
$sql = "SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE NOT EXISTS (SELECT * FROM `org_".DB2_EXAM_TABLE."` new WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type) 
	AND old.session='".$currentExamProperties[0]['session']."'
	AND old.exam_type='".$currentExamProperties[0]['exam_type']."'
	AND old.imported_automatically='".$currentExamProperties[0]['imported_automatically']."'
	AND old.deleted = '".$currentExamProperties[0]['deleted']."'";
$result = $sync->mysql_query($sql);
echo "DELETE on ".date("Y-m-d H:i:s")."\n\n";
var_dump($result);

/* Delete VENTUS_EXAM_TABLE entries if $result is found */
if (is_array($result) && !is_object($result)){
	foreach ($result as $row){
		$faculty->deleteRequest($row['exam_request_id']);
	}
}

echo "===================================== END ======================================\n\n";
?>
