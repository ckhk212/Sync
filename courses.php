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

/**
* A public variable
@var String stores current timestamp
*/
// $time_stamp = date("Y-m-d H:i:s");

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
// if ($return = $sync->mysql_insert($result,$sql)){
  // $delete_old_courses = "DELETE FROM org_courses WHERE last_updated < '".$time_stamp ."'";
  // $sync->mysql_query($delete_old_courses);
  // printf("Courses older than %s are deleted from org_courses\n", $time_stamp);
// }
unset($data);

?>
