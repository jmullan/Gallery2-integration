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
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

define('IN_PHPBB', true);
$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.'.$phpEx);

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);
//
// End session management
//

//
// Lets build a page ...
//

$page_title = "Gallery 2";
include($phpbb_root_path . 'includes/page_header.'.$phpEx);

$template->set_filenames(array(
	'body' => 'gallery2.tpl')
);

$sql = "SELECT * FROM phpbb_gallery2";
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
$fullpath = $row['fullPath'];;
$embedPath = $row['embedPath'];
$relativeG2Path = $row['relativePath'];
$embedUri = $row['embedURI'];
$loginRedirect = $row['loginPath'];
$cookiePath = $row['cookiePath'];

require_once($fullpath . 'embed.php'); 

if($userdata['user_level'] == ADMIN) 
{
	$ret = GalleryEmbed::init(array( 'embedUri' => $embedUri, 'embedPath' => $embedPath, 'relativeG2Path' => $relativeG2Path, 'loginRedirect' => $loginRedirect, 'activeUserId' => "admin")); 
	if ($ret->isError()) { 
		 echo $ret->getAsHtml();
		 exit; 
	} 
}
else if($userdata['user_id'] != ANONYMOUS) 
{
	$ret = GalleryEmbed::init(array( 'embedUri' => $embedUri, 'embedPath' => $embedPath, 'relativeG2Path' => $relativeG2Path, 'loginRedirect' => $loginRedirect, 'activeUserId' => $userdata['user_id'])); 
	if ($ret->isError()) { 
		 echo $ret->getAsHtml();
		 exit; 
	} 
}
else {
	$ret = GalleryEmbed::init(array( 'embedUri' => $embedUri, 'embedPath' => $embedPath, 'relativeG2Path' => $relativeG2Path, 'loginRedirect' => $loginRedirect, 'activeUserId' => 0)); 
	if ($ret->isError()) { 
		 echo $ret->getAsHtml();
		 exit; 
	} 
}

GalleryCapabilities::set('showSidebarBlocks', true);
$g2data = GalleryEmbed::handleRequest(); 

if ($g2data['isDone']) { 
	exit; // Gallery 2 has already sent output (redirect or binary data) 
} 

// Use $g2data['headHtml'] and $g2data['bodyHtml'] 
// to display Gallery 2 content inside embedding application 
// if you don't want to use $g2data['headHtml'] directly, you can get the css, 
// javascript and page title separately by calling... 
if (isset($g2data['headHtml'])) { 
	list($title, $css, $javascript) = GalleryEmbed::parseHead($g2data['headHtml']); 
}

foreach($css as $stylesheet) {
	$links .= $stylesheet."\n";
}

foreach($javascript as $js) {
	$jlink .= $js."\n";
}

$bodyHtml = $g2data['bodyHtml'];

$template->assign_vars(array(
	'BODY' => $bodyHtml,
	'CSS' => $links,
	'JAVASCRIPT' => $jlink));

$template->pparse('body');

include($phpbb_root_path . 'includes/page_tail.'.$phpEx);

?>