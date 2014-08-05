<?php 
// @author Kelvin Chan
// @date 2014-06-23
// @purpose queries to fetch courses data from DB2, and insert into ventus DB
// @version 1.4

$sql = "SELECT 
ACAD_ACT_TITLE,
INVENTORY_SESSION_CD,
INVENTORY_ACAD_ACT_CD,
INVENTORY_SECTION_CD
FROM 
SISR.TEACH_ASSIGN_COURSE_invent_EMAIL_V02
GROUP BY 
ACAD_ACT_TITLE,
INVENTORY_SESSION_CD,
INVENTORY_ACAD_ACT_CD,
INVENTORY_SECTION_CD";

$result = $this->db2_query($sql);

$sql = "INSERT INTO 
org_".COURSES_TABLE."_temp (
  name,
  session,
  code,
  section,
  last_updated
  )
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
course_id = course_id,
name = name,
session = session,
code = code,
section = section,
last_updated = last_updated";
$this->mysql_insert($result,$sql, count($result));

// unset the variables to prevent memory lost
unset($result, $sql);
