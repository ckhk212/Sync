<?php
// @author Kelvin Chan
// @date 2014-06-06
// @purpose queries to fetch sass employees' data from DB2, and insert into ventus DB
// @version 1.0

require_once ("/var/www/html/sass/sync/SyncObject.php");
$sync = new SyncObject();

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
FROM SIS.SASS_Employee_info";

$result = $sync->db2_query($db2_query);
echo (count($result)."\n");

$sql = "SELECT
service_id,
org_code
FROM
org_services";
$services = $sync->mysql_query($sql);

$final_result = $sync->join_results($result, $services, 'HOME_DEPARTMENT', 'org_code', 'service_id');

// var_dump($result);
// echo (count($result)."\n");
// exit();

$sql = "INSERT INTO
`org_employees_copy` (
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
`updated_on` = `updated_on` 
";
// DEFINE("DEBUG", '');
var_dump($sync->mysql_insert($final_result,$sql));
