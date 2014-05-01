<?php
// @author Kelvin Chan
// @date 2014-01-09
// @purpose various abstract functions for models to interact with the database
error_reporting( error_reporting() & ~E_NOTICE );
require_once ('config.php');

/**
*	Constant Definition
**/
define('default_bulk_size', 10000);
define('rolling_insert_bulk_size', 100000);

class SyncObject{
	private $db2; // db2 connection object
	private $mysql; // mysql connection object

	public function __construct($mysql_connection_type=NULL) {
		switch ($mysql_connection_type) {
			case 'pdo':
			try {
				$this->mysql = new PDO("mysql:host=".mysql_host.";port=".mysql_port.";dbname=".mysql_dbname."", mysql_username, mysql_passwrod);
			} catch (PDOException $e) {
				exit("pdo connect failed: ".$e->getMessage()."\n=================================== END ================================\n\n");
			}
			break;
			default:
			$this->mysql = new mysqli(mysql_host, mysql_username, mysql_passwrod, mysql_dbname);
			if ($this->mysql->connect_errno) {
				exit("mysqli connect failed: ".$this->mysql->connect_error."\n=================================== END ================================\n\n");
			}
			if(!isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] !== '--cron'){
				$this->mysql->set_charset('utf8');
			}
			break;
		}
		$connection_string = 
		"DATABASE=" . db2_dbname . ";" .
		"HOSTNAME=" . db2_host . ";" .
		"PORT=" . db2_port . ";" .
		"PROTOCOL=TCPIP;" .
		"UID=" . db2_username . ";" .
		"PWD=" . db2_passwrod . ";";
		if (!($this->db2 = db2_connect($connection_string, '', ''))){
			exit("db2 connect failed: ".db2_conn_errormsg()."\n=================================== END ================================\n\n");
		} 
	}


	public function db2_query($sql) { 
		if($result = db2_exec($this->db2, $sql)){
			$data = array();
			if($result && is_resource($result)) {
				while($row = db2_fetch_assoc($result)){
					$data[] = $this->trim($row);
				}
			}
			return $data;
		}else{
			exit("DB2 Query failed: ".db2_stmt_errormsg($this->db2)."\n=================================== END ================================\n\n");
		}
	}

	private function trim(&$data) {
		if(is_array($data)) {
			foreach($data as $key=>$value){
				$data[$key] = $this->trim($value);
			}
			return $data;
		} else {
			return trim($this->mysql->real_escape_string($data));
		}

	}  

	public function mysql_insert($data, $query, $bulk_size=default_bulk_size) {
		if ($this->mysql instanceof PDO) {
			return;
		}
		else if ($this->mysql->ping()) {
			/**
			*	Initialize some variables.
			*/
			$return = FALSE;
			$sql = NULL;
			$inner_count = 0;
			$key=0;
			/**
			*	Initialize an ArrayIterator object
			*/
			$iterator = new ArrayIterator($data);
			
			while ($iterator->valid()){
				/**
				*	Break down an large array into $bulk_size
				*/
				while($inner_count < $bulk_size){
					if ($iterator->current()){
						$sql .= "('" . implode("','", $iterator->current()) . "','" . date("Y-m-d H:i:s") . "'),";
					}
					$iterator->next(); // increment iterator
					++$inner_count;	// increment counter to check if it reaches $bulk_size
				}
				/**
				*	clean up date, and prepare to insert into database
				*/
				$sql = substr($sql, 0, -1);
				$sql_query = str_replace("{DATA}", $sql, $query);
				if(defined('DEBUG')) var_dump($sql_query);
				if ($result = $this->mysql->query($sql_query, MYSQLI_USE_RESULT)){
					unset($sql, $sql_query, $inner_count);
					$return = TRUE;
				}
				else{
					exit("MySQL query failed: ".$this->mysql->error." , execution terminated.\n=================================== END ================================\n\n");
				}
			}

		} else {
			printf("MySQL Ping failed: %s,\n trying to ping the mysql server...\n", $this->mysql->error);
			if($this->mysql->ping()){
				$return = TRUE;	
			}else{
				exit("Re-ping mysql server failed, execution terminated.\n=================================== END ================================\n\n");	
			}
		}
		return $return;
	}

	public function mysql_query($query){
		if ($this->mysql->ping()){
			if ($result = $this->mysql->query($query)){
				if($result && $result->num_rows > 0) {	
					$data = array();
					while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
						$data[] = $row;
					}
					$result->close();
					return $data;
				}else{
					return $result;
				}
			}else{
				exit("MySQL query failed: ".$this->mysql->error." , execution terminated.\n=================================== END ================================\n\n");
			} 
		}else{
			printf("MySQL Ping failed: %s,\n trying to ping the mysql server...\n", $this->mysql->error);
			if($this->mysql->ping()){
				return TRUE;	
			}else{
				exit("Re-ping mysql server failed, execution terminated.\n=================================== END ================================\n\n");
			}
		}
	}

	public function join_results(&$result_left, &$result_right, $left, $right, $value, $unset=TRUE) {
		$lookup = array();
		foreach($result_right as $row){
			$lookup[$row[$right]] = $row[$value];
		}
		foreach($result_left as $key=>$left_row) {
			if(isset($lookup[$left_row[$left]])){
				$result_left[$key][$left] = $lookup[$left_row[$left]]; 
			}
			else {
				if($unset){
					unset($result_left[$key]);
				}
			}
		}
		return $result_left;
	}

	public function db2_query_rolling_insert($sql, $mysql_template, $func, $bulk_size=rolling_insert_bulk_size) { 
		$return = FALSE;
		$link = $this->db2;
		$result = db2_exec($link, $sql)
		or die(db2_stmt_errormsg($link) . "\n\n" . $sql);
		$data = array();
		if($result && is_resource($result)) {
			while($row = db2_fetch_assoc($result)) {
				$data[] = $row;
				if(count($data) == $bulk_size) {
					$data = $func($data);
					if($return  = $this->mysql_insert($data, $mysql_template, $bulk_size)){
						$data = array(); 
					}else{
						exit("Function mysql_insert failed, execution terminated.\n=================================== END ================================\n\n");
					}
				}
			}
			$data = $func($data);
			if($return  = $this->mysql_insert($data, $mysql_template, $bulk_size)){
				return $return;
			}else{
				exit("Function mysql_insert failed, execution terminated.\n=================================== END ================================\n\n");
			}
		}
	}

	public function close_connection(){
		return $this->mysql->close();
	}
}