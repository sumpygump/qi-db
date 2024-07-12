<?php

/**
 * Bootstrap for launching tests
 *
 * @package Qi\Db\Tests
 */

// phpcs:disable PSR1.Files.SideEffects

require_once '../vendor/autoload.php';

define('MYSQL_HOST', getenv('QI_MYSQL_HOST') ? getenv('QI_MYSQL_HOST') : 'localhost');
define('MYSQL_USER', getenv('QI_MYSQL_USER') ? getenv('QI_MYSQL_USER') : 'root');
define('MYSQL_PASS', getenv('QI_MYSQL_PASS') ? getenv('QI_MYSQL_PASS') : 'password');
define('MYSQL_DB', getenv('QI_MYSQL_DB') ? getenv('QI_MYSQL_DB') : 'test1');

define('POSTGRES_HOST', getenv('QI_POSTGRES_HOST') ? getenv('QI_POSTGRES_HOST') : 'localhost');
define('POSTGRES_USER', getenv('QI_POSTGRES_USER') ? getenv('QI_POSTGRES_USER') : 'qi_user');
define('POSTGRES_PASS', getenv('QI_POSTGRES_PASS') ? getenv('QI_POSTGRES_PASS') : 'password');
define('POSTGRES_DB', getenv('QI_POSTGRES_DB') ? getenv('QI_POSTGRES_DB') : 'qi_test');
