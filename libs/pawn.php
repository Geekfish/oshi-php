<?php
class PawnWrapper {
	public $Id, $StartX, $StartY, $Power, $Owner;
	
	public function __construct( $pawn ) {
		$this->Id 		= $pawn->Id;
		$this->StartX 	= $pawn->StartX;
		$this->StartY 	= $pawn->StartY;
		$this->Power 	= $pawn->Power;
		$this->Owner    = $pawn->Owner;
	}
}

class Pawn {

	public $Id;
	public $StartX;
	public $StartY;
	public $Power;
	public $Owner;
	
	/**
	 * Generate a new Pawn Set (pawns ig list) (void)
	 */ 
	public static function GenerateSet( $game ) {
		$game_id = (integer)$game->Id;
		$sql = "INSERT INTO `pawnsig`
				( `game_id`, `status`, `x`, `y`, `pawn_id` )
				SELECT '$game_id', 1, `startx`, `starty`, `id` FROM `pawns`";
		
		mysql_query( $sql ) or die( mysql_error() );
	}
	
	/**
	 * Class Constructor
	 */
	public function __construct( $id ) {
			
		$id = mysql_real_escape_string( $id );
		
		$sql = "SELECT * FROM `pawns` WHERE `id` = '$id' LIMIT 1";
		
		$res = mysql_query( $sql ) or die( mysql_error() );
		
		if( mysql_num_rows( $res ) ) {
			$row = mysql_fetch_array( $res );
			
			$this->Id      = $row[ 'id' ];
			$this->StartX  = $row[ 'startx' ];
			$this->StartY  = $row[ 'starty' ];	
			$this->Power   = $row[ 'power' ];
			$this->Owner   = $row[ 'owner' ];			
			$this->mExists = true;
		}
	}
}
?>