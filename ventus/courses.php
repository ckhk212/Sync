<?php 
// @author Kelvin Chan
// @date 2014-05-09
// @purpose queries to fetch courses data from DB2, and insert into ventus DB
// @version 1.3

$sql = "SELECT 
ACAD_ACT_TITLE,
SESSION_CD,
ACAD_ACT_CD,
SECTION_CD
FROM 
".DB2_COURSES."
WHERE OFFERED_BY_INST != '350712'
GROUP BY 
ACAD_ACT_TITLE,
SESSION_CD,
ACAD_ACT_CD,
SECTION_CD";
$result = $sync->db2_query($sql);

$sql = "CREATE TABLE `org_".COURSES_TABLE."_temp` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `section` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_unique` (`session`,`code`,`section`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$sync->mysql_query($sql);

$sql = "INSERT INTO 
org_".COURSES_TABLE."_temp (
  name,
  session,
  code,
  section,
  last_updated
  )
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
name = VALUES(name),
session = VALUES(session),
code = VALUES(code),
section = VALUES(section),
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql, count($result));

// unset the variables to prevent memory lost
unset($result, $sql);
