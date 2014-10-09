<?php
// @author Kelvin Chan
// @date 2014-05-09
// @purpose queries to fetch course classes data from DB2, and insert into ventus DB
// @version 1.3

$sql = "SELECT
TRIM(B.ACAD_ACT_CD) || TRIM(B.SECTION_CD) || TRIM(B.SESSION_CD) AS COURSE,
B.TEACH_METHOD,
B.TEACH_METHOD_MEET,
B.GIVEN_NAME,
B.SURNAME,
CASE WHEN B.SHORT_EMAIL IS NULL 
THEN B.LONG_EMAIL 
ELSE B.SHORT_EMAIL 
END AS EMAIL,
A.MEET_START_DT,
A.MEET_END_DT,
A.MEET_START_TM,
A.MEET_END_TM,
A.MEET_BUILDING_CD,
A.MEET_ROOM_NR,
A.MEET_DAY,
CASE WHEN
B.OFFERED_BY_INST = 350712
THEN 1
ELSE 0
END AS STPAUL
FROM
".DB2_COURSE_SCHEDULE." A
RIGHT JOIN
".DB2_COURSE_ASSIGN_EMAIL." B
ON
A.ACAD_ACT_CD = B.ACAD_ACT_CD AND
A.SECTION_CD = B.SECTION_CD AND
A.SESSION_CD = B.SESSION_CD AND
A.TEACH_METHOD = B.TEACH_METHOD AND 
A.TEACH_METHOD_MEET = B.TEACH_METHOD_MEET";

$result = $sync->db2_query($sql);

$sql = "SELECT
CONCAT(TRIM(code),TRIM(section),TRIM(session)) AS code,
course_id
FROM
org_".COURSES_TABLE."_temp";
$courses = $sync->mysql_query($sql);

$result = $sync->join_results($result, $courses, 'COURSE','code','course_id');

$sql = "CREATE TABLE `org_".COURSE_CLASSES_TABLE."_temp` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `teaching_method` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `teaching_method_meet` tinyint(2) NOT NULL,
  `professor_first_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `professor_last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `professor_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `building_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `room_number` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `day_of_week` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `stpaul` tinyint(1) NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`class_id`),
  KEY `fk_org_".COURSE_CLASSES_TABLE."_org_".COURSES_TABLE."_idx_".date("Y-m-d H:i")."` (`course_id`),
  CONSTRAINT `fk_org_".COURSE_CLASSES_TABLE."_org_".COURSES_TABLE.date("Y-m-d H:i")."` FOREIGN KEY (`course_id`) REFERENCES `org_".COURSES_TABLE."_temp` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

$sync->mysql_query($sql);

$sql = "INSERT INTO 
org_".COURSE_CLASSES_TABLE."_temp ( 
  `course_id`,
  `teaching_method`,
  `teaching_method_meet`,
  `professor_first_name`,
  `professor_last_name`,
  `professor_email`,
  `start_date`,
  `end_date`,
  `start_time`,
  `end_time`,
  `building_code`,
  `room_number`,
  `day_of_week`,
  `stpaul`,
  `last_updated`
  )
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
teaching_method = VALUES(teaching_method),
teaching_method_meet = VALUES(teaching_method_meet),
professor_first_name = VALUES(professor_first_name),
professor_last_name = VALUES(professor_last_name),
professor_email = VALUES(professor_email),
start_date = VALUES(start_date),
end_date = VALUES(end_date),
start_time = VALUES(start_time),
end_time = VALUES(end_time),
building_code = VALUES(building_code),
room_number = VALUES(room_number),
day_of_week = VALUES(day_of_week),
stpaul = VALUES(stpaul),
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql, count($result));

// unset the variables to prevent memory lost
unset($courses, $result, $sql);
