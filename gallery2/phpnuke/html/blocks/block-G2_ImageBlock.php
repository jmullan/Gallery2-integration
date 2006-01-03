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
 * Gallery 2 image block for PHPNuke.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

/****************************************************************************/
/* This is your configuration section of your Gallery 2 image block.		*/
/*																			*/
/* 1. Select which block you want to show from the list below:				*/
/*																			*/
/* randomImage  : A random image is shown									*/
/* recentImage  : The most recent image is shown							*/
/* viewedImage  : The most popular image is shown							*/
/* randomAlbum  : The highlight from a random album is shown				*/
/* recentAlbum  : The highlight from the most recent album is shown			*/
/* viewedAlbum  : The highlight from the most popular album is shown		*/
/* dailyImage   : A new image each day										*/
/* weeklyImage  : A new image each week										*/
/* monthlyImage : A new image each month									*/
/* dailyAlbum   : A new album highlight each day							*/
/* weeklyAlbum  : A new album highlight each week							*/
/* monthlyAlbum : A new album highlight each month							*/
/*																			*/
   $blockType = "randomImage";

/* 2. Select what album/image properties you want displayed, you can		*/
/*    display more than one, but separate them by |	 (the pipe symbol)		*/
/*																			*/
/* title   : Show the title													*/
/* views   : Show how many views the item has								*/
/* date    : Show the capture/upload date									*/
/* owner   : Show the item owner											*/
/* heading : Show the item heading ("Random Image","Daily Image", etc)		*/
/* fullSize: Show the full sized item (not a thumbnail)						*/
/* none    : Don't show anything, just the thumbnail						*/
/*																			*/
   $display = "title|heading";
 
/*	3. What size image do you want displayed? If you want the default		*/
/*     thumbnail, leave this empty, otherwise, enter the max pixel size.	*/
/*     IF YOU SPECIFY A SIZE LARGER THAN THE DEFAULT THUMBNAIL SIZE, YOU	*/  
/*     MUST ALSO ADD "fullSize" TO THE $display VARIABLE ABOVE.				*/ 
	$maxSize='';
/*																			*/  
/****************************************************************************/

if (eregi("block-G2_ImageBlock.php", $_SERVER['SCRIPT_NAME'])) {
    Header("Location: index.php");
    die();
}

global $admin, $user, $cookie, $db, $prefix;

define("_G2_EMBED_PHP_FILE","embed.php");
define("_G2_CONFIGURATION_NOT_DONE","The module has not yet been configured.");

$g2result = $db->sql_query("SELECT * FROM ".$prefix."_g2config");
list($embedphpfile, $embedUri, $relativeG2Path, $loginRedirect, $activeUserId, $embedPath, $cookiepath, $showSidebar, $g2configurationDone, $embedVersion) = $db->sql_fetchrow($g2result);

if ($g2configurationDone != 1) {
	$content = _G2_CONFIGURATION_NOT_DONE; 
	return;
}

require_once($embedphpfile."/"._G2_EMBED_PHP_FILE);

if (is_admin($admin)) {
	$uid='admin';
}
else {
	if (is_user($user)) {
		cookiedecode($user);
		$uid='';  
		if (is_user($user)) {
			$uid = $cookie[0];
		}
	} 
}

$ret = GalleryEmbed::init(array(
	'embedPath' => $embedPath,
	'embedUri' => $embedUri,
	'relativeG2Path' => $relativeG2Path,
	'loginRedirect' => $loginRedirect,
	'activeUserId' => "$uid",
	'fullInit' => true));

if ($showSidebar) {
	$content = "The Gallery2 sidebar is enabled.<br>You should disable it before using this block.";
	return true;
}
   
$g2moddata = GalleryEmbed::handleRequest();
if(intval($maxSize) > 0) {
	list($ret,$html, $head) = GalleryEmbed::getImageBlock(array('blocks'=>$blockType, 'show'=>$display,'itemFrame'=>'solid','albumFrame'=>'solid','maxSize' => $maxSize));
}
else {
	list($ret,$html, $head) = GalleryEmbed::getImageBlock(array('blocks'=>$blockType, 'show'=>$display,'itemFrame'=>'solid','albumFrame'=>'solid'));
}

if (!isset($g2moddata['isDone'])) {
  echo 'isDone is not defined, something very bad must have happened.';
  exit;
}

if ($g2moddata['isDone']) {
  exit; 
}

$content = $head."\n<center>".$html."</center>";

?>