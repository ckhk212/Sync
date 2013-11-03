<?php
require 'SyncObject.php';
$sync = new SyncObject();
define ("DB2_EXAM_TABLE", "exams");
define ("VENTUS_EXAM_TABLE", "ventus_professor_exam_requests"); /* ventus exam table */
define ("PROFESSOR_MODEL", 'https://sassit.uottawa.ca/apps/ventus/professor/models/professor.php'); /* professor model */
define ("FACULTY_MODEL", 'https://sassit.uottawa.ca/apps/ventus/faculty/models/faculty.php'); /* faculty model */


// $start = microtime(TRUE); 
// $mem_start = memory_get_usage(TRUE);
// require DB2_EXAM_TABLE.".php";
// printf("%s tb is loaded on %s \n", ucfirst(DB2_EXAM_TABLE), date('Y-m-d H:i:s'));
// printf("%s tb took %fs and consumed %fkb \n\n", ucfirst(DB2_EXAM_TABLE), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
// echo "=================================== PARTIAL END ================================\n\n";

	/**
	*	drop the prvious backup tables
	*/
	// $sql = "DROP TABLE IF EXISTS `org_".DB2_EXAM_TABLE."_".date('Y-m',strtotime('-1 month'))."`";
	// $sync->mysql_query($sql);
	// $sql = "DROP TABLE IF EXISTS `org_".DB2_EXAM_TABLE."_".date('Y-m')."`";
	// $sync->mysql_query($sql);

	/* create backup tbs */
	// $sql = "RENAME TABLE `org_".DB2_EXAM_TABLE."` TO `org_".DB2_EXAM_TABLE."_".date('Y-m')."`";

	/* check if backup create successfully, else revert changes	*/
	// if($sync->mysql_query($sql)){
	// 	printf("%s tb is backup on %s\n", DB2_EXAM_TABLE, date('Y-m-d H:i:s'));
	// 	$sql = "RENAME TABLE `org_".DB2_EXAM_TABLE."_temp` TO `org_".DB2_EXAM_TABLE."`";
	// 	$sync->mysql_query($sql);
	// 	printf("%s tb is renamed on %s\n\n", DB2_EXAM_TABLE, date('Y-m-d H:i:s'));
	// }else{
	// 	printf("%s tb did not backup porperly on %s\n", DB2_EXAM_TABLE, date('Y-m-d H:i:s'));
	// 	$sql = "RENAME TABLE `org_".DB2_EXAM_TABLE."_".date('Y-m')."` TO `org_".DB2_EXAM_TABLE."`";
	// 	$sync->mysql_query($sql);
	// }

	/* Query to find entries that are not exisit on VENTUS_EXAM_TABLE */
	$sql="SELECT `session`, `course_code`, `course_section`, `exam_type`, `exam_date`, `exam_duration`, `exam_alternate_special`, 
	`contact_name`, `requestor_email`, `confirmation_key`, `is_confirmed`,`imported_automatically`, 
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
	// foreach ($result as $row){
		// reminderToAccessServiceStudents($data); /* send email to students */
	// }
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
		// define("DEBUG", '');
		$sql = "SET @update_id := null";
		$sync->mysql_query($sql);
		$sql="UPDATE `".VENTUS_EXAM_TABLE."` old SET old.exam_date='".$row[exam_date]."', old.exam_duration=".$row[exam_duration].", old.inserted_on='".$row[inserted_on]."',
		old.exam_request_id = (SELECT @update_id := exam_request_id)
		WHERE old.session = '".$row[session]."' AND
		old.course_code = '".$row[course_code]."' AND
		old.course_section = '".$row[course_section]."' AND
		old.exam_type = '".$row[exam_type]."' AND
		old.imported_automatically = '".$row[imported_automatically]."'";
		$sync->mysql_query($sql);
		$sql ="SELECT @update_id";
		var_dump($sync->mysql_query($sql));
		// updateRequestDetails($row['exam_request_id'], $row);
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
		$sql="UPDATE `".VENTUS_EXAM_TABLE."` old SET old.deleted=1
		WHERE old.session = '$row[session]' AND
		old.course_code = '$row[course_code]' AND
		old.course_section = '$row[course_section]' AND
		old.exam_type = '$row[exam_type]' AND
		old.imported_automatically = '$row[imported_automatically]'";
		$sync->mysql_query($sql);
		// deleteRequest($row['exam_request_id']);
	}
}

echo "===================================== END ======================================\n\n";
?>
