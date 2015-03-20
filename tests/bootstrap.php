<?php

require __DIR__ . "/../../../autoload.php";

$client = new \crodas\InfluxPHP\Client;
foreach ($client->getDatabases() as $db) {
    if (preg_match("/^test_/", $db->getName())) {
        $db->drop();
    }
}
