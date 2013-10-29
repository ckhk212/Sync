<?php
require 'SyncObject.php';
$sync = new SyncObject();
define ("EXIST_EXAM_TABLE", "ventus_professor_exam_requests"); /* existing exam table name */
define ("EXAM_ID", "exam_request_id"); /* existing exam table id */

$table_names = array("TEST_ventus_professor_exam_requests");
// $table_names = array("faculties", "departments", "programs", "courses",  "students", "student-courses");
// $table_names = array("student_courses");

// foreach ($table_names as $name){
// 	// $sync = new SyncObject();
// 	$start = microtime(TRUE); 
// 	$mem_start = memory_get_usage(TRUE);
// 	require $name.".php";
// 	printf("%s table is loaded on %s \n", ucfirst($name), date('Y-m-d H:i:s'));
// 	printf("%s table took %fs and consumed %fkb \n\n", ucfirst($name), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
// 	// $sync->close_connection();
// 	unset($start, $mem_start);
// }
// echo "=================================== PARTIAL END ================================\n\n";

/**
*	at this point, all org_*_temp tables are synced properly.
*/

foreach ($table_names as $name){

	/* Query to find entries that are not exisit in ventus table */
	$sql="SELECT * FROM `TEST_ventus_professor_exam_requests` new WHERE NOT EXISTS (SELECT * FROM ventus_professor_exam_requests old WHERE 
		new.session = old.session AND
		new.course_code = old.course_code AND
		new.course_section = old.course_section AND
		new.exam_type = old.exam_type)";
$result = $sync->mysql_query($sql);
printf("insertion");
var_dump($result);

/* for update only */
$sql = "SELECT new.* FROM TEST_ventus_professor_exam_requests new
LEFT JOIN ventus_professor_exam_requests old
ON 
new.session = old.session AND
new.course_code = old.course_code AND
new.course_section = old.course_section AND
new.exam_type = old.exam_type AND
new.imported_automatically = old.imported_automatically
WHERE 
new.exam_date != old.exam_date 
OR new.exam_duration != old.exam_duration";

$result = $sync->mysql_query($sql);
printf("update");
var_dump($result);

/* for delete only */
$sql = "SELECT * FROM `ventus_professor_exam_requests` old WHERE NOT EXISTS (SELECT * FROM TEST_ventus_professor_exam_requests new WHERE 
	new.session = old.session AND
	new.course_code = old.course_code AND
	new.course_section = old.course_section AND
	new.exam_type = old.exam_type) AND old.session='20139' AND exam_type='final' AND imported_automatically=1";

$result = $sync->mysql_query($sql);
printf("delete");
var_dump($result);
exit(1);

	/**
	*	create backup tables
	*/
	$sql = "RENAME TABLE `".$name."` TO `".$name."_".date('Y-m')."`";

	/**
	*	check if backup create successfully, else revert changes.
	*/
	if($sync->mysql_query($sql)){
		printf("%s table is backup on %s\n", $name, date('Y-m-d H:i:s'));
		$sql = "RENAME TABLE `".$name."_temp` TO `".$name."`";
		$sync->mysql_query($sql);
		printf("%s table is renamed on %s\n\n", $name, date('Y-m-d H:i:s'));
	}else{
		printf("%s table did not backup porperly on %s\n", $name, date('Y-m-d H:i:s'));
		$sql = "RENAME TABLE `".$name."_".date('Y-m')."` TO `".$name."`";
		$sync->mysql_query($sql);
	}
}

echo "===================================== END ======================================\n\n";

/*
* send out emails when there are changes on the final exam tables
*/
function updateRequestDetails($id, $data){
        //Before we make the update, let's fetch the existing information for this request so we can do a difference later
	$sql = "SELECT exam_type, exam_date, exam_duration, course_code, course_section, session FROM ".EXIST_EXAM_TABLE." WHERE ".EXAM_ID." = ".$id."";
	$data_before_update = $sync->mysql_query($data);

        //Before we make an update and send an email, we should check if there was an update at all
	if ($data_before_update['course_code'] != $data['course_code'] ||
		$data_before_update['course_section'] != $data['course_section'] ||
		$data_before_update['session'] != $data['session'] ||
		$data_before_update['exam_type'] != $data['exam_type'] ||
		$data_before_update['exam_date'] != $data['exam_date'] ||
		$data_before_update['exam_duration'] != $data['exam_duration']){


            //If there is any update made by the faculties, we want the exams team to be informed about this
		require_once FS_PHP.'/swift/swift_required.php';
	$transport = Swift_SmtpTransport::newInstance(SMTP_SERVER, SMTP_SERVER_PORT);
	$mailer = Swift_Mailer::newInstance($transport);

	$html = "<p>Hello Exams team,</p>";
	$html .= "<p>This is an automated message to let you know that a faculty member has made a change to a notice of examination.</p>";

	$html .= "<p><u>Old data for this request:</u></p>";
	$html .= "<p>Course: ".$data_before_update[0]['course_code']." ".$data_before_update[0]['course_section']."<br>";
	$html .= "Session: ".$data_before_update[0]['session']."<br>";
	$html .= "Type: ".$data_before_update[0]['exam_type']."<br>";
	$html .= "Exam: ".$data_before_update[0]['exam_date']." (".$data_before_update[0]['exam_duration']." minutes)</br>";
	$html .= "Is alternate? ".$data_before_update[0]['exam_alternate_special']." (Student: ".($data_before_update[0]['exam_alternate_special_student'] == "" ? 'n/a' : $data_before_update[0]['exam_alternate_special_student']).")</p>";

	$html .= "<p><u>New data for this request:</u></p>";
	$html .= "<p>Course: ".$data['course_code']." ".$data['course_section']."<br>";
	$html .= "Session: ".$data['session']."<br>";
	$html .= "Type: ".$data['exam_type']."<br>";
	$html .= "Exam: ".$data['exam_date']." (".$data['exam_duration']." minutes)</br>";
	$html .= "Is alternate? ".$data['exam_alternate_special']." (Student: ".($data['exam_alternate_special_student'] == "" ? 'n/a' : $data['exam_alternate_special_student']).")</p>";

	$message = Swift_Message::newInstance('Ventus NOE update') 
	->setFrom(array('ventus' . EMAIL_ORG_STAFF_DOMAIN => 'ventus' . EMAIL_ORG_STAFF_DOMAIN))
	->setTo(array(EMAIL_ALIAS_ACCESS_SERVICE_EXAMS . EMAIL_ORG_STAFF_DOMAIN => EMAIL_ALIAS_ACCESS_SERVICE_EXAMS . EMAIL_ORG_STAFF_DOMAIN))
	->setBody($html, 'text/html', 'utf-8');

	$mailer->send($message);
}
}

?>
