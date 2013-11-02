<?php
// require_once ('SyncObject.php');
// $sync = new SyncObject();
$db2_sql = "SELECT
ACAD_ACT_CD,
SESSION_CD,
SECTION_CD,
EXAM_DURATION,
EXAM_DT||' '||EXAM_START_TM AS EXAM_DATE
FROM SISR.CURRENT_SESS_EXAM_SCHEDULE_V01 
WHERE EXAM_LOCATION != 'takehome'";
$result = $sync->db2_query($db2_sql);
//var_dump($result);
// exit();
$sql ="DROP TABLE IF EXISTS `TEST_ventus_professor_exam_requests_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `TEST_ventus_professor_exam_requests_temp` (
  `exam_request_id` int(11) NOT NULL AUTO_INCREMENT,
  `session` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `course_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `course_section` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `exam_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `exam_date` datetime NOT NULL,
  `exam_duration` int(11) NOT NULL,
  `exam_alternate_special` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `exam_alternate_special_student` int(11) DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `contact_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `requestor_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `confirmation_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `prof_filled_control_sheet` tinyint(1) NOT NULL DEFAULT '0',
  `documents_received` tinyint(1) NOT NULL DEFAULT '0',
  `imported_automatically` tinyint(1) NOT NULL DEFAULT '0',
  `inserted_on` datetime NOT NULL,
  `updated_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`exam_request_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Created by: Kelvin Chan\nPurpose: Test out the exam data from DB2'";

$sync->mysql_query($sql);

$sql = "INSERT INTO
TEST_ventus_professor_exam_requests_temp (
	course_code,
	session,
	course_section,
	exam_duration,
	exam_date,
	inserted_on
)
VALUES
{DATA}";
$sync->mysql_insert($result,$sql);

$sql = "UPDATE TEST_ventus_professor_exam_requests_temp SET exam_type='final', exam_alternate_special='none', contact_name='REGISTRAR', requestor_email='examen@uottawa.ca', confirmation_key=CONCAT(SHA1(RAND()),SHA1(RAND())), is_confirmed=1, imported_automatically=1, updated_on=inserted_on";
$sync->mysql_query($sql);
unset($result);
?>
