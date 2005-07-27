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

global $admin, $user, $cookie;

define("_G2_EMBED_PHP_FILE","embed.php");
define("_G2_CONFIGURATION_NOT_DONE","The module has not yet been configured.");

include("modules/gallery2/gallery2.cfg");

if ($g2configurationdone != "true") {
	$content = _G2_CONFIGURATION_NOT_DONE; 
	return;
}

require_once($g2embedparams['embedphpfile']."/"._G2_EMBED_PHP_FILE);

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
	'embedPath' => $g2embedparams['embedPath'],
	'embedUri' => $g2embedparams['embedUri'],
	'relativeG2Path' => $g2embedparams['relativeG2Path'],
	'loginRedirect' => $g2embedparams['loginRedirect'],
	'activeUserId' => "$uid",
	'fullInit' => true));

if ($g2mainparams['showSidebar']=="true") {
	$content = "The Gallery2 sidebar is enabled.<br>You should disable it before using this block.";
	return true;
}

GalleryCapabilities::set('showSidebarBlocks', false);
$g2moddata = GalleryEmbed::handleRequest(array('extractSidebarBlocks' => true));

if(!isset($g2moddata['sidebarBlocksHtml'])) {
	$content = "You need to enable sidebar blocks in your Gallery 2 configuration.";
}
else { 
	$num_blocks = count($g2moddata['sidebarBlocksHtml']) - 1;
	$content = "<center>";
	for($i = 0; $i <= $num_blocks; $i++) {
		if($i != $num_blocks) {
			$content .= $g2moddata['sidebarBlocksHtml'][$i]."<hr size=\"1\" noshade>";
		}
		else {
			$content .= $g2moddata['sidebarBlocksHtml'][$i];
		}
	}
	$content .= "</center>";
}

?>