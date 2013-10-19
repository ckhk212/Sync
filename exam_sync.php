<?php
require 'SyncObject.php';
$sync = new SyncObject();

$table_names = array("ventus_professor_exam_requests");
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
	
	// $sql = "SELECT
	// `session`,
	// `course_code` ,
	// `course_section`,
	// `exam_type`,
	// `exam_date`,
	// `exam_duration`,
	// `exam_alternate_special`,
	// `exam_alternate_special_student`,
	// `contact_name`,
	// `contact_number`,
	// `requestor_email`,
	// `confirmation_key`,
	// `is_confirmed`,
	// `prof_filled_control_sheet`,
	// `documents_received`,
	// `imported_automatically`,
	// `inserted_on`,
	// `updated_on`,
	// `updated_by`
	// FROM ventus_professor_exam_requests";

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
	FROM ".$name." new WHERE NOT EXISTS (SELECT * FROM ventus_professor_exam_requests old WHERE 
		new.session = old.session AND
		new.course_code = old.course_code AND
		new.course_section = old.course_section AND
		new.exam_type = old.exam_type AND
		new.imported_automatically = old.imported_automatically
		)";
	$new_result = $sync->mysql_query($sql);
	var_dump($new_result);
	exit();

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
	$sql = "DROP TABLE IF EXISTS `".$name."_".date('Y-m',strtotime('-1 month'))."`";
	$sync->mysql_query($sql);
	$sql = "DROP TABLE IF EXISTS `".$name."_".date('Y-m')."`";
	$sync->mysql_query($sql);

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
