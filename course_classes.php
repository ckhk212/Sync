<?php

$sql = "SELECT
TRIM(ACAD_ACT_CD) || TRIM(SECTION_CD) || TRIM(SESSION_CD) AS COURSE,
MEET_DAY,
MEET_END_DT,
MEET_END_TM,
MEET_START_DT,
MEET_START_TM,
MEET_BUILDING_CD,
MEET_ROOM_NR,
TEACH_METHOD 
FROM
SISR.MEETING_SCHD_COURSES";
$result = $sync->db2_query($sql);


$sql = "SELECT
CONCAT(TRIM(code),TRIM(section),TRIM(session)) AS code,
course_id
FROM
org_courses_temp";
$courses = $sync->mysql_query($sql);

$result = $sync->join_results($result, $courses, 'COURSE','code','course_id');

/**
* A public variable
@var String stores current timestamp
*/
// $time_stamp = date("Y-m-d H:i:s");

$sql = "DROP TABLE IF EXISTS `org_course_classes_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_course_classes_temp` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `building_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `room_number` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `teaching_method` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `day_of_week` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`class_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$sync->mysql_query($sql);

$sql = "INSERT INTO 
org_course_classes_temp (
  course_id, 
  day_of_week, 
  end_date, 
  end_time, 
  start_date, 
  start_time, 
  building_code, 
  room_number, 
  teaching_method, 
  last_updated
  )
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
class_id = class_id,
course_id = course_id, 
day_of_week = VALUES(day_of_week), 
end_date = VALUES(end_date), 
end_time = VALUES(end_time), 
start_date = VALUES(start_date), 
start_time = VALUES(start_time), 
building_code = VALUES(building_code), 
room_number = VALUES(room_number), 
teaching_method = VALUES(teaching_method), 
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql);
// if ($return = $sync->mysql_insert($result,$sql)){
//   $delete_old_course_classes = "DELETE FROM org_course_classes WHERE last_updated < '".$time_stamp ."'";
//   $sync->mysql_query($delete_old_course_classes);
//   printf("Course-classes older than %s are deleted from org_course_classes\n", $time_stamp);
// }
unset($result);

?>
