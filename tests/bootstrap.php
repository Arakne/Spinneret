<?php

$loader = require __DIR__ . '/../vendor/autoload.php';

$_ENV['MYSQL_TEST_HOST'] ??= getenv('MYSQL_TEST_HOST') ?: '172.17.0.1';
$_ENV['MYSQL_TEST_DATABASE'] ??= getenv('MYSQL_TEST_DATABASE') ?: 'test';
$_ENV['MYSQL_TEST_USER'] ??= getenv('MYSQL_TEST_USER') ?: 'test';
$_ENV['MYSQL_TEST_PASSWORD'] ??= getenv('MYSQL_TEST_PASSWORD') ?: '';
$_ENV['FRONT_OFFICE_SECRET'] ??= 'secret';
$_ENV['BACK_OFFICE_SECRET'] ??= 'secret';
putenv('FRONT_OFFICE_SECRET=secret');
putenv('BACK_OFFICE_SECRET=secret');

date_default_timezone_set('Europe/Paris');
putenv('COLUMNS=120');

return $loader;
