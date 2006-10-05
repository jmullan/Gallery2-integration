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
 * Gallery 2 integration for phpBB3.
 * @version $Revision$ $Date$
 * @author Scott Gregory <jettyrat@jettyfishing.com>
 */

class acp_gallery2
{
	var $_integrationVersion = '0.0.1';
	var $_integrationVersionUrl = 'http://nukedgallery.sourceforge.net/phpbb3upgrade.txt';
	var $_integrationChangeLog = 'http://www.nukedgallery.net/postp18006.html#18006';
	var $_integrationDownload = 'http://www.nukedgallery.net/downloads-cat13.html';

	var $_compatibleGalleryVersion = '2.1';
	var $_compatibleEmbedVersionMajor = 1;
	var $_compatibleEmbedVersionMinor = 1;

	var $_timeLimit = 300;

	var $u_action;

	function _fix_slashes($value) {
		return str_replace('\\', '/', $value);
	}

	function main($id, $mode)
	{
		global $db, $user, $auth, $template;
		global $config, $SID, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$user->add_lang('acp/gallery2');
		$this->tpl_name = 'acp_gallery2';

		$action = (isset($_REQUEST['action'])) ? request_var('action', '') : $mode;

		switch ($action) {
			case 'save':
				$embedUri = (isset($_POST['embeduri'])) ? $this->_fix_slashes(request_var('embeduri', '')) : '';
				$g2Uri = (isset($_POST['g2uri'])) ? $this->_fix_slashes(request_var('g2uri', '')) : '';
				$activeAdminId = (isset($_POST['activeadminid'])) ? intval(request_var('activeadminid', '')) : 0;

				if ($embedUri != '' && $g2Uri != '') {
					if (empty($_POST['fullpath'])) {
						require($phpbb_admin_path . 'G2EmbedDiscoveryUtilities.class');
						list ($success, $fullPath, $errorString) = G2EmbedDiscoveryUtilities::getG2EmbedPathByG2Uri($g2Uri);
						if (empty($success)) {
							msg_handler(E_G2_ERROR, $errorString);
						}
					}
					else {
						$fullPath = request_var('fullpath', '');
					}

					$fullPath = $this->_fix_slashes($fullPath);

					$sql = 'SELECT * FROM ' . GALLERY2_TABLE;
					$result = $db->sql_query_limit($sql, 1);
					if(!$db->sql_numrows($result)) {
						$sql = 'INSERT INTO ' . GALLERY2_TABLE . " (fullPath, embedUri, g2Uri, activeAdminId, link, allLinks, allLinksAlbums, allLinksLimit) VALUES ('$fullPath', '$embedUri', '$g2Uri', $activeAdminId, 0, 0, 0, 0)";
					}
					else {
						$sql = 'UPDATE ' . GALLERY2_TABLE . " SET fullPath = '$fullPath', embedUri = '$embedUri', g2Uri = '$g2Uri', activeAdminId = $activeAdminId";
					}

					if (!$db->sql_query($sql)) {
						msg_handler(E_G2_ERROR, $user->lang['INSERT_QUERY_FAILED'], __FILE__, __LINE__);
					}

					list ($success, $msg) = $this->_checkConfig($embedUri);
					if (empty($success)) {
						$message = $msg . '<br />' . $user->lang['GALLERY2_SAVE_ERROR'];
					}
					else {
						$message = $msg . '<br />' . $user->lang['GALLERY2_SAVE_OK'];
					}
				}
				else {
					$message = $user->lang['GALLERY2_CONFIG_ERROR'];
					$error_flag = true;
				}

			case 'config':
				if (isset($message)) {
					$fullpath = $fullPath;
					$embeduri = $embedUri;
					$g2uri = $g2Uri;
					$activeadminid = $activeAdminId;
				}
				else {
					$sql = 'SELECT fullPath, embedUri, g2Uri, activeAdminId FROM ' . GALLERY2_TABLE;
					$result = $db->sql_query_limit($sql, 1);
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
						$fullpath = '';
					}
					else {
						$row = $db->sql_fetchrow($result);
						$fullpath = $row['fullPath'];
						$embeduri = $row['embedUri'];
						$g2uri = $row['g2Uri'];
						$activeadminid = $row['activeAdminId'];
					}
				}

				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_CONFIG_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_MESSAGE' => (isset($message)) ? $message : '',
					'L_CONFIG_TITLE' => $user->lang['GALLERY2_CONFIG_TITLE'],
					'L_G2URI' => $user->lang['GALLERY2_G2URI'],
					'L_CONFIG_EXPLAIN1' => $user->lang['GALLERY2_CONFIG_EXPLAIN1'],
					'L_EMBEDURI' => $user->lang['GALLERY2_EMBEDURI'],
					'L_CONFIG_EXPLAIN2' => $user->lang['GALLERY2_CONFIG_EXPLAIN2'],
					'L_FULLPATH' => $user->lang['GALLERY2_FULLPATH'],
					'L_CONFIG_EXPLAIN3' => $user->lang['GALLERY2_CONFIG_EXPLAIN3'],
					'L_ACTIVEADMINID' => $user->lang['GALLERY2_ACTIVEADMINID'],
					'L_CONFIG_EXPLAIN4' => $user->lang['GALLERY2_CONFIG_EXPLAIN4'],
					'L_SUBMIT' => $user->lang['SUBMIT'],
					'L_RESET' => $user->lang['RESET'],

					'S_CONFIG' => true,
					'S_ERROR' => (isset($error_flag)) ? $error_flag : false,
					'S_G2_SAVE' => $this->u_action . '&amp;action=save',
					'S_G2URI' => $g2uri,
					'S_EMBEDURI' => $embeduri,
					'S_FULLPATH' => $fullpath,
					'S_ACTIVEADMINID' => $activeadminid,
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

    			break;

			case 'sync':
				$this->_g2Init();

				list ($ret, $userList) = GalleryCoreApi::fetchUsernames();
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, $user->lang['G2_FETCHUSERNAMES_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				foreach ($userList as $id => $name) {
					if ($name != 'guest') {
						$sql = 'SELECT username FROM ' . USERS_TABLE . " WHERE username = '$name'";
						if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
							list ($ret, $groupsForUser) = GalleryCoreApi::fetchGroupsForUser($id);
							if (isset($ret)) {
								msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHGROUPSFORUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
							}

							$template->assign_block_vars('users', array(
								'USER_ID' => $id,
								'USER_NAME' => $name,
								'USER_GROUPS' => implode(', ', array_values($groupsForUser)))
							);

							$usersExist = true;
						}
					}
				}

				$this->_g2Done();

				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_SYNC_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_SYNC_TITLE' => $user->lang['GALLERY2_SYNC_TITLE'],
					'L_SYNC_EXISTING' => $user->lang['GALLERY2_SYNC_EXISTING'],
					'L_SYNC_EXPLAIN1' => $user->lang['GALLERY2_SYNC_EXPLAIN1'],
					'L_SYNC_EXPLAIN2' => $user->lang['GALLERY2_SYNC_EXPLAIN2'],
					'L_SYNC_EXPLAIN3' => $user->lang['GALLERY2_SYNC_EXPLAIN3'],
					'L_SYNC_EXPLAIN4' => $user->lang['GALLERY2_SYNC_EXPLAIN4'],
					'L_SYNC_EXPLAIN5' => $user->lang['GALLERY2_SYNC_EXPLAIN5'],
					'L_SYNC_EXPLAIN6' => $user->lang['GALLERY2_SYNC_EXPLAIN6'],
					'L_SYNC_EXPLAIN7' => $user->lang['GALLERY2_SYNC_EXPLAIN7'],
					'L_SYNC_ACTION' => $user->lang['GALLERY2_SYNC_ACTION'],
					'L_SYNC_USER' => $user->lang['GALLERY2_SYNC_USER'],
					'L_SYNC_USERID' => $user->lang['GALLERY2_SYNC_USERID'],
					'L_SYNC_GROUPS' => $user->lang['GALLERY2_SYNC_GROUPS'],
					'L_SYNC_IMPORT' => $user->lang['GALLERY2_SYNC_IMPORT'],
					'L_SYNC_DELETEALL' => $user->lang['GALLERY2_SYNC_DELETEALL'],
					'L_SYNC_DELETE' => $user->lang['GALLERY2_SYNC_DELETE'],
					'L_SYNC_LEAVE' => $user->lang['GALLERY2_SYNC_LEAVE'],
					'L_SYNC_NOW' => $user->lang['GALLERY2_SYNC_NOW'],
					'L_SYNC_LATER' => $user->lang['GALLERY2_SYNC_LATER'],
					'L_SYNC_BUTTON' => $user->lang['GALLERY2_SYNC_BUTTON'],

					'S_SYNC' => true,
					'S_G2_EXPORT' => $this->u_action . '&amp;action=export',
					'S_EXPLAIN' => (isset($usersExist)) ? $usersExist : false,
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

    			break;

			case 'options':
/*				if ($fp = @fopen($this->_integrationVersionUrl, 'r')) {
					$versionData = fread($fp, 4096);

					fclose($fp);

					$versionData = explode("\n", $versionData);

					$integrationVersion = explode('.', $this->_integrationVersion);

					if ($$versionData[0] == $integrationVersion[0] && $versionData[1] == $integrationVersion[1] && $versionData[2] == $integrationVersion[2]) {	
						$versionText = $user->lang['GALLERY2_TO_DATE'];
					}
					else {
						$versionText = sprintf($user->lang['GALLERY2_NOT_TO_DATE'], "$versionData[0].$versionData[1].$versionData[2]") . sprintf($user->lang['GALLERY2_VIEW_CHANGES'], $this->_integrationChangeLog, $this->_integrationDownload);
					}
				}
				else {
					$versionText = sprintf($user->lang['GALLERY2_URL_FAILED'], $this->_integrationVersionUrl);
				}
*/		
				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_ADMIN_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_ADMIN_TITLE' => $user->lang['GALLERY2_ADMIN_TITLE'],
					'L_OPTIONS_CONFIG' => $user->lang['GALLERY2_OPTIONS_CONFIG'],
					'L_OPTIONS_LINKS' => $user->lang['GALLERY2_OPTIONS_LINKS'],
					'L_OPTIONS_SYNC' => $user->lang['GALLERY2_OPTIONS_SYNC'],
					'L_OPTIONS_CONFIRM' => $user->lang['GALLERY2_OPTIONS_CONFIRM'],
					'L_VERSION_TITLE' => $user->lang['GALLERY2_VERSION_TITLE'],
					'L_VERSION_MSG' => (isset($versionText)) ? $versionText : '',

					'S_OPTIONS' => true,
					'S_G2_CONFIG' => $this->u_action . '&amp;action=config',
					'S_G2_LINKS' => $this->u_action . '&amp;action=links',
					'S_G2_SYNC' => $this->u_action . '&amp;action=sync',
					'S_G2_CONFIRM' => $this->u_action . '&amp;action=confirm',
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

				break;

			case 'linksave':
				$link = (!empty($_POST['link'])) ? 1 : 0;
				$allLinks = (!empty($_POST['all_links'])) ? 1 : 0;
				$allLinksAlbums = (!empty($_POST['showalbums'])) ? 1 : 0;
				$allLinksLimit = (intval($_POST['limit_links']) > 0) ? intval(request_var('limit_links', '')) : 0;

				$sql = 'UPDATE ' . GALLERY2_TABLE . " SET link = $link, allLinks = $allLinks, allLinksAlbums = $allLinksAlbums, allLinksLimit = $allLinksLimit";
				if (!$db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['INSERT_QUERY_FAILED'], __FILE__, __LINE__);
				}

				$message = $user->lang['GALLERY2_LINKS_MESSAGE'];

			case 'links':
				if (empty($message)) {
					$sql = 'SELECT link, allLinks, allLinksAlbums, allLinksLimit FROM ' . GALLERY2_TABLE;
					if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
						msg_handler(E_G2_ERROR, $user->lang['OBTAIN_SETTINGS_FAILED'], __FILE__, __LINE__);
					}
					$link = $row['link'];
					$allLinks = $row['allLinks'];
					$allLinksAlbums = $row['allLinksAlbums'];
					$allLinksLimit = $row['allLinksLimit'];
				}

				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_LINKS_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_LINKS_TITLE' => $user->lang['GALLERY2_LINKS_TITLE'],
					'L_ALLLINKS_TITLE' => $user->lang['GALLERY2_ALLLINKS_TITLE'],
					'L_MESSAGE' => (isset($message)) ? $message : '',
					'L_LINK' => $user->lang['GALLERY2_LINKS_LINK'],
					'L_LINKS_EXPLAIN1' => $user->lang['GALLERY2_LINKS_EXPLAIN1'],
					'L_ALL_LINKS' => $user->lang['GALLERY2_LINKS_ALLLINKS'],
					'L_LINKS_EXPLAIN2' => $user->lang['GALLERY2_LINKS_EXPLAIN2'],
					'L_ALL_SHOWALBUMS' => $user->lang['GALLERY2_LINKS_SHOWALBUMS'],
					'L_LINKS_EXPLAIN3' => $user->lang['GALLERY2_LINKS_EXPLAIN3'],
					'L_LIMIT_LINKS' => $user->lang['GALLERY2_LINKS_LIMITLINKS'],
					'L_LINKS_EXPLAIN4' => $user->lang['GALLERY2_LINKS_EXPLAIN4'],
					'L_LINKS_YES' => $user->lang['YES'],
					'L_LINKS_NO' => $user->lang['NO'],

					'S_LINKS' => true,
					'S_LINKS1' => (!empty($link)) ? 'checked ' : '',
					'S_LINKS0' => (empty($link)) ? 'checked ' : '',
					'S_ALL_LINKS1' => (!empty($allLinks)) ? 'checked ' : '',
					'S_ALL_LINKS0' => (empty($allLinks)) ? 'checked ' : '',
					'S_SHOWALBUMS1' => (!empty($allLinksAlbums)) ? 'checked ' : '',
					'S_SHOWALBUMS0' => (empty($allLinksAlbums)) ? 'checked ' : '',
					'S_LIMIT_LINKS' => (!empty($allLinksLimit)) ? $allLinksLimit : 0,
					'S_G2_LINKS' => $this->u_action . '&amp;action=linksave',
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

				break;

			case 'export':
				$export = (object) true;

				foreach(array('processed', 'existing', 'imported') as $key) {
					$export->groups[$key] = 0;
				}
				$export->groups['failures'] = array();

				foreach(array('processed', 'existing', 'nonactive', 'guest', 'admin', 'imported') as $key) {
					$export->users[$key] = 0;
				}
				$export->users['failures'] = array();

				// grab list of phpBB groups for use later
				$phpbbDefaultGroups = array('GUESTS', 'INACTIVE', 'INACTIVE_COPPA', 'REGISTERED', 'REGISTERED_COPPA', 'GLOBAL_MODERATORS', 'ADMINISTRATORS', 'BOTS');

				$phpbbGroupNames = array();

				$sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE;
				if (!$result = $db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['FETCH_GROUPDATA_FAILED'], __FILE__, __LINE__);
				}

				while($row = $db->sql_fetchrow($result)) {
					if (in_array($row['group_name'], $phpbbDefaultGroups)) {
						if ($row['group_name'] == 'REGISTERED') {
							$registeredGroupId = $row['group_id'];
						}
						elseif ($row['group_name'] == 'ADMINISTRATORS') {
							$administratorsGroupId = $row['group_id'];
						}
						elseif ($row['group_name'] == 'GUESTS') {
							$guestsGroupId = $row['group_id'];
						}
						elseif ($row['group_name'] == 'BOTS') {
							$botsGroupId = $row['group_id'];
						}
					}
					else {
						$phpbbGroupNames[$row['group_id']] = $row['group_name'];
					}
				}

				$phpbbGroupIds = array_flip($phpbbGroupNames);

				$gallery = $this->_g2Init();

				// grab G2 group parameters for use later
				$g2DefaultGroups = array('Registered Users', 'Site Admins', 'Everybody');

				list ($ret, $adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, $user->lang['G2_ADMINPARAMETER_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				list ($ret, $userGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.allUserGroup');
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, $user->lang['G2_GROUPPARAMETER_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				list ($ret, $everybodyGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.everybodyGroup');
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, $user->lang['G2_EVERYBODYPARAMETER_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				// handle existing G2 users
				$usersExisting = (isset($_POST['user'])) ? $_POST['user'] : array();
				if (count($usersExisting) != 0)	{
					$user_password = md5(''); // we can't use the G2 password because of the salted md5 hash
					foreach ($usersExisting as $g2Id => $g2Action) {
						switch ($g2Action) {
							case '1': // export G2 user to phpbb
								list ($ret, $entityId) = GalleryCoreApi::loadEntitiesById($g2Id);
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_LOADENTITIESBYID_FAILED'], $g2Id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								list ($ret, $groupsForUser) = GalleryCoreApi::fetchGroupsForUser($g2Id);
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHGROUPSFORUSER_FAILED'], $g2Id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								$group_id = (in_array($adminGroupId, array_keys($groupsForUser))) ? $administratorsGroupId : $registeredGroupId;

								$db->sql_transaction();

								$sql = 'INSERT INTO ' . USERS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
									'user_type'	=> USER_NORMAL,
									'group_id' => (int) $group_id,
									'user_regdate' => time(),
									'username' => $entityId->getuserName(),
									'user_password'	=> $user_password,
									'user_email' => $entityId->getemail(),
									'user_email_hash' => (int) crc32(strtolower($entityId->getemail())) . strlen($entityId->getemail()),
									'user_lastmark'	=> time(),
									'user_lang'	=> ($entityId->getlanguage()) ? $entityId->getlanguage() : 'en',
									'user_style' => $config['default_style'],
									'user_allow_pm' => 1)
								);
								if (!$db->sql_query($sql)) {
									$db->sql_transaction('rollback');
									msg_handler(E_G2_ERROR, $user->lang['INSERT_USERDATA_FAILED'], __FILE__, __LINE__);
								}

								$user_id = $db->sql_nextid();

								$sql = 'INSERT INTO ' . USER_GROUP_TABLE . ' ' . $db->sql_build_array('INSERT', array(
									'user_id' => (int) $user_id,
									'group_id' => (int) $group_id,
									'user_pending' => 0)
								);
								if (!$db->sql_query($sql)) {
									$db->sql_transaction('rollback');
									msg_handler(E_G2_ERROR, $user->lang['INSERT_USERGROUPDATA_FAILED'], __FILE__, __LINE__);
								}

								$groupsForUser = array_diff($groupsForUser, $g2DefaultGroups);

								if (!empty($groupsForUser)) {
									foreach ($groupsForUser as $groupName) {
										if (in_array($groupName, $phpbbGroupNames)) {
											$sql = 'INSERT INTO ' . USER_GROUP_TABLE . ' ' . $db->sql_build_array('INSERT', array(
												'user_id' => (int) $user_id,
												'group_id' => (int) $phpbbGroupIds[$groupName],
												'user_pending' => 0)
											);
											if (!$db->sql_query($sql)) {
												$db->sql_transaction('rollback');
												msg_handler(E_G2_ERROR, $user->lang['INSERT_USERGROUPDATA_FAILED'], __FILE__, __LINE__);
											}
										}
										else {
											// add gallery group to phpbb?
										}
									}
								}

								$db->sql_transaction('commit');

								break;

							case '2': // delete items and user from G2
							case '3': // keep items and delete user from G2
								list ($ret, $adminUsers) = GalleryCoreApi::fetchUsersForGroup($adminGroupId, 2);
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERSFORGROUP_FAILED'], $adminGroupId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}
								if (empty($adminUsers)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_RETURNADMINS_FAILED'], $adminGroupId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								$adminUsers = array_keys($adminUsers);

								if ($adminUsers[0] == $g2Id && count($adminUsers) == 1) {
									msg_handler(E_G2_ERROR, $user->lang['G2_DELETEADMIN_FAILED'], __FILE__, __LINE__);
								}

								$adminId = ($adminUsers[0] != $g2Id) ? $adminUsers[0] : $adminUsers[1];

								list ($ret, $entityId) = GalleryCoreApi::loadEntitiesById($adminId);
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_LOADENTITIESBYID_FAILED'], $adminId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								$gallery->setActiveUser($entityId);

								if (intval($g2Action) == 2) { // delete items
									$ret = GalleryCoreApi::deleteUserItems($g2Id);
									if (isset($ret)) {
										msg_handler(E_G2_ERROR, sprintf($user->lang['G2_DELETEUSERITEMS_FAILED'], $g2Id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
									}
								}
								else { // keep items
									$ret = GalleryCoreApi::remapOwnerId($g2Id, $user->getId());
									if (isset($ret)) {
										msg_handler(E_G2_ERROR, sprintf($user->lang['G2_REMAPOWNERID_FAILED'], $g2Id, $adminId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
									}
								}

								$ret = GalleryCoreApi::deleteEntityById($g2Id);
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_DELETEENTITYBYID_FAILED'], $g2Id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								break;

							default: // leave as is!
						}
					}
				}

				// handle existing phpBB user groups
				if (count($phpbbGroupNames) > 0) {
					$group_flag = $failed = false;

					$groupG2List = $externalEntityIdMap = array();

					$query = 'SELECT [Group::id], [Group::groupName] FROM [Group]';
					list ($ret, $results) = $gallery->search($query, array());
					if (isset($ret)) {
						msg_handler(E_G2_ERROR, $user->lang['G2_FETCHGROUPINFO_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
					}

					while($g2Result = $results->nextResult()) {	
						if ($g2Result[0] != $adminGroupId && $g2Result[0] != $userGroupId && $g2Result[0] != $everybodyGroupId) {
							$groupG2List[] = array('groupId' => $g2Result[0], 'groupName' => $g2Result[1]);
						}
					}

					list ($ret, $externalIdMap) = GalleryEmbed::getExternalIdMap('externalId');
					if (isset($ret)) {
						msg_handler(E_G2_ERROR, $user->lang['G2_GETEXTERNALIDMAP_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
					}

					foreach ($externalIdMap as $key => $entity) {
						if ($entity['entityType'] == 'GalleryGroup') {
							$externalEntityIdMap[] = array('entityId' => $entity['entityId'], 'externalId' => $entity['externalId']);
						}
					}

					foreach ($phpbbGroupNames as $groupName) {	
						for ($i = 0; $i < count($groupG2List); $i++) {
							if ($groupName == $groupG2List[$i]['groupName']) {
								for ($j = 0; $j < count($externalEntityIdMap); $j++) {
									if ($groupG2List[$i]['groupId'] == $externalEntityIdMap[$j]['entityId']) {
										$export->groups['existing']++;
										$group_flag = true;
										break 2;
									}
								}
								$ret = GalleryEmbed::addExternalIdMapEntry($groupName, $groupG2List[$i]['groupId'], 'GalleryGroup');
								if (empty($ret)) {
									$export->groups['imported']++;
									$group_flag = true;
									break;
								}
								else {
									$export->group['failures'][] = $groupName;
									$failed = true;
									break;
								}
							}
						}
						if (empty($group_flag) && empty($failed)) {
							$ret = GalleryEmbed::createGroup($groupName, $groupName);
							if (empty($ret)) {
								$export->groups['imported']++;
							}
							else {
								$export->group['failures'][] = $groupName;
							}
						}

						$export->groups['processed']++;
						$group_flag = $failed = false;
					}
				}

				// handle phpBB user import to G2
				$sql = 'SELECT user_id, group_id, user_regdate, username, user_password, user_email, user_lastvisit, user_lang FROM ' . USERS_TABLE . " WHERE group_id <> $botsGroupId";

				if (request_var('export', '') == 'later') {
					$sql .= " AND group_id = $administratorsGroupId OR group_id = $guestsGroupId";
				}

				if (!$result = $db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['OBTAIN_USERINFO_FAILED'], __FILE__, __LINE__);
				}

				$ucount = $db->sql_numrows($result);

				$failed = $guestIsSet = false;

				$admins = array();

				foreach ($phpbbDefaultGroups as $key => $group) {
					$phpbbDefaultGroups[$key] = "\"$group\"";
				}

				$this->_beginProgressBar();

				while($row = $db->sql_fetchrow($result)) {	
					$user_id = $row['user_id'];
					$group_id = $row['group_id'];
					$lastvisit = $row['user_lastvisit'];
					$args['fullname'] = $row['username'];
					$args['username'] = $row['username'];
					$args['hashedpassword'] = $row['user_password']; 
					$args['email'] = $row['user_email'];
					$args['creationtimestamp'] = $row['user_regdate'];
					$args['language'] = $row['user_lang'];
					$args['hashmethod'] = 'md5';

					if ($group_id != $guestsGroupId) {
						$ret = GalleryEmbed::isExternalIdMapped($user_id, 'GalleryUser');
						if (empty($ret)) {
							$export->users['existing']++;
						}
						elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
							if ($lastvisit > 0 || $group_id == $administratorsGroupId) {
								list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($args['username']);
								if (empty($ret)) {
									$ret = GalleryEmbed::addExternalIdMapEntry($user_id, $userId->getId(), 'GalleryUser');
									if (isset($ret)) {
										$export->users['failures'][] = $user_id . ' : ' . $args['username'];
										$failed = true;
									}
								}
								elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
									$ret = GalleryEmbed::createUser($user_id, $args);
									if (isset($ret)) {
										$export->users['failures'][] = $user_id . ' : ' . $args['username'];
										$failed = true;
									}
								}
								else {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $args['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								if (empty($failed)) {
									$g_sql = 'SELECT DISTINCT g.group_name FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug WHERE ug.user_id = $user_id AND ug.user_pending = 0 AND ug.group_id = g.group_id AND g.group_name NOT IN (" . implode(', ', $phpbbDefaultGroups) . ')';
									if (!$g_result = $db->sql_query($g_sql)) {
										msg_handler(E_G2_ERROR, $user->lang['FETCH_USERGROUPDATA_FAILED'], __FILE__, __LINE__);
									}

									while ($g_row = $db->sql_fetchrow($g_result)) {
										$ret = GalleryEmbed::addUserToGroup($user_id, $g_row['group_name']);
										if (isset($ret)) {
											$export->users['failures'][] = sprintf($user->lang['GALLERY2_EXPORT_ADDTOGROUP'], $user_id, $g_row['group_name']);
										}
									}

									if ($group_id === $administratorsGroupId) {
										$admins[$user_id] = $args['username'];
									}
								}

								$export->users['imported']++;
								$failed = false;
							}
							else {
								$export->users['nonactive']++;
							}
						}
						else {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $user_id), __FILE__, __LINE__);
						}
					}
					else {
						if (empty($guestIsSet)) {
							$ret = GalleryEmbed::isExternalIdMapped('guest', 'GalleryUser');
							if (empty($ret)) {
								$export->users['existing']++;
							}
							elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
								list ($ret, $guestUserId) = GalleryCoreApi::fetchUserByUserName('guest');
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], 'guest') . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								$ret = GalleryEmbed::addExternalIdMapEntry('guest', $guestUserId->getId(), 'GalleryUser');
								if (isset($ret)) {
									msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ADDEXTERNALMAPENTRY_FAILED'], 'guest') . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
								}

								$export->users['guest']++;
								$export->users['imported']++;
							}
							else {
								msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], 'guest'), __FILE__, __LINE__);
							}

							$guestIsSet = true;
						}
					}

					$export->users['processed']++;

					if ($export->users['processed'] % 100 == 0)	{
						$decimalPercent = $export->users['processed'] / $ucount;
						$this->_updateProgressBar($export->users['processed'], $decimalPercent);
					}
				}

				// handle any admins gathered
				if (count($admins) > 0) {
					$adminIsSet = false;

					if (!GalleryCoreApi::isUserInSiteAdminGroup()) {
						list ($ret, $adminUser) = GalleryCoreApi::fetchUsersForGroup($adminGroupId, 1);
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERSFORGROUP_FAILED'], $adminGroupId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						if (empty($adminUser)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_RETURNADMINS_FAILED'], $adminGroupId), __FILE__, __LINE__);
						}
						$validAdmin = array_keys($adminUser[0]);

						list ($ret, $entityId) = GalleryCoreApi::loadEntitiesById($validAdmin);
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_LOADENTITIESBYID_FAILED'], $validAdmin) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						$gallery->setActiveUser($entityId);
					}

					foreach ($admins as $user_id => $user_name) {
						list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($user_name);
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $user_name) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $adminGroupId);
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $adminGroupId, $userId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						if (empty($adminIsSet)) {
							$sql = 'UPDATE ' . GALLERY2_TABLE . " SET activeAdminId = $user_id";
							if (!$db->sql_query($sql)) {
								msg_handler(E_G2_ERROR, $user->lang['UPDATE_ACTIVEADMINID_FAILED'], __FILE__, __LINE__);
							}

							$adminIsSet = true;
						}

						$export->users['admin']++;
					}
				}

				$sql = 'UPDATE ' . GALLERY2_TABLE . " SET exportData = '" . serialize($export) . "'";
				if (!$db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['UPDATE_EXPORTDATA_FAILED'], __FILE__, __LINE__);
				}

				$this->_g2Done();

				$this->_updateProgressBar($export->users['processed'], 1);

				$this->_endProgressBar();

				break;

			case 'stats':
				$sql = 'SELECT exportData FROM ' . GALLERY2_TABLE;
				if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
					msg_handler(E_G2_ERROR, $user->lang['FETCH_EXPORTDATA_FAILED'], __FILE__, __LINE__);
				}

				$export = unserialize($row['exportData']);

				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_EXPORT_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_EXPORT_TITLE' => $user->lang['GALLERY2_EXPORT_TITLE'],
					'L_GROUPS_PROCESSED' => sprintf($user->lang['GALLERY2_EXPORT_G_PROCESSED'], $export->groups['processed']),
					'L_GROUPS_EXISTING' => sprintf($user->lang['GALLERY2_EXPORT_G_EXISTING'], $export->groups['existing']),
					'L_GROUPS_IMPORTED' => sprintf($user->lang['GALLERY2_EXPORT_G_IMPORTED'], $export->groups['imported']),
					'L_GROUPS_FAILURES1' => sprintf($user->lang['GALLERY2_EXPORT_G_FAILED1'], count($export->groups['failures'])),
					'L_GROUPS_FAILURES2' => $user->lang['GALLERY2_EXPORT_G_FAILED2'],
					'L_GROUPS_FAILURES3' => implode('<br />', $export->groups['failures']),
					'L_USERS_PROCESSED' => sprintf($user->lang['GALLERY2_EXPORT_U_PROCESSED'], $export->users['processed']),
					'L_USERS_EXISTING' => sprintf($user->lang['GALLERY2_EXPORT_U_EXISTING'], $export->users['existing']),
					'L_USERS_NONACTIVE' => sprintf($user->lang['GALLERY2_EXPORT_U_NONACTIVE'], $export->users['nonactive']),
					'L_USERS_GUEST' => sprintf($user->lang['GALLERY2_EXPORT_U_GUEST'], $export->users['guest']),
					'L_USERS_ADMIN' => sprintf($user->lang['GALLERY2_EXPORT_U_ADMIN'], $export->users['admin']),
					'L_USERS_IMPORTED' => sprintf($user->lang['GALLERY2_EXPORT_U_IMPORTED'], $export->users['imported']),
					'L_USERS_FAILURES1' => sprintf($user->lang['GALLERY2_EXPORT_U_FAILED1'], count($export->users['failures'])),
					'L_USERS_FAILURES2' => $user->lang['GALLERY2_EXPORT_U_FAILED2'],
					'L_USERS_FAILURES3' => implode('<br />', $export->users['failures']),
					'L_USERS_REASON1' => $user->lang['GALLERY2_EXPORT_REASON1'],
					'L_USERS_REASON2' => $user->lang['GALLERY2_EXPORT_REASON2'],
					'L_USERS_REASON3' => $user->lang['GALLERY2_EXPORT_REASON3'],
					'L_USERS_REASON4' => $user->lang['GALLERY2_EXPORT_REASON4'],
					'L_USERS_REASON5' => $user->lang['GALLERY2_EXPORT_REASON5'],

					'S_EXPORT' => true,
					'S_G_EXISTING' => ($export->groups['existing'] > 0) ? true : false,
					'S_G_FAILURES' => (count($export->groups['failures']) > 0) ? true : false,
					'S_U_EXISTING' => ($export->users['existing'] > 0) ? true : false,
					'S_U_NONACTIVE' => ($export->users['nonactive'] > 0) ? true : false,
					'S_U_GUEST' => ($export->users['guest'] > 0) ? true : false,
					'S_U_FAILURES' => (count($export->users['failures']) > 0) ? true : false,
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

				break;

			case 'confirm':
				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_UNMAP_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_CONFIRM_TITLE' => $user->lang['GALLERY2_UNMAP_TITLE'],
					'L_CONFIRM_EXPLAIN1' => $user->lang['GALLERY2_CONFIRM_EXPLAIN1'],
					'L_CONFIRM_EXPLAIN2' => $user->lang['GALLERY2_CONFIRM_EXPLAIN2'],
					'L_CONFIRM_EXPLAIN3' => $user->lang['GALLERY2_CONFIRM_EXPLAIN3'],
					'L_CONFIRM_BUTTON' => $user->lang['GALLERY2_OPTIONS_CONFIRM'],

					'S_CONFIRM' => true,
					'S_G2_CONFIRM' => $this->u_action . '&amp;action=unmap',
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

				break;

			case 'unmap':
				$users_processed = $users_unmapped = $groups_processed = 0;

				$this->_g2Init();

				if (!GalleryCoreApi::isUserInSiteAdminGroup()) {
					msg_handler(E_G2_ERROR, $user->lang['G2_AUTHADMIN_FAILED'], __FILE__, __LINE__);
				}

				list ($ret, $externalIdMap) = GalleryEmbed::getExternalIdMap('externalId');
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, $user->lang['G2_GETEXTERNALIDMAP_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				foreach ($externalIdMap as $mapping) {
					if (intval($mapping['externalId']) == $user->data['user_id'] || $mapping['externalId'] == 'guest') {
						$ret = GalleryCoreApi::removeMapEntry('ExternalIdMap', array('externalId' => $mapping['externalId'], 'entityType' => 'GalleryUser'));
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_REMOVEMAPENTRY_FAILED'], $mapping['externalId']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						$users_unmapped++;
					}
					elseif ($mapping['entityType'] == 'GalleryGroup') {
						$ret = GalleryEmbed::deleteGroup($mapping['externalId']);
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_DELETEGROUP_FAILED'], $mapping['externalId']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						$groups_processed++;
					}
					else {
						$ret = GalleryEmbed::deleteUser($mapping['externalId']);
						if (isset($ret)) {
							msg_handler(E_G2_ERROR, sprintf($user->lang['G2_DELETEUSER_FAILED'], $mapping['externalId']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
						}

						$users_processed++;
					}
				}

				$this->_g2Done();

				$sql = 'UPDATE ' . GALLERY2_TABLE . ' SET activeAdminId = 0';
				if (!$db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['UPDATE_ACTIVEADMINID_FAILED'], __FILE__, __LINE__);
				}

				$this->page_title = $user->lang['ACP_GALLERY2_INTEGRATION'] . ' :: ' . $user->lang['GALLERY2_UNMAP_TITLE'];

				$template->assign_vars(array(
					'L_INTEGRATION_TITLE' => $user->lang['ACP_GALLERY2_INTEGRATION'],
					'L_UNMAP_TITLE' => $user->lang['GALLERY2_UNMAP_TITLE'],
					'L_GROUPS_PROCESSED' => sprintf($user->lang['GALLERY2_UNMAP_G_PROCESSED'], $groups_processed),
					'L_USERS_PROCESSED' => sprintf($user->lang['GALLERY2_UNMAP_U_PROCESSED'], $users_processed),
					'L_USERS_UNMAPPED' => sprintf($user->lang['GALLERY2_UNMAP_U_UNMAPPED'], $users_unmapped),

					'S_UNMAP' => true,
					'S_INTEGRATION_VERSION' => $this->_integrationVersion)
				);

				break;

		}
	}

	function _g2Init() {
		global $db, $user;

		$sql = 'SELECT * FROM ' . GALLERY2_TABLE;
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['OBTAIN_SETTINGS_FAILED'], __FILE__, __LINE__);
		}

		require_once($row['fullPath']);

		$ret = GalleryEmbed::init(array(
			'embedUri' => $row['embedUri'], 
			'g2Uri' => $row['g2Uri'], 
			'activeUserId' => $row['activeAdminId'],
			'fullInit' => true,
			'apiVersion' => array($this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor))
		);
		if (isset($ret)) {
			msg_handler(E_G2_ERROR, $user->lang['G2_INIT_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}

		$gallery->guaranteeTimeLimit($this->_timeLimit);
		if (isset($ret)) {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_TIME_LIMIT'], $this->_timeLimit) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}

		return $gallery;
	}

	function _g2Done() {
		$ret = GalleryEmbed::done();
		if (isset($ret)) {
			global $user;

			msg_handler(E_G2_ERROR, $user->lang['G2_TRANSACTION_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}
	}

	function _checkConfig($embedUri) {
		global $user;

		/*
		* Check embedUri portions shamelessly 'borrowed' from Valiant's discovery utility
		*
		* Begin embedUri check
		*/

		if (strpos($embedUri, 'http') !== 0) {
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
			$host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
			$embedUri = sprintf('%s://%s%s', $protocol, $host, $embedUri);
		}

		$components = @parse_url($embedUri);
		if (!$components) {
			return array(false, "Unable to parse URL $embedUri. Please check the URL path to your gallery2.php file.");
		}
		$port = empty($components['port']) ? 80 : $components['port'];
		if (empty($components['path'])) {
			$components['path'] = '/';
		}

		$fd = @fsockopen($components['host'], $port, $errno, $errstr, 1);
		if (empty($fd)) {
			return array(false, "Error $errno: '$errstr' retrieving $embedUri");
		}

		$get = $components['path'];

		$ok = fwrite($fd, sprintf("GET %s HTTP/1.0\r\nHost: %s\r\n\r\n", $get, $components['host']));
		if (!$ok) {
			$errorStr = "Verification of gallery2.php location failed. fwrite call failed for $embedUri";
			if ($ok === false) {
				$errorStr .= "\nreturn value was false";
			}
			return array(false, $errorStr);
		}
		$ok = fflush($fd);
		if (!$ok) {
			if (version_compare(phpversion(), '4.2.0', '>=')) {
				/* Ignore false returned from fflush on PHP 4.1 */
				return array(false, "Verification of gallery2.php location failed. fflush call failed for $embedUri");
			}
		}

		$headers = array();
		$response = trim(fgets($fd, 4096));

		if (!preg_match("/^HTTP\/\d+\.\d+\s2\d{2}/", $response)) {
			return array(false, "URL derived from $embedUri is invalid");
		}

		/*
		* If we reach this point without error, the location of embedUri checks out ok
		*
		* End embedUri check
		*/

		$this->_g2Init();

		if (is_callable('GalleryEmbed', 'getApiVersion')) {
			list ($major, $minor) = GalleryEmbed::getApiVersion();
			$this->_g2Done();

			if ($major == $this->_compatibleEmbedVersionMajor && $minor >= $this->_compatibleEmbedVersionMinor) {
				/*
				* If we reach this point without error; g2Uri, fullPath, embedUri file checks have passed and version check has passed
				* Integration life is good!
				*/
				return array(true, $user->lang['GALLERY2_SETTINGS_PASSED']);
			}
			elseif ($major > $this->_compatibleEmbedVersionMajor) {
				/* GalleryEmbed module major version is newer, may or may not work */
				return array(false, sprintf($user->lang['GALLERY2_SETTINGS_WARNING'], $this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor, $major, $minor));
			}
			else {
				/* GalleryEmbed module version is older, good chance will not work */
				return array(false, sprintf($user->lang['GALLERY2_SETTINGS_FAILED'], $this->_compatibleGalleryVersion, $this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor, $major, $minor));
			}
		}
		else {
			$this->_g2Done();

			/* GalleryEmbed module version doesn't support getApiVersion. Gallery version is older than 2.1 */
			return array(false, sprintf($user->lang['GALLERY_OLDER_VERSION'], $this->_compatibleGalleryVersion));
		}
	}

	function _beginProgressBar() {
		global $user;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="imagetoolbar" content="no" />
<title>Gallery2 Integration :: Synchronize Gallery2 <-->phpBB3</title>

<script language="javascript" type="text/javascript">
var saveToGoDisplay = document.getElementById('progressToGo').style.display;
function updateProgressBar(description, percentComplete) {
	document.getElementById('progressDescription').innerHTML = description;
    var progressMade = Math.round(percentComplete * 100);
    var progressToGo = document.getElementById('progressToGo');

    if (progressMade == 100) {
      progressToGo.style.display = 'none'; 
    } else {
      progressToGo.style.display = saveToGoDisplay;
      progressToGo.style.width = (100 - progressMade) + "%";
    }

    document.getElementById('progressDone').style.width = progressMade + "%";
  }
</script>
<style type="text/css">
<!--

 /* General page style. The scroll bar colours only visible in IE5.5+ */
body {
	background-color: #E5E5E5;
	scrollbar-face-color: #DEE3E7;
	scrollbar-highlight-color: #FFFFFF;
	scrollbar-shadow-color: #DEE3E7;
	scrollbar-3dlight-color: #D1D7DC;
	scrollbar-arrow-color:  #006699;
	scrollbar-track-color: #EFEFEF;
	scrollbar-darkshadow-color: #98AAB1;
}

/* General font families for common tags */
font,p { font-family: Verdana, Arial, Helvetica, sans-serif }
p, td		{ font-size : 11; color : #000000; }
a:link,a:active,a:visited { color : #006699; }
a:hover		{ text-decoration: underline; color : #DD6900; }
.gbBlock {
    padding: 0.7em;
    border-width: 0 0 1px 0;
    border-style: inherit;
    border-color: inherit;
    /* IE can't inherit these */
    border-style: expression(parentElement.currentStyle.borderStyle);
    border-color: expression(parentElement.currentStyle.borderColor);
}
h1,h2 { font-family: "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif; font-size : 22px; font-weight : bold; text-decoration : none; line-height : 120%; color : #000000;}
#ProgressBar #progressDone {
    background-color: #fd6704;
    border: thin solid #ddd;
}

#ProgressBar #progressToGo {
    background-color: #eee;
    border: thin solid #ddd;
}
#gallery h2, #gallery h3, #gallery h4 {
    font-family: "Trebuchet MS", Arial, Verdana, Helvetica, sans-serif;
}
.giTitle, #gallery h2, #gallery h3, #gallery h4 {
    font-size: 1.3em;
    font-weight: bold;
}
#gallery .gbBlock h3 {
    margin-bottom: 0.5em;
}
-->
</style>
</head><body>
<h1><?php echo $user->lang['GALLERY2_EXPORT_USERS']; ?></h1><br />
<div id="ProgressBar" class="gbBlock">
  <p id="progressDescription">
    &nbsp;
  </p>

  <table width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td id="progressDone" style="display: inline-block; width:0%">&nbsp;</td>
      <td id="progressToGo" style="display: inline-block; width:100%; border-left: none">&nbsp;</td>
    </tr>
  </table>
</div>
<?php
	}

	function _updateProgressBar($users, $percent) {
		global $user;
?>
<script language="javascript" type="text/javascript">
	<!--
	updateProgressBar("<?php echo $users . ' ' . $user->lang['GALLERY2_EXPORT_PROCESSED']; ?>", <?php echo $percent; ?>);
	-->
</script>
<?php
		@ob_flush();
		flush();
	}

	function _endProgressBar() {
		global $user;
?>
<p>&nbsp;</p>
<form><input type="button" value="<?php echo $user->lang['GALLERY2_EXPORT_CONTINUE']; ?>" onClick="location.href='<?php echo $this->u_action . '&amp;action=stats'; ?>'"></form>
</body><html>
<?php
	 	exit;
	}

}

?>
