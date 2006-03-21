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
 * @author Scott Gregory
 */

define('IN_PHPBB', 1);

if (!empty($setmodules))
{
	$filename = basename(__FILE__);
	$module['Forums']['Gallery_2'] = $filename;
	return;
}

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
		$fullPath = trim($_POST['fullpath']);
		$embedUri = trim($_POST['embeduri']);
		$g2Uri = trim($_POST['g2uri']);
		$loginRedirect = trim($_POST['loginredirect']);
		$activeAdminId = ($_POST['activeadminid']) ? intval($_POST['activeadminid']) : 0;

		if ($fullPath == '' || $embedUri == '' || $g2Uri == '' || $loginRedirect == '') {
			message_die(GENERAL_MESSAGE, 'One or more fields are blank. Please go back and correct.');
		}

		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		$result = $db->sql_query($sql);
		if(!$db->sql_numrows($result)) {
			$sql = 'INSERT INTO ' . GALLERY2_TABLE . " (fullPath, embedUri, g2Uri, loginRedirect, activeAdminId) VALUES ('$fullPath', '$embedUri', '$g2Uri', '$loginRedirect', $activeAdminId)";
		}
		else {
			$sql = 'UPDATE ' . GALLERY2_TABLE . " SET fullPath = '$fullPath', embedUri = '$embedUri', g2Uri = '$g2Uri', loginRedirect = '$loginRedirect', activeAdminId = $activeAdminId";
		}

		$message = 'Configuration data successfully saved.';
	
		if (!$result = $db->sql_query($sql)) {
			message_die(GENERAL_ERROR, 'Could not insert data into Gallery 2 table', $lang['Error'], __LINE__, __FILE__, $sql);
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
			$url_array = parse_url($_SERVER['HTTP_REFERER']);
			$phpbb_url = str_replace('admin/admin_gallery2.php', '' , $path);
			$fullpath = dirname(dirname(__FILE__)) . '/gallery2/';
			$embeduri = $url_array['scheme'] . '://' . $url_array['host'] . '/' . $phpbb_url . 'gallery2.php';
			$g2uri = $url_array['scheme'] . '://' . $url_array['host'] . '/' . $phpbb_url . 'gallery2/';
			$loginredirect = $url_array['scheme'] . '://' . $url_array['host'] . '/' . $phpbb_url . 'login.php';
			$activeadminid = 0;
		}
		else {
			$row = $db->sql_fetchrow($result);
			$fullpath = $row['fullPath'];
			$embeduri = $row['embedUri'];
			$g2uri = $row['g2Uri'];
			$loginredirect = $row['loginRedirect'];
			$activeadminid = intval($row['activeAdminId']);
		}

		$template->assign_vars(array(
			'S_FULLPATH' => $fullpath,
			'S_EMBEDURI' => $embeduri,
			'S_G2URI' => $g2uri,
			'S_LOGINREDIRECT' => $loginredirect,
			'S_ACTIVEADMINID' => $activeadminid,
			'S_SAVECONFIG' => append_sid("admin_gallery2.$phpEx"),
			'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),

			'L_SUBMIT' => $lang['Submit'],
			'L_FULLPATH' => 'Full file path to your Gallery 2 directory: ',
			'L_EMBEDURI' => 'URL to gallery2.php: ',
			'L_G2URI' => 'URL to the gallery2 directory: ',
			'L_LOGINREDIRECT' => 'URL to your login.php file: ',
			'L_ACTIVEADMINID' => 'Active Admin ID: ',
			'L_CONFIG_EXPLAIN' => 'We have done our best to fill these values in for you. However, you should double check them for correctness.<br />Do not change the Active Admin ID, it is managed by the integration package.',
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
		$template->set_filenames(array(
			'body' => './admin/gallery2_show_body.tpl')
		);

		$template->assign_vars(array(
			'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),
			'L_CONFIG' => 'Configure Gallery 2 Integration',
			'L_SYNC' => 'Synchronize phpBB2 Users to Gallery 2',
			'G2_TITLE' => 'Gallery 2 Administration',
			'G2_ADMIN_TASK' => 'Choose your Gallery 2 Adminstration Task')
		);

		$template->pparse('body');

		include('./page_footer_admin.' . $phpEx);

}

?>