<?php
/**
 * phpGACL - Generic Access Control List
 * Copyright (C) 2002 Mike Benoit
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please join the
 * phpGACL mailing list. http://sourceforge.net/mail/?group_id=57103
 *
 * You may contact the author of phpGACL by e-mail at:
 * ipso@snappymail.ca
 *
 * The latest version of phpGACL can be obtained from:
 * http://phpgacl.sourceforge.net/
 */
require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/../src/Gacl.php';
require_once dirname(__FILE__) . '/../src/GaclApi.php';
require_once dirname(__FILE__) . '/../src/GaclAdminApi.php';

// phpGACL Configuration file.
if (!isset($configFile)) {
    $configFile = dirname(__FILE__) . '/../gacl.ini.php';
}

//Values supplied in $gaclOptions array overwrite those in the config file.
if (file_exists($configFile)) {
    $config = parse_ini_file($configFile);

    if (is_array($config)) {
        if (isset($gaclOptions)) {
            $gaclOptions = array_merge($config, $gaclOptions);
        } else {
            $gaclOptions = $config;
        }
    }
    unset($config);
}

if (class_exists('Monolog\Logger')) {
    $stream = new Monolog\Handler\StreamHandler(__DIR__ . '/admin-interface.log', Monolog\Logger::DEBUG);
    // Create the main logger of the app
    $logger = new Monolog\Logger('phpgacl_logger');
    $logger->pushHandler($stream);

    $gaclOptions['logger'] = $logger;
}

if (class_exists('Laminas\Cache\StorageFactory')) {
    /*
    $storage = Laminas\Cache\StorageFactory::factory(
        [
            'adapter' => [
                'name'    => 'memory',
            ],
        ]
    );

    $pool = new Laminas\Cache\Psr\CacheItemPool\CacheItemPoolDecorator($storage);

    $gaclOptions['cache'] = $pool;
    */
}

$gaclApi = new Higis\PhpGacl\GaclAdminApi($gaclOptions);

$gacl = &$gaclApi;

$db = &$gacl->db;


//Setup the Smarty Class.
// require_once($gaclOptions['smarty_dir'].'/Smarty.class.php');

$smarty = new Smarty();

$smarty->compile_check = true;
$smarty->template_dir  = $gaclOptions['smarty_template_dir'];
$smarty->compile_dir   = $gaclOptions['smarty_compile_dir'];
$smarty->config_dir    = $gaclOptions['smarty_config_dir'];

/*
 * Email address used in setup.php, please do not change.
 */
$authorEmail = 'ipso@snappymail.ca';

/*
 * Don't need to show notices, some of them are pretty lame and people get overly worried when they see them.
 * Mean while I will try to fix most of these. ;) Please submit patches if you find any I may have missed.
 */
error_reporting(E_ALL ^ E_NOTICE);
