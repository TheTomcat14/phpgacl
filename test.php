<?php
require_once __DIR__ . '/vendor/autoload.php';

require_once './src/Gacl.php';
require_once './src/GaclApi.php';

$stream = new Monolog\Handler\StreamHandler('php://stdout', Monolog\Logger::DEBUG);
$stream = new Monolog\Handler\StreamHandler(__DIR__ . '/stdout.log', Monolog\Logger::DEBUG);

// Create the main logger of the app
$logger = new Monolog\Logger('phpgacl_logger');
$logger->pushHandler($stream);

$options = [
    'db_type' 		  => "mysqli",
    'db_host'         => "localhost",
    'db_user'		  => "root",
    'db_password'	  => "",
    'db_name'		  => "gdpr-tool-contabo-20191011",
    'db_table_prefix' => "phpgacl_",
    'logger'          => $logger,
];
$phpgacl = new \Higis\PhpGacl\GaclApi($options);

var_dump($phpgacl->aclCheck('rasci', 'R', 'user', 'user_1', 'admin', 'module_access'));
var_dump($phpgacl->aclCheck('rasci', 'R', 'user', 'user_1', 'organisation-data-register', 'module_access'));

