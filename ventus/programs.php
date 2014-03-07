<?php
// @author Kelvin Chan
// @date 2014-01-09
// @purpose queries to fetch programs data from DB2, and insert into ventus DB

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
SISR.POST_INVENTORY_PROGRAMS
GROUP BY 
POST_CD";
$result = $sync->db2_query($sql);

$sql = "SELECT
department_id,
code
FROM
org_departments_temp";
$departments = $sync->mysql_query($sql);

$result = $sync->join_results($result,$departments,'SECOND_ORG_CD','code','department_id', FALSE);

unset($departments);

$sql = "DROP TABLE IF EXISTS `org_programs_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_programs_temp` (
	`program_id` int(11) NOT NULL AUTO_INCREMENT,
	`department_id` int(11) NOT NULL,
	`active` tinyint(1) NOT NULL,
	`code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`program_eng` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`program_fra` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`last_updated` datetime NOT NULL,
	PRIMARY KEY (`program_id`),
	UNIQUE KEY `code_UNIQUE` (`code`),
	KEY `fk_uottawa_programs_uottawa_departments_idx` (`department_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$sync->mysql_query($sql);

$sql = "INSERT INTO 
org_programs_temp (
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
department_id = VALUES(department_id),
active = VALUES(active),
code = VALUES(code),
program_eng = VALUES(program_eng),
program_fra = VALUES(program_fra),
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql);
unset($result);

?>
