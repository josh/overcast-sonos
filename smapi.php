<?php
include_once 'sonos.php';
set_time_limit(10);
ini_set("soap.wsdl_cache_enabled", "0");
$server = new SoapServer('Sonos.wsdl');
$server->setClass('Sonos');
$server->handle();
?>
