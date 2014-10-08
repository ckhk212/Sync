<?php
// @author Kelvin Chan
// @date 2014-10-07
// @purpose various abstract functions for models to interact with the database
// @version 1.3

namespace Sync;

class SyncObject{
	private $db2; // db2 connection object
	private $mysql; // mysql connection object
	private $mssql; // mssql connection object

	public function __construct($mysql_connection_type=NULL) {
		switch ($mysql_connection_type) {
			case 'pdo':
			try {
				$this->mysql = new \PDO("mysql:host=".mysql_host.";port=".mysql_port.";dbname=".mysql_dbname."", mysql_username, mysql_passwrod);
			} catch (PDOException $e) {
				exit("pdo connect failed: ".$e->getMessage()."\n=================================== END ================================\n\n");
			}
			break;
			case 'student-pictures':
			$this->mssql = mssql_connect(sp_mssql_host.':'.sp_mssql_port, sp_mssql_username, sp_mssql_passwrod);
			if (!$this->mssql){
				exit("MS-SQL connection failed: \n=================================== END ================================\n\n");
			}
			break;
			case 'student-cards':
			$this->mssql = mssql_connect(sc_mssql_host.':'.sc_mssql_port, sc_mssql_username, sc_mssql_passwrod);
			if (!$this->mssql){
				exit("MS-SQL connection failed: \n=================================== END ================================\n\n");
			}
			// mssql_select_db(sc_mssql_db, $this->mssql);
			break;
			default:
			$this->mysql = new \mysqli(mysql_host, mysql_username, mysql_passwrod, mysql_dbname);
			if ($this->mysql->connect_errno) {
				exit("mysqli connect failed: ".$this->mysql->connect_error."\n=================================== END ================================\n\n");
			}
			$this->mysql->set_charset('utf8');
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
			if($result && is_resource($result) && db2_num_fields($result) != 0) {
				while($row = db2_fetch_assoc($result)){
					$data[] = $this->trim($row);
				}
				return $data;
			}else{
				echo("Update Successfully!\n");
				return $result;
			}
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

	public function mysql_insert($data, $query, $bulk_size=DEFAULT_BULK_SIZE) {
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
			$iterator = new \ArrayIterator($data);
			
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
					var_dump($sql_query);
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

	public function mssql_query($query){
		return mssql_query($query);
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
					// array_push($undefined, $result_left[$key]);
					// printf("Cannnot be foound %s\n", print_r($result_left[$key]));
				}else{
					$result_left[$key][$left] = 0;
				}
			}
		}
		return $result_left;
	}

	public function close_connection(){
		return $this->mysql->close();
	}
}