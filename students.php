<?php

$sql = "DROP TABLE IF EXISTS `org_students_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_students_temp` (
  `student_id` int(11) NOT NULL DEFAULT '0',
  `faculty_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `student_status` varchar(45) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` varchar(255) NOT NULL,
  `year_of_study` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) NOT NULL,
  `corr_lang` varchar(255) DEFAULT NULL,
  `prim_lang` varchar(255) DEFAULT NULL,
  `perm_st` varchar(255) NOT NULL,
  `perm_city` varchar(255) NOT NULL,
  `perm_prov` varchar(255) NOT NULL,
  `perm_country_e` varchar(255) NOT NULL,
  `perm_country_f` varchar(255) NOT NULL,
  `perm_postal_cd` varchar(255) NOT NULL,
  `perm_phone` varchar(255) NOT NULL,
  `mail_st` varchar(255) NOT NULL,
  `mail_city` varchar(255) NOT NULL,
  `mail_prov` varchar(255) NOT NULL,
  `mail_country_e` varchar(255) NOT NULL,
  `mail_country_f` varchar(255) NOT NULL,
  `mail_postal_cd` varchar(255) NOT NULL,
  `mail_phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`student_id`),
  KEY `fk_uottawa_students_uottawa_faculties1_idx` (`faculty_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$sync->mysql_query($sql);

//Get a list of all possible faculties
$sql = "SELECT
code,
faculty_id
FROM
org_faculties_temp";
$faculties = $sync->mysql_query($sql);

//Get a list of all possible programs
$sql = "SELECT
code,
program_id
FROM
org_programs_temp";
$programs = $sync->mysql_query($sql);

//Get all the student from SIS database and set the limit to 100000 students per iteration.
// The pupose of this iteration is to avoid PHP Fatal error: Allowed memory size of 2000000000.
// The error is a known bug for php 5.1.6, it only reconized a maximum of 2GB memory, even the system has a 64bit format.
for ($count = 0;$count<8000000;$count+=100000){
  getPartialStudentData($count, $count+100000, $faculties, $programs);
}

function getPartialStudentData($startID, $endID,  $faculties, $programs){
  global $sync;
  $sql = "SELECT 
  A.STUDENT_ID AS STUDENT_ID,
  B.PRIMARY_ORG_CD AS FACULTY_ID,
  B.POST_CD AS  PROGRAM_ID,
  B.ATTENDANCE_CLASS,
  RTRIM(GIVEN_NAME),
  RTRIM(SURNAME), 
  BIRTH_DT,
  GENDER,
  YEAR_OF_STUDY,
  RTRIM(STUDENT_EMAIL),
  MARITAL_STS,
  CORRESPONDENCE_LNG,
  MOTHER_TONGUE,
  RTRIM(MAIL_CITY),
  MAIL_COUNTRY_ENGLISH_DESC,
  MAIL_POSTAL_CD,
  MAIL_PROVINCE_CD,
  RTRIM(MAIL_STREET_LINE1),
  MAIL_TELEPHONE_NR,
  RTRIM(PERM_CITY),
  PERM_COUNTRY_ENGLISH_DESC,
  PERM_POSTAL_CD,
  PERM_PROVINCE_CD,
  RTRIM(PERM_STREET_LINE1),
  PERM_TELEPHONE_NR
  FROM
  SISR.STUDENT_PERSONAL A
  LEFT OUTER JOIN
  SISR.STUDENT_REGISTRATION_INSCRIT_V3 B
  ON 
  A.STUDENT_ID = B.PERSON_ID WHERE A.STUDENT_ID >= '$startID' AND A.STUDENT_ID < '$endID'";

  $result = $sync->db2_query($sql);


  
//Join the faculty_id to the student's record
  $result = $sync->join_results($result, $faculties, 'FACULTY_ID', 'code', 'faculty_id', FALSE);


//Join the program_id to the student's record
  $result = $sync->join_results($result, $programs, 'PROGRAM_ID', 'code', 'program_id', FALSE);

//Insert/update the students' records into our DB
  $sql = "INSERT INTO
  org_students_temp (
    student_id, 
    faculty_id,
    program_id,
    student_status, 
    first_name, 
    last_name, 
    birth_date, 
    gender, 
    year_of_study, 
    email,
    marital_status, 
    corr_lang, 
    prim_lang, 
    mail_city,
    mail_country_e,
    mail_postal_cd, 
    mail_prov,
    mail_st,
    mail_phone,
    perm_city,
    perm_country_e,
    perm_postal_cd,
    perm_prov,
    perm_st,
    perm_phone,
    last_updated
    )
VALUES
{DATA}
ON DUPLICATE KEY UPDATE 
student_id = student_id,
faculty_id = VALUES(faculty_id),
program_id = VALUES(program_id),
student_status = VALUES(student_status),
first_name = VALUES(first_name),
last_name = VALUES(last_name),
birth_date = VALUES(birth_date),
gender = VALUES(gender),
year_of_study = VALUES(year_of_study),
marital_status = VALUES(marital_status),
corr_lang = VALUES(corr_lang),
prim_lang = VALUES(prim_lang),
perm_st = VALUES(perm_st),
perm_city = VALUES(perm_city),
perm_prov = VALUES(perm_prov),
perm_country_e = VALUES(perm_country_e),
perm_postal_cd = VALUES(perm_postal_cd),
perm_phone = VALUES(perm_phone),
mail_st = VALUES(mail_st),
mail_city = VALUES(mail_city),
mail_prov = VALUES(mail_prov),
mail_country_e = VALUES(mail_country_e),
mail_postal_cd = VALUES(mail_postal_cd),
mail_phone = VALUES(mail_phone),
email = VALUES(email),
last_updated = VALUES(last_updated)";

$sync->mysql_insert($result,$sql, 10000);

unset($result); 
}
?>
