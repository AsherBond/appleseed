<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   User
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** User Component
 * 
 * User Component Entry Class
 * 
 * @package     Appleseed.Components
 * @subpackage  User
 */
class cUser extends cComponent {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct();
	}
	
	public function Current ( $pData = null ) {
		$this->_Test++;
		
		$AuthUser = new cUserAuthorization ( );
		
		$AuthUser->LoggedIn();
		
		return ( $AuthUser );
	}
	
}

/** User Authorization Class
 * 
 * Returned by Current function
 * 
 * @package     Appleseed.Components
 * @subpackage  User
 */
class cUserAuthorization extends cBase {
	
	public $Username;
	public $Fullname;
	public $Domain;
	public $Remote;
	public $Anonymous;
	
	public function LoggedIn () {
		
      	$loginSession = isset($_COOKIE["gLOGINSESSION"]) ?  $_COOKIE["gLOGINSESSION"] : "";
      	
      	// Load the session information.
      	$sessionModel = new cModel ( "userSessions" );
      	$sessionModel->Retrieve ( array ( "Identifier" => $loginSession ) );
      	$sessionModel->Fetch();
      	
      	if ( !$sessionModel->Get ( "userAuth_uID" ) ) return ( false );
      	
      	// Load the user account information.
      	$userModel = new cModel ( "userAuthorization" );
      	$userModel->Retrieve ( array ( "uID" => $sessionModel->Get ( "userAuth_uID" ) ) );
      	$userModel->Fetch();
      	
      	if ( !$userModel->Get ( "Username" ) ) return ( false );
      	
      	$this->Username = $userModel->Get ( "Username" );
      	
      	// Load the user profile information.
      	$profileModel = new cModel ( "userProfile" );
      	$profileModel->Retrieve ( array ( "userAuth_uID" => $sessionModel->Get ( "userAuth_uID" ) ) );
      	$profileModel->Fetch();
      	
      	$this->Fullname = $profileModel->Get ( "Fullname" );
      	$this->Domain = $_SERVER['HTTP_HOST'];
      	
      	$this->Remote = false;
      	
      	return ( true );
      	
	}
	
}
