<?php
/**
 * SQL 클래스에서 넘겨진 결과값을 처리한다.
 * 
 * @version 1.0
 * @license GNU Lesser GPL
 * @copyright cosmosfarm (iam@cosmosfarm.com)
 * @link http://www.cosmosfarm.com
 * @link http://chan2rrj.blog.me
 */
class SqlResult {
	
	private $result;
	
	/**
	 * SQL 클래스에서 넘겨진 결과값을 처리한다.
	 * @param Array $result
	 */
	function __construct($result=""){
		if($result) $this->setResult($result);
	}
	
	/**
	 * Sql 결과를 입력받는다.
	 * @param Array $result
	 */
	function setResult($result){
		$this->result = $result;
		return $result;
	}
	
	/**
	 * 해당 키의 정수형 값을 반환한다.
	 * @param String $key
	 */
	function getInt($key){
		return intval($this->result[$key]);
	}
	
	/**
	 * 해당 키의 문자형 값을 반환한다.
	 * @param String $key
	 */
	function getStr($key){
		return stripslashes($this->result[$key]);
	}
	
	/**
	 * 해당 키의 날짜 값을 입력한 포맷으로 반환한다.
	 * @param String $key
	 * @param String $format
	 */
	function getDate($key, $format="Y-m-d"){
		return $this->result[$key] ? date("$format", $this->result[$key]) : "";
	}
	
	/**
	 * 본문을 반환한다.
	 * @param String $key
	 */
	function getContent($key){
		return nl2br($this->getStr($key));
	}
}
?>