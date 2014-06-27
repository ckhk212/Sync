<?php
// @author Kelvin Chan
// @date 2014-05-09
// @purpose controller for the org data synchronization with DB2
// @version 1.4

require_once '/var/www/html/sass/sync/SyncObject.php';
require_once FS_PHP.'/functions.php';
$sync = new SyncObject();

/**
	@purpose: Fetch current semester in short code (ex: 20135 - Summer of 2013 session... ) 
  	and calcualte the upper and lower limit for the control tables
		      **/
  	$semester = VentusFunctions::fetchSemester();
  	$current_year = substr($semester['now_short'], 0, 4);
  	$start_year= $current_year-LOWER_CONTROL_LIMIT;
  	$month = substr($semester['now_short'], -1);
  	$start_session = $start_year.$month;

  	$end_year = $current_year+UPPER_CONTROL_LIMIT;
  	$end_session = $end_year.$month;

  	$result = null;
  	$sql = "UPDATE ".DB2_CONTROL_TABLE."
  	Set Start_tmst = current timestamp,
  	End_tmst = current timestamp,
  	Start_session_cd = '".$start_session."',       
  	End_session_cd = '".$end_session."'";
  	$result = $sync->db2_query($sql);

  	if($result === null){	
	exit("Something went wrong updating control table! Sync process will be terminated"); // it should never reaches here since DB2 will terminated the process if error occured.
}

echo "Control table updated to Start Session: ".$start_session." End Session: ".$end_session."\n";

// unset the variables to prevent memory lost
unset($semester, $current_year, $start_session, $end_session);

$table_names = array(FACULTIES_TABLE, DEPARTMENTS_TABLE, PROGRAMS_TABLE, COURSES_TABLE, COURSE_CLASSES_TABLE, STUDENTS_TABLE, STUDENT_COURSE_CLASSES_TABLE, SASS_EMPLOYEES_TABLE);

/**
	@purpose: Clean up some of the possible tempory tables before starting the sync process
	**/
	for($i=count($table_names)-1;$i>=0;--$i){
		$sql ="DROP TABLE IF EXISTS `org_".$table_names[$i]."_temp`";
		$sync->mysql_query($sql);
	}
// unset the variables to prevent memory lost
	unset($i);

/**
	@purpose: Being the sync process
	**/
	foreach ($table_names as $name){
		$start = microtime(TRUE); 
		$mem_start = memory_get_usage(TRUE);
		require $name.".php";
		printf("%s table is loaded on %s \n", ucfirst($name), date('Y-m-d H:i:s'));
		printf("%s table took %fs and consumed %fkb \n\n", ucfirst($name), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
		unset($start, $mem_start);
	}
	echo "org_*_temp tables created\n";
	echo "=================================== PARTIAL END ================================\n\n";

/**
	@purpose: All tempory tables are created, and data are inserted properly. 
	Perform backup for the current production tables, and rename the newly created tables into production.
	**/
	if (isset($argv[1]) && $argv[1] === "DEBUG"){
		exit("===================================== DEBUD MODE END ======================================\n\n");
	}
	else{
		for($i=count($table_names)-1;$i>=0;--$i){
			/*
			*	drop the prvious backup tables
			*/
			$sql = "DROP TABLE IF EXISTS `org_".$table_names[$i]."_".date('Y-m',strtotime('-1 month'))."`";
			$sync->mysql_query($sql);
			$sql = "DROP TABLE IF EXISTS `org_".$table_names[$i]."_".date('Y-m')."`";
			$sync->mysql_query($sql);

			/*
			*	create backup tables
			*/
			$sql = "RENAME TABLE `org_".$table_names[$i]."` TO `org_".$table_names[$i]."_".date('Y-m')."`";

			/*
			*	check if backup create successfully, else revert changes.
			*/
			if($sync->mysql_query($sql)){
				printf("%s table is backup on %s\n", $table_names[$i], date('Y-m-d H:i:s'));
				$sql = "RENAME TABLE `org_".$table_names[$i]."_temp` TO `org_".$table_names[$i]."`";
				$sync->mysql_query($sql);
				printf("%s table is renamed on %s\n\n", $table_names[$i], date('Y-m-d H:i:s'));
			}else{
				printf("%s table did not backup porperly on %s\n", $table_names[$i], date('Y-m-d H:i:s'));
				$sql = "RENAME TABLE `org_".$table_names[$i]."_".date('Y-m')."` TO `org_".$table_names[$i]."`";
				$sync->mysql_query($sql);
			}
		}
		echo "===================================== END ======================================\n\n";
	}
