<?php
session_start();
require_once( "libs/sql.php" );
require_once( "libs/game.php" );
require_once( "libs/pawn.php" );
require_once( "libs/pawnig.php" );
require_once( "libs/user.php" );
require_once( "libs/usercredentials.php" );
require_once( "oshi.php" );

ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '0'); 

$server = new SoapServer( 'http://lostkingdom.net/oshi.wsdl' );
$server->setClass( 'Oshi' );
$server->handle();
?>