<?php

/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
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
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
 */

namespace crodas\InfluxPHP;

class DB extends BaseHTTP
{

    protected $client;
    protected $name;

    public function __construct(Client $client, $name)
    {
        $this->client = $client;
        $this->name = $name;
        $this->inherits($client);
        $this->base = '';
    }

    /**
     * Get database name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Drop database
     * 
     * @return type
     */
    public function drop()
    {
        return $this->client->deleteDatabase($this->name);
    }

    /**
     * Insert into database
     * 
     * @param type $name
     * @param array $data
     * @return type
     */
    public function insert($name, array $data)
    {
        $points = array();
        if (isset($data['name'])) {
            $name = $data['name'];
            unset($data['name']);
        }
        $keys = array_keys($data);
        if (count($keys) > 1) { // be sure that multiple entries are well-formatted
            for ($i = 0; $i < count($keys); $i++) {
                $elem = $data[$keys[$i]];
                if (!isset($data[$keys[$i]]['name'])) {
                    $data[$keys[$i]]['name'] = $name;
                }
            }
        } else {
            if (!in_array(0, $keys, true)) {
                return $this->insert($name, array($data));
            } elseif (!isset($data[0]['name'])) { // don't overwrite identifier name if submitted in data array
                $data[0]['name'] = $name;
            }
        }
        $body = array('database' => $this->name);
        foreach ($data as $id => $val) {
            $data[$id]["measurement"] = $name;
        }

        $points = array('points' => $data);
        $body = array_merge($body, $points);
        return $this->post('write', $body, array('db' => $this->name, 'time_precision' => $this->timePrecision));
    }

    /**
     * Get first element of query resultset
     * 
     * @param string $sql
     * @return type
     */
    public function first($sql)
    {
        return current($this->query($sql));
    }

    /**
     * Query database and get resultset 
     * 
     * @param string $sql
     * @return type
     */
    public function query($sql)
    {
        return ResultsetBuilder::buildResultSeries($this->get('query', array('db' => $this->name, 'q' => $sql, 'time_precision' => $this->timePrecision)));
      
    }
    
    /**
     * Create retention policy of a database
     * 
     * @param type $name
     * @param type $duration
     * @param type $replication
     * @param bool $default
     * @return type
     */
    public function setRetentionPolicy($name, $duration, $replication, $default = false)
    {
        $query = 'CREATE RETENTION POLICY ' . $name . ' ON ' . $this->name . ' DURATION ' . $duration . ' REPLICATION ' . $replication . ($default ? ' DEFAULT' : '');
        return $this->query($query);
    }

    /**
     * Modify the retention policy of a database
     * 
     * @param type $name
     * @param type $duration
     * @param type $replication
     * @param book $default
     * @return type
     */
    public function modifyRetentionPolicy($name, $duration=null, $replication=null, $default = false)
    {
        // the parameters are optional, so don't set if null is submitted. In any other case, change the values. 
        $query = 'ALTER RETENTION POLICY ' . $name . ' ON ' . $this->name .  
                ($duration !== null ? ' DURATION ' . $duration : '') . 
                ($replication !== null ? ' REPLICATION ' . $replication : '') . 
                ($default === true ? ' DEFAULT' : '');
        return $this->query($query);
    }

    
    /**
     * Show retention policies from database
     * 
     * @return type
     */
    public function getRetentionPolicies()
    {
        return($this->query('SHOW RETENTION POLICIES ' . $this->name));
    }
    
    
    
}
