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

class BaseHTTP
{
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $base;
    protected $timePrecision = 's';
    protected $children = array();

    const SECOND        = 's';
    const MILLISECOND   = 'm';
    const MICROSECOND   = 'u';
    const S     = 's';
    const MS    = 'm';
    const US    = 'u';

    protected function inherits(BaseHTTP $c)
    {
        $this->user   = $c->user;
        $this->pass   = $c->pass;
        $this->port   = $c->port;
        $this->host   = $c->host;
        $this->timePrecision = $c->timePrecision;
        $c->children[] = $this;
    }

    protected function getCurl($url, array $args = array())
    {
        $args = array_merge($args, array('u' => $this->user, 'p' => $this->pass));
        $url  = "http://{$this->host}:{$this->port}/{$this->base}{$url}";
        $url .= "?" . http_build_query($args);
        $ch   = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $ch;
    }

    protected function execCurl($ch, $json = false)
    {
        $response = curl_exec ($ch);
        $status   = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$type     = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($status[0] != 2) {
            $response = print_r(json_decode($response), true);
            throw new \RuntimeException($response);
        }
        return $json ? json_decode($response, true) : $response;
    }

    protected function delete($url)
    {
        $ch = $this->getCurl($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "DELETE",
        ));

        return $this->execCurl($ch);
    }

    public function getTimePrecision()
    {
        return $this->timePrecision;
    }

    public function setTimePrecision($p)
    {
        switch ($p) {
        case 'm':
        case 's':
        case 'u':
            $this->timePrecision = $p;
            if ($this instanceof Client) {
                foreach ($this->children as $children) {
                    $children->timePrecision = $p;
                }
            }
            return $this;
        }

        throw new \InvalidArgumentException("Expecting s, m or u as time precision");
    }

    protected function get($url, array $args = array())
    {
        $ch = $this->getCurl($url, $args);
        return $this->execCurl($ch, true);
    }

    
    protected function post($url, array $body, array $args = array())
    {
        $ch = $this->getCurl($url, $args);
        curl_setopt_array($ch, array(
            CURLOPT_POST =>  1,
            CURLOPT_POSTFIELDS => json_encode($body),
        ));

        return $this->execCurl($ch);
    }

}
