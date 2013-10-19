<?php
require 'SyncObject.php';
$sync = new SyncObject();

$table_names = array("faculties", "departments", "programs", "courses", "course_classes", "students", "student_courses");
// $table_names = array("faculties", "departments", "programs", "courses",  "students", "student-courses");
// $table_names = array("student_courses");

foreach ($table_names as $name){
	// $sync = new SyncObject();
	$start = microtime(TRUE); 
	$mem_start = memory_get_usage(TRUE);
	require $name.".php";
	printf("%s table is loaded on %s \n", ucfirst($name), date('Y-m-d H:i:s'));
	printf("%s table took %fs and consumed %fkb \n\n", ucfirst($name), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
	// $sync->close_connection();
	unset($start, $mem_start);
}
echo "=================================== PARTIAL END ================================\n\n";

/**
*	at this point, all org_*_temp tables are synced properly.
*/

foreach ($table_names as $name){
	/**
	*	drop the prvious backup tables
	*/
	$sql = "DROP TABLE IF EXISTS `org_".$name."_".date('Y-m',strtotime('-1 month'))."`";
	$sync->mysql_query($sql);
	$sql = "DROP TABLE IF EXISTS `org_".$name."_".date('Y-m')."`";
	$sync->mysql_query($sql);

	/**
	*	create backup tables
	*/
	$sql = "RENAME TABLE `org_".$name."` TO `org_".$name."_".date('Y-m')."`";

	/**
	*	check if backup create successfully, else revert changes.
	*/
	if($sync->mysql_query($sql)){
		printf("%s table is backup on %s\n", $name, date('Y-m-d H:i:s'));
		$sql = "RENAME TABLE `org_".$name."_temp` TO `org_".$name."`";
		$sync->mysql_query($sql);
		printf("%s table is renamed on %s\n\n", $name, date('Y-m-d H:i:s'));
	}else{
		printf("%s table did not backup porperly on %s\n", $name, date('Y-m-d H:i:s'));
		$sql = "RENAME TABLE `org_".$name."_".date('Y-m')."` TO `org_".$name."`";
		$sync->mysql_query($sql);
	}
}

echo "===================================== END ======================================\n\n";

?>
