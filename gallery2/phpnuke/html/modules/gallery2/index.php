<?php

/*
 * $RCSfile$
 *
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2005 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
/**
 * Gallery 2 index page for PHPNuke.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

if (!eregi("modules.php", $_SERVER['PHP_SELF'])) {
    die ("You can't access this file directly...");
}

define("MOD_NAME","gallery2");

/*********************************************************/
/* Standalone Message Function                           */
/*********************************************************/

function g2_message($mess) {
	global $admin, $bgcolor2, $module_name, $admin_file;
	include ("header.php");
	OpenTable();
	echo	"<br><center><a href=\"".$admin_file.".php?op=gallery2\">".
				"<img alt='Gallery::your photos on your website' src='modules/$module_name/images/g2.png' border=0></a><H3>Gallery2 Module Administration</H3>".
				"<br/><a href=\"".$admin_file.".php?op=gallery2\">Return Home</center>";
	CloseTable();
	echo "<br/>";

	OpenTable();
	echo "<center><font class=\"option\">";
	echo $mess;
	echo "</font></center>";
	CloseTable();

	include ("footer.php");
}

// --------------------------------------------------------
// Mapping between Phpnuke and Gallery2 language definition
// --------------------------------------------------------

$phpnuke2G2Lang = array('danish' => 'da','dutch' => 'nl','german' => 'de','greek' => 'el','english'	=> 'en','american' => 'en','spanish' => 'es','finnish' => 'fi','french' => 'fr','irish' => 'ga','italian' => 'it','japanese' => 'ja','norwegian' => 'no','polish' => 'pl','portuguese' => 'pt','swedish' => 'sv','chinese' => 'zh');


global $currentlang,$g2bodyHtml,$db,$user_prefix;

require_once("mainfile.php");
$module_name = basename(dirname(__FILE__));
get_lang($module_name);

if (is_admin($admin)) {
	// we log as an admin
	$uid='admin';
}
else {
	if (is_user($user))	{
		// we log as a normal user
		cookiedecode($user);
		$uid='';  
		if (is_user($user)) {
			$uid = $cookie[0];
		}
	} 
}
  
if ($g2bodyHtml==null) {
	include("modules/".MOD_NAME."/gallery2.cfg");
	
	if ($g2configurationdone != "true"){
			include "header.php";
		  	OpenTable();
		  	echo "<center>"._G2_CONFIGURATION_NOT_DONE."</center>";
			CloseTable();
		    include("footer.php");
		    return;
	}

	require_once($g2embedparams['embedphpfile']._G2_EMBED_PHP_FILE);
				
	$g2currentlang = $phpnuke2G2Lang[$currentlang];
				
	$ret = GalleryEmbed::init(array('embedPath' => $g2embedparams['embedPath'], 'embedUri' => $g2embedparams['embedUri'],'relativeG2Path' => $g2embedparams['relativeG2Path'],'loginRedirect' => $g2embedparams['loginRedirect'],'activeUserId' => "$uid",'activeLanguage' =>$g2currentlang));

	if ($ret->isError()) {
		if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			// check if there's no G2 user mapped to the activeUserId
			$ret = GalleryEmbed::isExternalIdMapped($uid, 'GalleryUser');
			if ($ret->isError() && ($ret->getErrorCode() & ERROR_MISSING_OBJECT)) {
				// user not mapped, map create G2 user now
				$query='SELECT user_id, name, username, user_password, user_email, user_lang,  user_regdate FROM '.$user_prefix."_users WHERE `user_id`='".$uid."'";
				$result=$db->sql_query($query);
				$sqluserdata		= $db->sql_fetchrow($result);
				$nukeuser_regdate	= $sqluserdata['user_regdate'];
				$nukeuser_lang		= $sqluserdata['user_lang'];
							
				// Get Arguments for the new user:
				$args = array('fullname'=> $sqluserdata['username'], 'username'=> $sqluserdata['username'], 'hashedpassword'=> $sqluserdata['user_password'], 'hashmethod'=> 'md5' , 'email'=> $sqluserdata['user_email'] , 'language' => $phpnuke2G2Lang[$nukeuser_lang], 'creationtimestamp'=> strtotime($nukeuser_regdate));
	
				$retcreate = GalleryEmbed::createUser($sqluserdata['user_id'], $args);
				if (!$retcreate->isSuccess()) {
					list($ret,$user) = GalleryCoreApi::fetchUserByUsername($sqluserdata['username']);
					$g2userId = $user->getId();
					if(!GalleryEmbed::addExternalIdMapEntry($sqluserdata['user_id'], $g2userId, "GalleryUser")) {
						echo 'Sorry, but your the following PHPNuke user could not be imported to Gallery 2:<br> '.$nukeuser_uname.'.<p> Here is the error message from G2: <br />'.$retcreate->getAsHtml();
					}
				}
						   		
				// Full G2 reinit with the new created user
				$ret = GalleryEmbed :: init(array ('embedPath' => $g2embedparams['embedPath'], 'embedUri' => $g2embedparams['embedUri'], 'relativeG2Path' => $g2embedparams['relativeG2Path'],'loginRedirect' => $g2embedparams['loginRedirect'],'activeUserId' => "$uid", 'activeLanguage' => $g2currentlang, 'fullInit' => 'true'));
			} 
			else {
				echo 'G2 did not return a success status. Here is the error message from G2: <br />'.$ret->getAsHtml();
			}
		} 
		else {
			echo 'G2 did not return a success status. Here is the error message from G2: <br />'.$ret->getAsHtml();
		}
	}
		  
	// handle the G2 request

	if ($g2mainparams['showSidebar']) {
		GalleryCapabilities::set('showSidebarBlocks', true);
		$g2moddata = GalleryEmbed::handleRequest();
		if (isset($g2moddata['sidebarBlocksHtml']) && !empty($g2moddata['sidebarBlocksHtml'])) {
			$g2bodyHtml = '<div id="gsSidebar" class = "gcBorder1">' . join('', $g2moddata['sidebarBlocksHtml']) . '</div>';
		}
	}
    	else {
		GalleryCapabilities::set('showSidebarBlocks', false);
		$g2moddata = GalleryEmbed::handleRequest();
    	}
		    
	// G2 Header hacking (contribution from dmolavi)
	// get the page title, javascript and css links from the <head> html from G2
	$title = ''; $javascript = array();    $css = array();
	if (isset($g2moddata['headHtml'])) {
		list($title, $css, $javascript) = GalleryEmbed::parseHead($g2moddata['headHtml']);
	}

	if($title != "") {
		$pagetitle = "&raquo; ".trim($title);
	}
	$header = "";
	if ($fd = fopen("header.php", "r")) {
		while (!feof($fd)) {
			$line = fgets($fd, 1024);
			$line = str_replace('<?php', '', $line);
			$line = str_replace(' ?>', '', $line);
			$line = str_replace('include("includes/javascript.php");', '',$line);
			$header .= $line;
			if (strstr($line, "<head")) {
				foreach($css as $stylesheet) {
					$links = $stylesheet;
					$links = str_replace('"', '\"', $links);
					$header .= 'echo "' . $links. '\n";' . "\n";
				}
				foreach($javascript as $script) {
					$scriptline = $script;
					$scriptline = str_replace('"','\"',$scriptline);
					$header .= 'echo "' . $scriptline. '\n";'."\n";
				}
			}
		}
	}
	eval($header);
		  
	// show error message if isDone is not defined
	if (!isset($g2moddata['isDone'])) {
		echo 'isDone is not defined, something very bad must have happened.';
		exit;
	}
		    
	// die if it was a binary data (image) request
	if ($g2moddata['isDone']) {
		exit; // uploads module does this too
	}
		    
	// Main G2 error message

	if ($ret->isError()) {
		echo $ret->getAsHtml();
	}
			  
	$g2bodyHtml=$g2moddata['bodyHtml'];
}
	  
OpenTable();
echo $g2bodyHtml;
CloseTable();
    
include("footer.php");

?>