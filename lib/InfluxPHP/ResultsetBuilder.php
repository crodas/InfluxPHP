<?php

/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2015 Ralf Geschke                                                 |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: Ralf Geschke <ralf@kuerbis.org>                                        |
  +---------------------------------------------------------------------------------+
 */

namespace crodas\InfluxPHP;

class ResultsetBuilder
{

    /**
     * Build a result object, dependent on the number of result series in the 
     * submitted resultset array
     * 
     * @param array $resultSet
     * @return \crodas\InfluxPHP\MultipleResultSeriesObject or \crodas\InfluxPHP\ResultSeriesObject or null
     */
    public static function buildResultSeries(array $resultSet)
    {
        $rows = array();
        if (!isset($resultSet['results'][0]['series'][0])) {
            return null;
        }

        if (isset($resultSet['results'][0]['series'])) {
            foreach ($resultSet['results'][0]['series'] as $resultElem) {
                $row = self::createResultSeriesObject($resultElem);
                $rows[] = $row;
            }
        }
        $seriesCount = count($rows);

        if ($seriesCount == 1) {
            $resultSeries = $rows[0];
        } else {
            $resultSeries = new MultipleResultSeriesObject($rows);
        }
        return $resultSeries;
    }

    /**
     * Create a ResultSeriesObject, i.e. an instance of an ArrayIterator,
     * enhanced with meta data of InfluxDB results
     * 
     * @param type $resultElem
     * @return \crodas\InfluxPHP\ResultSeriesObject
     */
    protected static function createResultSeriesObject($resultElem)
    {
        $resultColumns = $resultElem['columns'];
        $resultValues = $resultElem['values'];
        unset($resultElem['columns']);
        unset($resultElem['values']);
        $seriesElem = new ResultSeriesObject();
        if (isset($resultElem['name'])) {
            $name = $resultElem['name'];
            unset($resultElem['name']);
            $seriesElem->setName($name);
        }
        if (count($resultElem)) {
            $seriesElem->setMeta($resultElem);
        }

        foreach ($resultValues as $row) {
            if (count($resultColumns) != count($row)) {
                $diffCount = abs(count($resultColumns) - count($row));
                $resultColumns = array_pad($resultColumns, count($row), null);
                $row = array_pad($row, count($resultColumns), null);
            }

            $row = (object) array_combine($resultColumns, $row);
            $rows[] = $row;
        }
        $seriesElem->setRows($rows);
        return $seriesElem;
    }

}
