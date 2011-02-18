<?php
class GameWrapper {
	public $Id;
	public $WinnerId;
	public $Status;
	public $Start;
	public $End;
	public $OneId;
	public $TwoId;
	public $Turn;
	public $PlayerOne;
	public $PlayerTwo;
	public $Pawnsig;
	
	public function __construct( $game ) {
		$this->Id 			= $game->Id;
		$this->WinnerId 	= $game->WinnerId;
		$this->Status 		= $game->Status;
		$this->Start 		= $game->Start;
		$this->End 			= $game->End;
		$this->OneId 		= $game->OneId;
		$this->TwoId 		= $game->TwoId;
		$this->Turn	 	 	= $game->Turn;
		$this->PlayerOne	= New UserWrapper ( New User( $game->OneId ) );
		$this->PlayerTwo	= New UserWrapper ( New User( $game->TwoId ) );
		$this->Pawnsig 		= PawnIg::GetPawnigWrappersByGame( $game->Id );
	}
}

class Game {
	
	public $Id;
	public $WinnerId;
	public $Status = 'open';
	public $Start;	
	public $End;
	public $Pawnsig;	
	public $OneId;
	public $TwoId;
	public $Turn;
	public $PlayerOne;
	public $PlayerTwo;
	
	private $mExists = false;
	
	/**
	 * Initialize
	 *
	 */
	public function Initialize() {
		Pawn::GenerateSet( $this );
	}
	
	/**
	 * CAUTION:
	 * If I debug this function one more time my brain is going to explode.
	 *
	 * Now it's PREFECT!
	 */
	public function MovePawn( $pawnig_id, $x, $y ) {
		$debug = false;
		
		$pawnig = New PawnIg( $pawnig_id );
		
		//you cannot play a pawn of your opponent
		if( $this->Turn != $pawnig->Pawn->Owner ) {
			return false;
		}
		
		// Define movement direction
		if( $pawnig->X == $x ) {
			$direction = 'Y';
			$directionlower = 'y';
		}	
		else {
			$direction = 'X';
			$directionlower = 'x';
		}
		
		//can be positive or negative.
		$distance = $$directionlower - $pawnig->$direction;
		//get the absolute value
		$absDistance = abs( $distance );
		
		$pawnsInLine = $pawnig->GetPawnsInLine( $directionlower, $distance );
		
		if( $debug ) {
			echo "Starting Pawn Coordinates:
					X: " . $pawnig->X . "
					Y: " . $pawnig->Y . "
				Destination Coordinates :
					X: $x
					Y: $y
				Distance: $distance
				Absolute Distance : $absDistance
						";
		}
		//Same spot :(
		if( $pawnig->X == $x && $pawnig->Y == $y ) {
			return false;
		}
		//Diagonal :(
		if( $pawnig->X != $x && $pawnig->Y != $y ) {
			return false;
		}
		//Cannot move more squares than the pawns power level (1-3) :'(
		if( $pawnig->Pawn->Power < $absDistance ) {
			return false;
		}

		//for each square we need to pass to reach our destination...
		for( $i = 1; $i <= $absDistance; $i++ ) {
			if( $debug ) {
				echo "
				==================================
				Moving Pawn ( Step $i )... 
				";
			}
			
			//change the coords
			if( $distance >= 1 ) {
				++$pawnig->$direction;
			}
			else {
				--$pawnig->$direction;
			}
			if( $debug ) {
				echo "Pawn Coordinates:
					X: " . $pawnig->X . "
					Y: " . $pawnig->Y . "
							
				Pawn Power: " . $pawnig->Pawn->Power . "
				";
			}
			$currentX = $pawnig->X;
			$currentY = $pawnig->Y;
			if( $debug ) {
				echo "
				Moving pawns in line...
				";
			}
			$affectedPawns = 0;
			$affectedPawnsArray = array();
			
			
			for( $j = 1; $j <= $absDistance; $j++ ) {
				if( $affectedPawnArray = $this->GetPawnsByCoordsInArray( $pawnsInLine ,$currentX, $currentY ) ) {
					//print_r( $affectedPawnArray );
					$affectedPawn    = clone $affectedPawnArray[ 'pawnig' ];
					$affectedPawnKey = $affectedPawnArray[ 'key' ];
					$affectedPawns += 1;
					
					if( $affectedPawns > $pawnig->Pawn->Power ) {
						if( $debug ) {
							echo "INVALID MOVE: Affected pawns greater than pawn power";
						}
						return false;
					}
					
					//Move affected pawn to new position
					if( $distance >= 1 ) {
						$affectedPawn->$direction += 1;
					}
					else {
						$affectedPawn->$direction -= 1;
					}
					$affectedPawnsArray[$affectedPawnKey] = $affectedPawn;
					
					$currentX = $affectedPawn->X;
					$currentY = $affectedPawn->Y;
					
					if( $debug ) {
						echo "	NEW Coords: $currentX, $currentY )";
					}	
				}
			}
			if( $affectedPawns ) {
				foreach( $affectedPawnsArray as $key => $pawn ) {
					$pawnsInLine[$key] = clone $pawn;
				}
			}
		}
		
		//Save all pawns and check for casualties
		foreach ( $pawnsInLine as $pawnInLine ) {
			if( $pawnInLine->X > 9 || $pawnInLine->X < 1 ||
				$pawnInLine->Y > 9 || $pawnInLine->Y < 1 ) {
					//dieeee pawn, dieeee...
					$pawnInLine->Status = 0; 
					
				}
			$pawnInLine->Save();
		}

		$pawnig->Save();
		
		$this->ChangeTurn();
		
		return true;
		//Insert holy handgrenade sound *here*
	}
	
	private function ChangeTurn() {
		$this->Turn = ($this->Turn == 1)? 2 : 1;
		$this->Save();
	}
	
	public function IsUserTurn( $user ) {
		if( $this->Turn == 1 && $user->Id == $this->OneId ||
			$this->Turn == 2 && $user->Id == $this->TwoId ) {
			return true;
		}
		return false;
	}
	/**
	 * Game Class Constructor
	 *
	 * @param int $id [optional]
	 */
	public function __construct( $id = null ) {
		
		if( $id ) {
			$id = mysql_real_escape_string( $id );
			
			$sql = "SELECT * FROM `games` WHERE `id` = '$id' LIMIT 1";

			
			$res = mysql_query( $sql ) or die( mysql_error() );
			
			if( mysql_num_rows( $res ) ) {
				$row = mysql_fetch_array( $res );
				
				$this->Id        = $row[ 'id' ];
				$this->WinnerId  = $row[ 'winner_id' ];
				$this->Status    = $row[ 'status' ];	
				$this->Status    = $row[ 'status' ];	
				$this->Start     = $row[ 'start' ];
				$this->End		 = $row[ 'end' ];
				$this->OneId	 = $row[ 'one_id' ];
				$this->TwoId	 = $row[ 'two_id' ];
				$this->Turn	 	 = $row[ 'turn' ];
				$this->PlayerOne = New User( $this->OneId );
				$this->PlayerTwo = New User( $this->TwoId );
				$this->Pawnsig   = PawnIg::GetPawnsigByGame( $this->Id );
				$this->mExists = true;
			}
		}
		else {
			$this->Turn = 1;
		}
	}
	
	/**
	 * End the game
	 * set winner id to 0 (default) if the game was cancelled
	 *
	 * @param int $winnerId
	 * @return bool
	 */
	public function Close( $winnerId = 0 ) {
		$this->End = date( 'Y-m-d H:i:s' );
		if( $winnerId ) {
			$status = 'closed';
			$this->WinnerId = $winnerId;
		}
		else {
			$status = 'dropped';
		}
		if( $this->OneId != 0 ) {
			$this->PlayerOne->IsInGame = 0;
			$this->PlayerOne->Save();
		}
		if( $this->TwoId != 0 ) {
			$this->PlayerTwo->IsInGame = 0;
			$this->PlayerTwo->Save();
		}
		$this->Status = $status;
		$this->Save();
	}
	
	/**
	 * Sets one of the players in game
	 * Returns true on success, false if the place is already occupied
	 *
	 * @param int $id
	 * @param int $playerNum
	 * @return bool
	 */
	public function SetPlayer( $id, $playerNum ) {
		
		if( $playerNum == 1 ) {
			$textNum = 'One';
		}
		else {
			$textNum = 'Two';
		}
		
		$idPropertyName = $textNum . 'Id';
		$this->$idPropertyName = $id;
		//throw new SoapFault("Server", "trouble" );
		$this->Save();
		return true;
	}
	
	/**
	 * Set up the initial pawn set
	 *
	 */
	public function SetUpPawns() {
		Pawn::GenerateSet();
	}
	
	
	/**
	 * Get all the available open games
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return Game[]
	 */
	public static function GetOpenGames( $offset = 0, $limit = 20 ) {
		$offset = (integer) $offset;
		$limit  = (integer) $limit;
		
		$sql = "SELECT * FROM `games` WHERE `status` = 'open' LIMIT $offset, $limit";
		$res = mysql_query( $sql ) or die ( "Could not get open games" . mysql_error() );
		
		return Game::GetGameWrappersFromGames( Game::GetGamesFromResult( $res ) );
	}
	
	// TODO
	// Documentation
	private static function GetGameWrappersFromGames( $games ) {
		$gamewrappers = array();
		foreach ( $games as $game ) {
			$gamewrappers[] = New GameWrapper( $game );
		}
		return $gamewrappers;
	}
	public function GetPawnsByCoordsInArray($array,$x, $y) {
		foreach( $array as $key => $pawnig ) {
			if( $pawnig->X == $x && $pawnig->Y == $y ) {
				return array( 'key' => $key, 'pawnig' => $pawnig );
			}
		}
		return false;
	}
	public function GetPawnByCoords( $x, $y ) {
		$id = (integer)$this->Id;
		$x  = (integer)$x;
		$y  = (integer)$y;
		$sql = "SELECT `id`
				FROM `pawnsig`
				WHERE
					`game_id` = '$id'
				AND
					`x` = $x
				AND
					`y` = $y
				LIMIT 1;
				";
		
		$res = mysql_query( $sql );
		
		if( mysql_num_row( $res ) ) {
			$row = mysql_fetch_array($res);
			return New Pawnig( $row[ 'id' ] );
		}
		return false;
	 }
	// TODO
	// Documentation
	private static function GetGamesFromResult( $result ) {
		$games = array();
		
		while( $row = mysql_fetch_array( $result ) ) {
			$game = New Game();
			
			$game->Id        = $row[ 'id' ];
			$game->WinnerId  = $row[ 'winner_id' ];
			$game->Status	 = $row[ 'status' ];
			$game->Start	 = $row[ 'start' ];
			$game->End		 = $row[ 'end' ];
			$game->OneId	 = $row[ 'one_id' ];
			$game->TwoId	 = $row[ 'two_id' ];
			$game->Turn	 	 = $row[ 'turn' ];
			$game->PlayerOne = New User( $game->OneId );
			$game->PlayerTwo = New User( $game->TwoId );
			$game->Pawnsig   = PawnIg::GetPawnsigByGame( $game->Id );
			$game->mExists	 = true;
			$games[] = $game;
		}
		
		return $games;
	}
	
	public function Save() {
		$winner_id  = mysql_real_escape_string( $this->WinnerId );
		$status     = mysql_real_escape_string( $this->Status );
		$start      = mysql_real_escape_string( $this->Start );
		$end        = mysql_real_escape_string( $this->End );
		$one_id     = mysql_real_escape_string( $this->OneId );
		$two_id     = mysql_real_escape_string( $this->TwoId );
		$turn       = mysql_real_escape_string( $this->Turn );
		
		if ( $this->mExists ) {
			//Update game entry
			
			$id = mysql_real_escape_string( $this->Id );
			
			$sql = "UPDATE 
						`games` 
					SET
						`winner_id`  = '$winner_id',
						`status` 	 = '$status',
						`start`		 = '$start',
						`end` 		 = '$end',
						`one_id` 	 = '$one_id',
						`two_id`     = '$two_id',
						`turn`		 = '$turn'
					WHERE `id` = '$id'
					LIMIT 1";
					
			mysql_query( $sql ) or die( 'Unable to update game: ' . mysql_error() );	
		}
		else {
			//Create a new game
			$sql = "INSERT INTO 
						`games` 
					(
						`winner_id`,
						`status`,
						`start`,
						`end`,
						`one_id`,
						`two_id`,
						`turn`
					)
					VALUES
					( 
						'$winner_id',
						'$status',
						'$start',
						'$end',
						'$one_id',
						'$two_id',
						'$turn'
					)";
					
			mysql_query( $sql ) or die( 'Unable to insert user: ' . mysql_error() );
			
			$this->Id 	   = mysql_insert_id();
			$this->mExists = true;
		}	
	}
}
?>