<?php
// @author Kelvin Chan
// @date 2014-06-23
// @purpose queries to fetch departments data from DB2, and insert into ventus DB
// @version 1.2

$sql = "SELECT 
DEPARTMENT_ID,
FACULTY_ID,
DEPARTMENT_ENGLISH_NAME,
DEPARTMENT_FRENCH_NAME,
CASE WHEN DEPARTMENT_END_DT > '" . date('Y-m-d') . "'
THEN 1
ELSE 0
END  AS ACTIVE
FROM
".DB2_DEPARTMENTS."";
$result = $this->db2_query($sql);

$sql = "SELECT
code,
faculty_id
FROM
org_".FACULTIES_TABLE."_temp";
$this->faculties = $this->mysql_query($sql);

$result = $this->join_results($result,$this->faculties,'FACULTY_ID','code','faculty_id');

$sql = "INSERT INTO 
org_".DEPARTMENTS_TABLE."_temp (
	code,
	faculty_id,
	department_eng,
	department_fra,
	active,
	last_updated
	)
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
department_id = department_id,
code = code,
faculty_id = faculty_id,
department_eng = department_eng,
department_fra = department_fra,
active = active,
last_updated = last_updated";

$this->mysql_insert($result,$sql, count($result));

$sql = "INSERT INTO
org_".DEPARTMENTS_TABLE."_temp (
  code,
  faculty_id,
  department_eng,
  department_fra,
  active,
  last_updated
  )
VALUES
('', '0','No department', 'Aucun departement', '0', '".date("Y-m-d H:i:s")."')
ON DUPLICATE KEY UPDATE 
department_id = department_id,
code = code,
faculty_id = faculty_id,
department_eng = department_eng,
department_fra = department_fra,
active = active,
last_updated = last_updated";

$this->mysql_query($sql);

$last_id = $this->mysql_query("SELECT LAST_INSERT_ID()");

$this->mysql_query("UPDATE org_".DEPARTMENTS_TABLE."_temp SET department_id=0 where department_id=".$last_id['0']['LAST_INSERT_ID()']."");

// unset the variables to prevent memory lost
unset($result, $sql, $last_id);
