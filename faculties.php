<?php 

$sql = "SELECT 
FACULTY_ID,
RTRIM(FACULTY_ENGLISH_NAME),
RTRIM(FACULTY_FRENCH_NAME),
CASE WHEN
BELONGS_TO_INST_ID = 350712
THEN 1
ELSE 0
END AS STPAUL,
CASE WHEN
FACULTY_END_DT > DATE('" . date('Y-m-d') . "')
THEN 1 
ELSE 0 
END AS ACTIVE
FROM 
SISR.FACULTIES";
$result = $sync->db2_query($sql);

$sql ="DROP TABLE IF EXISTS `org_faculties_temp`";

$sync->mysql_query($sql);

$sql = "CREATE TABLE `org_faculties_temp` (
  `faculty_id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL,
  `code` varchar(255) NOT NULL,
  `stpaul` tinyint(1) NOT NULL,
  `faculty_eng` varchar(255) NOT NULL,
  `faculty_fra` varchar(255) NOT NULL,
  `last_updated` datetime NOT NULL,
  PRIMARY KEY (`faculty_id`),
  UNIQUE KEY `code_UNIQUE` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$sync->mysql_query($sql);

$sql = "INSERT INTO
org_faculties_temp (
	code, 
	faculty_eng, 
	faculty_fra, 
	stpaul, 
	active, 
	last_updated
	)
VALUES 
{DATA}
ON DUPLICATE KEY UPDATE 
faculty_id = faculty_id,
code = code,
faculty_eng = VALUES(faculty_eng), 
faculty_fra = VALUES(faculty_fra), 
stpaul = VALUES(stpaul),
active = VALUES(active),
last_updated = VALUES(last_updated)";
$sync->mysql_insert($result,$sql);
unset($result);

?>
