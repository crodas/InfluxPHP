<?php

require __DIR__ . "/../vendor/autoload.php";

$client = new \crodas\InfluxPHP\Client;

$dbs = $client->getDatabases();
if ($dbs) {
	foreach ((array)$dbs as $db) {
    	if (preg_match("/^test_/", $db->getName())) {
    		$db->drop();
    	}
	}

}
