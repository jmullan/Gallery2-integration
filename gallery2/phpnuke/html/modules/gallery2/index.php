<?php

/************************************************************************/
/* PHP-NUKE: Web Portal System                                          */
/* ===========================                                          */
/*                                                                      */
/* Copyright (c) 2002 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* ======================                                               */
/* Based on Automated FAQ                                               */
/* Copyright (c) 2001 by                                                */
/*    Richard Tirtadji AKA King Richard (rtirtadji@hotmail.com)         */
/*    Hutdik Hermawan AKA hotFix (hutdik76@hotmail.com)                 */
/* http://www.phpnuke.web.id                                            */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/
/*         Additional security & Abstraction layer conversion           */
/*                           2003 chatserv                              */
/*      http://www.nukefixes.com -- http://www.nukeresources.com        */
/************************************************************************/

if (!eregi("modules.php", $_SERVER['PHP_SELF'])) {
    die ("You can't access this file directly...");
}

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


global $currentlang,$g2bodyHtml;
require_once("mainfile.php");
$module_name = basename(dirname(__FILE__));
get_lang($module_name);
include("header.php");  

global $db,$user_prefix;
cookiedecode($user);
$uname = $cookie[1];
$uid='';  
if (is_user($user)) 
{
    $row3 = $db->sql_fetchrow($db->sql_query("SELECT user_id FROM $user_prefix"._users." WHERE username='$uname'"));
	$uid = intval($row3[user_id]);
} 
  
  if ($g2bodyHtml==null)
  {

		  include("modules/gallery2/gallery2.cfg");
		  
		  if ($g2configurationdone != "true")
		  {
		  	OpenTable();
		  	echo "<center>"._G2_CONFIGURATION_NOT_DONE."</center>";
			  CLoseTable();
		    include("footer.php");
		    return;
		 	}
		    
		  require_once($g2embedparams[embedphpfile]."/"._G2_EMBED_PHP_FILE);
		           
				// from configfile
				
				$g2currentlang = $Phpnuke2G2Lang[$currentlang];
				
				$ret = GalleryEmbed::init(array(
		           'embedUri' => $g2embedparams[embedUri],
		           'relativeG2Path' => $g2embedparams[relativeG2Path],
		           'loginRedirect' => $g2embedparams[loginRedirect],
		           'activeUserId' => "$uid",
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
		  
			  // TODO: Error message temporary removed to prevent notification for unmapped users 
			    
			  /*if ($ret->isError()) 
			  {
			    echo $ret->getAsHtml();
			  }*/
			  
				$g2bodyHtml=$g2moddata['bodyHtml'];
		}
	  
	  OpenTable();
	  echo $g2bodyHtml;
	  CLoseTable();
    
    include("footer.php");

?>