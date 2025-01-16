<?php
	class db {
		private $hostname = "";
		private $database = "";
		private $username = "";
		private $password = "";
		private $dbprefix = "";
		private $char_set = "";
		public $PDO;
		
		public function __construct($settings){
			try {
				$options = array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
					PDO::ATTR_EMULATE_PREPARES => false
				);
				
				foreach($settings as $key=>$val) {
					$this->$key = $val;
				}
				
				$this->PDO = new PDO(
					"mysql:host=" . $this->hostname . ";dbname=" . $this->database . ";charset=" . $this->char_set, 
					$this->username, 
					$this->password, 
					$options
				);
				$this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch (PDOException $e) {
				echo $e->getMessage();
			}
		}
		
		public function query($sql){
			$sql = trim($sql);
			
			if(strpos(strtoupper($sql), "SELECT") === 0)
				return new query($this->PDO->query($sql));
			elseif(strpos(strtoupper($sql), "INSERT") === 0) {
				$this->exec($sql);
				return $this->PDO->lastInsertId();
			}
			else
				return $this->exec($sql);
		}
		
		public function exec($sql){
			return $this->PDO->exec($sql);
		}
		
		public function prepare($sql){
			$this->PDO->prepare($sql);
		}
		
		public function execute($params){
			$this->PDO->execute($params);
		}
		
		public function found_rows(){
			return $this->PDO->query('SELECT FOUND_ROWS()')->fetchColumn();
		}
		
		public function select($table, $where=FALSE, $order=FALSE, $limit=0, $offset=0, $calc_found_rows=FALSE){
			$limit = (int)$limit;
			$offset = (int)$offset;
			
			return $this->query("SELECT" . (($calc_found_rows) ? " SQL_CALC_FOUND_ROWS" : "") . " * FROM `" . $this->dbprefix . $table . "`" . (($where) ? " WHERE " . $where : "") . (($order) ? " ORDER BY " . $order : "") . (($limit) ? " LIMIT " . $offset . ", " . $limit : ""));
		}
		
		/* public function insert($table, $data){
			foreach($data as $key=>$value){
				$keys[] = "`" . $key . "`";
				$values[] = "'" . addslashes($value) . "'";
			}
			
			$this->exec("INSERT INTO `" . $this->dbprefix . $table . "` (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ")");
			
			return $this->PDO->lastInsertId();
		} */
		public function insert($table, $data) {
			$keys = array_keys($data);
			$columns = implode(", ", array_map(fn($key) => "`$key`", $keys));
			$placeholders = implode(", ", array_fill(0, count($data), '?'));

			$sql = "INSERT INTO `" . $this->dbprefix . $table . "` ($columns) VALUES ($placeholders)";

			$stmt = $this->PDO->prepare($sql);
			$stmt->execute(array_values($data));

			return $this->PDO->lastInsertId();
		}

		public function update($table, $data, $where=FALSE, $limit=FALSE){
			$sql = "UPDATE `" . $this->dbprefix . $table . "` SET ";
			
			$params = array();
			foreach($data as $key=>$value)
				$params[] = "`" . $key . "` = '" . addslashes($value) . "'";
			
			$sql .= implode(", ", $params);
			
			$sql .= $where ? " WHERE " . $where : "";
			
			$sql .= $limit ? " LIMIT " . $limit : "";
			
			return $this->exec($sql);
		}

		public function delete($table, $where=FALSE){
			return $this->exec("DELETE FROM `" . $this->dbprefix . $table . "`" . (($where) ? " WHERE " . $where : ""));
		}
		
		public function __destruct(){
			$this->PDO = null;
		}
	}
	
	class query{
		private $_result;

		public function __construct($result){
			$this->_result = $result;
		}
		
		/*
			PDO::FETCH_NUM, 
			PDO::FETCH_ASSOC, 
			PDO::FETCH_COLUMN - массив с одного поля, 
			PDO::FETCH_KEY_PAIR - первой в колонкой надо обязательно выбирать уникальное поле, 
			PDO::FETCH_UNIQUE - индексированные уникальным полем
		*/
		public function row($mode=PDO::FETCH_OBJ){
			return $this->_result->fetch($mode);
		}
		
		public function rows($mode=PDO::FETCH_OBJ){
			return $this->_result->fetchAll($mode);
		}
	}
?>