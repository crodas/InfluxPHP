<?php
/**
 * @author Stephen "TheCodeAssassin" Hoogendijk <admin@tca0.nl>
 */
namespace InfluxPHP\tests;

use crodas\InfluxPHP\Client;

/**
 * Class BaseTest
 *
 * @package InfluxPHP\tests
 */
abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {

        $host = getenv('INFLUXDB_HOST');

        if (!$host) {
            $host = 'localhost';
        }

        $this->client = new Client($host);

        $this->cleanUp();
    }

    /**
     *
     */
    public function __destruct()
    {
        // cleanup
//        $this->cleanUp();
    }

    protected function cleanUp()
    {
        if (count($this->client->getDatabaseNames()) > 0) {
            foreach ($this->client->getDatabases() as $db) {
                if (preg_match("/^test_/", $db->getName())) {
                    $db->drop();
                }
            }
        }
    }


}