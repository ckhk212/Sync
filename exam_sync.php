<?php
require 'SyncObject.php';
$sync = new SyncObject();
define ("EXIST_EXAM", "ventus_professor_exam_requests"); /* existing table name */

$table_names = array("TEST_ventus_professor_exam_requests");
// $table_names = array("faculties", "departments", "programs", "courses",  "students", "student-courses");
// $table_names = array("student_courses");

// foreach ($table_names as $name){
// 	// $sync = new SyncObject();
// 	$start = microtime(TRUE); 
// 	$mem_start = memory_get_usage(TRUE);
// 	require $name.".php";
// 	printf("%s table is loaded on %s \n", ucfirst($name), date('Y-m-d H:i:s'));
// 	printf("%s table took %fs and consumed %fkb \n\n", ucfirst($name), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
// 	// $sync->close_connection();
// 	unset($start, $mem_start);
// }
// echo "=================================== PARTIAL END ================================\n\n";

/**
*	at this point, all org_*_temp tables are synced properly.
*/

foreach ($table_names as $name){

	/* Query to find entries that are not exisit in ventus table */
	$sql="SELECT 
	`session`,
	`course_code` ,
	`course_section`,
	`exam_type`,
	`exam_date`,
	`exam_duration`,
	`exam_alternate_special`,
	`exam_alternate_special_student`,
	`contact_name`,
	`contact_number`,
	`requestor_email`,
	`confirmation_key`,
	`is_confirmed`,
	`prof_filled_control_sheet`,
	`documents_received`,
	`imported_automatically`,
	`inserted_on`,
	`updated_on`,
	`updated_by`
	FROM ".$name." new WHERE NOT EXISTS (SELECT * FROM ".EXIST_EXAM.") old WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type AND
	(new.exam_date != old.exam_date OR
		new.exam_duration != old.exam_duration) AND
new.imported_automatically = old.imported_automatically
)";

/* for update only */
$sql = "SELECT new.* FROM TEST_ventus_professor_exam_requests new
LEFT JOIN ventus_professor_exam_requests old
ON 
new.session = old.session AND
new.course_code = old.course_code AND
new.course_section = old.course_section AND
new.exam_type = old.exam_type AND
new.imported_automatically = old.imported_automatically
WHERE 
new.exam_date != old.exam_date 
OR new.exam_duration != old.exam_duration";

$new_result = $sync->mysql_query($sql);
var_dump($new_result);
exit();

/* check ratehr if the data exist in the ventus table */
if(mysqli_num_rows($new_result) > 0){
	$sql= "SELECT exam_request_id FROM ".EXIST_EXAM." 
	WHERE 
	session = $new_result[session] AND
	course_code = $new_result[course_code] = old.course_code AND
	course_section = $new_result[course_section] AND
	exam_type = $new_result[exam_type] AND
	imported_automatically = $new_result[imported_automatically]";
	echo "I AM HERE";
	$new_result = $sync->mysql_query($sql);


}



exit(1);
	/*
	$sql = "SELECT
	`session`,
	`course_code` ,
	`course_section`,
	`exam_type`,
	`exam_date`,
	`exam_duration`,
	`exam_alternate_special`,
	`exam_alternate_special_student`,
	`contact_name`,
	`contact_number`,
	`requestor_email`,
	`confirmation_key`,
	`is_confirmed`,
	`prof_filled_control_sheet`,
	`documents_received`,
	`imported_automatically`,
	`inserted_on`,
	`updated_on`,
	`updated_by`
	FROM ventus_professor_exam_requests_copy";
	$exist_result = $sync->mysql_query($sql);
	*/

	/**
	*	drop the prvious backup tables
	*/
	// $sql = "DROP TABLE IF EXISTS `".$name."_".date('Y-m',strtotime('-1 month'))."`";
	// $sync->mysql_query($sql);
	// $sql = "DROP TABLE IF EXISTS `".$name."_".date('Y-m')."`";
	// $sync->mysql_query($sql);

	/**
	*	create backup tables
	*/
	$sql = "RENAME TABLE `".$name."` TO `".$name."_".date('Y-m')."`";

	/**
	*	check if backup create successfully, else revert changes.
	*/
	if($sync->mysql_query($sql)){
		printf("%s table is backup on %s\n", $name, date('Y-m-d H:i:s'));
		$sql = "RENAME TABLE `".$name."_temp` TO `".$name."`";
		$sync->mysql_query($sql);
		printf("%s table is renamed on %s\n\n", $name, date('Y-m-d H:i:s'));
	}else{
		printf("%s table did not backup porperly on %s\n", $name, date('Y-m-d H:i:s'));
		$sql = "RENAME TABLE `".$name."_".date('Y-m')."` TO `".$name."`";
		$sync->mysql_query($sql);
	}
}

echo "===================================== END ======================================\n\n";

?>
