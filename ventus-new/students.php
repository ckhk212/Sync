<?php
// @author Kelvin Chan
// @date 2014-06-23
// @purpose queries to fetch students data from DB2, and insert into ventus DB
// @version 1.2

/* get a list of all possible faculties */
$sql = "SELECT
code,
faculty_id
FROM
org_".FACULTIES_TABLE."_temp";
$faculties = $this->mysql_query($sql);

/* get a list of all possible programs */
$sql = "SELECT
code,
program_id
FROM
org_".PROGRAMS_TABLE."_temp";
$this->programs = $this->mysql_query($sql);

/* get current student size */
$db2_sql = "SELECT MAX(STUDENT_ID) FROM SISR.STUDENT_PERSONAL";
$this->maxStudentNum = $this->db2_query($db2_sql);
$this->maxStudentNum = $this->maxStudentNum[0][1];

//Get all the student from SIS database in incremental basis
for ($count = 0;$count<$this->maxStudentNum;$count+=STUDENT_INCREMENT_SIZE){
  getPartialStudentData($count, $count+STUDENT_INCREMENT_SIZE, $this);
}

// unset the variables to prevent memory lost
unset($faculties, $programs, $sql, $db2_sql, $count);

function getPartialStudentData($startID, $endID, $sync){
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
  ".DB2_STUDENT_PERSONAL." A
  LEFT OUTER JOIN
  ".DB2_STUDENT_REGISTRATION." B
  ON 
  A.STUDENT_ID = B.PERSON_ID 
  WHERE A.STUDENT_ID >= '$startID' AND A.STUDENT_ID < '$endID'
  AND B.ATTENDANCE_CLASS IS NOT NULL";

  $result = $sync->db2_query($sql);

//Join the faculty_id to the student's record
  $result = $sync->join_results($result, $sync->faculties, 'FACULTY_ID', 'code', 'faculty_id', FALSE);

//Join the program_id to the student's record
  $result = $sync->join_results($result, $sync->programs, 'PROGRAM_ID', 'code', 'program_id', FALSE);

//Insert/update the students' records into our DB
  $sql = "INSERT INTO
  org_".STUDENTS_TABLE."_temp (
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
faculty_id = faculty_id,
program_id = program_id,
student_status = student_status,
first_name = first_name,
last_name = last_name,
birth_date = birth_date,
gender = gender,
year_of_study = year_of_study,
marital_status = marital_status,
corr_lang = corr_lang,
prim_lang = prim_lang,
perm_st = perm_st,
perm_city = perm_city,
perm_prov = perm_prov,
perm_country_e = perm_country_e,
perm_country_f =  perm_country_f,
perm_postal_cd = perm_postal_cd,
perm_phone = perm_phone,
mail_st = mail_st,
mail_city = mail_city,
mail_prov = mail_prov,
mail_country_e = mail_country_e,
mail_country_f = mail_country_f,
mail_postal_cd = mail_postal_cd,
mail_phone = mail_phone,
email = email,
last_updated = last_updated";

$sync->mysql_insert($result,$sql, count($result));

unset($result);
}
