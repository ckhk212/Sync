<?php

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
SISR.DEPARTMENTS";
$result = $sync->db2_query($sql);



$sql = "SELECT
code,
faculty_id
FROM
org_faculties_temp";
$faculties = $sync->mysql_query($sql);

$result = $sync->join_results($result,$faculties,'FACULTY_ID','code','faculty_id');

unset($faculties);

$sql = "DROP TABLE IF EXISTS `org_departments_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_departments_temp` (
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
org_departments_temp (
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
$sync->mysql_insert($result,$sql);
unset($result);

?>
