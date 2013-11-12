<?php
use crodas\InfluxPHP\Client;

class DBTest extends \phpunit_framework_testcase
{
    public function testCreate()
    {
        $client = new Client;
        return $client->createDatabase("test_foobar");
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testCreateException()
    {
        $client = new Client;
        return $client->createDatabase("test_foobar");
    }
}
