InfluxDB [![Build Status](https://travis-ci.org/crodas/InfluxPHP.png?branch=master)](https://travis-ci.org/crodas/InfluxPHP)
========

Simple PHP client for [InfluxDB](http://influxdb.org/), an open-source, distributed, time series, events, and metrics database with no external dependencies.

How to install it
-----------------

The easiest way is to install it via [composer](http://getcomposer.org)

```bash
composer require crodas/influx-php:\*
```

How to use it
-------------

You need to create a client object.

```php
$client = new \crodas\InfluxPHP\Client(
   "localhost" /*default*/,
   8086 /* default */,
   "root" /* by default */,
   "root" /* by default */
);
```

The first time you should create an database.

```php
$db = $client->createDatabase("foobar");
$db->createUser("foo", "bar"); // <-- create user/password
```

Create data is very simple.

```php
$db = $client->foobar;
$db->insert("some label", ['foobar' => 'bar']); // single input
$db->insert("some label", [
    ['foobar' => 'bar'],
    ['foobar' => 'foo'],
]); // multiple input, this is better :-)
```

Now you can get the database object and start querying.

```php
$db = $client->foobar;
// OR
$db = $client->getDatabase("foobar");

foreach ($db->query("SELECT * FROM foo;") as $row) {
    var_dump($row, $row->time);
}
```
