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
 * @author Scott Gregory 
 */

define('IN_PHPBB', 1);

if (!empty($setmodules))
{
	$filename = basename(__FILE__);
	$module['Forums']['Gallery_2'] = $filename;
	return;
}

$currentIntegrationVersion = '0.5.3';
$integrationVersionText = "Gallery2 <--> phpBB2 Integration $currentIntegrationVersion";

$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);

if ($_POST['mode']) $mode = htmlspecialchars($mode);
elseif ($_POST['save']) $mode = 'save';
elseif ($_POST['config']) $mode = 'config';
elseif ($_POST['sync_intro']) $mode = 'sync_intro';
else $mode = '';

switch ($mode) {

	case 'save':
		$embedUri = clean($_POST['embeduri']);
		$g2Uri = clean($_POST['g2uri']);

		if ($embedUri == '' || $g2Uri == '') {
			message_die(GENERAL_MESSAGE, 'One or more fields are blank. Please go back and correct.');
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

		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		$result = $db->sql_query($sql);
		if(!$db->sql_numrows($result)) {
			$sql = 'INSERT INTO ' . GALLERY2_TABLE . " (fullPath, embedUri, g2Uri, activeAdminId) VALUES ('$fullPath', '$embedUri', '$g2Uri', $activeAdminId)";
		}
		else {
			$sql = 'UPDATE ' . GALLERY2_TABLE . " SET fullPath = '$fullPath', embedUri = '$embedUri', g2Uri = '$g2Uri', activeAdminId = $activeAdminId";
		}

		if (!$result = $db->sql_query($sql)) {
			message_die(GENERAL_ERROR, 'Could not insert data into Gallery 2 table', $lang['Error'], __LINE__, __FILE__, $sql);
		}

		require('./g2helper_admin.inc');
		$g2h_admin = new g2helper_admin($db);
		list ($success, $msg) = $g2h_admin->checkConfig();
		if (empty($success)) {
			$message = $msg . '<br />Configuration data successfully saved, but errors were encountered.';
		}
		else {
			$message = $msg . '<br />Configuration data successfully saved.';
		}
	
		$message .= '<br /><br />' . sprintf('Click %sHere%s to return to the Gallery 2 admin page', "<a href=\"" . append_sid("admin_gallery2.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

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
		}
		else {
			$row = $db->sql_fetchrow($result);
			$fullpath = $row['fullPath'];
			$embeduri = $row['embedUri'];
			$g2uri = $row['g2Uri'];
			$activeadminid = $row['activeAdminId'];
		}

		$template->assign_vars(array(
			'S_FULLPATH' => $fullpath,
			'S_EMBEDURI' => $embeduri,
			'S_G2URI' => $g2uri,
			'S_ACTIVEADMINID' => $activeadminid,
			'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),

			'L_SUBMIT' => $lang['Submit'],
			'L_FULLPATH' => 'Full file path to embed.php: ',
			'L_EMBEDURI' => 'URL path to gallery2.php: ',
			'L_G2URI' => 'URL path to the gallery2 directory: ',
			'L_ACTIVEADMINID' => 'Active Admin ID: ',
			'L_CONFIG_EXPLAIN1' => 'This value depends on your particular installation and you must ensure it is entered correctly.<br />Proper examples are http://example.com/gallery2/ or just /gallery2/',
			'L_CONFIG_EXPLAIN2' => 'This value has been auto-detected and should be correct, however, you should double check it.',
			'L_CONFIG_EXPLAIN3' => 'These values are automatically generated and managed by the integration package.<br />Do not change them unless you are certain you know what you are doing!',
			'L_CONFIG_TITLE' => 'Gallery 2 Integration Settings')
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);
    	break;

	case 'sync_intro':
		require('./g2helper_admin.inc');
		$g2h_admin = new g2helper_admin($db);
		$g2h_admin->init();

		$users_table = '';

		list ($ret, $userList) = GalleryCoreApi::fetchUsernames();
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, 'fetchUserNames failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		foreach ($userList as $id => $name) {
			if ($name != 'guest') {
				$sql = "SELECT username FROM " . USERS_TABLE . " WHERE username = '$name' LIMIT 1";
				if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {

					list ($ret, $groupsForUser) = GalleryCoreApi::fetchGroupsForUser($id);
					if (isset($ret)) {
						$g2h_admin->errorHandler(GENERAL_ERROR, "fetchGroupsForUser failed for $id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
					}

					$groupsForUser = array_values($groupsForUser);

					$users_table .= '<tr><td class="row3"><input type="radio" name="user[' . $id . ']" value="1" checked="checked" />Import To PHPBB DB</td>';
					$users_table .= '<td class="row3"><input type="radio" name="user[' . $id . ']" value="2" />Delete User, Delete Items</td>';
					$users_table .= '<td class="row3"><input type="radio" name="user[' . $id . ']" value="3" />Delete User, Keep Items</td>';
					$users_table .= '<td class="row3"><input type="radio" name="user[' . $id . ']" value="4" />Leave As Is</td>';
					$users_table .= "<td class=\"row3\" align=\"center\">$name</td><td class=\"row3\" align=\"center\">$id</td>";
					$users_table .= '<td class="row3" align="center">';
					for ($i = 0; $i < count($groupsForUser); $i++) {
						if ($i > 0) $users_table .= '<br />';
						$users_table .= $groupsForUser[$i];
					}
					$users_table .= "</td></tr>\n";
				}
			}
		}

		$g2h_admin->done();

		if ($users_table != '') {
			$users_table = '<table class="forumline" cols="6">' . "\n"
			. '<tr><th colspan="4" width="60%">Action</th><th width="13%">G2 User</th><th width="13%">G2 ID</th><th width="13%">G2 Groups</th></tr>' . "\n"
			. $users_table
			. "</table>\n";
		}

		$explain = '<p>This will export your current phpBB2 users to Gallery 2. Note that for large numbers of users, this may take some time.</p><p>You may choose if you want to export all the current phpBB2 users to Gallery 2 now, or have each user exported the first time they access Gallery 2.<br />The latter option is the fastest and easiest if you have a large number of users.</p>';
		if ($users_table != '') $explain .= '<p>If there are any users listed in the table below, it means they exist in the Gallery 2 database, but not in the phpBB2 database.<br />It is recommended these users either be imported to phpBB2 or deleted from Gallery2.</p><p>Note that users imported to phpBB2 will not have a password due to the fact that Gallery 2 uses a different password storage scheme than phpBB2.<br />The first time these imported users login to phpBB2, they will have to change their password from blank to their desired password for the synchronization to be complete.</p><p>If the users are left as is, these users will not be synchronized with phpBB2 and you may experience COLLISION errors later on if you try to add them to phpBB2.<p>If you choose to delete the users and there are already items existing for them (ie, photo albums), you can choose to delete the items along with the user, or keep them.<br />If the items are kept, they will be re-mapped to the first admin that Gallery 2 finds.</p><p>In any case the sychronization must be run at least once so that groups, guest and admin accounts can be properly mapped.</p>';

		$template->set_filenames(array(
			'body' => './admin/gallery2_sync_intro_body.tpl')
		);

		$template->assign_vars(array(
			'S_G2_ACTION' => append_sid("./gallery2_export.$phpEx"),
			'L_SYNC_TITLE' => 'Export phpBB Users to Gallery 2',
			'L_SYNC_EXPLAIN' => $explain,
			'L_SYNC_USER_LIST' => $users_table,
			'L_SYNC' => 'Begin Synchronization')
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);
    	break;

	default:
		$url = 'http://nukedgallery.sourceforge.net/phpbbupgrade.txt';
		if ($fp = @fopen($url, 'r')) {
			$versionData = fread($fp, 4096);
			fclose($fp);

			$versionData = explode("\n", $versionData);
			$latestHeadRevision = intval($versionData[0]);
			$latestMajorRevision = intval($versionData[1]);
			$latestMinorRevision = intval($versionData[2]);
			$latestVersion = "$latestHeadRevision.$latestMajorRevision.$latestMinorRevision";

			$integrationVersion = explode('.', $currentIntegrationVersion);
			$integrationHeadVersion = intval($integrationVersion[0]);
			$integrationMajorVersion = intval($integrationVersion[1]);
			$integrationMinorVersion = intval($integrationVersion[2]);

			if ($latestHeadRevision == $integrationHeadVersion && $latestMajorRevision == $integrationMajorVersion && $latestMinorRevision == $integrationMinorVersion) {	
				$versionText = '<p style="color:green">You have the most current integration package.</p>';
			}
			else {
				$versionText = '<p style="color:red">Your integration package is <b>not</b> up to date.  '
				. "Latest version available is <b>$latestVersion</b></p>"
				. '<p>To see what has changed, read the ChangeLog here: <a href="http://www.nukedgallery.net/postp11212.html#11212" target="_blank">http://www.nukedgallery.net/postp11212.html#11212</a><br />'
				. 'You can download the latest integration package from: <a href="http://www.nukedgallery.net/downloads-cat12.html" target="_blank">http://www.nukedgallery.net/downloads-cat12.html</a></p>';
			}
		}
		else {
			fclose($fp);

			$versionText = "Could not open $url for input.";
		}
		
		$template->set_filenames(array(
			'body' => './admin/gallery2_show_body.tpl')
		);

		$template->assign_vars(array(
			'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),
			'L_CONFIG' => 'Configure Gallery 2 Integration',
			'L_SYNC' => 'Synchronize phpBB2 Users to Gallery 2',
			'G2_TITLE' => 'Gallery 2 Administration',
			'G2_ADMIN_TASK' => 'Choose your Gallery 2 Adminstration Task',
			'G2_VERSION_TITLE' => 'Integration Version Information',
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
