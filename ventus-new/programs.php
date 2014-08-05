<?php
// @author Kelvin Chan
// @date 2014-06-23
// @purpose queries to fetch programs data from DB2, and insert into ventus DB
// @version 1.2

$sql = "SELECT 
MAX(SECOND_ORG_CD) AS SECOND_ORG_CD,
POST_CD,
MAX(POST_ENG_TITLE1) AS POST_ENG_TITLE1,
MAX(POST_FRE_TITLE1) AS POST_FRE_TITLE1,
CASE WHEN 
MAX(POST_OFFER_END_SESSION_CD) > '" . date('Y-m-d') . "'
THEN 1
ELSE 0
END AS ACTIVE
FROM 
".DB2_PROGRAMS."
GROUP BY 
POST_CD";
$result = $this->db2_query($sql);

$sql = "SELECT
department_id,
code
FROM
org_".DEPARTMENTS_TABLE."_temp";
$this->departments = $this->mysql_query($sql);

$result = $this->join_results($result,$this->departments,'SECOND_ORG_CD','code','department_id', FALSE);

$sql = "INSERT INTO 
org_".PROGRAMS_TABLE."_temp (
	department_id,
	code,
	program_eng,
	program_fra,
	active,
	last_updated 
	)
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE
program_id = program_id,
department_id = department_id,
active = active,
code = code,
program_eng = program_eng,
program_fra = program_fra,
last_updated = last_updated";

$this->mysql_insert($result,$sql, count($result));


$sql = "INSERT INTO
org_".PROGRAMS_TABLE."_temp (
  department_id,
  code,
  program_eng,
  program_fra,
  active,
  last_updated 
  )
VALUES
(0, '','No program', 'Aucun program', '0', '".date("Y-m-d H:i:s")."')
ON DUPLICATE KEY UPDATE
program_id = program_id,
department_id = department_id,
active = active,
code = code,
program_eng = program_eng,
program_fra = program_fra,
last_updated = last_updated";

$this->mysql_query($sql);

$last_id = $this->mysql_query("SELECT LAST_INSERT_ID()");

$this->mysql_query("UPDATE org_".PROGRAMS_TABLE."_temp SET program_id=0 where program_id=".$last_id['0']['LAST_INSERT_ID()']."");

unset($result, $sql, $last_id);