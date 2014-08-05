<?php 
// @author Kelvin Chan
// @date 2014-06-23
// @purpose queries to fetch faculties data from DB2, and insert into ventus DB
// @version 1.2

$sql = "SELECT 
FACULTY_ID,
RTRIM(FACULTY_ENGLISH_NAME),
RTRIM(FACULTY_FRENCH_NAME),
CASE WHEN
BELONGS_TO_INST_ID = 350712
THEN 1
ELSE 0
END AS STPAUL,
CASE WHEN
FACULTY_END_DT > DATE('" . date('Y-m-d') . "')
THEN 1 
ELSE 0 
END AS ACTIVE
FROM 
".DB2_FACULTUES."";
$result = $this->db2_query($sql);

$sql = "INSERT INTO
org_".FACULTIES_TABLE."_temp (
	code, 
	faculty_eng, 
	faculty_fra, 
	stpaul, 
	active, 
	last_updated
	)
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
faculty_id = faculty_id,
code = code,
faculty_eng = faculty_eng, 
faculty_fra = faculty_fra, 
stpaul = stpaul,
active = active,
last_updated = last_updated";

$this->mysql_insert($result,$sql, count($result));

$sql = "INSERT INTO
org_".FACULTIES_TABLE."_temp (
  code, 
  faculty_eng, 
  faculty_fra, 
  stpaul, 
  active, 
  last_updated
  )
VALUES
('', 'NO FACULTY', 'Aucun faculte','0', '0', '".date("Y-m-d H:i:s")."')
ON DUPLICATE KEY UPDATE 
faculty_id = faculty_id,
code = code,
faculty_eng = faculty_eng, 
faculty_fra = faculty_fra, 
stpaul = stpaul,
active = active,
last_updated = last_updated";

$this->mysql_query($sql);

$last_id = $this->mysql_query("SELECT LAST_INSERT_ID()");

$this->mysql_query("UPDATE org_".FACULTIES_TABLE."_temp SET faculty_id=0 where faculty_id=".$last_id['0']['LAST_INSERT_ID()']."");

// unset the variables to prevent memory lost
unset($result, $sql, $last_id);