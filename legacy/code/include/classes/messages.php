<?php
  // +-------------------------------------------------------------------+
  // | Appleseed Web Community Management Software                       |
  // | http://appleseed.sourceforge.net                                  |
  // +-------------------------------------------------------------------+
  // | FILE: messages.php                            CREATED: 01-29-2006 + 
  // | LOCATION: /code/include/classes/             MODIFIED: 11-08-2006 +
  // +-------------------------------------------------------------------+
  // | Copyright (c) 2004-2008 Appleseed Project                         |
  // +-------------------------------------------------------------------+
  // | This program is free software; you can redistribute it and/or     |
  // | modify it under the terms of the GNU General Public License       |
  // | as published by the Free Software Foundation; either version 2    |
  // | of the License, or (at your option) any later version.            |
  // |                                                                   |
  // | This program is distributed in the hope that it will be useful,   |
  // | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
  // | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
  // | GNU General Public License for more details.                      |	
  // |                                                                   |
  // | You should have received a copy of the GNU General Public License |
  // | along with this program; if not, write to:                        |
  // |                                                                   |
  // |   The Free Software Foundation, Inc.                              |
  // |   59 Temple Place - Suite 330,                                    | 
  // |   Boston, MA  02111-1307, USA.                                    |
  // |                                                                   |
  // |   http://www.gnu.org/copyleft/gpl.html                            |
  // +-------------------------------------------------------------------+
  // | AUTHORS: Michael Chisari <michael.chisari@gmail.com>              |
  // +-------------------------------------------------------------------+
  // | VERSION:      0.2.2                                               |
  // | DESCRIPTION.  Message class definitions.                          |
  // +-------------------------------------------------------------------+

  // Message Meta-class.
  class cMESSAGE extends cDATACLASS {

    var $messageInformation;
    var $messageNotification;
    var $messageStore;
    var $messageLabelList;
    var $messageLabels;

    function cMESSAGE ($pDEFAULTCONTEXT = NULL) {
      
      $this->messageInformation = new cMESSAGEINFORMATION ($pDEFAULTCONTEXT);
      $this->messageNotification = new cMESSAGENOTIFICATION ($pDEFAULTCONTEXT);
      $this->messageStore = new cMESSAGESTORE ($pDEFAULTCONTEXT);
      $this->messageRecipient = new cMESSAGERECIPIENTS ($pDEFAULTCONTEXT);
      $this->messageLabelList = new cMESSAGELABELLIST ($pDEFAULTCONTEXT);
      $this->messageLabels = new cMESSAGELABELS ($pDEFAULTCONTEXT);

    } // Constructor
    
    function Initialize () {
      global $gREADDATA, $gREADACTION;
      global $gLABELDATA;
      global $gCIRCLEDATA;
      
      $gREADDATA = array ("Z" => MENU_DISABLED . "Mark As:",
                          "READ_ALL" => "&nbsp;Read+",
                          "UNREAD_ALL" => "&nbsp;Unread+");
      if (!$gREADACTION) $gREADACTION = 'Z';
      
      $gLABELDATA = $this->CreateFullLabelMenu ();
      $gCIRCLEDATA = $this->CreateCircleMenu ();
      
      return (TRUE);
    } // Initialize
    
    function CountNewMessages () {
      global $zLOCALUSER, $zOLDAPPLE, $zHTML;

      $NotificationTable = $this->messageNotification->TableName;
      $InformationTable = $this->messageInformation->TableName;

      $zLOCALUSER->userInformation->Select ("userAuth_uID", $zLOCALUSER->uID);
      $zLOCALUSER->userInformation->FetchArray ();
      $stamp = $zLOCALUSER->userInformation->MessageStamp;

      // Count notifications.
      $query = "SELECT COUNT($NotificationTable.tID) " .
               "AS     CountResult " .
               "FROM   $NotificationTable " .
               "WHERE  Standing = " . MESSAGE_UNREAD . " " .
               "AND    Location = " . FOLDER_INBOX . " " . 
               "AND    userAuth_uID = " . $zLOCALUSER->uID . " " . 
               "AND    Stamp > '" . $stamp . "'";

      $this->Query ($query);
      $this->FetchArray();
      $total = $this->CountResult;

      // Count stored messages.
      $query = "SELECT COUNT($InformationTable.tID) " .
               "AS     CountResult " .
               "FROM   $InformationTable " .
               "WHERE  Standing = " . MESSAGE_UNREAD . " " .
               "AND    Location = " . FOLDER_INBOX . " " . 
               "AND    userAuth_uID = " . $zLOCALUSER->uID . " " .
               "AND    Received_Stamp > '" . $stamp . "'";

      $this->Query ($query);
      $this->FetchArray();

      // Add for total.
      $total += $this->CountResult;

      if ($total) {
        global $gMESSAGECOUNT;
        $gMESSAGECOUNT = $total;
        $username = $zLOCALUSER->Username;
        $return = $zHTML->CreateLink ("profile/$username/messages/", __("New Message Count", array ( 'count' => $gMESSAGECOUNT ) ) );
      } else {
        $return = OUTPUT_NBSP;
      } // if

      return ($return);

    } // CountNewMessages

    // Count new messages in each folder.
    function CountNewInFolders () {

      global $gFOLDERSELECT, $gFOLDERCOUNT;
      global $zFOCUSUSER;

      // Set the folder highlighting to 'normal' by default.
      $gFOLDERSELECT['INBOX'] = 'normal'; $gFOLDERSELECT['SENT'] = 'normal';
      $gFOLDERSELECT['DRAFTS'] = 'normal'; $gFOLDERSELECT['ALL'] = 'normal';
      $gFOLDERSELECT['SPAM'] = 'normal';  $gFOLDERSELECT['TRASH'] = 'normal';

      // Count the number of new messages in Inbox.
      $inboxcount = $this->CountNewInInbox ();
      if ($inboxcount == 0) {
        $gFOLDERCOUNT['INBOX'] = '';
      } else {
        $gFOLDERCOUNT['INBOX'] = '(' . $inboxcount  . ')';
      } // if

      // Count the number of new messages in Spam.
      $spamcount = $this->CountNewInSpam ();
      if ($spamcount == 0) {
        $gFOLDERCOUNT['SPAM'] = '';
      } else {
        $gFOLDERCOUNT['SPAM'] = '(' . $spamcount  . ')';
      } // if

      // Count the number of new messages in Drafts.
      $draftscount = $this->CountNewInDrafts ();
      if ($draftscount == 0) {
        $gFOLDERCOUNT['DRAFTS'] = '';
      } else {
        $gFOLDERCOUNT['DRAFTS'] = '(' . $draftscount  . ')';
      } // if
      
      // Count the number of new messages in Trash.
      $trashcount = $this->CountNewInTrash ();
      if ($trashcount == 0) {
        $gFOLDERCOUNT['TRASH'] = '';
      } else {
        $gFOLDERCOUNT['TRASH'] = '(' . $trashcount  . ')';
      } // if

    } // CountNewInFolders

    // Count New Messages In Trash
    function CountNewInTrash () {
      global $zFOCUSUSER;

      $NotificationTable = $this->messageNotification->TableName;
      $InformationTable = $this->messageInformation->TableName;

      // Count notifications.
      $query = "SELECT COUNT($NotificationTable.tID) " .
               "AS     CountResult " .
               "FROM   $NotificationTable " .
               "WHERE  $NotificationTable.Standing = " . MESSAGE_UNREAD . " " .
               "AND    $NotificationTable.Location = " . FOLDER_TRASH . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();
      $total = $this->CountResult;

      // Count stored messages.
      $query = "SELECT COUNT($InformationTable.tID) " .
               "AS     CountResult " .
               "FROM   $InformationTable " .
               "WHERE  $InformationTable.Standing = " . MESSAGE_UNREAD . " " .
               "AND    $InformationTable.Location = " . FOLDER_TRASH . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();

      // Add for total.
      $total += $this->CountResult;

      return ($total);

    } // CountNewInTrash

    // Count New Messages In Drafts
    function CountNewInDrafts () {
      global $zFOCUSUSER;

      $StoreTable = $this->messageStore->TableName;
      $RecipientsTable = $this->messageRecipient->TableName;

      // Count stored messages.
      $query = "SELECT COUNT($StoreTable.tID) " .
               "AS     CountResult " .
               "FROM   $StoreTable " .
               "WHERE  $StoreTable.Location = " . FOLDER_DRAFTS . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();

      // Add for total.
      $total = $this->CountResult;

      return ($total);

    } // CountNewInDrafts

    // Count New Messages In Inbox
    function CountNewInInbox () {
      global $zFOCUSUSER;

      $NotificationTable = $this->messageNotification->TableName;
      $InformationTable = $this->messageInformation->TableName;

      // Count notifications.
      $query = "SELECT COUNT($NotificationTable.tID) " .
               "AS     CountResult " .
               "FROM   $NotificationTable " .
               "WHERE  $NotificationTable.Standing = " . MESSAGE_UNREAD . " " .
               "AND    $NotificationTable.Location = " . FOLDER_INBOX . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();
      $total = $this->CountResult;

      // Count stored messages.
      $query = "SELECT COUNT($InformationTable.tID) " .
               "AS     CountResult " .
               "FROM   $InformationTable " .
               "WHERE  $InformationTable.Standing = " . MESSAGE_UNREAD . " " .
               "AND    $InformationTable.Location = " . FOLDER_INBOX . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();

      // Add for total.
      $total += $this->CountResult;

      return ($total);

    } // CountNewInInbox

    // Count New Messages In Spam
    function CountNewInSpam () {
      global $zFOCUSUSER;

      $NotificationTable = $this->messageNotification->TableName;
      $InformationTable = $this->messageInformation->TableName;

      // Count notifications.
      $query = "SELECT COUNT($NotificationTable.tID) " .
               "AS     CountResult " .
               "FROM   $NotificationTable " .
               "WHERE  $NotificationTable.Standing = " . MESSAGE_UNREAD . " " .
               "AND    $NotificationTable.Location = " . FOLDER_SPAM . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();
      $total = $this->CountResult;

      // Count stored messages.
      $query = "SELECT COUNT($InformationTable.tID) " .
               "AS     CountResult " .
               "FROM   $InformationTable " .
               "WHERE  $InformationTable.Standing = " . MESSAGE_UNREAD . " " .
               "AND    $InformationTable.Location = " . FOLDER_SPAM . " " . 
               "AND    userAuth_uID = " . $zFOCUSUSER->uID;

      $this->Query ($query);
      $this->FetchArray();

      // Add for total.
      $total += $this->CountResult;

      return ($total);

    } // CountNewInSpam

    // Determine which of the folders is currently selected.
    function DetermineCurrentFolder () {
      global $gPROFILESUBACTION, $gFOLDERSELECT;
      global $gFOLDERID;

      $gFOLDERID = '';

      // Determine which section of mail we're looking at.
      switch ($gPROFILESUBACTION) {
        case '':
        case 'inbox':
          $gFOLDERSELECT['INBOX'] = "selected";  
          $gFOLDERID = FOLDER_INBOX;
        break;

        case 'sent':
          $gFOLDERSELECT['SENT'] = "selected";  
          $gFOLDERID = FOLDER_SENT;
        break;

        case 'drafts':
          $gFOLDERSELECT['DRAFTS'] = "selected";  
          $gFOLDERID = FOLDER_DRAFTS;
        break;

        case 'all':
          $gFOLDERSELECT['ALL'] = "selected";  
        break;

        case 'spam':
          $gFOLDERSELECT['SPAM'] = "selected";  
          $gFOLDERID = FOLDER_SPAM;
        break;

        case 'trash':
          $gFOLDERSELECT['TRASH'] = "selected";  
          $gFOLDERID = FOLDER_TRASH;
        break; 
    
        default:
          $gFOLDERSELECT[$gPROFILESUBACTION] = "selected";  
        break;
      } // switch

    } // DetermineCurrentFolder

    function LoadMessages () {
      global $gFOLDERID, $zFOCUSUSER;
      global $gPROFILESUBACTION, $gSORT;
      global $gLABELNAME;

      $returnbuffer = NULL;
  
      switch ($gFOLDERID) {
        case FOLDER_INBOX:
          $returnbuffer = $this->BufferInbox ();
          return ($returnbuffer);
        break;
        case FOLDER_SENT:
          $returnbuffer = $this->BufferSent ();
          return ($returnbuffer);
        break;
        case FOLDER_DRAFTS:
          $returnbuffer = $this->BufferDrafts ();
          return ($returnbuffer);
        break;
        case FOLDER_TRASH:
          $returnbuffer = $this->BufferTrash ();
          return ($returnbuffer);
        break;
        case FOLDER_SPAM:
          $returnbuffer = $this->BufferSpam ();
          return ($returnbuffer);
        break;
        default:
        break;
      } // switch

      if ($gFOLDERID) {
      } elseif ($gPROFILESUBACTION == 'all') {

        // Select all new and archived messages.
        $returnbuffer = $this->BufferAll ();

        return ($returnbuffer);

      } else {

        $returnbuffer = $this->BufferLabel ();
        return ($returnbuffer);

      } // if

      return (FALSE);

    } // LoadMessages

    function BufferLabel () {
      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION, $gLABELNAME, $gPROFILESUBACTION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;
      
      global $gFOLDERID;

      global $gSENDERNAME, $gSENDERONLINE;

      $gLABELNAME = $gPROFILESUBACTION;
      $labelcriteria = array ("userAuth_uID" => $zFOCUSUSER->uID,
                              "Label"        => $gLABELNAME);

      $this->messageLabels->SelectByMultiple ($labelcriteria);
      $this->messageLabels->FetchArray ();

      if ($this->messageLabels->CountResult () == 0) {
        return (FALSE);
      } // if

      $labelid = $this->messageLabels->tID;

      $NotificationTable = $this->messageNotification->TableName;
      $InformationTable = $this->messageInformation->TableName;
      $messageLabelListTable = $this->messageLabelList->TableName;

      $statement_left  = "(SELECT $NotificationTable.tID, " .
                         "        $NotificationTable.userAuth_uID, " .
                         "        $NotificationTable.Sender_Username, " .
                         "        $NotificationTable.Sender_Domain, " .
                         "        $NotificationTable.Subject, " .
                         "        $NotificationTable.Identifier, " .
                         "        $NotificationTable.Stamp, " .
                         "        $NotificationTable.Standing, " .
                         "        $NotificationTable.Location " .
                         " FROM   $NotificationTable, $messageLabelListTable " .
                         " WHERE  $messageLabelListTable.Identifier = $NotificationTable.Identifier " .
                         " AND    $NotificationTable.userAuth_uID = " . $zFOCUSUSER->uID . " " .
                         " AND    $NotificationTable.Location != " . FOLDER_SENT . " " .
                         " AND    $NotificationTable.Location != " . FOLDER_DRAFTS . " " .
                         " AND    $NotificationTable.Location != " . FOLDER_TRASH . " " .
                         " AND    $NotificationTable.Location != " . FOLDER_SPAM . " " .
                         " AND    $messageLabelListTable.messageLabels_tID = " . $labelid . ")";
      $statement_right = "(SELECT $InformationTable.tID, " .
                         "        $InformationTable.userAuth_uID, " .
                         "        $InformationTable.Sender_Username, " .
                         "        $InformationTable.Sender_Domain, " .
                         "        $InformationTable.Subject, " .
                         "        $InformationTable.Identifier, " .
                         "        $InformationTable.Received_Stamp AS Stamp, " .
                         "        $InformationTable.Standing, " .
                         "        $InformationTable.Location " .
                         " FROM   $InformationTable, $messageLabelListTable " .
                         " WHERE  $messageLabelListTable.Identifier = $InformationTable.Identifier " .
                         " AND    $InformationTable.userAuth_uID = " . $zFOCUSUSER->uID . " " .
                         " AND    $InformationTable.Location != " . FOLDER_SENT . " " .
                         " AND    $InformationTable.Location != " . FOLDER_DRAFTS . " " .
                         " AND    $InformationTable.Location != " . FOLDER_TRASH . " " .
                         " AND    $InformationTable.Location != " . FOLDER_SPAM . " " .
                         " AND    $messageLabelListTable.messageLabels_tID = " . $labelid . ")";
      $query = $statement_left . " UNION " . $statement_right . " ORDER BY Stamp DESC";

      $this->Query ($query);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();

      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('users.messages', $this);

      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/label/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/label/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/label/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Loop through the list.
      $gTARGET = "/profile/" . $zFOCUSUSER->Username . "/messages/" . $gPROFILESUBACTION . "/";
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Sender_Username, $this->Sender_Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Sender_Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->Sender_Username = $this->Sender_Username;
        $this->Sender_Domain = $this->Sender_Domain;
        $this->Subject = $this->Subject;
        $this->Stamp = $this->Stamp;
        $this->Standing = $this->Standing;
        $this->FormatDate ("Stamp");

        global $bINBOXMARK;
        $bINBOXMARK = NULL;
        
        if ( ($this->Location == FOLDER_INBOX) and 
             ($gFOLDERID != FOLDER_INBOX) ) {
          $bINBOXMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.inbox.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            if ($this->messageLabels->tID == $labelid) continue;
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "_new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/label/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/label/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);

    } // BufferLabel

    function BufferInbox () {
      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;

      global $gSENDERNAME, $gSENDERONLINE;
      
      global $gTABLEPREFIX;

      $statement_left  = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Stamp FROM " . $gTABLEPREFIX . "messageNotification WHERE userAuth_uID = " . $zFOCUSUSER->uID . " AND Location = " . FOLDER_INBOX . ") ";
      $statement_right = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Received_Stamp AS Stamp FROM " . $gTABLEPREFIX . "messageInformation WHERE Location = " . FOLDER_INBOX . " AND userAuth_uID = " . $zFOCUSUSER->uID . ") ";
      $query = $statement_left . " UNION " . $statement_right;
      $query .= " ORDER BY Stamp DESC";

      $this->Query ($query);

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/inbox/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();

      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('user.messages', $this);
      
      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/inbox/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/inbox/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      // Loop through the list.
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Sender_Username, $this->Sender_Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Sender_Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->Sender_Username = $this->Sender_Username;
        $this->Sender_Domain = $this->Sender_Domain;
        $this->Subject = $this->Subject;
        $this->Stamp = $this->Stamp;
        $this->Standing = $this->Standing;
        $this->FormatDate ("Stamp");

        $bINBOXMARK = NULL;

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/inbox/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/inbox/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);
    } // BufferInbox

    function BufferSent () {

      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;

      global $gSENDERNAME, $gSENDERONLINE;

      global $gTABLEPREFIX;
      
      $messageStore = $this->messageStore->TableName;
      $messageRecipient = $this->messageRecipient->TableName;

      $query = "SELECT   $messageStore.tID AS tID, " .
               "         $messageStore.userAuth_uID AS userAuth_uID, " .
               "         $messageRecipient.Identifier AS Identifier, " .
               "         $messageRecipient.Username AS Username, " .
               "         $messageRecipient.Domain AS Domain, " .
               "         $messageStore.Subject AS Subject, " .
               "         $messageStore.Stamp AS Stamp " .
               "FROM     $messageStore, $messageRecipient " .
               "WHERE    $messageStore.userAuth_uID = " . $zFOCUSUSER->uID .  " " . 
               "AND      $messageRecipient.messageStore_tID = $messageStore.tID " . 
               "AND      $messageStore.Location = " . FOLDER_SENT . " " .
               "GROUP BY $messageStore.Subject " .
               "ORDER BY $messageStore.Stamp DESC";

      $this->Query ($query);

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/sent/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();

      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('users.messages', $this);

      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/sent/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/sent/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      // Loop through the list.
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Username, $this->Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->Sender_Username = $this->Sender_Username;
        $this->Sender_Domain = $this->Sender_Domain;
        $this->Subject = $this->Subject;
        $this->Stamp = $this->Stamp;
        $this->Standing = $this->Standing;
        $this->FormatDate ("Stamp");

        $bINBOXMARK = NULL;

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "_new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/sent/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/sent/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);
    } // BufferSent

    function BufferDrafts () {

      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;

      global $gSENDERNAME, $gSENDERONLINE;

      global $gTABLEPREFIX;

      $messageStore = $this->messageStore->TableName;
      $messageRecipient = $this->messageRecipient->TableName;

      $query = "SELECT   $messageStore.tID AS tID, " .
               "         $messageStore.userAuth_uID AS userAuth_uID, " .
               "         $messageRecipient.Identifier AS Identifier, " .
               "         $messageRecipient.Username AS Username, " .
               "         $messageRecipient.Domain AS Domain, " .
               "         $messageRecipient.Standing AS Standing, " .
               "         $messageStore.Subject AS Subject, " .
               "         $messageStore.Stamp AS Stamp " .
               "FROM     $messageStore, $messageRecipient " .
               "WHERE    $messageStore.userAuth_uID = " . $zFOCUSUSER->uID .  " " . 
               "AND      $messageRecipient.messageStore_tID = $messageStore.tID " . 
               "AND      $messageStore.Location = " . FOLDER_DRAFTS . " " .
               "GROUP BY $messageStore.Subject " .
               "ORDER BY $messageStore.Stamp DESC";

      $this->Query ($query);

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/drafts/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();

      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('users.messages', $this);

      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/drafts/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/drafts/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      // Loop through the list.
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Username, $this->Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->FormatDate ("Stamp");

        $bINBOXMARK = NULL;

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "_new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/drafts/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/drafts/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);
    } // BufferDrafts

    function BufferTrash () {
      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;

      global $gSENDERNAME, $gSENDERONLINE;

      global $gTABLEPREFIX;

      // Old query.
      $query = "SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Received_Stamp AS Stamp, Location FROM " . $gTABLEPREFIX . "messageInformation WHERE userAuth_uID = " . $zFOCUSUSER->uID . " AND Location = " . FOLDER_TRASH;
      $query .= " ORDER BY Stamp DESC";

      $statement_left  = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Stamp FROM " . $gTABLEPREFIX . "messageNotification WHERE userAuth_uID = " . $zFOCUSUSER->uID . " AND Location = " . FOLDER_TRASH . ") ";
      $statement_right = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Received_Stamp AS Stamp FROM " . $gTABLEPREFIX . "messageInformation WHERE Location = " . FOLDER_TRASH . " AND userAuth_uID = " . $zFOCUSUSER->uID . ") ";
      $query = $statement_left . " UNION " . $statement_right;
      $query .= " ORDER BY Stamp DESC";

      $this->Query ($query);

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/trash/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();

      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('users.messages', $this);

      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/trash/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/trash/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      // Loop through the list.
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Sender_Username, $this->Sender_Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Sender_Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->Sender_Username = $this->Sender_Username;
        $this->Sender_Domain = $this->Sender_Domain;
        $this->Subject = $this->Subject;
        $this->Stamp = $this->Stamp;
        $this->Standing = $this->Standing;
        $this->FormatDate ("Stamp");

        global $bINBOXMARK; 
        $bINBOXMARK = NULL;

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "_new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/trash/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/trash/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);
    } // BufferTrash

    function BufferAll () {
      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;
      
      global $gTABLEPREFIX;
      
      global $gFOLDERID;

      global $gSENDERNAME, $gSENDERONLINE;

      $statement_left  = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Stamp, Location FROM " . $gTABLEPREFIX . "messageNotification WHERE userAuth_uID = " . $zFOCUSUSER->uID . " AND Location != " . FOLDER_SPAM . " ) ";
      $statement_right = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Received_Stamp AS Stamp, Location FROM " . $gTABLEPREFIX . "messageInformation WHERE Location != " . FOLDER_TRASH . " AND userAuth_uID = " . $zFOCUSUSER->uID . " AND Location != " . FOLDER_SPAM . ") ";
      $query = $statement_left . " UNION " . $statement_right;
      $query .= " ORDER BY Stamp DESC";

      $this->Query ($query);

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/all/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();
      
      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('users.messages', $this);

      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/all/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/all/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      // Loop through the list.
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Sender_Username, $this->Sender_Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Sender_Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->Sender_Username = $this->Sender_Username;
        $this->Sender_Domain = $this->Sender_Domain;
        $this->Subject = $this->Subject;
        $this->Stamp = $this->Stamp;
        $this->Standing = $this->Standing;
        $this->FormatDate ("Stamp");

        global $bINBOXMARK; 
        $bINBOXMARK = NULL;

        if ( ($this->Location == FOLDER_INBOX) and 
             ($gFOLDERID != FOLDER_INBOX) ) {
          $bINBOXMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.inbox.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "_new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/all/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/all/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);
    } // BufferAll

    function BufferSpam () {
      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION; 

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;

      global $gSENDERNAME, $gSENDERONLINE;
      
      global $gTABLEPREFIX;

      $statement_left  = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Stamp FROM " . $gTABLEPREFIX . "messageNotification WHERE userAuth_uID = " . $zFOCUSUSER->uID . " AND Location = " . FOLDER_SPAM . ") ";
      $statement_right = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Received_Stamp AS Stamp FROM " . $gTABLEPREFIX . "messageInformation WHERE Location = " . FOLDER_SPAM . " AND userAuth_uID = " . $zFOCUSUSER->uID . ") ";
      $query = $statement_left . " UNION " . $statement_right;
      $query .= " ORDER BY Stamp DESC";

      $this->Query ($query);

      $returnbuffer = NULL;
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/spam/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Calculate scroll values.
      $gSCROLLMAX[$zOLDAPPLE->Context] = $this->CountResult();

      // Adjust for a recently deleted entry.
      $zOLDAPPLE->AdjustScroll ('users.messages', $this);

      // Check if any results were found.
      if ($gSCROLLMAX[$zOLDAPPLE->Context] == 0) {
        $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/spam/list.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        $this->Message = __("No Results Found");
        $returnbuffer .= $this->CreateBroadcast();
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/spam/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        return ($returnbuffer);
      } // if

      // Loop through the list.
      for ($listcount = 0; $listcount < $gSCROLLSTEP[$zOLDAPPLE->Context]; $listcount++) {
       if ($this->FetchArray()) {

        list ($gSENDERNAME, $gSENDERONLINE) = $zOLDAPPLE->GetUserInformation($this->Sender_Username, $this->Sender_Domain);

        // No Fullname information found.  Using username.
        if (!$gSENDERNAME) $gSENDERNAME = $this->Sender_Username;

        $gCHECKED = FALSE;
        if ($gACTION == 'SELECT_ALL') $gCHECKED = TRUE;

        $this->Sender_Username = $this->Sender_Username;
        $this->Sender_Domain = $this->Sender_Domain;
        $this->Subject = $this->Subject;
        $this->Stamp = $this->Stamp;
        $this->Standing = $this->Standing;
        $this->FormatDate ("Stamp");

        $bINBOXMARK = NULL;

        global $bLABELSMARK; 

        $this->messageLabelList->Select ("Identifier", $this->Identifier);
        if ($this->messageLabelList->CountResult () == 0) {
          $bLABELSMARK = NULL;
        } else {
          $labelarray = array ();

          while ($this->messageLabelList->FetchArray ()) {
            $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
            $this->messageLabels->FetchArray ();
            array_push ($labelarray, $this->messageLabels->Label);
          } // while

          global $gLABELLISTING;
          $gLABELLISTING = join (", ", $labelarray);

          $bLABELSMARK = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/mark.labels.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // if

        $gMESSAGESTANDING = "";
        if ($this->Standing == MESSAGE_UNREAD) $gMESSAGESTANDING = "_new";

        global $gPOSTDATA;
        $gPOSTDATA['ACTION'] = "VIEW";
        $gPOSTDATA['IDENTIFIER'] = $this->Identifier;
        $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/spam/list.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        unset ($gPOSTDATA['ACTION']);
        unset ($gPOSTDATA['IDENTIFIER']);

       } else {
        break;
       } // if
      } // for

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/spam/list.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);
    } // BufferSpam

    function SelectAllMessages () {

      global $zFOCUSUSER, $gSORT;
      
      global $gTABLEPREFIX;

      $statement_left  = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Stamp FROM " . $gTABLEPREFIX . "messageNotification) ";
      $statement_right = "(SELECT tID, userAuth_uID, Sender_Username, Sender_Domain, Identifier, Subject, Standing, Received_Stamp AS Stamp FROM " . $gTABLEPREFIX . "messageInformation) ";
      $query = $statement_left . " UNION " . $statement_right;
      $query .= " ORDER BY Stamp DESC";

      $this->Query ($query);

      return (0);
    } // SelectAllMessages

    function LocateMessage ($pIDENTIFIER) {
      global $zFOCUSUSER;
      
      // Check messageNotification
      $query = "SELECT tID FROM " . $this->messageNotification->TableName . " WHERE Identifier = '$pIDENTIFIER' AND userAuth_uID = " . $zFOCUSUSER->uID;
      $this->Query ($query);
      if ($this->CountResult () > 0) return ('messageNotification');
      // Check messageInformation
      $query = "SELECT tID FROM " . $this->messageInformation->TableName . " WHERE Identifier = '$pIDENTIFIER' AND userAuth_uID = " . $zFOCUSUSER->uID;
      $this->Query ($query);
      if ($this->CountResult () > 0) return ('messageInformation');
      
      // Check messageStore
      $messageStore = $this->messageStore->TableName;
      $messageRecipient = $this->messageRecipient->TableName;
      $query = "SELECT $messageStore.tID AS tID " .
               "FROM   $messageStore, $messageRecipient " .
               "WHERE  $messageRecipient.Identifier = '$pIDENTIFIER' " .
               "AND    $messageRecipient.messageStore_tID = $messageStore.tID " .
               "AND    $messageStore.userAuth_uID = " . $zFOCUSUSER->uID;
      $this->Query ($query);
      if ($this->CountResult () > 0) return ('messageStore');
    } // LocateMessage

    function SelectMessage ($pIDENTIFIER) {

      $classlocation = $this->LocateMessage ($pIDENTIFIER);
      $this->Identifier = $pIDENTIFIER;

      if ($classlocation == 'messageNotification') {
        if (!$this->RetrieveMessage ()) {
          return (FALSE);
        } // if
      } elseif ($classlocation == 'messageInformation') {
        // Message is an archive.
        $this->messageInformation->Select ("Identifier", $pIDENTIFIER);
        $this->messageInformation->FetchArray();
        $this->tID = $this->messageInformation->tID;
        $this->userAuth_uID = $this->messageInformation->userAuth_uID;
        $this->Subject = $this->messageInformation->Subject;
        $this->Body = $this->messageInformation->Body;
        $this->Stamp = $this->messageInformation->Received_Stamp;
        $this->Location = $this->messageInformation->Location;
        $this->Identifier = $this->messageInformation->Identifier;
        $this->FormatDate ("Stamp");
        $this->Sender_Username = $this->messageInformation->Sender_Username;
        $this->Sender_Domain = $this->messageInformation->Sender_Domain;
        
        // Check for corresponding local message recipient record and mark as read.
        $this->messageRecipient->Select ('Identifier', $pIDENTIFIER);
        $this->messageRecipient->FetchArray();
        $this->messageRecipient->Standing = MESSAGE_READ;
        $this->messageRecipient->Update ();
      } elseif ($classlocation == 'messageStore') {
        // Message is in sent folder.
        $this->messageRecipient->Select ("Identifier", $pIDENTIFIER);
        $this->messageRecipient->FetchArray();
        $this->messageStore->Select ("tID", $this->messageRecipient->messageStore_tID);
        $this->messageStore->FetchArray();
        $this->tID = $this->messageStore->tID;
        $this->userAuth_uID = $this->messageStore->userAuth_uID;
        $this->Subject = $this->messageStore->Subject;
        $this->Body = $this->messageStore->Body;
        $this->Stamp = $this->messageStore->Stamp;
        $this->Location = $this->messageStore->Location;
        $this->FormatDate ("Stamp");
      } // if

      return (TRUE);

    } // SelectMessage

    function LoadDraft () {
      global $zOLDAPPLE;

      if ($this->Location == FOLDER_DRAFTS) {
        global $gRECIPIENTNAME, $gRECIPIENTDOMAIN;
        global $gBODY, $gSUBJECT, $gtID;
        global $bRECIPIENT;
        global $gFRAMELOCATION;
        global $gRECIPIENTADDRESS;

        $this->messageRecipient->Select ("messageStore_tID", $this->tID);
        $addresses = array ();
        while ($this->messageRecipient->FetchArray ()) {
          $addresses[] = $this->messageRecipient->Username . '@' . $this->messageRecipient->Domain;
        }
        $gRECIPIENTADDRESS = join (', ', $addresses);
        $gtID = $this->tID;
        $gBODY = html_entity_decode ($this->Body);
        $gSUBJECT = $this->Subject;

        $bRECIPIENT = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/recipient.unknown.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
      } // if
    } // LoadDraft
    
    // Remove a draft from the drafts folder.
    function RemoveDraft ($pDRAFTID) {
      
      $this->messageStore->Select ("tID", $pDRAFTID);
      $this->messageStore->FetchArray ();
      
      $this->messageRecipient->Delete("messageStore_tID = $pDRAFTID");
      
      // Check to make sure it is stored in the drafts folder.
      if ($this->messageStore->Location == FOLDER_DRAFTS) {
        // Delete record.
        $this->messageStore->Delete ();
      } // if
      
      return (TRUE);
    } // RemoveDraft

    // Mark The Current Message As Read.
    function MarkAsRead () {

      // Do not change the status of a Draft message.
      if ($this->Location == FOLDER_DRAFTS) return (TRUE);

      $classlocation = $this->LocateMessage ($this->Identifier);
 
      // Check if user owns this message.
      if ($this->$classlocation->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      // Save some cycles by only updating if it's currely UNREAD.
      if ($this->$classlocation->Standing == MESSAGE_UNREAD) {
        $this->$classlocation->Standing = MESSAGE_READ;
        $this->$classlocation->Update ();
      } // if

    } // MarkAsRead
    
    // Mark The Current Message As Unread.
    function MarkAsUnread () {

      // Do not change the status of a Draft message.
      if ($this->Location == FOLDER_DRAFTS) return (FALSE);

      // Check if user owns this message.
      if ($this->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $classlocation = $this->LocateMessage ($this->Identifier);

      // Save some cycles by only updating if it's currely READ.
      if ($this->$classlocation->Standing == MESSAGE_READ) {
        $this->$classlocation->Standing = MESSAGE_UNREAD;
        $this->$classlocation->Update ();
      } // if

      $this->Message = __( "Message Marked Unread" );

      return (TRUE);

    } // MarkAsUnread


    function MarkListAsRead ($pDATALIST) {

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $id) {

        $classlocation = $this->LocateMessage ($id);

        // Select the message in question.
        $this->$classlocation->Select ("Identifier", $id);
        $this->$classlocation->FetchArray ();

        // Check if user owns this message.
        if ($this->$classlocation->CheckReadAccess () == FALSE) {
          global $gMESSAGEID;
          $gMESSAGEID = $id;
          $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
          $this->Error = -1;
          continue;
        } // if 

        $this->$classlocation->tID = $id;
        $this->$classlocation->Standing = MESSAGE_READ;

        $this->$classlocation->userAuth_uID = SQL_SKIP;
        $this->$classlocation->Identifier = SQL_SKIP;
        $this->$classlocation->Sender_Fullname = SQL_SKIP;
        $this->$classlocation->Sender_Username = SQL_SKIP;
        $this->$classlocation->Sender_Domain = SQL_SKIP;
        $this->$classlocation->Subject = SQL_SKIP;
        $this->$classlocation->Body = SQL_SKIP;
        $this->$classlocation->Sent_Stamp = SQL_SKIP;
        $this->$classlocation->Received_Stamp = SQL_SKIP;
        $this->$classlocation->Stamp = SQL_SKIP;
        $this->$classlocation->Location = SQL_SKIP;

        $this->$classlocation->Update ("Identifier", $id);

      } // if

      return (TRUE);

    } // MarkListAsRead

    function MarkListAsUnread ($pDATALIST) {

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if
      foreach ($pDATALIST as $key => $id) {

        $classlocation = $this->LocateMessage ($id);

        // Select the message in question.
        $this->$classlocation->Select ("Identifier", $id);
        $this->$classlocation->FetchArray ();

        // Check if user owns this message.
        if ($this->$classlocation->CheckReadAccess () == FALSE) {
          global $gMESSAGEID;
          $gMESSAGEID = $id;
          $this->$classlocation->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
          $this->$classlocation->Error = -1;
          continue;
        } // if 

        $this->$classlocation->tID = $id;
        $this->$classlocation->Standing = MESSAGE_UNREAD;

        $this->$classlocation->userAuth_uID = SQL_SKIP;
        $this->$classlocation->Sender_Username = SQL_SKIP;
        $this->$classlocation->Sender_Domain = SQL_SKIP;
        $this->$classlocation->Identifier = SQL_SKIP;
        $this->$classlocation->Subject = SQL_SKIP;
        $this->$classlocation->Body = SQL_SKIP;
        $this->$classlocation->Sent_Stamp = SQL_SKIP;
        $this->$classlocation->Received_Stamp = SQL_SKIP;
        $this->$classlocation->Stamp = SQL_SKIP;
        $this->$classlocation->Location = SQL_SKIP;

        $this->$classlocation->Update ("Identifier", $id);

      } // if

      return (TRUE);

    } // MarkListAsUnread

    function CreateLabelLinks ($pIDENTIFIER) {

      global $zHTML, $zFOCUSUSER;

      $this->messageLabelList->Select ("Identifier", $pIDENTIFIER);

      $labellist = array ();

      while ($this->messageLabelList->FetchArray ()) {
        $this->messageLabels->Select ("tID", $this->messageLabelList->messageLabels_tID);
        $this->messageLabels->FetchArray ();
        $label = $zHTML->CreateLink ("profile/" . $zFOCUSUSER->Username . "/messages/" . $this->messageLabels->Label . "/", $this->messageLabels->Label);
        array_push ($labellist, $label);
      } // while

      $labellistfinal = join (', ', $labellist);
 
      return ($labellistfinal);

    } // CreateLabelLinks

    function Label ($pLABELVALUE) {
      global $zOLDAPPLE;
      
      global $gIDENTIFIER;

      $checkcriteria = array ("Identifier" => $this->Identifier,
                              "messageLabels_tID" => $pLABELVALUE);
      $this->messageLabelList->SelectByMultiple ($checkcriteria); 
      $this->messageLabelList->FetchArray ();

      if ($this->messageLabelList->CountResult () == 0) {
        $this->messageLabelList->Identifier = $this->Identifier;
        $this->messageLabelList->messageLabels_tID = $pLABELVALUE;
        $this->messageLabelList->Add ();

        $this->messageLabels->Select ("tID", $pLABELVALUE);
        $this->messageLabels->FetchArray ();

        $this->Message = __("Message Labeled", array ( "label" => $this->messageLabels->Label ) );
 
      } else {
        $this->messageLabelList->Select ("Identifier", $gIDENTIFIER);
        $this->FetchArray ();
        $this->messageLabelList->Delete ();

        $this->messageLabels->Select ("tID", $pLABELVALUE);
        $this->messageLabels->FetchArray ();

        $this->Message = __("Message Label Removed", array ( "label" => $this->messageLabels->Label ) );
      } // if

      return (TRUE);
    } // Label

    function AddLabelToList ($pDATALIST) {
      global $zOLDAPPLE;
      
      global $gLABELVALUE, $gSELECTBUTTON;

      $labelaction = substr ($gLABELVALUE, 0, 1);
      if ( ($labelaction == 'r') or
           ($labelaction == 'a') ) {
        $gLABELVALUE = substr ($gLABELVALUE, 1, strlen ($gLABELVALUE));
      } // if

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $ID) {
        $checkcriteria = array ("Identifier" => $ID,
                                "messageLabels_tID" => $gLABELVALUE);
        $this->messageLabelList->SelectByMultiple ($checkcriteria); 
        $this->messageLabelList->FetchArray ();

        if ( ($this->messageLabelList->CountResult () == 0) and
             ($labelaction == 'a') ) {
          // Add the label.
          $this->messageLabelList->Identifier = $ID;
          $this->messageLabelList->messageLabels_tID = $gLABELVALUE;
          $this->messageLabelList->Add ();
        } elseif ($labelaction == 'r') {
          // Remove the label.
          $this->messageLabelList->Delete ();
        } // if
      } // foreach
      if ($pDATALIST) $gSELECTBUTTON = 'Select None';

      $this->messageLabels->Select ("tID", $gLABELVALUE);
      $this->messageLabels->FetchArray ();

      $zOLDAPPLE->SetTag ('LABELNAME', $this->messageLabels->Label);
           
      if ($labelaction == 'a') {
        $this->Message = __("Message Label Applied All", array ( "label" => $this->messageLabels->Label ) );
      } else {
        $this->Message = __("Message Label Removed All", array ( "label" => $this->messageLabels->Label ) );
      } // if

      unset ($gLABELVALUE);
      
    } // AddLabelToList

    function CreateFullLabelMenu () {

      global $zFOCUSUSER;
      global $gLABELVALUE;

      $this->messageLabels->Select ("userAuth_uID", $zFOCUSUSER->uID, "Label ASC");
  
      $applyarray = array ();
      $removearray = array ();

      // Create the list of available labels.
      if ($this->messageLabels->CountResult () == 0) {

      } else {

        $foundnewlabels = TRUE;

        // Start the menu list at '1'.
        $applyarray = array ("X" => MENU_DISABLED . __("Apply Label"));

        $removearray = array ("Z" => MENU_DISABLED . __("Remove Label"));

        $gLABELVALUE = 'X';

        // Loop through the list of labels.
        while ($this->messageLabels->FetchArray ()) {
          $applyarray['a' . $this->messageLabels->tID] = "&nbsp; " . $this->messageLabels->Label;
          $removearray['r' . $this->messageLabels->tID] = "&nbsp; " . $this->messageLabels->Label;
        } // while
        $returnarray = array_merge ($applyarray, $removearray);
      } // if

      $gLABELVALUE = 'X';

      return ($returnarray);

    } // CreateFullLabelMenu
    
    function CreateCircleMenu () {
      
      global $zFOCUSUSER;
      
      $CIRCLES = new cFRIENDCIRCLES ();
      
      $CIRCLES->Select ("userAuth_uID", $zFOCUSUSER->uID);
      
      if ($CIRCLES->CountResult() == 0) return (NULL);
      
      // Start the menu list at '1'.
      $return = array ("X" => MENU_DISABLED . __("Send To"));

      while ($CIRCLES->FetchArray ()) {
        $return[$CIRCLES->Name] = $CIRCLES->Name;
      } // while
      
      return ($return);
    } // CreateCircleMenu

    // Buffer the label menu for a specific message.
    function CreateSpecificLabelMenu () {

      global $zFOCUSUSER;
      global $gLABELVALUE;

      $this->messageLabels->Select ("userAuth_uID", $zFOCUSUSER->uID, "Label ASC");
  
      $applyarray = array ();
      $removearray = array ();

      // Create the list of available labels.
      if ($this->messageLabels->CountResult () == 0) {

      } else {

        $foundnewlabels = TRUE;

        // Start the menu list at '1'.
        $applyarray = array ("X" => MENU_DISABLED . __("Apply Label"));

        $removearray = array ("Z" => MENU_DISABLED . __("Remove Label"));

        $gLABELVALUE = 'X';

        // Loop through the list of labels.
        while ($this->messageLabels->FetchArray ()) {
          $applyarray['a' . $this->messageLabels->tID] = "&nbsp; " . $this->messageLabels->Label;
          $removearray['r' . $this->messageLabels->tID] = "&nbsp; " . $this->messageLabels->Label;
        } // while
        $returnarray = array_merge ($applyarray, $removearray);
      } // if

      $excludelist = array ();

      // Select the labels which are attached to this message.
      $this->messageLabelList->Select ("Identifier", $this->Identifier);

      $sort = "Label ASC";

      // NOTE: A JOIN statement would be faster.

      if ($this->messageLabelList->CountResult () == 0) {
        // Select all labels.
        $labelcriteria = array ("userAuth_uID" => $zFOCUSUSER->uID);   
        $this->messageLabels->SelectByMultiple ($labelcriteria, "Label", $sort);

      } else {
        // Exclude found labels.
        while ($this->messageLabelList->FetchArray ()) {
          array_push ($excludelist, $this->messageLabelList->messageLabels_tID);
        } // while
        $excludestring = join (" AND tID <>", $excludelist);
        $excludestring = "userAuth_uID = $zFOCUSUSER->uID " .
                         "AND tID <>" . $excludestring;
        $this->messageLabels->SelectWhere ($excludestring, $sort);
      } // if
  
      $returnarray = array ();

      // Create the list of available labels.
      if ($this->messageLabels->CountResult () == 0) {

      } else {

        $foundnewlabels = TRUE;

        // Start the menu list at '1'.
        $returnarray = array ("X" => MENU_DISABLED . __("Apply Label"));

        $gLABELVALUE = 'X';

        // Loop through the list of labels.
        while ($this->messageLabels->FetchArray ()) {
          $returnarray[$this->messageLabels->tID] = "&nbsp; " . $this->messageLabels->Label;
        } // while

      } // if

      // Create the list of removable labels.
      if (count ($excludelist) == 0) {
      } else {
        
        if ($foundnewlabels) {
          $returnarray["Y"] = MENU_DISABLED . "&nbsp;";
        } // if

        $returnarray["Z"] = MENU_DISABLED . __("Remove Label");

        $removestring = join (" OR tID =", $excludelist);
        $removestring = "tID =" . $removestring;
        $this->messageLabels->SelectWhere ($removestring, $sort);

        while ($this->messageLabels->FetchArray ()) {
          $returnarray[$this->messageLabels->tID] = "&nbsp; " . $this->messageLabels->Label;
        } // while

      } // if

      if ($foundnewlabels) {
        $gLABELVALUE = 'X';
      } else {
        $gLABELVALUE = 'Z';
      } // if 


      return ($returnarray);

    } // CreateSpecificLabelMenu

    function RetrieveMessage () {
      global $zOLDAPPLE, $zFOCUSUSER;
      
      // Message is a remote notification.
      $this->messageNotification->Select ("Identifier", $this->Identifier);
      $this->messageNotification->FetchArray ();

      // Select which server to use.
      $useServer = $zOLDAPPLE->ChooseServerVersion ($this->messageNotification->Sender_Domain);
      if (!$useServer) {
      	$this->Error = -1;
        $this->Message = __("Invalid Node Error");
      	return (FALSE);
      } // if
      
      require_once ('legacy/code/include/classes/asd/' . $useServer);
      
      // Use backwards compatible client class.
      $CLIENT = new cCLIENT();
      $remotedata = $CLIENT->RetrieveMessage($zFOCUSUSER->Username, $this->messageNotification->Sender_Domain, $this->messageNotification->Identifier);
      unset ($CLIENT);
      
      $body = $remotedata->Body;
      $subject = $remotedata->Subject;
      $stamp = $remotedata->Stamp;
      
      if ($remotedata->Error) {
      	$this->Error = -1;
        
        $this->Message = __($remotedata->ErrorTitle);
        return (FALSE);
      } else {
        // Convert data into usable form.
        $this->tID = $this->messageNotification->tID;
        $this->userAuth_uID = $this->messageNotification->userAuth_uID;
        $this->Subject = $zOLDAPPLE->Purifier->Purify ($subject);
        $this->Body = html_entity_decode ($zOLDAPPLE->Purifier->Purify ($body));
        $this->Stamp = ucwords ($stamp);
        $this->Identifier = $this->messageNotification->Identifier;
        $this->Location = $this->messageNotification->Location;
        $this->FormatDate ("Stamp");
        $this->Sender_Username = $this->messageNotification->Sender_Username;
        $this->Sender_Domain = $this->messageNotification->Sender_Domain;
        return (TRUE);
      } // if
     
      return (TRUE);
    } // RetrieveMessage

    function MoveToInbox () {
      global $zFOCUSUSER;

      // Check if user owns this message.
      if ($this->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $classlocation = $this->LocateMessage ($this->Identifier);

      switch ($classlocation) {
        case 'messageInformation':
          // Select existing message with this Identifier.
          $this->messageInformation->Select ("Identifier", $this->Identifier);
          $this->messageInformation->FetchArray ();
          $this->messageInformation->Location = FOLDER_INBOX;
          $this->messageInformation->Update ();
        break;
        case 'messageNotification':
          // Select existing message with this Identifier.
          $this->messageNotification->Select ("Identifier", $this->Identifier);
          $this->messageNotification->FetchArray ();
          $this->messageNotification->Location = FOLDER_INBOX;
          $this->messageNotification->Update ();
        break;
      } // switch

      $this->Message = __("Message Moved To Inbox");

    } // MoveToInbox

    function MoveToArchive () {
      global $zFOCUSUSER;

      // Check if user owns this message.
      if ($this->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $classlocation = $this->LocateMessage ($this->Identifier);

      switch ($classlocation) {
        case 'messageNotification':
          // Remote message
          $this->SaveMessage (FOLDER_ARCHIVE);
        break;
        case 'messageInformation':
          // Select existing message with this Identifier.
          $this->messageInformation->Select ("Identifier", $this->Identifier);
          $this->messageInformation->FetchArray ();
          $this->messageInformation->Location = FOLDER_ARCHIVE;
          $this->messageInformation->Update ();
        break;
      } // switch

      $this->Message = __("Message Has Been Archived");

    } // MoveToArchive

    function ReportSpam () {
      global $zFOCUSUSER;

      // Check if user owns this message.
      if ($this->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $classlocation = $this->LocateMessage ($this->Identifier);

      switch ($classlocation) {
        case 'messageNotification':
          // Select existing message with this Identifier.
          $this->messageNotification->Select ("Identifier", $this->Identifier);
          $this->messageNotification->FetchArray ();
          $this->messageNotification->Location = FOLDER_SPAM;
          $this->messageNotification->Update ();
          // Remote message
        break;
        case 'messageInformation':
          // Select existing message with this Identifier.
          $this->messageInformation->Select ("Identifier", $this->Identifier);
          $this->messageInformation->FetchArray ();
          $this->messageInformation->Location = FOLDER_SPAM;
          $this->messageInformation->Update ();
        break;
      } // switch

      $this->Message = __("Message Has Been Marked Spam");

    } // ReportSpam

    function NotSpam () {
      global $zFOCUSUSER;

      // Check if user owns this message.
      if ($this->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $classlocation = $this->LocateMessage ($this->Identifier);

      switch ($classlocation) {
        case 'messageNotification':
          // Select existing message with this Identifier.
          $this->messageNotification->Select ("Identifier", $this->Identifier);
          $this->messageNotification->FetchArray ();
          $this->messageNotification->Location = FOLDER_INBOX;
          $this->messageNotification->Update ();
          // Remote message
        break;
        case 'messageInformation':
          // Select existing message with this Identifier.
          $this->messageInformation->Select ("Identifier", $this->Identifier);
          $this->messageInformation->FetchArray ();
          $this->messageInformation->Location = FOLDER_ARCHIVE;
          $this->messageInformation->Update ();
        break;
      } // switch

      $this->Message = __("Message No Longer Marked Spam");

    } // NotSpam

    function SaveMessage ($pLOCATION) {
      global $zFOCUSUSER;

      // Download Remote message

      // Select any possible existing messages with this Identifier.
      $this->messageInformation->Select ("Identifier", $this->Identifier);

      // Check if a body exists.
      if (!$this->Body) {
        // No message could be retrieved, replace body with error message.
        $this->messageNotification->Select ("Identifier", $this->Identifier);
        $this->messageNotification->FetchArray ();
        $this->messageInformation->userAuth_uID = $zFOCUSUSER->uID;
        $this->messageInformation->Sender_Username = $this->messageNotification->Sender_Username;
        $this->messageInformation->Sender_Domain = $this->messageNotification->Sender_Domain;
        $this->messageInformation->Identifier = $this->messageNotification->Identifier;
        $this->messageInformation->Subject = $this->messageNotification->Subject;
        $this->messageInformation->Body = __("Message Could Not Be Retrieved");
        $this->messageInformation->Sent_Stamp = $this->messageNotification->Stamp;
        $this->messageInformation->Received_Stamp = SQL_NOW;
        $this->messageInformation->Standing = $this->messageNotification->Standing;
        $this->messageInformation->Location = $pLOCATION;
      } else {
        // Store information.
        $this->messageInformation->userAuth_uID = $zFOCUSUSER->uID;
        $this->messageInformation->Sender_Username = $this->Sender_Username;
        $this->messageInformation->Sender_Domain = $this->Sender_Domain;
        $this->messageInformation->Identifier = $this->Identifier;
        $this->messageInformation->Subject = $this->Subject;
        $this->messageInformation->Body = $this->Body;
        $this->messageInformation->Sent_Stamp = $this->Stamp;
        $this->messageInformation->Received_Stamp = SQL_NOW;
        $this->messageInformation->Standing = $this->Standing;
        $this->messageInformation->Location = $pLOCATION;
      } // if

      if ($this->messageInformation->CountResult() == 0) {
        // Add new archived message.
        $this->messageInformation->Add ();
      } else {
        // Update existing archived message.
        $this->messageInformation->Update ();
      } // if

      // Delete old notification.
      $this->messageNotification->Select ("Identifier", $this->Identifier);
      $this->messageNotification->FetchArray ();
      $this->messageNotification->Delete ();

      return (TRUE);
    } // SaveMessage

    function MoveToTrash () {
      global $zFOCUSUSER;

      $classlocation = $this->LocateMessage ($this->Identifier);
      $this->$classlocation->Select ("Identifier", $this->Identifier);
      $this->$classlocation->FetchArray ();

      // Check if user owns this message.
      if ($this->$classlocation->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $this->$classlocation->Location = FOLDER_TRASH;
      $this->$classlocation->Update ();
      
      $this->Message = __("Message Moved To Trash");

    } // MoveToTrash

    function MoveListToTrash ($pDATALIST) {

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $id) {

        $this->Identifier = $id;
        
        $this->MoveToTrash ();
      } // if

      $this->Message = __("Selected Messages Moved To Trash");

      return (TRUE);

    } // MoveListToTrash

    function MoveListToArchive ($pDATALIST) {

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $id) {

        // Select the message in question.
        $this->SelectMessage ($id);

        // Check if user owns this message.
        if ($this->CheckReadAccess () == FALSE) {
          global $gMESSAGEID;
          $gMESSAGEID = $id;
          $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
          $this->Error = -1;
          continue;
        } // if 

        $this->MoveToArchive ();
      } // if

      $this->Message = __("Selected Messages Have Been Archived");

      return (TRUE);

    } // MoveListToArchive

    function MoveListToInbox ($pDATALIST) {
      
      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $id) {

        // Select the message in question.
        $this->SelectMessage ($id);

        // Check if user owns this message.
        if ($this->CheckReadAccess () == FALSE) {
          global $gMESSAGEID;
          $gMESSAGEID = $id;
          $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
          $this->Error = -1;
          continue;
        } // if 

        $this->MoveToInbox ();
      } // if
      
      $this->Message = __("Selected Messages Moved To Inbox");

      return (TRUE);

    } // MoveListToInbox

    function ReportListAsSpam ($pDATALIST) {

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $id) {

        // Select the message in question.
        $this->SelectMessage ($id);

        // Check if user owns this message.
        if ($this->CheckReadAccess () == FALSE) {
          global $gMESSAGEID;
          $gMESSAGEID = $id;
          $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
          $this->Error = -1;
          continue;
        } // if 

        $this->ReportSpam ();
      } // if

      $this->Message = __("Selected Messages Marked Spam");

      return (TRUE);

    } // ReportListAsSpam

    // Send an Appleseed message.
    function Send ($pADDRESSES, $pSUBJECT, $pBODY, $pSENDERUSERNAME = NULL) {
      global $zOLDAPPLE, $zFOCUSUSER; 
      
      global $gSITEDOMAIN, $gtID;
      
      // Append found circles onto addresslist.
      if (!$addresslist = $this->CreateAddressList ($pADDRESSES)) {
        $this->Message = __("Error Unable To Send Message");
        $this->Errorlist['recipientaddress'] = __("Error No Recipients");
        $this->Error = -1;
        return (FALSE);
      } 
      
      // If no subject, use standard "no subject" text.
      if ($pSUBJECT == NULL) {
        $pSUBJECT = __("Error No Subject");
      } // if

      // Verify addresses in list.
      foreach ($addresslist as $id => $address) {
        if (!$this->VerifyAddress ($address)) return (FALSE);
      } // foreach
      
      // To: field will be filled with addresses instead of circle:CircleName's
      global $gRECIPIENTADDRESS;
      $gRECIPIENTADDRESS = join (', ', $addresslist);
      
      // Store the "sent" message.
      $table_id = $this->StoreMessage ($zFOCUSUSER->uID, $pSUBJECT, $pBODY, SQL_NOW, FOLDER_SENT);
      
      // Loop through the list of recipients.
      foreach ($addresslist as $id => $address) {
        // Split the address.
        list ($username, $domain) = explode ('@', $address);  
        
        // Create a unique identifier.
        $identifier = $zOLDAPPLE->RandomString (128);
        
        // Send a message notification.
        if ($domain != $gSITEDOMAIN) {
          $this->RemoteMessage ($table_id, $zFOCUSUSER->uID, $address, $identifier, $pSUBJECT, $pBODY);
        } else {
          $this->LocalMessage ($table_id, $zFOCUSUSER->uID, $address, $identifier, $pSUBJECT, $pBODY);
        } // if
      } // foreach
      
      $this->RemoveDraft ($gtID);
      
      if ($this->Error) {
      	return (FALSE);
      } else {
        $this->Message = __("Message Sent");
        return (TRUE);
      }

    } // Send
    
    function CreateAddressList ($pADDRESSES) {
      
      // Find each address in list.
      $addresslist = explode (',', $pADDRESSES);
      
      // Load circle requests from list.
      foreach ($addresslist as $id => $address) {
        $circles = null;
        if (strstr ($address, ':')) {
          list ($type, $circlename) = explode (':', $address);
          unset ($addresslist[$id]);
          if (!$circles = $this->LoadFromCircle($circlename)) continue;
          foreach ($circles as $cid => $address) {
            $addresslist[] = $address;
          } // foreach
        } // if
      } // foreach 
      
      // Second pass to remove empty addresses.
      foreach ($addresslist as $id => $address) {
        $address = str_replace (' ', '', $address);
        $addresslist[$id] = $address;
        
        // If it's empty, pop off list and skip.
        if (!$address) {
          unset ($addresslist[$id]);
          continue;
        } // if
      } // foreach
      
      if (count($addresslist) == 0) return (FALSE);
      
      return ($addresslist);
    } // CreateAddressList
    
    // Load a list of addresses from a friend's circle.
    function LoadFromCircle ($pCIRCLE) {
      global $zFOCUSUSER;
      
      $FRIENDS = new cFRIENDINFORMATION ();
      
      // Load the circle information.
      $criteria = array ("userAuth_uID" => $zFOCUSUSER->uID,
                         "Name"         => $pCIRCLE);
      $FRIENDS->friendCircles->Select ("Name", $pCIRCLE);
      $FRIENDS->friendCircles->FetchArray ();
      
      // Check if circle with this name was found.
      if ($FRIENDS->friendCircles->CountResult() == 0) {
        $this->Error = -1;
        global $gBADCIRCLE;
        $gBADCIRCLE = $pCIRCLE;
        $this->Message = __("Unable To Send Message");
        $this->Errorlist['recipientaddress'] = __("Friends Circle Does Not Exist", array ( "circle" => $gBADCIRCLE ) );
        $this->Error = -1;
      } // if
      
      $return = array ();
      
      // Load the friend circles list.
      $FRIENDS->friendCirclesList->Select ("friendCircles_tID", $FRIENDS->friendCircles->tID);
      while ($FRIENDS->friendCirclesList->FetchArray()) {
        $FRIENDS->Select ("tID", $FRIENDS->friendCirclesList->friendInformation_tID);
        $FRIENDS->FetchArray ();
        $address = $FRIENDS->Username . '@' . $FRIENDS->Domain;
        $return[] = $address;
      } // while
      
      return ($return); 
    } // LoadFromCircle
    
    // Place the message in the message store.
    function StoreMessage ($pUSERID, $pSUBJECT, $pBODY, $pSTAMP, $pLOCATION) {
      $this->messageStore->userAuth_uID = $pUSERID;
      $this->messageStore->Subject = $pSUBJECT;
      $this->messageStore->Body = $pBODY;
      $this->messageStore->Stamp = $pSTAMP;
      $this->messageStore->Location = $pLOCATION;
      
      $this->messageStore->Add ();
      
      // Return the table ID of the recently stored message.
      return ($this->messageStore->AutoIncremented ());
    } // StoreMessage
    
    // Recieve a local message.
    function LocalMessage ($pMESSAGEID, $pSENDERID, $pRECIEVERADDRESS, $pIDENTIFIER, $pSUBJECT, $pBODY, $pSENTSTAMP = SQL_NOW, $pRECIEVEDSTAMP = SQL_NOW, $pSTANDING = MESSAGE_UNREAD, $pLOCATION = FOLDER_INBOX) {
      global $gSITEDOMAIN;
      
      // Get the information about the sender.
      $USER = new cOLDUSER();
      $USER->Select ("uID", $pSENDERID);
      $USER->FetchArray ();
      $username = $USER->Username;
      $domain = $gSITEDOMAIN;
      $fullname = $USER->userProfile->getAlias();
      
      // Get the information about the reciever.
      list ($reciever_username, $reciever_domain) = explode ('@', $pRECIEVERADDRESS);
      $USER->Select ("Username", $reciever_username);
      $USER->FetchArray ();
      $reciever_id = $USER->uID;
      $reciever_fullname = $USER->userProfile->getAlias();
      $reciever_email = $USER->userProfile->Email;
      
      unset ($USER);
      
      // Add the received message.
      $this->messageInformation->userAuth_uID = $reciever_id;
      $this->messageInformation->Sender_Username = $username;
      $this->messageInformation->Sender_Domain = $domain;
      $this->messageInformation->Identifier = $pIDENTIFIER;
      $this->messageInformation->Subject = $pSUBJECT;
      $this->messageInformation->Body = $pBODY;
      $this->messageInformation->Sent_Stamp = $pSENTSTAMP;
      $this->messageInformation->Received_Stamp = $pRECIEVEDSTAMP;
      $this->messageInformation->Standing = $pSTANDING;
      $this->messageInformation->Location = $pLOCATION;
      
      $this->messageInformation->Add ();
      
      // Add the recipient.
      $this->messageRecipient->messageStore_tID = $pMESSAGEID;
      $this->messageRecipient->userAuth_uID = $pSENDERID;
      $this->messageRecipient->Identifier = $pIDENTIFIER;
      $this->messageRecipient->Username = $reciever_username;
      $this->messageRecipient->Domain = $reciever_domain;
      $this->messageRecipient->Standing = $pSTANDING;
      
      $this->messageRecipient->Add ();
      
      // Send an email notification to the reciever.
      //$this->NotifyMessage ($reciever_email, $reciever_username, $reciever_fullname, $fullname);
      $recipient = $this->messageRecipient->Username  . '@' . $this->messageRecipient->Domain;
      $sender = $username . '@' . $domain;
      $this->_Email ( $reciever_email, $recipient, $sender, $pSUBJECT );
      

      return (TRUE);
      
    } // LocalMessage
    
    // Send a remote message notification.
    function RemoteMessage ($pMESSAGEID, $pSENDERID, $pRECIEVERADDRESS, $pIDENTIFIER, $pSUBJECT, $pBODY, $pSENTSTAMP = SQL_NOW, $pRECIEVEDSTAMP = SQL_NOW, $pSTANDING = MESSAGE_UNREAD, $pLOCATION = FOLDER_INBOX) {
      global $zOLDAPPLE;
      global $zXML, $zREMOTE;
      
      global $gAPPLESEEDVERSION;
      
      global $gSITEDOMAIN;
      
      // Get the information about the sender.
      $USER = new cOLDUSER();
      $USER->Select ("uID", $pSENDERID);
      $USER->FetchArray();
      $senderfullname = $USER->userProfile->getAlias();
      $senderusername = $USER->Username;
      
      // Get the information about the reciever.
      list ($username, $domain) = explode ('@', $pRECIEVERADDRESS);

      // Select which server to use.
      $useServer = $zOLDAPPLE->ChooseServerVersion ($domain);
      if (!$useServer) {
      	$this->Error = -1;
        $this->Message = __("Invalid Node Error");
      	return (FALSE);
      } // if
      
      require_once ('legacy/code/include/classes/asd/' . $useServer);
      
      // Use backwards compatible client class.
      $CLIENT = new cCLIENT();
      $remotedata = $CLIENT->RemoteMessage($username, $domain, $pIDENTIFIER, $pSUBJECT, $senderusername, $senderfullname);
      unset ($CLIENT);
      
      if ($remotedata->Error) {
      	$this->Error = -1;
        $this->Message = __($remotedata->ErrorTitle);
        return (FALSE);
      } else {
        // Add the recipient.
        $this->messageRecipient->messageStore_tID = $pMESSAGEID;
        $this->messageRecipient->userAuth_uID = $pSENDERID;
        $this->messageRecipient->Identifier = $pIDENTIFIER;
        $this->messageRecipient->Username = $username;
        $this->messageRecipient->Domain = $domain;
        $this->messageRecipient->Standing = $pSTANDING;
        $this->messageRecipient->Add ();
        return (TRUE);
      } // if
      
    } // RemoteMessage
    
    function VerifyAddress ($pADDRESS) {
      global $zOLDAPPLE;
      
      // Step 1: Check if address is valid.
      if (!$zOLDAPPLE->CheckEmail ($pADDRESS)) {
        global $gWRONGADDRESS;
        $gWRONGADDRESS = $pADDRESS;
        $this->Message = __("Unable To Send Message");
        $this->Errorlist['recipientaddress'] = __("Address Is Invalid", array ( "address" => $gWRONGADDRESS ) );
        $this->Error = -1;
        return (FALSE);
      } // if

      // Step 2: Check if user exists.
      list ($username, $domain) = explode ('@', $pADDRESS);
      if (!$zOLDAPPLE->GetUserInformation ($username, $domain)) {
        $this->Error = -1;
        global $gWRONGADDRESS;
        $gWRONGADDRESS = $pADDRESS;
        $this->Message = __("Unable To Send Message");
        $this->Errorlist['recipientaddress'] = __("User Does Not Exist", array ( "address" => $gWRONGADDRESS ) );
        $this->Error = -1;
        return (FALSE);
      } // if
      
      return (TRUE);
    } // VerifyAddress
    
    // Notify the user that a message has been sent.
    function NotifyMessage ($pEMAIL, $pRECIPIENTUSERNAME, $pRECIPIENTFULLNAME, $pSENDERNAME) {
    	
      global $zOLDAPPLE;
      global $gSITEDOMAIN;

      global $gSENDERNAME;
      $gSENDERNAME = $pSENDERNAME;

      global $gRECIPIENTFULLNAME;
      $gRECIPIENTFULLNAME = $pRECIPIENTFULLNAME;

      global $gMESSAGESURL, $gSITEURL;
      $gMESSAGESURL = $gSITEURL . "/profile/" . $pRECIPIENTUSERNAME . "/messages/";

      $to = $pEMAIL;

      $subject = __("Comment Subject", array ( "sender" => $gSENDERNAME, "sitedomain" => $gSITEDOMAIN ) );

      $body = __("Comment Body", array ( "to" => $gRECIPIENTFULLNAME, "from" => $gSENDERNAME, "link" => $gMESSAGESURL ) );

      $from = __("messages@site", array ( "domain" => $gSITEDOMAIN ) );

      $fromname = __( "Message From" );

      $zOLDAPPLE->Mailer->From = $from;
      $zOLDAPPLE->Mailer->FromName = $fromname;
      $zOLDAPPLE->Mailer->Body = $body;
      $zOLDAPPLE->Mailer->Subject = $subject;
      $zOLDAPPLE->Mailer->AddAddress ($to);
      $zOLDAPPLE->Mailer->AddReplyTo ($from);

      $zOLDAPPLE->Mailer->Send();

      $zOLDAPPLE->Mailer->ClearAddresses();
      
      unset ($to);
      unset ($subject);
      unset ($body);

      return (TRUE);

    } // NotifyMessage
    
	private function _Email ( $pAddress, $pRecipient, $pSender, $pSubject ) {
		global $zApp;
		
		$data = array ( 'account' => $pSender, 'source' => ASD_DOMAIN, 'request' => $pSender );
		$CurrentInfo = $zApp->GetSys ( 'Event' )->Trigger ( 'On', 'User', 'Info', $data );
		$SenderFullname = $CurrentInfo->fullname;
		$SenderNameParts = explode ( ' ', $CurrentInfo->fullname );
		$SenderFirstName = $SenderNameParts[0];
		
		list ( $RecipientUsername, $RecipientDomain ) = explode ( '@', $pRecipient );
		
		$SenderAccount = $pSender;
		
		$RecipientEmail = $pAddress;
		$MailSubject = __( "Legacy Someone Sent A Message", array ( "fullname" => $SenderFullname ) );
		$Byline = __( "Legacy Sent A Message" );
		$Subject = $pSubject;
		$Link = 'http://' . ASD_DOMAIN . '/profile/' . $RecipientUsername . '/messages/';
		$Body = __( "Legacy Message Description", array ( 'fullname' => $SenderFullname, 'domain' => ASD_DOMAIN, 'firstname' => $senderFirstname, 'link' => $Link ) );
		$LinkDescription = __( "Legacy Click Here" );
		
		$Message = array ( 'Type' => 'User', 'SenderFullname' => $SenderFullname, 'SenderAccount' => $SenderAccount, 'RecipientEmail' => $RecipientEmail, 'MailSubject' => $MailSubject, 'Byline' => $Byline, 'Subject' => $Subject, 'Body' => $Body, 'LinkDescription' => $LinkDescription, 'Link' => $Link );
		$zApp->GetSys ( 'Components' )->Talk ( 'Postal', 'Send', $Message );
		
		return ( true );
	} 
	

    function SaveDraft ($pADDRESSES, $pSUBJECT, $pBODY) {
      global $zOLDAPPLE, $zFOCUSUSER;

      global $gIDENTIFIER;
      global $gSITEDOMAIN;
      global $gtID;
      
      $this->RemoveDraft ($gtID);

      if ($pSUBJECT == NULL) {
        $pSUBJECT = __("No Subject");
      } // if

      // Store the message.
      $table_id = $this->StoreMessage ($zFOCUSUSER->uID, $pSUBJECT, $pBODY, SQL_NOW, FOLDER_DRAFTS);
      
      $addresslist = $this->CreateAddressList ($pADDRESSES);
      
      // Store the recipients.
      foreach ($addresslist as $id => $address) {
        $address = str_replace (' ', '', $address);
        $identifier = $zOLDAPPLE->RandomString (128);

        list ($username, $domain) = explode ('@', $address);
        $this->messageRecipient->messageStore_tID = $table_id;
        $this->messageRecipient->userAuth_uID = $zFOCUSUSER->uID;
        $this->messageRecipient->Identifier = $identifier;
        $this->messageRecipient->Username = $username;
        $this->messageRecipient->Domain = $domain;
        $this->messageRecipient->Standing = MESSAGE_UNREAD;
        $this->messageRecipient->Add ();
      } // foreach
      
      $this->Message = __("Record Updated");

      return (TRUE);
      
    } // SaveDraft

    function BufferRecipientList () {
      global $zHTML, $zOLDAPPLE;
      
      global $gFRAMELOCATION;
      
      global $bRECIPIENT;
        
      $this->messageRecipient->Select ("messageStore_tID", $this->tID);
      
      $buffer = null;
      
      while ($this->messageRecipient->FetchArray()) {
        $bRECIPIENT = $zHTML->CreateUserLink ($this->messageRecipient->Username, $this->messageRecipient->Domain);
        if ($this->messageRecipient->Standing == MESSAGE_READ) {
          $zOLDAPPLE->SetTag ('READSTATUS', __("Read"));
        } else {
          $zOLDAPPLE->SetTag ('READSTATUS', __("Unread"));
        } // if
        $bufferarray[] =  $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/sent/recipient.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
      } // while
      $buffer = join (' ', $bufferarray);
      
      return ($buffer);
    } // BufferRecipientList
    
    function DeleteForever () {

      $classlocation = $this->LocateMessage ($this->Identifier);
      $this->$classlocation->Select ("Identifier", $this->Identifier);
      $this->$classlocation->FetchArray ();

      // Check if user owns this message.
      if ($this->$classlocation->CheckReadAccess () == FALSE) {
        global $gMESSAGEID;
        $gMESSAGEID = $this->tID;
        $this->Message = __("Message Access Denied", array ( "id" => $gMESSAGEID ) );
        $this->Error = -1;
        return (FALSE);
      } // if 

      $this->$classlocation->tID = $this->$classlocation->tID;
      $this->$classlocation->Delete ();

      $this->Message = __("Record Deleted");

      return (TRUE);
    } // DeleteForever

    function DeleteListForever ($pDATALIST) {

      if (count ($pDATALIST) == 0) {
        $this->Message = __("None Selected");
        $this->Error = -1;
        return (FALSE);
      } // if

      foreach ($pDATALIST as $key => $id) {

        $this->SelectMessage ($id);
        $this->DeleteForever ();

      } // foreach

      $this->Message = __("Records Deleted", array ('count' => count($pDATALIST)));

      return (TRUE);

    } // DeleteListForever

    // Create the label list buffer.
    function BufferLabelList () {

      global $gLABELNAME;
      global $gLABELSELECT; global $gCOUNTNEWMESSAGES;
      global $gFRAMELOCATION, $gPROFILESUBACTION, $gLABELSELECT;
      global $gLABELNAME;

      global $zFOCUSUSER, $zOLDAPPLE;

      $labelcriteria = array ("userAuth_uID" => $zFOCUSUSER->uID);   
      $this->messageLabels->SelectByMultiple ($labelcriteria, "Label");

      // Check if any labels were found.
      if ($this->messageLabels->CountResult () == 0) {

        // None found.  Output an error.
        $this->messageLabelList->Message = __("No Results Found");

        return (NULL);

      } else {

        $output = "";

        // Buffer the labels list.
        ob_start ();  
    
        $labelcriteria = array ("userAuth_uID" => $zFOCUSUSER->uID);   
        $this->messageLabels->SelectByMultiple ($labelcriteria, "Label");
  
        // Loop through the list of labels.
        while ($this->messageLabels->FetchArray ()) {

          // Push the label name into a global variable.
          $gLABELNAME = $this->messageLabels->Label;
      
          // Count the number of new messages.
          $this->CountNewInLabels ($this->messageLabels->tID);
   
          // Determine whether a label has been selected or not.
          if ($gPROFILESUBACTION == $gLABELNAME) {
            $gLABELSELECT[$gLABELNAME] = 'selected';
          } else {
            $gLABELSELECT[$gLABELNAME] = 'normal';
          } // if
     
          $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/user/messages/label.aobj", INCLUDE_SECURITY_NONE);
        } // while
  
        $output = ob_get_clean();
    
      } // if

      return ($output);

    } // BufferLabelList

    // Count new messages for each label.
    function CountNewInLabels ($pLABELID) {
      global $zFOCUSUSER, $zOLDAPPLE, $zHTML;

      global $gFRAMELOCATION, $gLABELNAME, $gPROFILESUBACTION; 

      global $gCOUNTNEWMESSAGES;

      global $gTARGET, $gSCROLLSTEP, $gSCROLLMAX, $gSORT;

      global $gLABELDATA;

      global $gMESSAGESTAMP, $gMESSAGESTANDING;

      global $gACTION, $gCHECKED;

      global $gSENDERNAME, $gSENDERONLINE;

      $NotificationTable = $this->messageNotification->TableName;
      $InformationTable = $this->messageInformation->TableName;
			$messageLabelListTable = $this->messageLabelList->TableName;

      $query  = "(SELECT COUNT($NotificationTable.tID) AS CountResult " .
                " FROM   $NotificationTable, $messageLabelListTable " .
                " WHERE  $messageLabelListTable.Identifier = $NotificationTable.Identifier " .
                " AND    $NotificationTable.Standing = " . MESSAGE_UNREAD . " " .
                " AND    $NotificationTable.Location != " . FOLDER_SPAM . " " .
                " AND    $NotificationTable.Location != " . FOLDER_TRASH . " " .
                " AND    $NotificationTable.userAuth_uID = " . $zFOCUSUSER->uID . " " .
                " AND    $messageLabelListTable.messageLabels_tID = " . $pLABELID . ")";

      $this->Query ($query);
      $this->FetchArray();
      $total = $this->CountResult;
      $this->CountResult = 0;

      $query = "(SELECT COUNT($InformationTable.tID) AS CountResult " .
               " FROM   $InformationTable, $messageLabelListTable " .
               " WHERE  $messageLabelListTable.Identifier = $InformationTable.Identifier " .
               " AND    $InformationTable.Standing = " . MESSAGE_UNREAD . " " .
               " AND    $InformationTable.userAuth_uID = " . $zFOCUSUSER->uID . " " .
               " AND    $InformationTable.Location != " . FOLDER_SPAM . " " .
               " AND    $InformationTable.Location != " . FOLDER_TRASH . " " .
               " AND    $messageLabelListTable.messageLabels_tID = " . $pLABELID . ")";

      $this->Query ($query);
      $this->FetchArray();
      $total += $this->CountResult;

      if ($total == 0) {
        $gCOUNTNEWMESSAGES = "";
      } else {
        $gCOUNTNEWMESSAGES = '(' . $total . ')';
      } // if 

      return (TRUE);
 
    } // CountNewInLabels

  } // cMESSAGE

  // Message information class.
  class cMESSAGEINFORMATION extends cDATACLASS {

    var $tID, $userAuth_uID, $Sender_Username, $Sender_Domain, 
        $Subject, $Body, $Received_Stamp, $Sent_Stamp,
        $Location, $Standing;
    var $Cascade;

    function cMESSAGEINFORMATION ($pDEFAULTCONTEXT = '') {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . 'messageInformation';
      $this->tID = '';
      $this->userAuth_uID = '';
      $this->Sender_Username = '';
      $this->Sender_Domain = '';
      $this->Subject = '';
      $this->Body = '';
      $this->Sent_Stamp = '';
      $this->Received_Stamp = '';
      $this->Standing = '';
      $this->Location = '';
      $this->PageContext = '';
      $this->Error = 0;
      $this->Message = '';
      $this->Result = '';
      $this->FieldNames = '';
      $this->PrimaryKey = 'tID';
      $this->Cascade = '';
 
      // Create extended field definitions
      $this->FieldDefinitions = array (

        'tID'            => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

        'userAuth_uID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

       'Sender_Username' => array ('max'        => '32',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

         'Sender_Domain' => array ('max'        => '64',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Subject'        => array ('max'        => '128',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Body'           => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => NO,
                                   'datatype'   => 'STRING'),

        'Sent_Stamp'     => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => YES,
                                   'datatype'   => 'DATETIME'),

        'Received_Stamp' => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => YES,
                                   'datatype'   => 'DATETIME'),

        'Standing'       => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),

        'Location'       => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),
      );

      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;

      // Grab the fields from the database.
      $this->Fields();
 
    } // Constructor

  } // cMESSAGEINFORMATION

  // Message Label List class.
  class cMESSAGELABELLIST extends cDATACLASS {

    var $tID, $messageLabels_tID, $messageContent_tID;

    function cMESSAGELABELLIST ($pDEFAULTCONTEXT = '') {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . 'messageLabelList';
      $this->tID = '';
      $this->messageLabels_tID = '';
      $this->messageContent_tID = '';
      $this->Error = 0;
      $this->Message = '';
      $this->Result = '';
      $this->PrimaryKey = 'tID';
      $this->ForeignKey = 'messageContent_tID';
 
      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;
 
      // Create extended field definitions
      $this->FieldDefinitions = array (
        'tID'            => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

  'messageContent_tID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),

   'messageLabels_tID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),
      );

      // Grab the fields from the database.
      $this->Fields();

    } // Constructor

    // Count messages for label.
    function CountInLabel ($pLABELID) {
     
      // Determine how many messages are attached to this label.
      $countcriteria = array ("messageLabels_tID" => $pLABELID);
      $this->SelectByMultiple ($countcriteria);

      $countresult = $this->CountResult ();

      return ($countresult);

    } // CountInLabels

  } // cMESSAGELABELLIST

  // Message labels class.
  class cMESSAGELABELS extends cDATACLASS {

    var $tID, $userAuth_uID, $Label;

    function cMESSAGELABELS ($pDEFAULTCONTEXT = '') {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . 'messageLabels';
      $this->tID = '';
      $this->userAuth_uID = '';
      $this->Label = '';
      $this->Error = 0;
      $this->Message = '';
      $this->Result = '';
      $this->PrimaryKey = 'tID';
      $this->ForeignKey = 'messageContent_tID';
 
      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;
 
      // Create extended field definitions
      $this->FieldDefinitions = array (
        'tID'            => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

        'userAuth_uID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),

        'Label'          => array ('max'        => '128',
                                   'min'        => '1',
                                   'illegal'    => ', .',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

      );

      // Grab the fields from the database.
      $this->Fields();
 
    } // Constructor

    function LoadLabels () {
      global $zFOCUSUSER;

      $this->Select ("userAuth_uID", $zFOCUSUSER->uID);

      return (TRUE);

    } // LoadLabels

  } // cMESSAGELABELS

  // Message notification class.
  class cMESSAGENOTIFICATION extends cDATACLASS {

    var $tID, $userAuth_uID, $Sender_Username, $Sender_Domain, 
        $Subject, $Identifier, $Stamp, $Standing;
    var $Cascade;

    function cMESSAGENOTIFICATION ($pDEFAULTCONTEXT = '') {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . 'messageNotification';
      $this->tID = '';
      $this->userAuth_uID = '';
      $this->Sender_Username = '';
      $this->Sender_Domain = '';
      $this->Identifier = '';
      $this->Subject = '';
      $this->Stamp = '';
      $this->PageContext = '';
      $this->Error = 0;
      $this->Message = '';
      $this->Result = '';
      $this->FieldNames = '';
      $this->PrimaryKey = 'tID';
      $this->Cascade = '';
 
      // Create extended field definitions
      $this->FieldDefinitions = array (

        'tID'            => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

        'userAuth_uID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

       'Sender_Username' => array ('max'        => '32',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

         'Sender_Domain' => array ('max'        => '64',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Subject'        => array ('max'        => '128',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Identifier'     => array ('max'        => '128',
                                   'min'        => '128',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Stamp'          => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => YES,
                                   'datatype'   => 'DATETIME'),

        'Standing'       => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),

        'Location'       => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),
      );

      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;

      // Grab the fields from the database.
      $this->Fields();
 
    } // Constructor

  } // cMESSAGENOTIFICATION

  // Message store class.
  class cMESSAGESTORE extends cDATACLASS {

    var $tID, $userAuth_uID, $Sender_Username, $Sender_Domain, 
        $Subject, $Identifier, $Stamp;
    var $Cascade;

    function cMESSAGESTORE ($pDEFAULTCONTEXT = '') {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . 'messageStore';
      $this->tID = '';
      $this->userAuth_uID = '';
      $this->Sender_Username = '';
      $this->Sender_Domain = '';
      $this->Identifier = '';
      $this->Subject = '';
      $this->Stamp = '';
      $this->PageContext = '';
      $this->Error = 0;
      $this->Message = '';
      $this->Result = '';
      $this->FieldNames = '';
      $this->PrimaryKey = 'tID';
      $this->Cascade = '';
 
      // Create extended field definitions
      $this->FieldDefinitions = array (

        'tID'            => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

        'userAuth_uID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

        'Subject'        => array ('max'        => '128',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Stamp'          => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => YES,
                                   'datatype'   => 'DATETIME'),
      );

      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;

      // Grab the fields from the database.
      $this->Fields();
 
    } // Constructor
    
  } // cMESSAGESTORE
  
  class cMESSAGERECIPIENTS extends cDATACLASS {

    var $tID, $messageStore_tID, $userAuth_uID, $Identifier,
         $Username,  $Domain,  $Standing;
    var $Cascade;

    function cMESSAGERECIPIENTS ($pDEFAULTCONTEXT = '') {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . 'messageRecipient';
      $this->tID = '';
      $this->userAuth_uID = '';
      $this->messageStore_tID = '';
      $this->Identifier = '';
      $this->Username = '';
      $this->Domain = '';
      $this->Standing = '';
      $this->PageContext = '';
      $this->Error = 0;
      $this->Message = '';
      $this->Result = '';
      $this->FieldNames = '';
      $this->PrimaryKey = 'tID';
      $this->ForeignKey = 'messageStore_tID';
      $this->Cascade = '';
 
      // Create extended field definitions
      $this->FieldDefinitions = array (

        'tID'            => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

        'userAuth_uID'   => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => 'unique',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'INTEGER'),

              'Username' => array ('max'        => '32',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

                'Domain' => array ('max'        => '64',
                                   'min'        => '1',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Identifier'     => array ('max'        => '128',
                                   'min'        => '128',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => NO,
                                   'sanitize'   => YES,
                                   'datatype'   => 'STRING'),

        'Standing'       => array ('max'        => '',
                                   'min'        => '',
                                   'illegal'    => '',
                                   'required'   => '',
                                   'relation'   => '',
                                   'null'       => YES,
                                   'sanitize'   => NO,
                                   'datatype'   => 'INTEGER'),

      );

      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;

      // Grab the fields from the database.
      $this->Fields();
 
    } // Constructor
    
  } // cMESSAGERECIPIENTS
?>
