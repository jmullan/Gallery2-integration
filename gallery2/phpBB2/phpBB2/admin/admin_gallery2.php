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

if (!empty($setmodules))
{
	$filename = basename(__FILE__);
	$module['Forums']['Gallery_2'] = $filename;
	return;
}

$currentIntegrationVersion = '0.5.8';
$integrationVersionUrl = 'http://nukedgallery.sourceforge.net/phpbbupgrade.txt';
$integrationChangeLog = 'http://www.nukedgallery.net/postp11212.html#11212';
$integrationDownload = 'http://www.nukedgallery.net/downloads-cat12.html';

$integrationVersionText = "Gallery2 <--> phpBB2 Integration $currentIntegrationVersion";

$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);

$lang_file_path = $phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_gallery2.' . $phpEx;
if (file_exists($lang_file_path)) {
	include($lang_file_path);
}
else {
	include($phpbb_root_path . 'language/lang_english/lang_gallery2.' . $phpEx);
}

if (isset($_POST['save'])) {
	$mode = 'save';
}
elseif (isset($_POST['config'])) {
	$mode = 'config';
}
elseif (isset($_POST['sync_intro'])) {
	$mode = 'sync_intro';
}
elseif (isset($_POST['unmap'])) {
	$mode = 'unmap';
}
elseif (isset($_POST['stats'])) {
	$mode = 'stats';
}
else {
	$mode = '';
}

switch ($mode) {

	case 'save':
		$embedUri = clean($_POST['embeduri']);
		$g2Uri = clean($_POST['g2uri']);

		if ($embedUri == '' || $g2Uri == '') {
			message_die(GENERAL_MESSAGE, $lang['GALLERY2_CONFIG_ERROR']);
		}

		if ($_POST['fullpath'] == '') {
			require('./G2EmbedDiscoveryUtilities.class');
			list ($success, $fullPath, $errorString) = G2EmbedDiscoveryUtilities::getG2EmbedPathByG2Uri($g2Uri);
			if (empty($success)) {
				message_die(GENERAL_MESSAGE, $errorString);
			}

			$fullPath = (get_magic_quotes_gpc()) ? addslashes($fullPath) : $fullPath;
		}
		else {
			$fullPath = $_POST['fullpath'];
		}

		$fullPath = clean($fullPath);

		$activeAdminId = ($_POST['activeadminid']) ? intval($_POST['activeadminid']) : 0;

		$utf8 = ($_POST['utf8']) ? 1 : 0;

		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		$result = $db->sql_query($sql);
		if(!$db->sql_numrows($result)) {
			$sql = 'INSERT INTO ' . GALLERY2_TABLE . " (fullPath, embedUri, g2Uri, activeAdminId, utf8_translate) VALUES ('$fullPath', '$embedUri', '$g2Uri', $activeAdminId, $utf8)";
		}
		else {
			$sql = 'UPDATE ' . GALLERY2_TABLE . " SET fullPath = '$fullPath', embedUri = '$embedUri', g2Uri = '$g2Uri', activeAdminId = $activeAdminId, utf8_translate = $utf8";
		}

		if (!$result = $db->sql_query($sql)) {
			message_die(GENERAL_ERROR, $lang['INSERT_QUERY_FAILED'], '', __LINE__, __FILE__, $sql);
		}

		require('./g2helper_admin.inc');
		$g2h_admin = new g2helper_admin($db);
		$g2h_admin->init();

		list ($success, $msg) = $g2h_admin->checkConfig();

		$g2h_admin->done();

		if (empty($success)) {
			$message = $msg . '<br />' . $lang['GALLERY2_SAVE_ERROR'];
		}
		else {
			$message = $msg . '<br />' . $lang['GALLERY2_SAVE_OK'];
		}
	
		$message .= '<br /><br />' . sprintf($lang['Click_return_gallery2_index'], '<a href="' . append_sid("admin_gallery2.$phpEx") . '">', '</a>') . '<br /><br />' . sprintf($lang['Click_return_admin_index'], '<a href="' . append_sid("index.$phpEx?pane=right") . '">', '</a>');

		message_die(GENERAL_MESSAGE, $message);
    	break;

	case 'config':
		$template->set_filenames(array(
			'body' => './admin/gallery2_config_body.tpl')
		);

		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		$result = $db->sql_query($sql);
		if (!$db->sql_numrows($result)) {
			$url_path = $_SERVER['SCRIPT_NAME'];
			$url_path = explode('/', $url_path);
			$working_url_path = '/';
			for ($i = 1; $i < count($url_path) - 2; $i++) {
				$working_url_path .= $url_path[$i] . '/';
			}
			$embeduri = $working_url_path . 'gallery2.php';
			$g2uri = '/gallery2/';
			$activeadminid = 0;
			$utf8 = 0;
		}
		else {
			$row = $db->sql_fetchrow($result);
			$fullpath = $row['fullPath'];
			$embeduri = $row['embedUri'];
			$g2uri = $row['g2Uri'];
			$activeadminid = $row['activeAdminId'];
			$utf8 = $row['utf8_translate'];
		}

		$template->assign_vars(array(
			'S_FULLPATH' => $fullpath,
			'S_EMBEDURI' => $embeduri,
			'S_G2URI' => $g2uri,
			'S_ACTIVEADMINID' => $activeadminid,
			'S_UTF8_1' => ($utf8) ? 'checked ' : '',
			'S_UTF8_0' => ($utf8) ? '' : 'checked ',
			'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),

			'L_SUBMIT' => $lang['Submit'],
			'L_ENABLE' => $lang['GALLERY2_ENABLE'],
			'L_DISABLE' => $lang['GALLERY2_DISABLE'],
			'L_FULLPATH' => $lang['GALLERY2_FULLPATH'],
			'L_EMBEDURI' => $lang['GALLERY2_EMBEDURI'],
			'L_G2URI' => $lang['GALLERY2_G2URI'],
			'L_ACTIVEADMINID' => $lang['GALLERY2_ACTIVEADMINID'],
			'L_UTF8' => $lang['GALLERY2_UTF8'],
			'L_CONFIG_EXPLAIN1' => $lang['GALLERY2_CONFIG_EXPLAIN1'],
			'L_CONFIG_EXPLAIN2' => $lang['GALLERY2_CONFIG_EXPLAIN2'],
			'L_CONFIG_EXPLAIN3' => $lang['GALLERY2_CONFIG_EXPLAIN3'],
			'L_CONFIG_EXPLAIN4' => $lang['GALLERY2_CONFIG_EXPLAIN4'],
			'L_CONFIG_EXPLAIN5' => $lang['GALLERY2_CONFIG_EXPLAIN5'],
			'L_CONFIG_TITLE' => $lang['GALLERY2_CONFIG_TITLE'])
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);
    	break;

	case 'sync_intro':
		require('./g2helper_admin.inc');
		$g2h_admin = new g2helper_admin($db);
		$g2h_admin->init();

		list ($ret, $userList) = GalleryCoreApi::fetchUsernames();
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, $lang['G2_FETCHUSERNAMES_FAILED'] . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$template->set_filenames(array(
			'body' => './admin/gallery2_sync_intro_body.tpl')
		);

		foreach ($userList as $id => $name) {
			if ($name != 'guest') {
				$name = $g2h_admin->utf8Untranslate($name);

				$sql = 'SELECT username FROM ' . USERS_TABLE . " WHERE username = '$name' LIMIT 1";
				if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {

					list ($ret, $groupsForUser) = GalleryCoreApi::fetchGroupsForUser($id);
					if (isset($ret)) {
						$g2h_admin->errorHandler(GENERAL_ERROR, sprintf($lang['G2_FETCHGROUPSFORUSER_FAILED'], $id) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
					}

					$template->assign_block_vars('users_existing', array(
						'USER_ID' => $id,
						'USER_NAME' => $name,
						'USER_GROUPS' => $g2h_admin->utf8Untranslate(implode(', ', array_values($groupsForUser))))
					);

					$usersExist = true;
				}
			}
		}

		$g2h_admin->done();

		if (isset($usersExist)) {
			$template->assign_block_vars('switch_explain', array(
				'L_SYNC_EXPLAIN3' => $lang['GALLERY2_SYNC_EXPLAIN3'],
				'L_SYNC_EXPLAIN4' => $lang['GALLERY2_SYNC_EXPLAIN4'],
				'L_SYNC_EXPLAIN5' => $lang['GALLERY2_SYNC_EXPLAIN5'],
				'L_SYNC_EXPLAIN6' => $lang['GALLERY2_SYNC_EXPLAIN6'],
				'L_SYNC_EXPLAIN7' => $lang['GALLERY2_SYNC_EXPLAIN7'])
			);
		}

		$template->assign_vars(array(
			'S_G2_ACTION' => append_sid("./gallery2_export.$phpEx"),
			'L_SYNC_TITLE' => $lang['GALLERY2_SYNC_TITLE'],
			'L_SYNC_ACTION' => $lang['GALLERY2_SYNC_ACTION'],
			'L_SYNC_USER' => $lang['GALLERY2_SYNC_USER'],
			'L_SYNC_USERID' => $lang['GALLERY2_SYNC_USERID'],
			'L_SYNC_GROUPS' => $lang['GALLERY2_SYNC_GROUPS'],
			'L_SYNC_IMPORT' => $lang['GALLERY2_SYNC_IMPORT'],
			'L_SYNC_DELETEALL' => $lang['GALLERY2_SYNC_DELETEALL'],
			'L_SYNC_DELETE' => $lang['GALLERY2_SYNC_DELETE'],
			'L_SYNC_LEAVE' => $lang['GALLERY2_SYNC_LEAVE'],
			'L_SYNC_EXPLAIN1' => $lang['GALLERY2_SYNC_EXPLAIN1'],
			'L_SYNC_EXPLAIN2' => $lang['GALLERY2_SYNC_EXPLAIN2'],
			'L_SYNC_NOW' => $lang['GALLERY2_SYNC_NOW'],
			'L_SYNC_LATER' => $lang['GALLERY2_SYNC_LATER'],
			'L_SYNC' => $lang['GALLERY2_SYNC_BUTTON'])
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);
    	break;

	case 'unmap':
		$template->set_filenames(array(
			'body' => './admin/gallery2_unmap_confirm.tpl')
		);

		$template->assign_vars(array(
			'S_G2_ACTION' => append_sid("./gallery2_unmap.$phpEx"),
			'L_CONFIRM_TITLE' => $lang['GALLERY2_UNMAP_TITLE'],
			'L_CONFIRM_EXPLAIN1' => $lang['GALLERY2_CONFIRM_EXPLAIN1'],
			'L_CONFIRM_EXPLAIN2' => $lang['GALLERY2_CONFIRM_EXPLAIN2'],
			'L_CONFIRM_EXPLAIN3' => $lang['GALLERY2_CONFIRM_EXPLAIN3'],
			'L_CONFIRM_BUTTON' => $lang['GALLERY2_OPTIONS_CONFIRM'])
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);
    	break;

	case 'stats':
		$sql = 'SELECT exportData FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			message_die(CRITICAL_ERROR, $lang['FETCH_EXPORTDATA_FAILED'], '', __LINE__, __FILE__, $sql);
		}

		if (!empty($row['exportData'])) {
			$export = unserialize($row['exportData']);
		}
		else {
			$export = (object) true;

			foreach (array('processed', 'existing', 'imported') as $key) {
				$export->groups[$key] = 0;
			}
			$export->groups['failures'] = array();

			foreach (array('processed', 'existing', 'nonactive', 'guest', 'admin', 'imported') as $key) {
				$export->users[$key] = 0;
			}
			$export->users['failures'] = array();
		}

		$message = '<p>' . sprintf($lang['GALLERY2_EXPORT_G_PROCESSED'], $export->groups['processed']) . '</p>' . "\n";

		if ($export->groups['existing'] > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_G_EXISTING'], $export->groups['existing']) . '</p>' . "\n";
		}

		$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_G_IMPORTED'], $export->groups['imported']) . '</p>' . "\n";

		if (count($export->groups['failures']) > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_G_FAILED1'], count($export->groups['failures'])) . '</p>' . "\n"
			. '<p>' . $lang['GALLERY2_EXPORT_G_FAILED2'] . '</p>' . "\n"
			. implode('<br />', $export->groups['failures']);
		}

		$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_PROCESSED'], $export->users['processed']) . '</p>' . "\n";

		if ($export->users['existing'] > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_EXISTING'], $export->users['existing']) . '</p>' . "\n";
		}

		if ($export->users['nonactive'] > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_NONACTIVE'], $export->users['nonactive']) . '</p>' . "\n";
		}

		if ($export->users['guest'] > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_GUEST'], $export->users['guest']) . '</p>' . "\n";
		}

		if ($export->users['admin'] > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_ADMIN'], $export->users['admin']) . '</p>' . "\n";
		}

		$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_IMPORTED'], $export->users['imported']) . '</p>' . "\n";

		if (count($export->users['failures']) > 0) {
			$message .= '<p>' . sprintf($lang['GALLERY2_EXPORT_U_FAILED1'], count($export->users['failures'])) . '</p>' . "\n"
			. '<p>' . $lang['GALLERY2_EXPORT_U_FAILED2'] . '</p>' . "\n"
			. implode('<br />', $export->users['failures'])
			. '<p>' . $lang['GALLERY2_EXPORT_REASON1'] . "\n"
			. '<ul><li>' . $lang['GALLERY2_EXPORT_REASON2'] . '</li>' . "\n"
			. '<li>' . $lang['GALLERY2_EXPORT_REASON3'] . '</li>' . "\n"
			. '<li>' . $lang['GALLERY2_EXPORT_REASON4'] . '</li></ul>' . "\n"
			. $lang['GALLERY2_EXPORT_REASON5'] . '</p>' . "\n";
		}

		message_die(GENERAL_MESSAGE, $message);
    	break;

	default:
		if ($fp = @fopen($integrationVersionUrl, 'r')) {
			$versionData = fread($fp, 4096);
			fclose($fp);

			$versionData = explode("\n", $versionData);

			$integrationVersion = explode('.', $currentIntegrationVersion);

			if ($versionData[0] == $integrationVersion[0] && $versionData[1] == $integrationVersion[1] && $versionData[2] == $integrationVersion[2]) {	
				$versionText = $lang['GALLERY2_TO_DATE'];
			}
			else {
				$versionText = sprintf($lang['GALLERY2_NOT_TO_DATE'], "$versionData[0].$versionData[1].$versionData[2]") . sprintf($lang['GALLERY2_VIEW_CHANGES'], $integrationChangeLog, $integrationDownload);
			}
		}
		else {
			$versionText = sprintf($lang['GALLERY2_URL_FAILED'], $integrationVersionUrl);
		}

		$template->set_filenames(array(
			'body' => './admin/gallery2_show_body.tpl')
		);

		$template->assign_vars(array(
			'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),
			'L_CONFIG' => $lang['GALLERY2_OPTIONS_CONFIG'],
			'L_SYNC' => $lang['GALLERY2_OPTIONS_SYNC'],
			'L_UNMAP' => $lang['GALLERY2_OPTIONS_CONFIRM'],
			'L_RESULTS' => $lang['GALLERY2_OPTIONS_RESULTS'],
			'G2_TITLE' => $lang['GALLERY2_ADMIN_TITLE'],
			'G2_ADMIN_TASK' => $lang['GALLERY2_ADMIN_TITLE'],
			'G2_VERSION_TITLE' => $lang['GALLERY2_VERSION_TITLE'],
			'G2_VERSION_MSG' => $versionText)
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);

}

function clean($value) {
	$value = trim($value);
	$value = (get_magic_quotes_gpc()) ? stripslashes($value) : $value;
	$value = str_replace('\\', '/', $value);
	return $value;
}

?>
