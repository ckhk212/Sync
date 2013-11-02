<?php 

$sql = "SELECT 
ACAD_ACT_TITLE,
SESSION_CD,
ACAD_ACT_CD,
SECTION_CD,
TEACH_METHOD,
GIVEN_NAME,
SURNAME,
SHORT_EMAIL
FROM 
SISR.TEACH_ASSIGN_COURSE_EMAIL
GROUP BY 
ACAD_ACT_TITLE,
SESSION_CD,
ACAD_ACT_CD,
SECTION_CD,
TEACH_METHOD,
GIVEN_NAME,
SURNAME,
SHORT_EMAIL";
$result = $sync->db2_query($sql);


$sql = "DROP TABLE IF EXISTS `org_courses_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_courses_temp` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `section` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `teach_method` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `professor_first_name` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `professor_last_name` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `professor_email` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `session` (`session`,`code`,`section`,`teach_method`,`professor_first_name`,`professor_last_name`),
  KEY `index3` (`teach_method`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$sync->mysql_query($sql);

$sql = "INSERT INTO 
org_courses_temp (
  name,
  session,
  code,
  section,
  teach_method,
  professor_first_name,
  professor_last_name,
  professor_email,
  last_updated
  )
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
name = VALUES(name),
professor_email = VALUES(professor_email),
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql);
/*
INSERT INTO table2 (column1, column2, column3)
SELECT column1, 'no', column3 FROM table2 WHERE column2 = 'yes'

this could be the patch solution for missing professor email on TUT 
*/
unset($data);

?>
