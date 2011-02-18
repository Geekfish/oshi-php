<?php
session_start();
require_once( "libs/sql.php" );
require_once( "libs/game.php" );
require_once( "libs/pawn.php" );
require_once( "libs/pawnig.php" );
require_once( "libs/user.php" );
require_once( "libs/usercredentials.php" );
require_once( "oshi.php" );


$credentials = New UserCredentials();
$credentials->Email = '';
$credentials->Password = ''

$oshi = New Oshi();
$game = New Game( 1 );
?><pre><?php
//print_r ( $game->Pawnsig );
//$pawnig = New PawnIg( 80 );
//print_r( $pawnig->GetPawnsInLine( 'y', -3 ) );
$game->MovePawn( 1, 4, 3 );
//var_dump( $oshi->UserLogin( $credentials ) );
//var_dump( $oshi->UserLogout() );
?>
</pre><?php
?>