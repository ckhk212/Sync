<?php
/*
@purpose Script to determine if final exams need to be inserted, updated and/or deleted
@author Kelvin Chan
@date 	2014-03-20
@version 1.2
*/

define ("DB2_EXAM_TABLE", "exams"); // final exam data fetched from DB2
define ("VENTUS_EXAM_TABLE", "ventus_professor_exam_requests"); // Ventus production exam table

define('FOLDER_PATH', '/var/www/html/sass/sync/exam/'); // file save path
define('FILE_NAME', 'Final Exam Data'); // file name
define('FILE_EXT', 'csv'); // file extension

require '/var/www/html/sass/sync/SyncObject.php';
$sync = new SyncObject();

/* some esstenial includes from Ventus */
require_once('/var/www/html/sass/apps/ventus/includes/php/config.php');
require_once(FS_PROFESSOR . '/models/professor.php'); 
require_once(FS_FACULTY . '/models/faculty.php');

$professor = new RequestForm(); // ventus professor object
$faculty = new Faculty(); // ventus faculty object
$csvContent = array(); // file content

// unserialize the defined exam period
$examPeriod = unserialize(PROFESSOR_NOE_SUBMISSION_BLACKOUT);

/* Find exams that are not exisit on VENTUS_EXAM_TABLE */
$sql="SELECT `session`, `course_code`, `course_section`, `exam_type`, `exam_date`, `exam_duration`, `exam_alternate_special`, 
`contact_name`, `requestor_email`, `confirmation_key` as cid, `is_confirmed`,`imported_automatically`, 
`inserted_on`
FROM `org_".DB2_EXAM_TABLE."` new WHERE NOT EXISTS (SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type AND
	new.deleted = old.deleted AND
	new.imported_automatically = old.imported_automatically )";
$result = $sync->mysql_query($sql);

if (is_array($result) && !is_object($result)){

	/* if today's date is smaller than the defined PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE, then change the NOEs table */
	if(strtotime(date("Y-m-d H:i:s")) < strtotime(PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE)){
		echo "INSERT on ".date("Y-m-d H:i:s")."\n\n";
		var_dump($result); 
		$sql="INSERT INTO `".VENTUS_EXAM_TABLE."` (`session`, `course_code`, `course_section`, `exam_type`, `exam_date`, `exam_duration`, `exam_alternate_special`, 
			`contact_name`, `requestor_email`, `confirmation_key`, `is_confirmed`, `imported_automatically`, 
			`inserted_on`, `updated_on`) VALUES {DATA}";
		$sync->mysql_insert($result,$sql);
		foreach ($result as $row){
			$row['source'] = "prof";	
			$professor->reminderToAccessServiceStudents($row); /* send email to students */
		}	
	}

	/* if today's date is greater than the defined PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE and also less than the end of exam date, then prepare CSV */
	if(strtotime(date("Y-m-d H:i:s")) > strtotime(PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE) && strtotime(date("Y-m-d H:i:s")) < strtotime($examPeriod["end"])){
		// array_push($csvContent, "New");
		// $csvContent = array_merge($csvContent, $result);
	}
}

/* Find exams require update on VENTUS_EXAM_TABLE */
$sql = "SELECT new.* FROM `org_".DB2_EXAM_TABLE."` new
RIGHT JOIN `".VENTUS_EXAM_TABLE."` old
ON 
new.session = old.session AND
new.course_code = old.course_code AND
new.course_section = old.course_section AND
new.exam_type = old.exam_type AND
new.deleted = old.deleted AND
new.imported_automatically = old.imported_automatically
WHERE 
new.exam_date != old.exam_date 
OR new.exam_duration != old.exam_duration";
$result = $sync->mysql_query($sql);

/* Update VENTUS_EXAM_TABLE entries if $result is found */
if (is_array($result) && !is_object($result)){
	/* if today's date is smaller than the defined PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE, then change the NOEs table */
	if(strtotime(date("Y-m-d H:i:s")) < strtotime(PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE)){
		echo "UDPATE on ".date("Y-m-d H:i:s")."\n\n";
		var_dump($result);
		foreach ($result as $row){
			$sql ="SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE old.session = '".$row[session]."' AND
			old.course_code = '".$row[course_code]."' AND
			old.course_section = '".$row[course_section]."' AND
			old.exam_type = '".$row[exam_type]."' AND
			old.imported_automatically = '".$row[imported_automatically]."'";
			$old_result = $sync->mysql_query($sql);
			$faculty->updateRequestDetails($old_result[0]['exam_request_id'], $row);
		}
	}

	/* if today's date is greater than the defined PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE and also less than the end of exam date, then prepare CSV */
	if(strtotime(date("Y-m-d H:i:s")) > strtotime(PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE) && strtotime(date("Y-m-d H:i:s")) < strtotime($examPeriod["end"])){
		// array_push($csvContent, "Update");
		// $csvContent = array_merge($csvContent, $result);
	}
}

/* 
	@purpose Get current semester final exam fields: session, exam_type, imported_automatically, deleted
			The fields are use for the following query to find which final exams are no longer exist on the Ventus
			production table
*/
$sql = "SELECT session, exam_type, imported_automatically, deleted FROM `org_".DB2_EXAM_TABLE."` ORDER BY session DESC LIMIT 1";
$currentExamProperties = $sync->mysql_query($sql);


			/* Find entries need to be deleted on VENTUS_EXAM_TABLE */
$sql = "SELECT * FROM `".VENTUS_EXAM_TABLE."` old WHERE NOT EXISTS (SELECT * FROM `org_".DB2_EXAM_TABLE."` new WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type) 
AND old.session='".$currentExamProperties[0]['session']."'
AND old.exam_type='".$currentExamProperties[0]['exam_type']."'
AND old.imported_automatically='".$currentExamProperties[0]['imported_automatically']."'
AND old.deleted = '".$currentExamProperties[0]['deleted']."'";
$result = $sync->mysql_query($sql);

/* Delete VENTUS_EXAM_TABLE entries if $result is found */
if (is_array($result) && !is_object($result)){
	/* if today's date is smaller than the defined PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE, then change the NOEs table */
	if(strtotime(date("Y-m-d H:i:s")) < strtotime(PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE)){
		echo "DELETE on ".date("Y-m-d H:i:s")."\n\n";
		var_dump($result);
		foreach ($result as $row){
			$faculty->deleteRequest($row['exam_request_id']);
		}
	}

	/* if today's date is greater than the defined PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE and also less than the end of exam date, then prepare CSV */
	if(strtotime(date("Y-m-d H:i:s")) > strtotime(PROFESSOR_NOE_SUBMISSION_BLACKOUT_STUDENT_RESPONSE_DEADLINE) && strtotime(date("Y-m-d H:i:s")) < strtotime($examPeriod["end"])){
		// array_push($csvContent, "Delete");
		// $csvContent = array_merge($csvContent, $result);
	}
}

echo "===================================== END ======================================\n\n";

if (!empty($csvContent) && is_array($csvContent) && !is_object($csvContent)){
	$csv = ",Session,Course Code,Course Section,Exam Date,Exam Duration,,,";

	foreach($csvContent as $row)
	{
		if (!is_array($row)){
			$title = $row;	
		}
		else{
			$csv .=	'
			"'.$title.'","';
			foreach($row as $key=>$v)
			{
				if($key === "session" || $key ==="course_code" || $key === "course_section" || $key === "exam_date" || $key === "exam_duration" )
				{
					$csv .= str_replace(array("\r\n","\n","\r"),'',$v).'","';
				}
			}
			$csv = substr($csv,0,-2);
		}
	}

	// delete old csv
	unlink(FOLDER_PATH.FILE_NAME.".".FILE_EXT);
	// create new csv
	$created = file_put_contents(FOLDER_PATH.FILE_NAME.".".FILE_EXT, $csv);

	/* once csv is created, then send an attatched email to Jean-Luc Daoust <jldaoust@uottawa.ca> */
	if ($created){
		require_once FS_PHP.'/swift/swift_required.php';
		$transport = Swift_SmtpTransport::newInstance(SMTP_SERVER, SMTP_SERVER_PORT);
		$mailer = Swift_Mailer::newInstance($transport);

		$html = "<p>Hello Exams team,</p>";
		$html .= "<p>This is an automated message to let you know that some final exams have changed, so please go ahead and modify the notice of examination if necessary.</p>";
		// Add troubleshooting footer
		$html .= "<hr><small>For internal use:" . base64_encode(" SENT " . date('Y-m-d H:i:s') . ' SCRIPT ' . $_SERVER['PHP_SELF']) . '</small>';

		$message = Swift_Message::newInstance('Ventus final exam changes') 
		->setFrom(array('ventus' . EMAIL_ORG_STAFF_DOMAIN => 'ventus' . EMAIL_ORG_STAFF_DOMAIN))
		->setTo(array(EMAIL_ALIAS_ACCESS_SERVICE_EXAMS . EMAIL_ORG_STAFF_DOMAIN => EMAIL_ALIAS_ACCESS_SERVICE_EXAMS . EMAIL_ORG_STAFF_DOMAIN))
		->setBcc(EMAIL_ALIAS_ACCESS_SERVICE_VENTUS_COMMUNICATION . EMAIL_ORG_STAFF_DOMAIN)
		->attach(Swift_Attachment::fromPath(FOLDER_PATH.FILE_NAME.".".FILE_EXT))
		->setBody($html, 'text/html', 'utf-8');

		$result = $mailer->send($message);
		if($result){
			echo "Email sent on ".date("Y-m-d H:i:s")."\n\n";
		}
	}
}
?>
