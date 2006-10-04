<?php

/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2006 Bharat Mediratta
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
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
/**
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 * @author Scott Gregory <jettyrat@jettyfishing.com>
 */

define('IN_PHPBB', 1);

$no_page_header = TRUE;
$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);

require('./g2helper_admin.inc');
$g2h_admin = new g2helper_admin($db);

$lang_file_path = $phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_gallery2.' . $phpEx;
if (file_exists($lang_file_path)) {
	include($lang_file_path);
}
else {
	include($phpbb_root_path . 'language/lang_english/lang_gallery2.' . $phpEx);
}

$users_processed = $users_unmapped = $groups_processed = 0;

$g2h_admin->init();

if (!GalleryCoreApi::isUserInSiteAdminGroup()) {
	$g2h_admin->errorHandler(GENERAL_ERROR, $lang['G2_AUTHADMIN_FAILED'], __LINE__, __FILE__);
}

list ($ret, $externalIdMap) = GalleryEmbed::getExternalIdMap('externalId');
if (isset($ret)) {
	$g2h_admin->errorHandler(GENERAL_ERROR, $lang['G2_GETEXTERNALIDMAP_FAILED'] . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
}

foreach ($externalIdMap as $mapping) {
	if (intval($mapping['externalId']) == $userdata['user_id'] || $mapping['externalId'] == 'guest') {
		$ret = GalleryCoreApi::removeMapEntry('ExternalIdMap', array('externalId' => $mapping['externalId'], 'entityType' => 'GalleryUser'));
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, sprintf($lang['G2_REMOVEMAPENTRY_FAILED'], $mapping['externalId']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$users_unmapped++;
	}
	elseif ($mapping['entityType'] == 'GalleryGroup') {
		$ret = GalleryEmbed::deleteGroup($mapping['externalId']);
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, sprintf($lang['G2_DELETEGROUP_FAILED'], $mapping['externalId']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$groups_processed++;
	}
	else {
		$ret = GalleryEmbed::deleteUser($mapping['externalId']);
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, sprintf($lang['G2_DELETEUSER_FAILED'], $mapping['externalId']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$users_processed++;
	}
}

$g2h_admin->done();

$sql = 'UPDATE ' . GALLERY2_TABLE . ' SET activeAdminId = 0, exportData = NULL';
if (!$db->sql_query($sql)) {
	$g2h_admin->errorHandler(CRITICAL_ERROR, $lang['UPDATE_ACTIVEADMINID_FAILED'], __LINE__, __FILE__, $sql);
}

$message = '<p>' . sprintf($lang['GALLERY2_UNMAP_G_PROCESSED'], $groups_processed) . "</p>\n";
$message .= '<p>' . sprintf($lang['GALLERY2_UNMAP_U_PROCESSED'], $users_processed) . "</p>\n";
$message .= '<p>' . sprintf($lang['GALLERY2_UNMAP_U_UNMAPPED'], $users_unmapped) . "</p>\n";

message_die(GENERAL_MESSAGE, $message);

?>
