<?php
// @author Kelvin Chan
// @date 2014-05-09
// @purpose queries to fetch departments data from DB2, and insert into ventus DB

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
$result = $sync->db2_query($sql);

$sql = "SELECT
code,
faculty_id
FROM
org_".FACULTIES_TABLE."_temp";
$faculties = $sync->mysql_query($sql);

$result = $sync->join_results($result,$faculties,'FACULTY_ID','code','faculty_id');

$sql = "CREATE TABLE `org_".DEPARTMENTS_TABLE."_temp` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `department_eng` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `department_fra` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  KEY `fk_uottawa_departments_uottawa_faculties_idx` (`faculty_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

$sync->mysql_query($sql);

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
faculty_id = VALUES(faculty_id),
department_eng = VALUES(department_eng),
department_fra = VALUES(department_fra),
active = VALUES(active),
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql, count($result));

// unset the variables to prevent memory lost
unset($faculties, $result, $sql);
