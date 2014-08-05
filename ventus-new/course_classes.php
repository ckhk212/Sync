<?php
// @author Kelvin Chan
// @date 2014-06-23
// @purpose queries to fetch course classes data from DB2, and insert into ventus DB
// @version 1.4

$sql = "SELECT
TRIM(B.INVENTORY_ACAD_ACT_CD) || TRIM(B.INVENTORY_SECTION_CD) || TRIM(B.INVENTORY_SESSION_CD) AS COURSE,
B.TEACH_METHOD,
B.TEACH_METHOD_MEET,
B.SURNAME,
B.GIVEN_NAME,
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
FULL OUTER JOIN
SISR.TEACH_ASSIGN_COURSE_invent_EMAIL_V02 B
ON
A.ACAD_ACT_CD = B.INVENTORY_ACAD_ACT_CD AND
A.SECTION_CD = B.INVENTORY_SECTION_CD AND
A.SESSION_CD = B.INVENTORY_SESSION_CD AND
A.TEACH_METHOD = B.TEACH_METHOD AND 
A.TEACH_METHOD_MEET = B.TEACH_METHOD_MEET";

$result = $this->db2_query($sql);

$sql = "SELECT
CONCAT(TRIM(code),TRIM(section),TRIM(session)) AS code,
course_id
FROM
org_".COURSES_TABLE."_temp";
$this->courses = $this->mysql_query($sql);

$result = $this->join_results($result, $this->courses, 'COURSE','code','course_id');

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
class_id = class_id,
course_id = course_id,
teaching_method = teaching_method,
teaching_method_meet = teaching_method_meet,
professor_first_name = professor_first_name,
professor_last_name = professor_last_name,
professor_email = professor_email,
start_date = start_date,
end_date = end_date,
start_time = start_time,
end_time = end_time,
building_code = building_code,
room_number = room_number,
day_of_week = day_of_week,
stpaul = stpaul,
last_updated = last_updated";
$this->mysql_insert($result,$sql, count($result));

// unset the variables to prevent memory lost
unset($result, $sql);
