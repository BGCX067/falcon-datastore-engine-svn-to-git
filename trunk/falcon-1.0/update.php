<?php
require_once "class/Falcon.class.php";

$uuid;
$documentName;
$document;

foreach ($_POST as $key => $value){
	if($key == "datastore") $uuid = $value;
	elseif($key == "documentName") $documentName = $value;
	else $document[$key] = $value;
}
if(!$uuid || !$documentName || !$document) exit;

$falcon = new Falcon();

if($falcon->connectDatastore($uuid)){
	$result = $falcon->update($documentName, $falcon->buildJSONDocument($document));
}

if($result) echo 1;
else echo 0;
?>