<?php
namespace InfluxPHP\tests;

use crodas\InfluxPHP\Client;
use crodas\InfluxPHP\DB;
use crodas\InfluxPHP\MultipleResultSeriesObject;
use crodas\InfluxPHP\ResultSeriesObject;
use RuntimeException;


class DBTest extends BaseTest
{

    public function testCreate()
    {
        return $this->client->createDatabase("test_foobar");
    }

    /**
     *  @expectedException RuntimeException
     */
    public function testCreateException()
    {
        return $this->client->createDatabase("test_foobar");
    }

    /**
     *  @dependsOn testCreateException
     */
    public function testDelete()
    {
        return $this->client->deleteDatabase("test_foobar");
    }

    /**
     *  @dependsOn testDelete
     *  @expectedException \RuntimeException
     */
    public function testDeleteException()
    {
        return $this->client->deleteDatabase("test_foobar");
    }

    public function testDBObject()
    {
        $this->client->createDatabase("test_xxx");
        $this->assertTrue($this->client->test_xxx instanceof DB);
        $this->assertTrue($this->client->getDatabase("test_xxx") instanceof DB);
        $this->client->test_xxx->drop();
    }

    public function testTimePrecision()
    {
        $this->assertEquals('s', $this->client->getTimePrecision());
        $db = $this->client->createDatabase("test_yyyy");
        $this->assertEquals('s', $db->getTimePrecision());


        $this->client->setTimePrecision('m');
        $this->assertEquals('m', $this->client->getTimePrecision());
        $this->assertEquals('m', $db->getTimePrecision());

        $db1 = $this->client->createDatabase("test_yyyx");
        $this->assertEquals('m', $db->getTimePrecision());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidTimePrecision()
    {
        $this->client->SetTimePrecision(array());
    }

    public function testInsert()
    {
        $db = $this->client->createDatabase('test_zzz');

        for ($i = 0; $i < 144; $i++) {
            $data = array(
                array(
                    'tags' => array('type' => $i % 2 ? 'two' : 'one'),
            'fields' => array(
                'value' => (float) $i * 10,
                'type' => $i % 2 ? 'two' : 'one'
            ),
            'timestamp' => strtotime("2015-01-01T00:00:00Z") + $i * 10 * 60
                )
            );
            $db->insert('test1', $data);
        }
        usleep(500000); // hope that's enough to be all values written
        $this->assertEquals($db->first("select * from test1 where value=0")->time, '2015-01-01T00:00:00Z');

        ///todo crashes node on RC29
//        $this->assertEquals($db->first("select last(value) from test1")->last, 1430);
    }

    /** @depends testInsert */
    public function testQueryAggregateCount()
    {
        $db = $this->client->test_zzz;
        $result = $db->query("SELECT count(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->count, 6);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->count, 6);
    }

    /** @depends testInsert */
    public function testQueryAggregateMean()
    {
        $db = $this->client->test_zzz;
        $result = $db->query("SELECT mean(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->mean, 745);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->mean, 1405);
    }

    /** @depends testInsert */
    public function testQueryAggregateSum()
    {
        $db = $this->client->test_zzz;
        $result = $db->query("SELECT sum(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->sum, 4470);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->sum, 8430);
    }

    /** @depends testInsert */
    public function testQueryAggregateFirst()
    {
        $db = $this->client->test_zzz;
        $result = $db->query("SELECT first(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->first, 720);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->first, 1380);
    }

    /** @depends testInsert */
    public function notestQueryAggregateLast()
    {
        $db = $this->client->test_zzz;
        $result = $db->query("SELECT last(value) FROM test1 where  time >= '2015-01-01T12:00:00Z' and time < '2015-01-02T00:00:00Z' group by time(1h)");
        $this->assertEquals(count($result), 12);
        $this->assertEquals($result[0]->time, '2015-01-01T12:00:00Z');
        $this->assertEquals($result[0]->last, 770);
        $this->assertEquals($result[11]->time, '2015-01-01T23:00:00Z');
        $this->assertEquals($result[11]->last, 1430);
    }


    /** @depends testInsert */
    public function testQueryAggregateMultipleResultSeries()
    {
        $db = $this->client->test_zzz;
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

    /** @depends testInsert
     * @expectedException \RuntimeException
     */
    public function testDatabaseExists()
    {
        $db = $this->client->createDatabase("test_zzz");
    }

    public function lalala_testQuery()
    {
        $db = $this->client->createDatabase("test_xxx");
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
        $db = $this->client->test_xxx;

        $this->client->setTimePrecision('u');
        foreach ($db->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time > time() * 1000);
        }

        $this->client->setTimePrecision('m');
        foreach ($db->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time < time() * 10000);
        }

        $this->client->setTimePrecision('s');
        foreach ($db->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time < time() + 20);
        }

        $db->drop();
    }

}
