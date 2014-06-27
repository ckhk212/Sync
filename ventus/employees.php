<?php
// @author Kelvin Chan
// @date 2014-06-25
// @purpose queries to fetch sass employees' data from DB2, and insert into ventus DB
// @version 1.1

$sql = "CREATE TABLE `org_".SASS_EMPLOYEES_TABLE."_temp` (
  `emp_alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `emp_number` int(11) NOT NULL,
  `emp_first_name` varchar(2555) COLLATE utf8_unicode_ci NOT NULL,
  `emp_last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `emp_gender` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `emp_birthdate` date NOT NULL,
  `emp_office_phone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `emp_office_phone_ext` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `inserted_on` datetime NOT NULL,
  `updated_on` datetime NOT NULL,
  PRIMARY KEY (`emp_alias`),
  KEY `connect_to_service_idx_".date("Y-m-d H:i")."` (`service_id`),
  CONSTRAINT `org_employees_copy_ibfk_".date("Y-m-d H:i")."` FOREIGN KEY (`service_id`) REFERENCES `org_services` (`service_id`) ON DELETE NO ACTION ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

$sync->mysql_query($sql);

$db2_query = "SELECT 
LOWER(USER_ID), 
EMPLOYEE_ID, 
EMP_GIVEN_NAME, 
EMP_SURNAME,
GENDER,
BIRTH_DT, 
TELEPHONE_NR, 
EXTENSION_NR,
HOME_DEPARTMENT
FROM ".DB2_SASS_EMPLOYEES;

$result = $sync->db2_query($db2_query);

$sql = "SELECT
service_id,
org_code
FROM
org_services";
$services = $sync->mysql_query($sql);

$final_result = $sync->join_results($result, $services, 'HOME_DEPARTMENT', 'org_code', 'service_id');

$sql = "INSERT INTO
`org_".SASS_EMPLOYEES_TABLE."_temp` (
	`emp_alias`,
	`emp_number`,
	`emp_first_name`, 
	`emp_last_name`,
	`emp_gender`,
	`emp_birthdate`,
	`emp_office_phone`,
	`emp_office_phone_ext`,
	`service_id`,
	`inserted_on`
	)
VALUES
{DATA}
ON DUPLICATE KEY UPDATE
`emp_alias` = LOWER(`emp_alias`),
`emp_number` = `emp_number`,
`emp_last_name` = `emp_last_name`,
`emp_gender` = `emp_gender`,
`emp_birthdate` = `emp_birthdate`,
`emp_office_phone` = `emp_office_phone`,
`emp_office_phone_ext` = `emp_office_phone_ext`,
`service_id` = `service_id`,
`updated_on` = `updated_on`";
$sync->mysql_insert($final_result,$sql);

unset($sql, $db2_query, $result, $final_result);
