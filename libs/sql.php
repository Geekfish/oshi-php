<?php
/**
 * mySQL Connection
 *
 */
 
 //Settings
 $db_hostname = '';
 $db_username = '';
 $db_password = '';
 $db_name	  = '';
 
 //Connection
$link = mysql_connect( $db_hostname, $db_username, $db_password ) 
	or die( 'Failed to connect: ' . mysql_error() );

//Database Selection
$db_selected = mysql_select_db( $db_name, $link );


function logevent( $text ) {
	$sql = "
		INSERT INTO `log` (
			`text`
		)
		VALUES (
			'$text'
		);
	";
	
	$query = mysql_query( $sql );
}

?>