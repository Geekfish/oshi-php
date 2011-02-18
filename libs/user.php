<?php
class UserWrapper {
	public $Id;
	public $Name;
	public $Level;
	public $IsOnline;
	public $IsInGame;
	public $LastLogin;
	public $LastPing;
	
	public function __construct( $user ) {
		$this->Id 		 = $user->Id;
		$this->Name 	 = $user->Name;
		$this->Level 	 = $user->Level;
		$this->IsOnline  = $user->Level;
		$this->IsInGame  = $user->Level;
		$this->LastLogin = $user->LastLogin;
		$this->LastPing  = $user->LastPing;
	}
}

class User {
	

	
	
	public $Id;
	public $Name;
	public $IsOnline;
	public $IsInGame;
	public $LastLogin;
	public $LastPing;
	
	/**
	 * User Level
	 * 1 = banned
	 * 2 = normal
	 * 5 = moderator
	 * 8 = administrator
	 *
	 */
	public $Level;
	
	private $mCredentials;
	private $mExists = false;
	
	/**
	 * Class Construct
	 */
	public function __construct( $constructor = null ) {

		if( is_numeric( $constructor ) ) {
			$id = $constructor;
			
			$id = mysql_real_escape_string( $id );
			
			$sql = "SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1";
		}
		//is string...
		else {
			$email = $constructor;
			
			$email = mysql_real_escape_string( $email );
			
			$sql = "SELECT * FROM `users` WHERE `email` = '$email' LIMIT 1";
		}
		
		$res = mysql_query( $sql ) or die( mysql_error() );
		
		if( mysql_num_rows( $res ) ) {
			$row = mysql_fetch_array( $res );
			
			$this->Id        = $row[ 'id' ];
			$this->mEmail    = $row[ 'email' ];
			$this->mPassword = $row[ 'password' ];	
			$this->Name      = $row[ 'name' ];
			$this->Level     = $row[ 'level' ];
			$this->IsOnline  = $row[ 'is_online' ];
			$this->IsInGame  = $row[ 'is_ingame' ];
			$this->LastLogin = $row[ 'last_login' ];
			$this->LastPing  = $row[ 'last_ping' ];
			
			$this->mExists = true;
		}
	}
	
	/**
	 * User Login
	 * Return Values:
	 * [res]
	 * 0 = invalid credentials
	 * 1 = success
	 * 2 = wrong password
	 * 3 = email not found
	 * 4 = banned
	 * [userid]
	 *
	 *
	 * @param string $email
	 * @param string $password
	 * @return array
	 */
	 
	public static function Register( $username, $password, $email )  {
	
		if ( $username && $password && $email ) {
		
			$user = new User( $email );
			
			$user->Name 		= $username;
			$user->mPassword 	= $password;
			$user->mEmail 		= $email;
			$user->Level 		= 2;
			
			if( !$user->mExists ) {
				$user->Save();
				return 'success'; 
			} else {
				return 'exists';
			}
		
		} else {
			return 'missing';
		}
	}
	 
	public static function Login( UserCredentials $credentials ) {
		//Credentials check
		$res = 0;
		if( $credentials->Email && $credentials->Password ) {
			$res = 3;
			$user = new User( $credentials->Email );
			if( $user->mExists ) {
				$res = 2;
				if( $user->CheckPassword( $credentials->Password ) ) {
					$res = 4;
					//check if user is banned
					if( !$user->IsBanned() ) {
						//Login :)
						$user->IsOnline  = true;
						$user->LastLogin = date( 'Y-m-d H:i:s' );
						$user->Save();
						$res = 1;
					}
				}
			}
		}
		return array( 'res' => $res, 'userid' => $user->Id );
	}
	public function GetCurrentGameWrapper() {
		return New GameWrapper( $this->GetCurrentGame() );
	}
	
	public function GetCurrentGame() {
		$id = $this->Id;
		$sql = "SELECT `id` FROM `games`
				WHERE 
					`status` NOT IN ('closed', 'dropped' )
					AND
					`one_id` = $id
				OR
					`status` NOT IN ('closed', 'dropped' )
					AND
					`two_id` = $id
				LIMIT 1";
		$res = mysql_query( $sql );
		$row = mysql_fetch_array( $res );
		return New Game( $row[ 'id' ] );
	}
	
	//Update the user's last ping timestamp
	public function ConfirmIdle() {
		$this->IsOnline = 1;
		$this->LastPing = date( 'Y-m-d H:i:s' );
		$this->Save();
	}
	
	// Perform logout cleanup
	public function Logout() {
		$this->IsOnline = 0;
		$this->IsInGame = 0;
		$this->LastPing = date( 'Y-m-d H:i:s' );
		$this->Save();
	}
	
	// Insert a new row, or update it with the changes
	public function Save() {
		
		$name 	    = mysql_real_escape_string( $this->Name );
		$password   = mysql_real_escape_string( $this->mPassword );
		$email 	    = mysql_real_escape_string( $this->mEmail );
		$level	    = mysql_real_escape_string( $this->Level );
		$is_online  = mysql_real_escape_string( $this->IsOnline );
		$is_ingame  = mysql_real_escape_string( $this->IsInGame );
		$last_login = mysql_real_escape_string( $this->LastLogin );
		$last_ping  = mysql_real_escape_string( $this->LastPing );
		
		if ( $this->mExists ) {
			//Update user entry
			
			$id = mysql_real_escape_string( $this->Id );
			
			$sql = "UPDATE 
						`users` 
					SET
						`name` 		 = '$name',
						`password` 	 = '$password',
						`email` 	 = '$email',
						`level`		 = '$level',
						`is_online`  = '$is_online',
						`is_ingame`  = '$is_ingame',
						`last_login` = '$last_login',
						`last_ping`  = '$last_ping'
					WHERE `id` = '$id'
					LIMIT 1";
					
			mysql_query( $sql ) or die( 'Unable to update user: ' . mysql_error() );	
		}
		else {
			//Create a new user
			$sql = "INSERT INTO 
						`users` 
					(
						`name`,
						`password`,
						`email`,
						`level`,
						`is_online`,
						`is_ingame`,
						`last_login`,
						`last_ping`
					)
					VALUES
					( 
						'$name',
						'$password',
						'$email',
						'$level',
						'$is_online',
						'$is_ingame',
						'$last_login',
						'$last_ping'
					)";
					
			mysql_query( $sql ) or die( 'Unable to insert user: ' . mysql_error() );
			
			$this->Id 	   = mysql_insert_id();
			$this->mExists = true;
		}	
	}
	 
	/**
	 * Checks if the user is banned
	 *
	 * @return bool
	 */
	public function IsBanned() {
		return $this->Level == 1;
	}
	
	/**
	 * Returns a list of User objects using a mysql result resource
	 *
	 * @param resource $result
	 * @return array 
	 */
	private static function GetUsersFromResult( $result ) {
		$users = array();
		
		while( $row = mysql_fetch_array( $result ) ) {
			$user = New User();
			
			$user->Id        = $row[ 'id' ];
			$user->mEmail    = $row[ 'email' ];
			$user->mPassword = $row[ 'password' ];	
			$user->Name      = $row[ 'name' ];
			$user->Level     = $row[ 'level' ];
			$user->IsOnline  = $row[ 'is_online' ];
			$user->IsInGame  = $row[ 'is_ingame' ];
			$user->LastLogin = $row[ 'last_login' ];
			$user->LastPing  = $row[ 'last_ping' ];
			$user->mExists   = true;
			
			$users[] = $user;
		}
		
		return $users;
	}
	
	/**
	 * Returns a list of UserWrapper objects from a db result resource
	 *
	 * @param resource $result
	 * @return array 
	 */
	private static function GetUserWrappersFromResult( $result ) {
		$users = array();
		
		while( $row = mysql_fetch_array( $result ) ) {
			$user = New UserWrapper( New User( $row[ 'id' ] ) );			
			$users[] = $user;
		}
		
		return $users;
	}
	
	/**
	 * Get a list of online users
	 * Returns a SELECT mysql result resource
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return array 
	 */
	public static function GetOnlineUsers( $offset = 0, $limit = 20 ) {
		$offset = (integer) $offset;
		$limit  = (integer) $limit;
		
		$sql = "SELECT * FROM `users` WHERE `is_online` = 1 LIMIT $offset, $limit";
		$res = mysql_query( $sql ) or die ( "Could not get online users" . mysql_error() );
		
		return User::GetUserWrappersFromResult( $res );
	}
	
	/**
	 * Create a new game
	 * Returns: True on Success / False on Failure
	 *
	 * @return Game
	 */
	public function CreateGame() {
		if( !$this->IsInGame ) {
			$game = New Game();
			
			$game->SetPlayer( $this->Id, 1 );
			$game->Status = 'open';
			$game->Save();
			$this->IsInGame = true;
			$this->Save();
			return true;
		}
		return false;
	}
	
	/**
	 * Join an existing game
	 * Returns: True on Success / False on Failure
	 *
	 * @param int $gameId
	 * @return bool 
	 */
	public function JoinGame( $gameId ) {

		$game = New Game( $gameId );
		if( !$game->SetPlayer( $this->Id, 2 ) ) {
			return false;
		}
		$this->IsInGame = true;
		$game->Status   = 'inplay';
		$game->Start    = date( 'Y-m-d H:i:s' );
		$game->Initialize();
		$this->Save();
		$game->Save();
		return true;
	}
	
	/**
	 * Checks the user's password
	 *
	 * @param string $password
	 * @return bool
	 */
	private function CheckPassword( $password ) {
		//TODO: add encryption:
		//return md5( $password ) == $this->mPassword;
		return $password == $this->mPassword;
	}
	
	
	// CRON JOBS
	// Runs once per minute to clean up inactive users status
	public static function StatusCleanup() {
		$sql = "UPDATE `users`
				SET 
				 `is_online` = 0,
				 `is_ingame` = 0
				WHERE
				 `last_ping` < ( NOW() - INTERVAL 1 MINUTE )
				";
				
		mysql_query( $sql );
	}
	
	// Todo: clean dropped.
}
?>