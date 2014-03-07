<?php 
require_once("../SyncObject.php");
$sync = new SyncObject();
if (!isset($argv[1]) && !isset($argv[2])) exit("Error: Missing start/end argument values!\n For example: 20139 as year 2013 fall semester.\n");
else{
	$sql = "update UOTT.APPL_TMST_RANGE_V_SASSAPP
	Set Start_tmst = current timestamp,
	End_tmst = current timestamp,
	Start_session_cd = '".$argv[1]."',       
	End_session_cd = '".$argv[2]."'";
	$result = $sync->db2_query($sql);
	var_dump ($result);
}
?>