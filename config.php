<?php
$config['db']['host'] = 'localhost';
$config['db']['user'] = 'root';
$config['db']['password'] = '';
$config['db']['dbname'] = 'otelseasy_live';


$config['secret'] = 'subinrabin';


$config['memcached']['host'] = '15.206.192.189';
$config['memcached']['port'] = 22122;


$config['log']['path'] = __DIR__ . '/apilogs/app-'.(date('Y-m-d')).'.log';

return $config;
