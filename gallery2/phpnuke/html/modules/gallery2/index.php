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

if (!eregi("modules.php", $_SERVER['PHP_SELF'])) {
    die ("You can't access this file directly...");
}

// ------------------------------------------------------------
// Inclusion of needed functions for User creation on the fly
// TODO: Should be moved or called away from index.php
// ------------------------------------------------------------

/**
 * g2getallexternalIdmappings: get all extId, entityId mappings
 *
 * get all extId, entityId mappings from G2
 * useful, i.e. for import/export synchronization update
 * used only by the import/export method
 *
 * @author Andy Staudacher
 * @access public
 * @param none
 * @return array(bool success, array(entityId => array(externalId => integer,
 *                             entityType => string, entityId => integer)),
 *                             array(externalId => array(externalId => integer,
 *                             entityType => string, entityId => integer)))
 * @throws Systemexception if it failed
 */
function g2getallexternalIdmappings() {
	
	global $gallery;

	$query = 'SELECT [ExternalIdMap::entityId], [ExternalIdMap::externalId], [ExternalIdMap::entityType]
			FROM [ExternalIdMap]';

	list ($ret, $results) = $gallery->search($query, array ());
	if ($ret->isError()) {
		g2_message('Failed to fetch a list of all extId maps fromG2. Here is the error message from G2: <br /> [#(1)]'.$ret->getAsHtml());
		return array (false, null, null);
	}
	$mapsbyentityid = array ();
	$mapsbyexternal = array ();
	while ($result = $results->nextResult()) {
		$entry = array ('externalId' => $result[1], 'entityId' => $result[0], 'entityType' => $result[2]);
		$mapsbyentityid[$result[0]] = $entry;
		$mapsbyexternal[$result[1]] = $entry;
	}

	return array (true, $mapsbyentityid, $mapsbyexternal);
}


/**
 * g2addexternalMapEntry: add an externalId map entry
 *
 * Add an entry in the G externalId, entityId map table
 *
 * @author Andy Staudacher
 * @access public
 * @param integer the uid
 * @param integer the entityId from G2
 * @param integer/string the roles type, 1 for groups, 0 for users, or the entityType string
 * @return bool true or false
 */
function g2addexternalMapEntry($externalId, $entityId, $entityType) 
{
	include ("modules/gallery2/gallery2.cfg");
	
	if (is_int($entityType)) {
		$entityType = $entityType == 0 ? 'GalleryUser' : 'GalleryGroup';
	}
	
	require_once ($g2embedparams[embedphpfile]."/".'modules/core/classes/ExternalIdMap.class');
	
	$ret = ExternalIdMap :: addMapEntry(array ('externalId' => $externalId, 'entityType' => $entityType, 'entityId' => $entityId));
	if ($ret->isError()) {
		echo 'Failed to create a extmap entry for role uid ['.$externalId.'] and entityId ['.$entityId.'], entityType ['.$entityType.']. Here is the error message from G2: <br />'.$ret->getAsHtml();
		return false;
	}
	return true;
}


// --------------------------------------------------------
// Mapping between Phpnuke and Gallery2 language definition
// --------------------------------------------------------

$Phpnuke2G2Lang = array(
'danish' 		=> 'da',
'dutch' 		=> 'nl',
'german' 		=> 'de',
'greek' 		=> 'el',
'english' 		=> 'en',
'american' 		=> 'en',
'spanish' 		=> 'es',
'finnish' 		=> 'fi',
'french' 		=> 'fr',
'irish' 		=> 'ga',	// not available
'italian' 		=> 'it',
'japanese'		=> 'ja',	// not available
'norwegian' 	=> 'no',
'polish' 		=> 'pl',
'portuguese'	=> 'pt',
'swedish' 		=> 'sv',
'chinese' 		=> 'zh',
);


global $currentlang,$g2bodyHtml;
require_once("mainfile.php");
$module_name = basename(dirname(__FILE__));
get_lang($module_name);

global $db,$user_prefix;

if (is_admin($admin)) 
{
	// we log as an admin
	$uid='admin';
}
else
{
	if (is_user($user))
	{
		// we log as a normal user
		cookiedecode($user);
		$uid='';  
		if (is_user($user)) 
		{
			$uid = $cookie[0];
		}
	} 
}
  
if ($g2bodyHtml==null)
{
	include("modules/gallery2/gallery2.cfg");
	
	if ($g2configurationdone != "true")
		{
			include "header.php";
		  	OpenTable();
		  	echo "<center>"._G2_CONFIGURATION_NOT_DONE."</center>";
			CloseTable();
		    include("footer.php");
		    return;
		 }

	require_once($g2embedparams[embedphpfile]."/"._G2_EMBED_PHP_FILE);
				
	$g2currentlang = $Phpnuke2G2Lang[$currentlang];
				
	$ret = GalleryEmbed::init(array(
		'embedUri' => $g2embedparams[embedUri],
		'relativeG2Path' => $g2embedparams[relativeG2Path],
		'loginRedirect' => $g2embedparams[loginRedirect],
		'activeUserId' => "$uid",
		'activeLanguage' =>$g2currentlang));

	if ($ret->isError())
	{
		if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) 
		{
			// check if there's no G2 user mapped to the activeUserId
			$ret = GalleryEmbed::isExternalIdMapped($uid, 'GalleryUser');
			if ($ret->isError() && ($ret->getErrorCode() & ERROR_MISSING_OBJECT)) 
			{
				// user not mapped, map create G2 user now
				$query='SELECT user_id, name, username, user_password, user_email, user_lang,  user_regdate FROM '.$user_prefix."_users WHERE `user_id`='".$uid."'";
				$result=$db->sql_query($query);
				$sqluserdata		= $db->sql_fetchrow($result);
				$nukeuser_regdate	= $sqluserdata['user_regdate'];
			
				list( $regmonth, $regday, $regyear ) = split( " ", $nukeuser_regdate );
				$regphpusertimestamp = mktime( 0, 0, 0, $regmonth, $regday, $regyear );
				$nukeuser_lang		= $sqluserdata['user_lang'];
				$nukeuser_uname		= $sqluserdata['username'];
							
				// Get Arguments for the new user:
				$args['fullname']  	=	$sqluserdata['name'];
				$args['username'] 	= $nukeuser_uname;
				$args['hashedpassword'] =	$sqluserdata['user_password'];
				$args['hashmethod'] = 	'md5';
				$args['email'] 		=	$sqluserdata['user_email'];
				$args['language']	=	$Phpnuke2G2Lang[$nukeuser_lang];
				$args['creationtimestamp']	=	$regphpusertimestamp;
									
				$retcreate = GalleryEmbed :: createUser($uid, $args);
				if (!$retcreate->isSuccess()) 
				{
					echo 'Failed to create G2 user with extId ['.$uid.']. Here is the error message from G2: <br />'.$retcreate->getAsHtml();
					return false;
				}
									
				if (!g2addexternalMapEntry($nukeuser_uname, $uid, 0)) 
				{
					return false;
				}
						   		
				// Full G2 reinit with the new created user
				$ret = GalleryEmbed :: init(array ('embedUri' => $g2embedparams[embedUri], 
					'relativeG2Path' => $g2embedparams[relativeG2Path],
					'loginRedirect' => $g2embedparams[loginRedirect],
					'activeUserId' => "$uid",
					'activeLanguage' => $g2currentlang, 
					'fullInit' => 'true'));
			} 
			else 
			{
				echo 'G2 did not return a success status. Here is the error message from G2: <br />'.$ret->getAsHtml();
			}
		} 
		else 
		{
			echo 'G2 did not return a success status. Here is the error message from G2: <br />'.$ret->getAsHtml();
		}
	}

	if ($g2mainparams[showSidebar]!="true")
	{
		GalleryCapabilities::set('showSidebar', false);
	}
		  
	// handle the G2 request
	$g2moddata = GalleryEmbed::handleRequest();
		    
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
		    
	// Main G2 error message

	if ($ret->isError()) 
	{
		echo $ret->getAsHtml();
	}
			  
	$g2bodyHtml=$g2moddata['bodyHtml'];
}
	  
OpenTable();
echo $g2bodyHtml;
CloseTable();
    
include("footer.php");

?>