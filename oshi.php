<?php
class Oshi {
	
	/**
	 * Login
	 * Return Values:
	 * 0 = invalid credentials
	 * 1 = success
	 * 2 = wrong password
	 * 3 = email not found
	 * 4 = banned
	 *
	 * @param string $Email
	 * @param string $Password
	 * @return int
	 */
	function UserLogin( $credentials ) {
		 $result = User::Login( $credentials );
		if( $result[ 'userid' ] ) {
			//Sessions only work on the declared server handle class :'(
			$_SESSION[ 'userid' ] = $result[ 'userid' ];
		}
		return $result[ 'res' ];
	}
	
	/**
	 * Logout (void)
	 * Destroys the session
	 *
	 */
	function UserLogout() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = New User( $_SESSION[ 'userid' ] );
			$user->Logout();
			unset( $_SESSION[ 'userid' ] );
		}
	}
	
	/**
	 * Checks the connection status (void)
	 * Returns true on connected,
	 * false if the session has ended
	 *
	 * @return boolean
	 */
	function CheckConnection() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Create a new Game
	 * Returns true on success, false on error
	 *
	 * @return bool
	 */
	function CreateGame() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = New User( $_SESSION[ 'userid' ] );
			return $user->CreateGame();
		}
		return false;
	}
	
	/**
	 * Join an existing game
	 * Arguments: the id of the game we want to connect to
	 * Returns true on success, false on error
	 *
	 * @param int gameId
	 * @return bool
	 */
	function JoinGame( $gameId ) {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = new User( $_SESSION[ 'userid' ] );
			return $user->JoinGame( $gameId );
		}
		return false;
	}
	
	/*
	 * Quit the game (no winner)
	 *
	 */
	function QuitGame() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = new User( $_SESSION[ 'userid' ] );
			$game = $user->GetCurrentGame();
			$game->Close();
		}
	}
	
	/**
	 * Join an existing game
	 * Arguments: the id of the game we want to connect to
	 *
	 * @return GameWrapper
	 */
	function GetCurrentGame() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = new User( $_SESSION[ 'userid' ] );
			return $user->GetCurrentGameWrapper();
		}
		
	}
	
	/**
	 * Gets a list of online users
	 * Returns a list of UserWrapper objects
	 *
	 * @return UserWrapper[]
	 */
	function GetOnlineUsers() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			return User::GetOnlineUsers();
		}
		else {
			return array();
		}
	}
	
	/**
	 * Gets a list of open games
	 * Returns a list of GameWrapper objects
	 *
	 * @return GameWrapper[]
	 */
	function GetOpenGames() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			return Game::GetOpenGames();
		}
		else {
			return array();
		}
	}
	
	/**
	 * When idle, call this at least once per minute
	 * to confirm online status
	 * 
	 * @return boolean
	 */
	function ConfirmIdle() {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = New User( $_SESSION[ 'userid' ] );
			$user->ConfirmIdle();
			return true;
		}
		return false;
	}
	
		/**
	 * When idle, call this at least once per minute
	 * to confirm online status
	 * 
	 * @return boolean
	 */
	function MovePawn( $pawnId, $x, $y ) {
		if( isset( $_SESSION[ 'userid' ] ) ) {
			$user = new User( $_SESSION[ 'userid' ] );
			$game = $user->GetCurrentGame();
			if( $game->IsUserTurn( $user ) ) {
				return $game->MovePawn( $pawnId, $x, $y);
			}
			//throw new SoapFault("Server", "first check" );
		}
		return false;
	}
}
?>