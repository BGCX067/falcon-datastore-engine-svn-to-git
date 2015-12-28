<?php
require_once "class/Falcon.class.php";

header("Content-type: application/json");

$uuid = $_REQUEST["datastore"];
$data = $_REQUEST["data"];
$rpp = $_REQUEST["rpp"]?$_REQUEST["rpp"]:100;
$page = $_REQUEST["page"]?$_REQUEST["page"]:1;

$falcon = new Falcon();

if($falcon->connectDatastore($uuid)){
	echo $falcon->rpp($rpp)->page($page)->sort($falcon->DESC)->find($data);
}
?>