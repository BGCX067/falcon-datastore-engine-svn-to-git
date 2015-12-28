<?php
require_once "Sql.class.php";
require_once "SqlResult.class.php";

/**
 * Falcon Datastore Engine
 * 
 * "팔콘 데이터저장소 엔진"은 MySQL을 기반으로 하는 NoSQL 엔진 입니다.
 * 모든 입력, 출력이 JSON 문서로 되어 있어 웹 애플리케이션, 스마트폰 애플케이션 개발에 용의 합니다.
 * 이 엔진은 소규모 애플리케이션에 적합 합니다.
 * 
 * @version 1.0
 * @license GNU Lesser GPL
 * @copyright cosmosfarm (iam@cosmosfarm.com)
 * @link http://www.cosmosfarm.com
 * @link http://chan2rrj.blog.me
 */
class Falcon {
	
	// 사용할 데이터저장소 id
	private $datastoreID;
	
	// DB 테이블 명
	private $datastoreTable = "falcon_datastore";
	private $documentTable = "falcon_document";
	private $datamapTable = "falcon_datamap";
	
	// 페이지에 보여질 결과의 수
	private $rpp = 10;
	// 페이지 번호 (시작은 1부터)
	private $page = 1;
	// 결과 정렬
	private $sort = "DESC";
	
	/**
	 * 오름차순 (Ascending)
	 * @var Integer
	 * @return 1
	 */
	var $ASC = 1;
	
	/**
	 * 내림차순 (Descending)
	 * @var Integer
	 * @return -1
	 */
	var $DESC = -1;
	
	/**
	 * 데이터저장소에 연결한다.
	 * @param String $uuid
	 */
	function connectDatastore($uuid=""){
		if(!$uuid) die("Falcon->connectDatastore() :: 데이터저장소의 UUID 정보가 없습니다.");
		
		$sql = new Sql("SELECT id FROM $this->datastoreTable WHERE uuid=?");
		$sql->setStr(1, $uuid);
		$sql->getQuery();
		$result = new SqlResult($sql->getRow());
		$this->datastoreID = $result->getInt(0);
		if($this->datastoreID <= 0) return false;//die("Falcon->connectDatastore() :: 데이터저장소가 존제하지 않습니다.");
		return true;
	}
	
	/**
	 * 데이터저장소를 만든다.
	 * @param String $owner
	 * @param String $description
	 */
	function createDatastore($owner, $description){
		if(!$owner) die("Falcon->createDatastore() :: 데이터저장소의 소유자 정보가 없습니다.");
		elseif(!$description) die("Falcon->createDatastore() :: 데이터저장소의 설명 정보가 없습니다.");
		
		$uuid = $this->getUUID();
		$sql = new Sql("INSERT INTO $this->datastoreTable (uuid, owner, description) VALUE (?,?,?)");
		$sql->setStr(1, $uuid);
		$sql->setStr(2, $owner);
		$sql->setStr(3, $description);
		
		if($sql->getQuery()) return $uuid;
		else return false;
	}
	
	/**
	 * 데이터저장소를 삭제한다.
	 * @param String $uuid
	 * @param String $owner
	 */
	function removeDatastore($uuid, $owner){
		if(!$uuid) die("Falcon->removeDatastore() :: 데이터저장소의 UUID 정보가 없습니다.");
		elseif(!$owner) die("Falcon->removeDatastore() :: 데이터저장소의 소유자 정보가 없습니다.");
		
		$sql = new Sql("SELECT id FROM $this->datastoreTable WHERE uuid=? AND owner=?");
		$sql->setStr(1, $uuid);
		$sql->setStr(2, $owner);
		$sql->getQuery();
		$result = new SqlResult($sql->getRow());
		$datastoreID = $result->getInt(0);
		
		$sql->setQuery("DELETE FROM $this->datastoreTable WHERE uuid=? AND owner=?");
		$sql->setStr(1, $uuid);
		$sql->setStr(2, $owner);
		$rs = $sql->getQuery();
		
		// 데이터저장소 삭제가 정상적으로 이루어 지면 모든 문서를 삭제한다.
		if($rs) $this->removeAllDocuments($datastoreID);
		
		return $rs;
	}
	
	/**
	 * 데이터저장소 설명 정보를 업데이트 한다.
	 * @param String $uuid
	 * @param String $description
	 */
	function updateDatastore($uuid, $description){
		if(!$uuid) die("Falcon->updateDatastore() :: 데이터저장소의 UUID 정보가 없습니다.");
		elseif(!$description) die("Falcon->updateDatastore() :: 데이터저장소의 설명 정보가 없습니다.");
		
		$sql = new Sql("UPDATE $this->datastoreTable SET description=? WHERE uuid=?");
		$sql->setStr(1, $description);
		$sql->setStr(2, $uuid);
		return $sql->getQuery();
	}
	
	/**
	 * 데이터저장소의 JSON 문서를 입력&갱신한다.
	 * @param String $documentName
	 * @param JSON $document
	 */
	function update($documentName, $document){
		if(!$documentName) die("Falcon->update() :: 입력&갱신될 JSON 문서 이름이 없습니다.");
		elseif(!$document) die("Falcon->update() :: JSON 문서가 없습니다.");
		elseif(!$this->datastoreID) die("Falcon->update() :: 연결된 데이터저장소가 없습니다.");
		
		$datamap = json_decode($document, true);
		$documentID = $this->getDocumentID($documentName);
		
		// 같은 문서 이름이 있으면 업데이트 한다. 
		if($documentID > 0){
			// 데이터맵을 지운다.
			$datamapSql = new Sql("DELETE FROM $this->datamapTable WHERE document=?");
			$datamapSql->setInt(1, $documentID);
			$datamapSql->getQuery();
			
			// 데이터맵을 새로 입력한다.
			return $this->insertDatamap($documentID, $datamap);
		}
		// 새로 입력
		else{
			return $this->insert($documentName, $datamap);
		}
	}
	
	/**
	 * 문서정보와 데이터맵을 입력한다.
	 * @param String $documentName
	 * @param Array $datamap
	 */
	private function insert($documentName, $datamap){
		// 문서정보를 입력한다.
		$sql = new Sql("INSERT INTO $this->documentTable (`datastore`, `documentName`) VALUE (?,?)");
		$sql->setInt(1, $this->datastoreID);
		$sql->setStr(2, $documentName);
		$rs = $sql->getQuery();
		
		// 데이터맵을 입력한다.
		if($rs) $rs2 = $this->insertDatamap($sql->getInsertID(), $datamap);
		
		if($rs && $rs2) return $rs;
		else return false;
	}
	
	/**
	 * 데이터맵을 입력한다.
	 * @param Integer $documentID
	 * @param Array $datamap
	 * @param Integer $super
	 */
	private function insertDatamap($documentID, $datamap, $super=0){
		$sql = new Sql("INSERT INTO $this->datamapTable (`super`, `document`, `key`, `value`) VALUE (?,?,?,?)");
		
		foreach($datamap as $key => $value){
			
			$sql->setStr(1, $super);
			$sql->setStr(2, $documentID);
			$sql->setStr(3, $key);
			
			// 배열인지 확인하고 배열이 아니면 Value값 입력
			if(!is_array($value)){
				$sql->setStr(4, $value);
				$rs = $sql->getQuery();
			}
			// 배열이면 value값 공백으로 입력하고 재귀 호출
			else{
				$sql->setStr(4, "");
				$sql->getQuery();
				
				$rs = $this->insertDatamap($documentID, $value, $sql->getInsertID());
			}
		}
		return $rs;
	}
	
	/**
	 * 데이터저장소에서 JSON 문서를 찾아 삭제한다.
	 * @param JSON or DocumentName $document
	 */
	function remove($document=""){
		if(!$this->datastoreID) die("Falcon->remove() :: 연결된 데이터저장소가 없습니다.");
		
		$datamap = json_decode($document, true);
		
		// 입력받은 $document 값이 없으면 전체 문서를 삭제한다.
		if(!$document){
			return $this->removeAllDocuments($this->datastoreID);
		}
		
		// 입력받은 $document 값이 배열이 아니면 문서 이름에서 찾아 삭제한다.
		elseif(!is_array($datamap)){
			$documentID = $this->getDocumentID($document);
			return $this->removeDocument($documentID);
		}
		
		// JSON 문서를 찾아 모두 삭제한다.
		else{
			$documentNames = $this->buildDocumentNames($datamap);
			foreach($documentNames as $key => $value){
				$this->removeDocument($key);
			}
			return true;
		}
	}
	
	/**
	 * 데이터저장소의 모든 문서를 삭제한다.
	 * @param Integer $datastoreID
	 */
	private function removeAllDocuments($datastoreID){
		// 데이터맵을 지우기 위해 입력된 문서들을 가져온다.
		$sql->setQuery("SELECT id FROM $this->documentTable WHERE datastore=?");
		$sql->setStr(1, $datastoreID);
		$sql->getQuery();
			
		// 루프를 돌며 가져온 문서의 데이터맵을 삭제
		while($result->setResult($sql->getRow())){
			$this->removeDatamap($result->getInt(0));
		}
		
		// 문서 데이터를 지운다.
		$sql->setQuery("DELETE FROM $this->documentTable WHERE datastore=?");
		$sql->setInt(1, $datastoreID);
		return $sql->getQuery();
	}
	
	/**
	 * JSON 문서를 삭제한다.
	 * @param Integer $documentID
	 */
	private function removeDocument($documentID){
		// 문서의 데이터맵을 먼저 삭제한다.
		if($this->removeDatamap($documentID)){
			// 문서를 삭제한다.
			$sql->setQuery("DELETE FROM $this->documentTable WHERE id=?");
			$sql->setInt(1, $documentID);
			return $sql->getQuery();
		}
		return false;
	}
	
	/**
	 * 문서의 데이터맵을 삭제한다.
	 * @param Integer $documentID
	 */
	private function removeDatamap($documentID){
		// 데이터맵을 지운다.
		$sql = new Sql("DELETE FROM $this->datamapTable WHERE document=?");
		$sql->setInt(1, $documentID);
		return $sql->getQuery();
	}
	
	/**
	 * 페이지에 보여질 결과의 수 (기본값은 10)
	 * @param Integer $rpp
	 */
	function rpp($rpp=10){
		if($rpp < 1 || !$rpp) $this->rpp = 10;
		if($rpp) $this->rpp = $rpp;
		return $this;
	}
	
	/**
	 * 페이지 번호 (시작은 1부터)
	 * @param Integer $page
	 */
	function page($page=1){
		if($page < 1 || !$page) $this->page = 1;
		elseif($page) $this->page = $page;
		return $this;
	}
	
	/**
	 * 결과를 정렬한다. (오름&내림차순)
	 * @param Integer $order
	 */
	function sort($order=1){
		if($order > 0) $this->sort = "ASC";
		else $this->sort = "DESC";
		return $this;
	}
	
	/**
	 * 데이터저장소에서 JSON 문서를 찾아 반환한다.
	 * @param JSON or DocumentName $document
	 */
	function find($document=""){
		if(!$this->datastoreID) die("Falcon->find() :: 연결된 데이터저장소가 없습니다.");
		
		$datamap = json_decode($document, true);
		
		// 입력받은 $document 값이 없으면 전체 문서를 반환한다.
		if(!$document){
			return $this->buildJSONDocument($this->buildAllDatamap());
		}
		
		// 입력받은 $document 값이 배열이 아니면 문서 이름에서 찾아 반환한다.
		elseif(!is_array($datamap)){
			$documentID = $this->getDocumentID($document);
			return $this->buildJSONDocument( $this->buildDatamap($documentID));
		}
		
		// JSON 문서를 찾아 반환한다.
		else{
			$documentNames = $this->buildDocumentNames($datamap);
			
			$datamap = array();
			foreach($documentNames as $key => $value){
				$datamap[$value] = $this->buildDatamap($key);
			}
			return $this->buildJSONDocument($datamap);
		}
	}
	
	/**
	 * 데이터저장소에 저장된 JSON 문서를 찾아 문서갯수를 반환한다.
	 * @param JSON or DocumentName $document
	 */
	function findCount($document=""){
		if(!$this->datastoreID) die("Falcon->findCount() :: 연결된 데이터저장소가 없습니다.");
		
		$datamap = json_decode($document, true);
		
		// 입력받은 $document 값이 없으면 전체 문서갯수를 반환한다.
		if(!$document){
			$sql = new Sql("SELECT COUNT(documentName) FROM $this->documentTable WHERE `datastore`=?");
			$sql->setStr(1, $this->datastoreID);
			$sql->getQuery();
			$result = new SqlResult($sql->getRow());
			
			return $result->getInt(0);
		}
		
		// 입력받은 $document 값이 배열이 아니면 문서 이름에서 찾아 반환한다.
		elseif(!is_array($datamap)){
			return $this->getDocumentID($document) > 0 ? 1 : 0;
		}
		
		// JSON 문서를 찾아 갯수를 반환한다.
		else return $this->getDocumentsCount($datamap);
	}
	
	/**
	 * JSON 데이터로 문서를 찾아 문서들의 이름을 반환한다.
	 * @param JSON $document
	 */
	function findDocumentNames($document){
		$datamap = json_decode($document, true);
		
		if(!$this->datastoreID) die("Falcon->findDocumentNames() :: 연결된 데이터저장소가 없습니다.");
		elseif(!$document) return null;
		elseif(!is_array($datamap)) return null;
		
		$documentNames = $this->buildDocumentNames( $datamap );
		$datamap = array();
		foreach($documentNames as $key => $value){
			$datamap[ $key ] = $value;
		}
		
		return $this->buildJSONDocument( $datamap );
	}
	
	/**
	 * 배열을 JSON 문서로 변환한다.
	 * @param Array $datamap
	 */
	function buildJSONDocument($datamap){
		if(!$datamap) return "";
		elseif(!is_array($datamap)) return $datamap;
		
		return urldecode( json_encode( $this->arrayUrlEncode($datamap) ) );
	}
	
	/**
	 * 데이터저장소에 저장된 모든 문서의 데이터맵을 반환한다.
	 */
	private function buildAllDatamap(){
		// 입력받은 문서이름이 없기 때문에 데이터저장소에 저장되어 있는 모든 JSON 문서를 가져온다.
		$start = $this->rpp * ($this->page-1);
		$sql = new Sql("SELECT `id`,`documentName` FROM $this->documentTable WHERE `datastore`=? ORDER BY `id` $this->sort LIMIT $start, $this->rpp");
		$sql->setStr(1, $this->datastoreID);
		$sql->getQuery();
		
		$datamap = array();
		$result = new SqlResult();
		while($result->setResult($sql->getRow())){
			$datamap[ $result->getStr(1) ] = $this->buildDatamap( $result->getStr(0) );
		}
		return $datamap;
	}
	
	/**
	 * 문서의 데이터맵을 구축한뒤 배열로 반환한다.
	 * @param Integer $documentID
	 * @param Integer $super
	 */
	private function buildDatamap($documentID, $super=0){
		// 데이터저장소에서 데이터맵을 가져오기 위한 쿼리문
		$sql = new Sql("SELECT `id`, `key`, `value` FROM $this->datamapTable WHERE `super`=? AND `document`=?");
		$sql->setStr(1, $super);
		$sql->setStr(2, $documentID);
		$sql->getQuery();
		
		$datamap = array();
		$result = new SqlResult();
		while($result->setResult($sql->getRow())){
			
			// Value가 있으면 데이터맵에 추가한다.
			if($result->getStr(2)){
				$datamap[ $result->getStr(1) ] = $result->getStr(2);
			}
			
			// Value가 없으면 새로운 노드를 만든다. (재귀호출)
			else{
				$child = $this->buildDatamap($documentID, $result->getStr(0));
				$datamap[ $result->getStr(1) ] = $child ? $child : "";
			}
		}
		
		return $datamap;
	}
	
	/**
	 * 데이터맵과 일치하는 JSON 문서들의 이름을 배열로 반환한다.
	 * @param Array $datamap
	 */
	private function buildDocumentNames($datamap){
		// 인덱스 이퀄(=) 검색
		$start = $this->rpp * ($this->page-1);
		$sql = new Sql("SELECT $this->documentTable.`id`, $this->documentTable.`documentName`
						FROM $this->datamapTable LEFT JOIN $this->documentTable ON ($this->datamapTable.`document` = $this->documentTable.`id`)
						WHERE $this->documentTable.`datastore`=? AND $this->datamapTable.`key`=? AND $this->datamapTable.`value` like ? ORDER BY $this->datamapTable.`id` $this->sort LIMIT $start, $this->rpp");
		
		/* Value에 fulltext 인덱스가 설정되어 있다면 MATCH() AGAINST() 검색
		$sql = new Sql("SELECT $this->documentTable.`id`, $this->documentTable.`documentName`
						FROM $this->datamapTable LEFT JOIN $this->documentTable ON ($this->datamapTable.`document` = $this->documentTable.`id`)
						WHERE $this->documentTable.`datastore`=? AND $this->datamapTable.`key` like ? AND MATCH($this->datamapTable.`value`) AGAINST(?) ORDER BY $this->datamapTable.`id` $this->sort LIMIT $start, $rpp");
		*/
		
		$sql->setStr(1, $this->datastoreID);
		
		$documentNames = array();
		foreach($datamap as $key => $value){
			$sql->setStr(2, $key);
			
			if(!is_array($value)) $sql->setStr(3, $value);
			else $sql->setStr(3, "");
			
			$sql->getQuery();
			
			$result = new SqlResult();
			while($result->setResult($sql->getRow())){
				$documentNames[ $result->getInt(0) ] = $result->getStr(1);
			}
		}
		
		return $documentNames;
	}
	
	/**
	 * 데이터맵과 일치하는 JSON 문서들의 갯수를 반환한다.
	 * @param Array $datamap
	 */
	private function getDocumentsCount($datamap){
		// 인덱스 이퀄(=) 검색
		$sql = new Sql("SELECT COUNT($this->documentTable.`id`)
						FROM $this->datamapTable LEFT JOIN $this->documentTable ON ($this->datamapTable.`document` = $this->documentTable.`id`)
						WHERE $this->documentTable.`datastore`=? AND $this->datamapTable.`key`=? AND $this->datamapTable.`value` like ?");
		
		/* Value에 fulltext 인덱스가 설정되어 있다면 MATCH() AGAINST() 검색
		$sql = new Sql("SELECT COUNT($this->documentTable.`id`)
						FROM $this->datamapTable LEFT JOIN $this->documentTable ON ($this->datamapTable.`document` = $this->documentTable.`id`)
						WHERE $this->documentTable.`datastore`=? AND $this->datamapTable.`key` like ? AND MATCH($this->datamapTable.`value`) AGAINST(?)");
		*/
		
		$sql->setStr(1, $this->datastoreID);
		
		$documentCount = 0;
		foreach($datamap as $key => $value){
			$sql->setStr(2, $key);
			if(!is_array($value)) $sql->setStr(3, $value);
			else $sql->setStr(3, "");
			
			$sql->getQuery();
			$result = new SqlResult();
			if($result->setResult($sql->getRow())) $documentCount += $result->getInt(0);
		}
		
		return $documentCount;
	}
	
	/**
	 * JSON 문서 이름으로 문서 ID를 반환한다.
	 * @param String $documentName
	 */
	private function getDocumentID($documentName){
		$sql = new Sql("SELECT `id` FROM $this->documentTable WHERE `datastore`=? AND `documentName`=?");
		$sql->setInt(1, $this->datastoreID);
		$sql->setStr(2, $documentName);
		$sql->getQuery();
		
		$result = new SqlResult();
		$result->setResult($sql->getRow());
		
		return $result->getStr(0);
	}
	
	/**
	 * json_encode()를 실행하기 전에 한글 문자가 깨지는것을 방지하기 위해 urlencode()로 한글을 변환해준다.
	 * @param Array $array
	 */
	private function arrayUrlEncode($array){
		if(!$array) return "";
		elseif(!is_array($array)) return urlencode($array);
		
		$newArray = array();
		foreach($array as $key => $value){
			$key = urlencode($key);
			
			if(is_array($value)) $newArray[$key] = $this->arrayUrlEncode($value);
			else $newArray[$key] = urlencode($value);
		}
		
		return $newArray;
	}
	
	/**
	 * UUID값을 반환한다. (총 36자리 문자열)
	 */
	function getUUID(){
		mt_srand((double)microtime()*10000);
		$charid = md5(uniqid(rand(), true));
		$hyphen = chr(45); // "-"
		$uuid = substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12);
		return $uuid;
	}
}
?>