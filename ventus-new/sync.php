<?php
// @author Kelvin Chan
// @date 2014-05-09
// @purpose controller for the org data synchronization with DB2
// @version 1.4

require_once 'config.php';
require_once '/var/www/html/sass/sync/SyncObject.php';

Class Sync extends SyncObject{

    public $faculities;
    public $departments;
    public $programs;
    public $courses;
    public $courseClasses;
    public $teach_methods;
    public $students;
    public $maxStudentNum;
    public $numOfStudent;
    public $tableNames;
    public $numOfTables;
    public $start_session;
    public $end_session;

    public function __construct() {
        parent::__construct();
        $this->tableNames = array(FACULTIES_TABLE, DEPARTMENTS_TABLE, PROGRAMS_TABLE, COURSES_TABLE, COURSE_CLASSES_TABLE, STUDENTS_TABLE, STUDENT_COURSE_CLASSES_TABLE);
        $this->numOfTables = count($this->tableNames)-1;
    }

    public function setFiscalYearRange(){
        require_once FS_PHP.'/functions.php';
        $semester = VentusFunctions::fetchSemester();
        $current_year = substr($semester['now_short'], 0, 4);
        $start_year= $current_year-LOWER_CONTROL_LIMIT;
        $month = substr($semester['now_short'], -1);
        $start_session = $start_year.$month;

        $end_year = $current_year+UPPER_CONTROL_LIMIT;
        $end_session = $end_year.$month;

        $sql = "UPDATE ".DB2_CONTROL_TABLE."
        Set Start_tmst = current timestamp,
        End_tmst = current timestamp,
        Start_session_cd = '".$start_session."',       
        End_session_cd = '".$end_session."'";
        $this->db2_query($sql);
        printf("Control table updated to Start Session: ".$start_session." End Session: ".$end_session."\n");
    }

    public function executeSync(){
        // foreach ($this->tableNames as $name){
        $name = $this->tableNames[6];
             $start = microtime(TRUE); 
             $mem_start = memory_get_usage(TRUE);
             require $name.".php";
             printf("%s table is loaded on %s \n", ucfirst($name), date('Y-m-d H:i:s'));
             printf("%s table took %fs and consumed %fkb \n\n", ucfirst($name), microtime(TRUE)-$start, round((memory_get_usage(TRUE)-$mem_start)/1024.2));
        // }
    }

   public function generateTempTables(){
        $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[0]."_temp` (
              `faculty_id` int(11) NOT NULL AUTO_INCREMENT,
              `active` tinyint(1) NOT NULL,
              `code` varchar(255) NOT NULL,
              `stpaul` tinyint(1) NOT NULL,
              `faculty_eng` varchar(255) NOT NULL,
              `faculty_fra` varchar(255) NOT NULL,
              `last_updated` datetime NOT NULL,
              PRIMARY KEY (`faculty_id`),
              UNIQUE KEY `code_UNIQUE` (`code`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        
        $this->mysql_query($sql);
        printf("org_".$this->tableNames[0]."_temp is created successfully\n");

        $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[1]."_temp` (
              `department_id` int(11) NOT NULL AUTO_INCREMENT,
              `faculty_id` int(11) NOT NULL,
              `active` tinyint(1) NOT NULL,
              `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `department_eng` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `department_fra` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `last_updated` datetime NOT NULL,
              PRIMARY KEY (`department_id`),
              UNIQUE KEY `code_UNIQUE` (`code`),
              KEY `fk_org_".$this->tableNames[1]."_org_".$this->tableNames[0]."_idx_".date("Y-m-d H:i")."` (`faculty_id`),
              CONSTRAINT `fk_org_".$this->tableNames[1]."_org_".$this->tableNames[0]."_".date("Y-m-d H:i")."` FOREIGN KEY (`faculty_id`) REFERENCES `org_".$this->tableNames[0]."_temp` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        

        $this->mysql_query($sql);
        printf("org_".$this->tableNames[1]."_temp is created successfully\n");

        $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[2]."_temp` (
              `program_id` int(11) NOT NULL AUTO_INCREMENT,
              `department_id` int(11) NOT NULL DEFAULT 0,
              `active` tinyint(1) NOT NULL,
              `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `program_eng` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `program_fra` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `last_updated` datetime NOT NULL,
              PRIMARY KEY (`program_id`),
              UNIQUE KEY `code_UNIQUE` (`code`),
              KEY `fk_org_".$this->tableNames[2]."_org_".$this->tableNames[1]."_idx_".date("Y-m-d H:i")."` (`department_id`),
              CONSTRAINT `fk_org_".$this->tableNames[2]."_org_".$this->tableNames[1]."_".date("Y-m-d H:i")."` FOREIGN KEY (`department_id`) REFERENCES `org_".$this->tableNames[1]."_temp` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->mysql_query($sql);
        printf("org_".$this->tableNames[2]."_temp is created successfully\n");

        $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[3]."_temp` (
              `course_id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `session` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
              `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
              `section` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
              `last_updated` datetime NOT NULL,
              PRIMARY KEY (`course_id`),
              UNIQUE KEY `course_unique` (`session`,`code`,`section`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->mysql_query($sql);
        printf("org_".$this->tableNames[3]."_temp is created successfully\n");

        $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[4]."_temp` (
              `class_id` int(11) NOT NULL AUTO_INCREMENT,
              `course_id` int(11) NOT NULL,
              `teaching_method` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `teaching_method_meet` tinyint(2) NOT NULL,
              `professor_first_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `professor_last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `professor_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `start_date` date NOT NULL,
              `end_date` date NOT NULL,
              `start_time` time NOT NULL,
              `end_time` time NOT NULL,
              `building_code` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
              `room_number` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
              `day_of_week` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
              `stpaul` tinyint(1) NOT NULL,
              `last_updated` datetime NOT NULL,
              PRIMARY KEY (`class_id`),
              -- UNIQUE KEY  `class_unique` (`course_id`,`teaching_method`,`teaching_method_meet`,`professor_email`),
              KEY `fk_org_".$this->tableNames[4]."_org_".$this->tableNames[3]."_idx_".date("Y-m-d H:i")."` (`course_id`),
              CONSTRAINT `fk_org_".$this->tableNames[4]."_org_".$this->tableNames[3]."_".date("Y-m-d H:i")."` FOREIGN KEY (`course_id`) REFERENCES `org_".$this->tableNames[3]."_temp` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->mysql_query($sql);
        printf("org_".$this->tableNames[4]."_temp is created successfully\n");

         $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[5]."_temp` (
              `student_id` int(11) NOT NULL DEFAULT 0,
              `faculty_id` int(11) NOT NULL DEFAULT 0,
              `program_id` int(11) NOT NULL DEFAULT 0,
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
              KEY `fk_org_".$this->tableNames[5]."_org_".$this->tableNames[0]."_idx".date("Y-m-d H:i")."` (`faculty_id`),
              KEY `fk_org_".$this->tableNames[5]."_org_".$this->tableNames[2]."_idx".date("Y-m-d H:i")."` (`program_id`),
              CONSTRAINT `fk_org_".$this->tableNames[5]."_org_".$this->tableNames[0]."_".date("Y-m-d H:i")."` FOREIGN KEY (`faculty_id`) REFERENCES `org_".$this->tableNames[0]."_temp` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_org_".$this->tableNames[5]."_org_".$this->tableNames[2]."_".date("Y-m-d H:i")."` FOREIGN KEY (`program_id`) REFERENCES `org_".$this->tableNames[2]."_temp` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $this->mysql_query($sql);
        printf("org_".$this->tableNames[5]."_temp is created successfully\n");

        $sql = "CREATE TABLE IF NOT EXISTS `org_".$this->tableNames[6]."_temp` (
            `student_id` int(11) NOT NULL,
            `class_id` int(11) NOT NULL,
            `last_updated` datetime NOT NULL,
            KEY `fk_org_".$this->tableNames[6].$this->tableNames[5]."_idx_".date("Y-m-d H:i")."` (`student_id`),
            KEY `fk_org_".$this->tableNames[6].$this->tableNames[4]."_idx_".date("Y-m-d H:i")."` (`class_id`),
            CONSTRAINT `fk_org_".$this->tableNames[6].$this->tableNames[5]."_".date("Y-m-d H:i")."` FOREIGN KEY (`student_id`) REFERENCES `org_".$this->tableNames[5]."_temp` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_org_".$this->tableNames[6].$this->tableNames[4]."_".date("Y-m-d H:i")."` FOREIGN KEY (`class_id`) REFERENCES `org_".$this->tableNames[4]."_temp` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

        $this->mysql_query($sql);
        printf("org_".$this->tableNames[6]."_temp is created successfully\n");
    }   

    public function removeBackupTables(){
         for($i=$this->numOfTables;$i>=0;--$i){
            $sql = "DROP TABLE IF EXISTS `org_".$this->tableNames[$i]."_".date('Y-m',strtotime('-1 month'))."`";
            $this->mysql_query($sql);
            $sql = "DROP TABLE IF EXISTS `org_".$this->tableNames[$i]."_".date('Y-m')."`";
            $this->mysql_query($sql);
        }
    }     

    public function backupTables(){
        for($i=$this->numOfTables;$i>=0;--$i){
            $sql = "RENAME TABLE `org_".$this->tableNames[$i]."` TO `org_".$this->tableNames[$i]."_".date('Y-m')."`";
        }
    }

    public function removeTempTables(){
        for($i=$this->numOfTables;$i>=0;--$i){
            $sql ="DROP TABLE IF EXISTS `org_".$this->tableNames[$i]."_temp`";
            $this->mysql_query($sql);
            printf("org_".$this->tableNames[$i]."_temp is removed successfully\n");
        }
   }   

    public function renameTemp(){
        for($i=$this->numOfTables;$i>=0;--$i){
            $sql = "RENAME TABLE `org_".$this->tableNames[$i]."_temp` TO `org_".$this->tableNames[$i]."`";
            $this->mysql_query($sql);
            printf("%s table is renamed on %s\n\n", $this->tableNames[$i], date('Y-m-d H:i:s'));
        }
    }
}

