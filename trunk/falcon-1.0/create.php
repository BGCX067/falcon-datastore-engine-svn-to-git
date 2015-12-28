<?php
require_once "class/Falcon.class.php";


$falcon = new Falcon();
echo $falcon->createDatastore("owner", "description");
?>