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

class Client extends BaseHTTP
{

    /**
     * READ privilege
     */
    const PRIV_READ = 'READ';

    /**
     * WRITE privilege 
     */
    const PRIV_WRITE = 'WRITE';

    /**
     * ALL privilege
     */
    const PRIV_ALL = 'ALL';

    protected $host;
    protected $port;
    protected $user;
    protected $pass;

    public function __construct($host = "localhost", $port = 8086, $u = 'root', $p = 'root')
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $u;
        $this->pass = $p;
    }

    /**
     * Delete a database
     * 
     * @param string $name
     * @return type
     */
    public function deleteDatabase($name)
    {
        return $this->get('query', array('q' => 'DROP DATABASE ' . $name));
    }

    /**
     * Create a database and return DB instance
     * 
     * @param string $name
     * @return \crodas\InfluxPHP\DB
     */
    public function createDatabase($name)
    {
        $this->get('query', array('q' => 'CREATE DATABASE ' . $name));
        return new DB($this, $name);
    }

    /**
     * Show existing databases
     * 
     * @return array of DB objects or null
     */
    public function getDatabases()
    {
        $self = $this;
        $dbs = $this->get('query', array('q' => 'SHOW DATABASES'));

        if (isset($dbs['results'][0]['series'][0]['values'])) {
            return array_map(function($obj) use($self) {
                return new DB($self, $obj[0]);
            }, $dbs['results'][0]['series'][0]['values']);
        }
        return null;
    }

    /**
     * Create a user
     * 
     * @param string $name
     * @param string $password
     * @return type
     */
    public function createUser($name, $password)
    {
        return $this->get('query', array('q' => 'CREATE USER ' . $name . " WITH PASSWORD '" . $password . "'"));
    }

    /**
     * Delete a user
     * 
     * @param string $name
     * @return type
     */
    public function deleteUser($name)
    {
        return $this->get('query', array('q' => 'DROP USER ' . $name));
    }

  
    /**
     * Show existing users
     * 
     * @return type ResultSeriesObject 
     */
    public function getUsers()
    {       
        return ResultsetBuilder::buildResultSeries($this->get('query', array('q' => 'SHOW USERS')));
    }

    /**
     * Privilege control - grant privilege
     * 
     * @param string $privilege, it is recommended to user the PRIV_* constants
     * @param type $database
     * @param type $user
     */
    public function grantPrivilege($privilege, $database, $user)
    {
        return($this->get('query', array('q' => 'GRANT ' . $privilege . ' ON ' . $database . ' TO ' . $user)));
    }

    /**
     * Privilege control - revoke privilege
     * 
     * @param string $privilege
     * @param string $database
     * @param string $user
     * @return type
     */
    public function revokePrivilege($privilege, $database, $user)
    {
        return($this->get('query', array('q' => 'REVOKE ' . $privilege . ' ON ' . $database . ' TO ' . $user)));
    }

    /**
     * Set the cluster administrator
     * 
     * @param string $user
     * @return type
     */
    public function setAdmin($user)
    {
        return($this->get('query', array('q' => 'GRANT ALL PRIVILEGES TO ' . $user)));
    }

    /**
     * Revoke cluster administration privilege
     * 
     * @param string $user
     * @return type
     */
    public function deleteAdmin($user)
    {
        return($this->get('query', array('q' => 'REVOKE ALL PRIVILEGES FROM ' . $user)));
    }
    
    
    /**
     * Get database 
     * 
     * @param type $name
     * @return \crodas\InfluxPHP\DB
     */
    public function getDatabase($name)
    {
        return new DB($this, $name);
    }
    
    /**
     * Shortcut for getDatabase
     * 
     * @see getDatabase()
     * @param type $name
     * @return \crodas\InfluxPHP\DB
     */
    public function __get($name)
    {
        return new DB($this, $name);
    }

}
