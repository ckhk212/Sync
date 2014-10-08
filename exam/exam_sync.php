<?php
// @author Kelvin Chan
// @date 2014-10-09
// @purpose controller for the final exam synchronization with DB2 
namespace Sync;

require_once 'config.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../SyncObject.php';
$sync = new SyncObject();

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../../apps/ventus/includes/php/bootstrap.php';

$start = microtime(TRUE);
$mem_start = memory_get_usage(TRUE);
require_once EXAMS_TABLE.".php";
printf("%s table is loaded on %s \n", ucfirst(EXAMS_TABLE), date(DATETIME_MYSQL));
printf("%s table took %fs and consumed %fkb \n\n", ucfirst(EXAMS_TABLE), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
echo "=================================== PARTIAL END ================================\n\n";

/**
*       drop the prvious backup tables
*/
$sql = "DROP TABLE IF EXISTS `org_".EXAMS_TABLE."_".date('Y-m',strtotime('-1 month'))."`";
$sync->mysql_query($sql);
$sql = "DROP TABLE IF EXISTS `org_".EXAMS_TABLE."_".date('Y-m')."`";
$sync->mysql_query($sql);

// /* create backup tbs */
$sql = "RENAME TABLE `org_".EXAMS_TABLE."` TO `org_".EXAMS_TABLE."_".date('Y-m')."`";

// /* check if backup create successfully, else revert changes     */
if($sync->mysql_query($sql)){
	printf("%s table is backup on %s\n", EXAMS_TABLE, date(DATETIME_MYSQL));
	$sql = "RENAME TABLE `org_".EXAMS_TABLE."_temp` TO `org_".EXAMS_TABLE."`";
	$sync->mysql_query($sql);
	printf("%s table is renamed on %s\n\n", EXAMS_TABLE, date(DATETIME_MYSQL));
}else{
	printf("%s table did not backup porperly on %s\n", EXAMS_TABLE, date(DATETIME_MYSQL));
	$sql = "RENAME TABLE `org_".EXAMS_TABLE."_".date('Y-m')."` TO `org_".EXAMS_TABLE."`";
	$sync->mysql_query($sql);
}

echo "===================================== END ======================================\n\n";