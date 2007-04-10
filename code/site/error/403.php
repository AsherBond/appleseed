<?php
  // +-------------------------------------------------------------------+
  // | Appleseed Web Community Management Software                       |
  // | http://appleseed.sourceforge.net                                  |
  // +-------------------------------------------------------------------+
  // | FILE: 403.php                                 CREATED: 01-01-2005 + 
  // | LOCATION: /code/site/error/                  MODIFIED: 02-11-2005 +
  // +-------------------------------------------------------------------+
  // | Copyright (c) 2004-2006 Appleseed Project                         |
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
  // | VERSION:      0.3.0                                               |
  // | DESCRIPTION:  Provides handling for a custom 403 error page.      |
  // +-------------------------------------------------------------------+

  // Change to document root directory.
  chdir ($_SERVER['DOCUMENT_ROOT']);

  // Include BASE API classes.
  require_once ('code/include/classes/BASE/application.php'); 
  require_once ('code/include/classes/base.php'); 
  require_once ('code/include/classes/system.php'); 
  require_once ('code/include/classes/BASE/remote.php'); 

  // Include Appleseed classes.
  require_once ('code/include/classes/appleseed.php'); 
  require_once ('code/include/classes/privacy.php'); 
  require_once ('code/include/classes/friends.php'); 
  require_once ('code/include/classes/messages.php'); 
  require_once ('code/include/classes/users.php'); 
  require_once ('code/include/classes/auth.php'); 

  // Create the Application class.
  $zAPPLE = new cAPPLESEED ();
  
  // Set Global Variables (Put this at the top of wrapper scripts)
  $zAPPLE->SetGlobals ();

  // Initialize Appleseed.
  $zAPPLE->Initialize("site.error.403");

  // Lookup the page title.
  $zSTRINGS->Lookup ('BROWSER.SUBTITLE', 'SITE.ERROR.403');
  $gPAGESUBTITLE = $zSTRINGS->Output;

  // Lookup the title.
  $zSTRINGS->Lookup ('OBJECT.TITLE', 'SITE.ERROR.403');
  $gERRORTITLE = $zSTRINGS->Output;

  // Lookup the content.
  $zSTRINGS->Lookup ('OBJECT.CONTENT', 'SITE.ERROR.403');
  $gERRORMSG = $zSTRINGS->Output;

  // Include the outline frame.
  $zAPPLE->IncludeFile ("$gFRAMELOCATION/frames/site/error/403.afrw", INCLUDE_SECURITY_NONE);

?>
