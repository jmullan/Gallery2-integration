<?php

/************************************************************************/
/* PHP-NUKE: Web Portal System                                          */
/* ===========================                                          */
/*                                                                      */
/* Copyright (c) 2002 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/
/*         Additional security & Abstraction layer conversion           */
/*                           2003 chatserv                              */
/*      http://www.nukefixes.com -- http://www.nukeresources.com        */
/************************************************************************/

if (eregi("block-G2_Sidebar.php", $_SERVER['SCRIPT_NAME'])) {
    Header("Location: index.php");
    die();
}


// ----------------- Lang difinition (temporary) ---------------------

define("_G2_EMBED_PHP_FILE","embed.php");
define("_G2_CONFIGURATION_NOT_DONE","The module has not yet been configured.");


// --------------------------------------------------------
// Mapping between Phpnuke and Gallery2 language definition
// --------------------------------------------------------

$Phpnuke2G2Lang = array(
'danish' 		=> 'da',
'dutch' 		=> 'nl',
'german' 		=> 'de',
'greek' 			=> 'el',
'english' 		=> 'en',
'american' 		=> 'en',
'spanish' 		=> 'es',
'finnish' 		=> 'fi',
'french' 		=> 'fr',
'irish' 		=> 'ga',			// not available
'italian' 		=> 'it',
'japanese'			=> 'ja',	// not available
'norwegian' 	=> 'no',
'polish' 		=> 'pl',
'portuguese'	=> 'pt',
'swedish' 	=> 'sv',
'chinese' 			=> 'zh',
);


// -------------------------------------------------------------------


global $prefix, $multilingual, $currentlang, $db,$g2bodyHtml;
  
  include("modules/gallery2/gallery2.cfg");
  
  if ($g2configurationdone != "true")
  {
  	$content .= _G2_CONFIGURATION_NOT_DONE; 
  	return;
  }

require_once($g2embedparams[embedphpfile]."/"._G2_EMBED_PHP_FILE);

	$g2currentlang = $Phpnuke2G2Lang[$currentlang];

	$ret = GalleryEmbed::init(array(
       'embedUri' => $g2embedparams[embedUri],
       'relativeG2Path' => $g2embedparams[relativeG2Path],
       'loginRedirect' => $g2embedparams[loginRedirect],
       'activeUserId' => $g2embedparams[activeUserId],
       'activeLanguage' =>$g2currentlang));
       
		  	if ($g2mainparams[showSidebar]!="true")
		  	{
		    	GalleryCapabilities::set('showSidebar', false);
		  	}

       
    // handle the G2 request
    $g2moddata = GalleryEmbed::handleRequest();
    
  
    // show error message if isDone is not defined
    if (!isset($g2moddata['isDone'])) 
    {
      echo 'isDone is not defined, something very bad must have happened.';
      exit;
    }
    
    // die if it was a binary data (image) request
    if ($g2moddata['isDone']) 
    {
      exit; // uploads module does this too
    }
  
	  if ($ret->isError()) 
	  {
	    echo $ret->getAsHtml();
	  }
	  
		$g2bodyHtml = $g2moddata['bodyHtml']; 

		$content .= '<table border="0" cellpadding="0" cellspacing="0">';
		$content .= $g2moddata['sidebarHtml']; 
		$content .= '</table>';
		

?>