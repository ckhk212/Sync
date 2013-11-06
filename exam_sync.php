<?php
require 'SyncObject.php';
$sync = new SyncObject();
define ("DB2_EXAM_TABLE", "exams");
define ("VENTUS_EXAM_TABLE", "ventus_professor_exam_requests"); /* ventus exam table */
define ("PROFESSOR_MODEL", 'https://sassit.uottawa.ca/apps/ventus/professor/models/professor.php'); /* professor model */
define ("FACULTY_MODEL", 'https://sassit.uottawa.ca/apps/ventus/faculty/models/faculty.php'); /* faculty model */

$start = microtime(TRUE); 
$mem_start = memory_get_usage(TRUE);
require DB2_EXAM_TABLE.".php";
printf("%s tb is loaded on %s \n", ucfirst(DB2_EXAM_TABLE), date('Y-m-d H:i:s'));
printf("%s tb took %fs and consumed %fkb \n\n", ucfirst(DB2_EXAM_TABLE), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
echo "=================================== PARTIAL END ================================\n\n";

/**
	*	drop the prvious backup tables
	*/
	$sql = "DROP TABLE IF EXISTS `org_".DB2_EXAM_TABLE."_".date('Y-m',strtotime('-1 month'))."`";
	$sync->mysql_query($sql);
	$sql = "DROP TABLE IF EXISTS `org_".DB2_EXAM_TABLE."_".date('Y-m')."`";
	$sync->mysql_query($sql);

/* create backup tbs */
$sql = "RENAME TABLE `org_".DB2_EXAM_TABLE."` TO `org_".DB2_EXAM_TABLE."_".date('Y-m')."`";

/* check if backup create successfully, else revert changes	*/
if($sync->mysql_query($sql)){
	printf("%s tb is backup on %s\n", DB2_EXAM_TABLE, date('Y-m-d H:i:s'));
	$sql = "RENAME TABLE `org_".DB2_EXAM_TABLE."_temp` TO `org_".DB2_EXAM_TABLE."`";
	$sync->mysql_query($sql);
	printf("%s tb is renamed on %s\n\n", DB2_EXAM_TABLE, date('Y-m-d H:i:s'));
}else{
	printf("%s tb did not backup porperly on %s\n", DB2_EXAM_TABLE, date('Y-m-d H:i:s'));
	$sql = "RENAME TABLE `org_".DB2_EXAM_TABLE."_".date('Y-m')."` TO `org_".DB2_EXAM_TABLE."`";
	$sync->mysql_query($sql);
}

echo "===================================== END ======================================\n\n";
?>
