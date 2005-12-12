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
 * Gallery 2 sidebar block for PHPNuke.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

if (eregi("block-G2_Sidebar.php", $_SERVER['SCRIPT_NAME'])) {
    Header("Location: index.php");
    die();
}

global $admin, $user, $cookie,$db,$prefix;

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

GalleryCapabilities::set('showSidebarBlocks', false);
$g2moddata = GalleryEmbed::handleRequest();

if (isset($g2moddata['headHtml'])) {
	list($title, $css, $javascript) = GalleryEmbed::parseHead($g2moddata['headHtml']);
}

if(!isset($g2moddata['sidebarBlocksHtml'])) {
	$content = "You need to enable sidebar blocks in your Gallery 2 configuration.";
}
else { 
	$num_blocks = count($g2moddata['sidebarBlocksHtml']) - 1;
	foreach($css as $stylesheet) {
		$content .= $stylesheet;
	}
	$content .= "<div id=\"gsSidebar\">";
	for($i = 0; $i <= $num_blocks; $i++) {
		if($i != $num_blocks) {
			$content .= $g2moddata['sidebarBlocksHtml'][$i]."<hr size=\"1\" noshade>";
		}
		else {
			$content .= $g2moddata['sidebarBlocksHtml'][$i];
		}
	}
	$content .= "</div>";
}

?>