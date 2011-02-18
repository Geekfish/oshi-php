<?php
class PawnIgWrapper {
	public $Id, $GameId, $X, $Y, $Status, $PawnId, $Pawn;
	
	public function __construct( $pawnig ) {
		$this->Id 		= $pawnig->Id;
		$this->GameId 	= $pawnig->GameId;
		$this->X 		= $pawnig->X;
		$this->Y 		= $pawnig->Y;
		$this->Status 	= $pawnig->Status;
		$this->PawnId 	= $pawnig->PawnId;
		$this->Pawn 	= New PawnWrapper( New Pawn( $this->PawnId ) );
	}
}

// (Pawn In Game)
// Stupid plural PawnsIg or Pawnsig
class PawnIg {
	/**
	 * Id
	 *
	 * @var int
	 */
	public $Id;
	
	/**
	 * Game Id
	 *
	 * @var int
	 */
	public $GameId;
	
	/**
	 * X
	 *
	 * @var int
	 */
	public $X;
	
	/**
	 * Y
	 *
	 * @var int
	 */
	public $Y;
	
	/**
	 * Status
	 *
	 * @var int
	 */
	public $Status;
	
	/**
	 * Pawn Id
	 *
	 * @var int
	 */
	public $PawnId;
	
	public $Pawn;
	
	private $mExists;
	
	/**
	 * Pawnig Constructor
	 */
	public function __construct( $id = null ) {
			
		$id = mysql_real_escape_string( $id );
		
		$sql = "SELECT * FROM `pawnsig` WHERE `id` = '$id' LIMIT 1";
		
		$res = mysql_query( $sql ) or die( mysql_error() );
		
		if( mysql_num_rows( $res ) ) {
			$row = mysql_fetch_array( $res );
			
			$this->Id      = $row[ 'id' ];
			$this->GameId  = $row[ 'game_id' ];
			$this->X 	   = $row[ 'x' ];	
			$this->Y       = $row[ 'y' ];
			$this->Status  = $row[ 'status' ];
			$this->PawnId  = $row[ 'pawn_id' ];
			
			$this->Pawn = New Pawn( $this->PawnId );
			
			$this->mExists = true;
		}
	}
	
	public function Save() {

		$game_id = mysql_real_escape_string( $this->GameId );
		$x     	 = mysql_real_escape_string( $this->X );
		$y       = mysql_real_escape_string( $this->Y );
		$status  = mysql_real_escape_string( $this->Status );
		$pawn_id = mysql_real_escape_string( $this->PawnId );
		
		if ( $this->mExists ) {
			//Update game entry
			
			$id = mysql_real_escape_string( $this->Id );
			
			$sql = "UPDATE 
						`pawnsig` 
					SET
						`game_id` = '$game_id',
						`x`  	  = '$x',
						`y` 	  = '$y',
						`status`  = '$status',
						`pawn_id` = '$pawn_id'
					WHERE `id` = '$id'
					LIMIT 1";
					
			mysql_query( $sql ) or die( 'Unable to update game: ' . mysql_error() );	
		}
		else {
			//Create a new game
			$sql = "INSERT INTO 
						`pawnsig` 
					(
						`game_id`,
						`x`,
						`y`,
						`status`,
						`pawn_id`
					)
					VALUES
					( 
						'$game_id',
						'$x',
						'$y',
						'$status',
						'$pawn_id'
					)";
					
			mysql_query( $sql ) or die( 'Unable to insert pawnig: ' . mysql_error() );
			
			$this->Id 	   = mysql_insert_id();
			$this->mExists = true;
		}
	}
	
	public function GetPawnsFrom( $directionlower, $from, $to )
	{
		$direction = strtoupper( $directionlower );
		
		if( $directionlower == 'x' ) {
			$stableCoord = 'y';
			$stableCoordValue = $this->Y;
		}
		else {
			$stableCoord = 'x';
			$stableCoordValue = $this->X;
		}
		
		$affectedCoord = $directionlower;
		
		$sql = "SELECT *
				FROM `pawnsig`
				WHERE
					`$stableCoord` = '$stableCoordValue'
				AND
					`$affectedCoord`
						BETWEEN 
							LEAST( '$from', '$to' )
						AND
							GREATEST( '$from', '$to' )
				";
		
		$res = mysql_query( $sql );
		
		return PawnIg::GetPawnsigFromResult( $res );
	}
	
	public function GetPawnsInLine( $directionlower, $distance ) {
		$direction = strtoupper( $directionlower );
		$gameid = $this->GameId;
		if( $distance >= 1 ) {
			$b = 9;
			$affectedCoordValue = $this->$direction + 1;
		}
		else {
			$b = 1;
			$affectedCoordValue = $this->$direction - 1;
		}
		
		if( $directionlower == 'x' ) {
			$stableCoord = 'y';
			$stableCoordValue = $this->Y;
		}
		else {
			$stableCoord = 'x';
			$stableCoordValue = $this->X;
		}
		
		$affectedCoord = $directionlower;
		
		$sql = "SELECT *
				FROM `pawnsig`
				WHERE
					`$stableCoord` = '$stableCoordValue'
				AND `game_id` = '$gameid'
				AND
					`$affectedCoord`
						BETWEEN 
							LEAST( '$affectedCoordValue', '$b' )
						AND
							GREATEST( '$affectedCoordValue', '$b' )
				";
		
		$res = mysql_query( $sql );
		
		return PawnIg::GetPawnsigFromResult( $res );
	 }
	 
	 
	/**
	 * Get Pawnsig From Result
	 * Returns a list of pawnsig using a mysql result resource
	 *
	 * @param resource $result
	 * @return array 
	 */
	 private static function GetPawnsigFromResult( $res ) {
		
		$pawnsig = array();
		
		while( $row = mysql_fetch_array( $res ) ) {
			$pawnig = New PawnIg();
			
			$pawnig->Id      = $row[ 'id' ];
			$pawnig->GameId  = $row[ 'game_id' ];
			$pawnig->X 	     = $row[ 'x' ];	
			$pawnig->Y       = $row[ 'y' ];
			$pawnig->Status  = $row[ 'status' ];
			$pawnig->PawnId  = $row[ 'pawn_id' ];		
			$pawnig->Pawn    = New Pawn( $pawnig->PawnId );
			$pawnig->mExists = true;
			
			$pawnsig[] = $pawnig;
		}
		
		return $pawnsig;
	 }
	 
	 public static function GetPawnsigByGame( $game_id ) {
		$game_id = (integer)$game_id;
		
		$res = mysql_query( "SELECT * FROM `pawnsig` WHERE `game_id` = '$game_id'" );
		return PawnIg::GetPawnsigFromResult( $res );
	 }
	 
	 private static function GetPawnigWrappersFromPawnsig( $pawnsig ) {
		$pawnigwrappers = array();
		foreach ( $pawnsig as $pawnig ) {
			$pawnigwrappers[] = New PawnigWrapper( $pawnig );
		}
		return $pawnigwrappers;
	}
	 
	 public static function GetPawnigWrappersByGame( $game_id ) {
		return PawnIg::GetPawnigWrappersFromPawnsig( PawnIg::GetPawnsigByGame( $game_id ) );
	 }
}
?>