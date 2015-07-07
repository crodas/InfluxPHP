<?php

use crodas\InfluxPHP\Client;
use crodas\InfluxPHP\DB;
use crodas\InfluxPHP\MultipleResultSeriesObject;
use crodas\InfluxPHP\ResultSeriesObject;

class DBTest extends \PHPUnit_Framework_TestCase
{


    public static function setUpBeforeClass() 
    {
        $client = new Client;
        $db = $client->createDatabase('test_zzz');

        for ($i = 0; $i < 144; $i++) {
            $data = array(array('tags' => array('type' => $i % 2 ? 'two' : 'one'),
            'fields' => array('value' => $i * 10,
                'type' => $i % 2 ? 'two' : 'one'),
            'time' => date("c", strtotime("2015-01-01T00:00:00Z") + $i * 10 * 60)));
            $db->insert('test1', $data);
        }
    }

    public static function tearDownAfterClass()
    {
          $client = new Client;
          $db = $client->test_zzz;
          $db->drop();
    }


    public function testCreate()
    {
        $client = new Client;
        return $client->createDatabase("test_foobar");
    }

    public function testDelete()
    {
        $client = new Client;
        return $client->deleteDatabase("test_foobar");
    }

    public function testDBObject()
    {
        $client = new Client;
        $client->createDatabase("test_xxx");
        $this->assertTrue($client->test_xxx instanceof DB);
        $this->assertTrue($client->getDatabase("test_xxx") instanceof DB);
        $client->test_xxx->drop();
    }

    public function testTimePrecision()
    {
        $client = new Client;
        $this->assertEquals('s', $client->getTimePrecision());
        $db = $client->createDatabase("test_yyyy");
        $this->assertEquals('s', $db->getTimePrecision());


        $client->setTimePrecision('m');
        $this->assertEquals('m', $client->getTimePrecision());
        $this->assertEquals('m', $db->getTimePrecision());

        $db1 = $client->createDatabase("test_yyyx");
        $this->assertEquals('m', $db->getTimePrecision());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTimePrecision()
    {
        $client = new Client;
        $client->SetTimePrecision(array());
    }

    /**
     * @medium
     */
    public function testInsert()
    {
        $client = new Client;
        $db = $client->test_zzz;

        $this->assertEquals($db->first("select * from test1 where value=0")->time, '2015-01-01T00:00:00Z');
        $this->assertEquals($db->first("select last(value) from test1")->last, 1430);
    }

    /** 
      * @depends testInsert 
      * @medium
    */
    public function testQueryAggregateCount()
    {
        $client = new Client;

        $db = $client->test_zzz;
        $result = $db->query("SELECT count(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->count, 6);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->count, 6);
    }

    /** 
      * @depends testInsert 
      * @medium
    */
    public function testQueryAggregateMean()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $result = $db->query("SELECT mean(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->mean, 745);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->mean, 1405);
    }

    /** 
      * @depends testInsert 
      * @medium
    */
    public function testQueryAggregateSum()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $result = $db->query("SELECT sum(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->sum, 4470);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->sum, 8430);
    }

    /** 
      * @depends testInsert 
      * @medium
    */
    public function testQueryAggregateFirst()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $result = $db->query("SELECT first(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->first, 720);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->first, 1380);
    }

    /** 
      * @depends testInsert 
      * @medium
    */
    public function testQueryAggregateLast()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $result = $db->query("SELECT last(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->last, 770);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->last, 1430);
    }


    /** 
      * @depends testInsert 
      * @medium
    */
    public function testQueryAggregateMultipleResultSeries()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $result = $db->query("SELECT count(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h), type");
        $this->assertTrue($result instanceof \crodas\InfluxPHP\MultipleResultSeriesObject);
        $result1 = $result[0];
        $result2 = $result[1];
        $this->assertTrue($result1 instanceof \crodas\InfluxPHP\ResultSeriesObject);
        $this->assertTrue($result2 instanceof \crodas\InfluxPHP\ResultSeriesObject);
        $this->assertEquals($result1->tags['type'], 'one');
        $this->assertEquals($result2->tags['type'], 'two');


        $this->assertEquals(count($result1), 12);
        $this->assertEquals($result1[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result1[0]->count, 3);
        $this->assertEquals($result1[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result1[11]->count, 3);

        $this->assertEquals(count($result2), 12);
        $this->assertEquals($result2[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result2[0]->count, 3);
        $this->assertEquals($result2[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result2[11]->count, 3);
    }

    
    /** 
     * @medium
     */
    public function testDefaultRetentionPolicy()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $policy = $db->getRetentionPolicies();
        $this->assertCount(1, $policy);
        $this->assertEquals('default', $policy[0]->name);
        $this->assertEquals(true, $policy[0]->default);
        
    }
    
    /** 
     * @depends testDefaultRetentionPolicy
     * @medium
     */
    public function testSetRetentionPolicy()
    {
        $client = new Client;
        $db = $client->test_zzz;
        $result = $db->setRetentionPolicy('testpolicy','1w',1);
        $policy = $db->getRetentionPolicies();
        
        $this->assertCount(2, $policy);
        
    }
    
    /** 
     * @depends testSetRetentionPolicy
     * @medium
     */
    public function testGetRetentionPolicy()
    {
        $client = new Client;
        $db = $client->test_zzz;
       
        $policy = $db->getRetentionPolicies();
        
        $this->assertCount(2, $policy);
        $this->assertEquals('default', $policy[0]->name);
        $this->assertEquals(true, $policy[0]->default);
        $this->assertEquals('testpolicy', $policy[1]->name);
        $this->assertEquals(false, $policy[1]->default);
    }
    
    public function lalala_testQuery()
    {
        $client = new Client;
        $db = $client->createDatabase("test_xxx");
        //$db->createUser("root", "root");

        $db->insert("foobar", array('fields' => array('type' => '/foobar', 'karma' => 10)));

        $db->insert("foobar", array('fields' => array('type' => '/foobar', 'karma' => 20)));
        $db->insert("foobar", array('fields' => array('type' => '/barfoo', 'karma' => 30)));
        usleep(100000); // ugly, wait 0.1 seconds to be sure that values are written into database

        $this->assertEquals($db->first("SELECT max(karma) FROM foobar")->max, 30);
        $this->assertEquals($db->first("SELECT min(karma) FROM foobar")->min, 10);
        $this->assertEquals($db->first("SELECT mean(karma) FROM foobar")->mean, 20);

        foreach ($db->query("SELECT mean(karma), type FROM foobar GROUP BY type") as $row) {
            var_dump($row);
            $dtParsed = date_parse($row->time);
            var_dump($dtParsed);

            $this->assertEquals($dtParsed['error_count'], 0);
            if ($row->type == "/foobar") {
                $this->assertEquals(15, $row->mean);
            } else {
                $this->assertEquals(20, $row->mean);
            }
        }
    }

    /** @dependsOn testQuery */
    function fooooo_testDifferentTimePeriod()
    {
        $client = new Client;
        $db = $client->test_xxx;

        $client->setTimePrecision('u');
        foreach ($db->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time > time() * 1000);
        }

        $client->setTimePrecision('m');
        foreach ($db->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time < time() * 10000);
        }

        $client->setTimePrecision('s');
        foreach ($db->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time < time() + 20);
        }

        $db->drop();
    }

}
