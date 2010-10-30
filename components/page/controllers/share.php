<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   Page
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Page Component Share Controller
 * 
 * Page Component Share Controller Class
 * 
 * @package     Appleseed.Components
 * @subpackage  Page
 */
class cPageShareController extends cController {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct( );
	}
	
	public function Display ( $pView = null, $pData = array ( ) ) {
		
		$this->_Focus = $this->Talk ( 'User', 'Focus' );
		$this->_Current = $this->Talk ( 'User', 'Current' );
		
		$friends = $this->Talk ( 'Friends', 'Friends' );
		
		if ( $this->_Current->Account == $this->_Focus->Account ) { 
		} else if ( !$this->_Current ) {
			return ( false );
		} else if ( !in_array ( $this->_Current->Account, $friends ) ) {
			return ( false );
		}
		
		$this->View = $this->GetView ( 'share' );
		
		$privacyData = array ( 'start' => $start, 'step'  => $step, 'total' => $total, 'link' => $link );
		$privacyControls =  $this->View->Find ('.privacy', 0);
		$privacyControls->innertext = $this->GetSys ( 'Components' )->Buffer ( 'privacy', $pageData ); 
		
		$this->View->Find ( '[name=Context]', 0 )->value = $this->Get ( 'Context' );
		
		$this->View->Display();
		
		return ( true );
	}
	
	public function Share ( $pView = null, $pData = array ( ) ) {
		
		$this->Model = $this->GetModel (); 
		
		$this->_Focus = $this->Talk ( 'User', 'Focus' );
		$this->_Current = $this->Talk ( 'User', 'Current' );
		
		if ( !$this->_Current ) {
			$this->GetSys ( 'Foundation' )->Redirect ( 'common/403.php' );
			return ( false );
		}
		
		$Owner = $this->_Current->Account;
		$Content = $this->GetSys ( 'Request' )->Get ( 'Content' );
		$Privacy = $this->GetSys ( 'Request' )->Get ( 'Privacy' );
		
		$friends = $this->Talk ( 'Friends', 'Friends' );
		
		$Current = false;
		if ( $this->_Focus->Account == $this->_Current->Account ) $Current = true;
		
		$this->Model->Post ( $Content, $Privacy, $this->_Focus->Id, $Owner, $Current );
		
		$Identifier = $this->Model->Get ( 'Identifier' );
		
		foreach ( $friends as $f => $friend ) {
			if ( $friend == $this->_Current->Account ) continue;
			$Access = $this->Talk ( 'Privacy', 'Check', array ( 'Requesting' => $friend, 'Type' => 'Post', 'Identifier' => $Identifier ) );
			if ( !$Access ) unset ( $friends[$f] );
		}
		
		$notifyData = array ( 'OwnerId' => $this->_Focus->Id, 'Friends' => $friends, 'ActionOwner' => $this->_Current->Account, 'Action' => 'posted', 'ContextOwner' => $this->_Focus->Account, 'Context' => 'page', 'Comment' => $Content, 'Identifier' => $Identifier );
		$this->Talk ( 'Newsfeed', 'Notify', $notifyData );
		
		// Don't send an email if we're posting on our own page.
		if ( $this->_Current->Account != $this->_Focus->Account ) {
			$this->_Email ( $Content, $Identifier );
		}
		
		return ( $this->Display ( $pView, $pData ) );
	}
	
	private function _Email ( $pContent, $pIdentifier) {
		$data = array ( 'account' => $this->_Current->Account, 'source' => ASD_DOMAIN, 'request' => $this->_Current->Account );
		$CurrentInfo = $this->GetSys ( 'Event' )->Trigger ( 'On', 'User', 'Info', $data );
		$SenderFullname = $CurrentInfo->fullname;
		$SenderNameParts = explode ( ' ', $CurrentInfo->fullname );
		$SenderFirstName = $SenderNameParts[0];
		
		$SenderAccount = $this->_Current->Account;
		
		$RecipientEmail = $this->_Focus->Email;
		$MailSubject = __( "Someone Posted On Your Page", array ( "fullname" => $SenderFullname ) );
		$Byline = __( "Posted On Your Page" );
		$Subject = __( "Someone Said", array ( "firstname" => $SenderFirstName ) );
		$Body = $pContent;
		$LinkDescription = __( "Click Here" );
		$Link = 'http://' . ASD_DOMAIN . '/profile/' . $this->_Focus->Username . '/page/' . $pIdentifier;
		
		$Message = array ( 'Type' => 'User', 'SenderFullname' => $SenderFullname, 'SenderAccount' => $SenderAccount, 'RecipientEmail' => $RecipientEmail, 'MailSubject' => $MailSubject, 'Byline' => $Byline, 'Subject' => $Subject, 'Body' => $Body, 'LinkDescription' => $LinkDescription, 'Link' => $Link );
		$this->Talk ( 'Postal', 'Send', $Message );
		
		return ( true );
	} 
	
}