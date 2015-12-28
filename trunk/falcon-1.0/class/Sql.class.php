<?php
/**
 * MySQL 데이터베이스를 다룬다.
 * 
 * @version 1.0
 * @license GNU Lesser GPL
 * @copyright cosmosfarm (iam@cosmosfarm.com)
 * @link http://www.cosmosfarm.com
 * @link http://chan2rrj.blog.me
 */
class Sql {
	
	private $url = 'localhost';
	private $id = 'id';
	private $pw = 'password';
	private $dbName = 'db_name';
	
	private $db;
	private $query;
	private $result;
	private $queryArray = array();
	private $questionMarkArray = array();
	
	/**
	 * SQL을 다룬다.
	 * @param String $query
	 */
	function __construct($query=''){
		$this->db = @new mysqli($this->url, $this->id, $this->pw, $this->dbName);
		$this->checkError();
		if($query) $this->setQuery($query);
	}
	
	/**
	 * 쿼리문을 입력한다.
	 * @param String $query
	 */
	function setQuery($query){
		unset($this->result);
		$this->query = $query;
		$this->queryArray = explode("?", $query);
	}
	
	/**
	 * 지정된 위치에 정수를 넣는다. (index 1은 첫 번째)
	 * @param Integer $index
	 * @param Integer $int
	 */
	function setInt($index, $int){
		if($index <= 0) die("Sql->setInt() :: 인덱스는 0 보다 작거나 같을 수 없습니다.");
		elseif($index >= count($this->queryArray)) die("Sql->setInt() :: 인덱스가 범위를 넘어 갔습니다.");
		$this->questionMarkArray[$index-1] = intval($int);
	}
	
	/**
	 * 지정된 위치에 문자열을 넣는다. (index 1은 첫 번째)
	 * @param Integer $index
	 * @param String $str
	 */
	function setStr($index, $str){
		if($index <= 0) die("Sql->setInt() :: 인덱스는 0 보다 작거나 같을 수 없습니다.");
		elseif($index >= count($this->queryArray)) die("Sql->setInt() :: 인덱스가 범위를 넘어 갔습니다.");
		$this->questionMarkArray[$index-1] = "'".addslashes(trim($str))."'";
	}
	
	/**
	 * 지정된 위치에 패스워드를 넣는다. (index 1은 첫 번째)
	 * @param Integer $index
	 * @param String $pass
	 */
	function setPassword($index, $pass){
		if($index <= 0) die("Sql->setInt() :: 인덱스는 0 보다 작거나 같을 수 없습니다.");
		elseif($index >= count($this->queryArray)) die("Sql->setInt() :: 인덱스가 범위를 넘어 갔습니다.");
		$this->questionMarkArray[$index-1] = "password('".addslashes(trim($str))."')";
	}
	
	/**
	 * 쿼리를 실행한다.
	 * @param Boolean $debug
	 */
	function getQuery($debug=false){
		if(!$this->query) die("Sql->getQuery() :: 쿼리문이 없습니다.");
		$query = $this->getQueryString();
		
		// 디버그를 위해 쿼리문을 화면에 출력한다.
		if($debug) echo $query."<br>";
		
		$this->result = @$this->db->query($query);
		$this->checkError($query);
		return $this->result;
	}
	
	/**
	 * fetch_row()를 실행하고 반환한다.
	 */
	function getRow(){
		if(!$this->result) die("Sql->getRow() :: 쿼리 실행 결과가 없습니다. Sql->getQuery()를 먼저 요청하세요");
		return $this->result->fetch_row();
	}
	
	/**
	 * fetch_assoc()를 실행하고 반환한다.
	 */
	function getArray(){
		if(!$this->result) die("Sql->getRow() :: 쿼리 실행 결과가 없습니다. Sql->getQuery()를 먼저 요청하세요");
		return $this->result->fetch_assoc();
	}
	
	/**
	 * 쿼리의 결과수를 반환한다.
	 */
	function getCountRows(){
		if(!$this->result) die("Sql->getCountRows() :: 쿼리 실행 결과가 없습니다. Sql->getQuery()를 먼저 요청하세요");
		return $this->result->num_rows;
	}
	
	/**
	 * 최근 작업으로 변경된 행 개수를 반환한다.
	 */
	function getAffectedRows(){
		if(!$this->result) die("Sql->getAffectedRows() :: 쿼리 실행 결과가 없습니다. Sql->getQuery()를 먼저 요청하세요");
		return $this->db->affected_rows;
	}
	
	/**
	 * 마지막에 실행된 auto_increment 값을 가져온다.
	 */
	function getInsertID(){
		return $this->db->insert_id;
	}
	
	/**
	 * 쿼리문을 만든다.
	 */
	function getQueryString(){
		if(count($this->queryArray) > 1){
			$query = $this->queryArray[0];
			for($i=1; $i<count($this->queryArray); $i++){
				if(is_null($this->questionMarkArray[$i-1])) die("Sql->getQueryString() :: 모든 Question Mark에 값을 넣어 주세요 :: $query");
				$query .= $this->questionMarkArray[$i-1].$this->queryArray[$i];
			}
		}
		else{
			$query = $this->query;
		}
		
		return $query;
	}
	
	/**
	 * 자동으로 INSERT 쿼리문을 만들어 반환한다.
	 * @param String $table
	 * @param Array $columns
	 */
	function buildInsertQuery($table, $columns){
		$query = "INSERT INTO $table (";
		$questionMarks = "";
		
		for($i=0; $i<count($columns); $i++){
			$query .= "`$columns[$i]`";
			$questionMarks .= "?";
			
			if($i<count($columns)-1){
				$query .= ",";
				$questionMarks .= ",";
			}
		}
		
		$query .= ") VALUE ($questionMarks)";
		
		return $query;
	}
	
	/**
	 * 자동으로 UPDATE 쿼리문을 만들어 반환한다.
	 * @param String $table
	 * @param Array $columns
	 * @param String $where
	 */
	function buildUpdateQuery($table, $columns, $where){
		$query = "UPDATE $table SET ";
		
		for($i=0; $i<count($columns); $i++){
			$query .= "`$columns[$i]`" . "=?";
			
			if($i<count($columns)-1) $query .= ",";
		}
		
		$query .= " WHERE $where";
		
		return $query;
	}
	
	/**
	 * DB 연결 과정의 오류를 확인한다.
	 * @param String $msg
	 */
	private function checkError($msg=""){
		if(mysqli_connect_errno()){
		    printf("MySQL 오류: %s (%s)\n", mysqli_connect_error(), $msg);
		    exit();
		}
	}
	
	/**
	 * DB와 연결을 종료한다.
	 */
	function close(){
		$this->db->close();
	}
}
?>