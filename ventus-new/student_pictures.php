<?php
// @author Kelvin Chan
// @date 2014-05-27
// @purpose queries to fetch students pictures data, and download them into ventus servrer
// @version 1.0
unset($sync);
$sync = new SyncObject('mssql');

if ($sync){

  for ($count = 0;$count<$numOfStudents;$count+=$count+=STUDENT_INCREMENT_SIZE){
    getPartialStudentData($count, $count+STUDENT_INCREMENT_SIZE);
  } 
  unset($numOfStudents);
}
else{
  exit("Something is wrong initializing SyncObject");
}

function getPartialStudentData($startID, $endID){
  $query = "SELECT LTRIM(RTRIM(img.Employee_Number)) AS Student_id, img.Photo, card.Expiry_Date
  FROM studcards.GA_IMAGE_IDS img
  INNER JOIN dbo.EPI_CARD card ON
  img.Employee_Number = card.Employee_Number
  WHERE img.Photo IS NOT NULL
  AND img.Image_Version=0
  AND img.Employee_Number >= '000".$startID."' 
  AND img.Employee_Number < '000".$endID."'";

  $sync->mssql_query($query);

  while($row = mssql_fetch_assoc($data)){
    // encrypt student number
   $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB), MCRYPT_RAND);
   $enc = mcrypt_encrypt(MCRYPT_BLOWFISH, hash('md5', HASH_GENERATION_RANDOM_STRING), $row['Student_id'], MCRYPT_MODE_ECB, $iv);
   $result = base64_encode($enc);
   // save picture to server
   $data = file_put_contents(FS_STUDENT_PICTURE.$result.'.jpg', $row['Photo']);
   printf("%d downloaded successfully!\n", $result);
 }
}