<?php

use crodas\InfluxPHP\ResultSeriesObject;

class ResultSeriesObjectTest extends \PHPUnit_Framework_TestCase
{

    public function testCreate()
    {
        $result = new ResultSeriesObject();
        $this->assertTrue($result instanceof ResultSeriesObject);
    }

    // todo: more tests...
    
}
