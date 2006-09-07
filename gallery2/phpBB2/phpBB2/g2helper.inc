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

class g2helper {

	var $_compatibleEmbedVersionMajor = 1;
	var $_compatibleEmbedVersionMinor = 1;
	var $_activeAdminId;
	var $_init_array;
	var $_adminGroupId;

	function g2helper($db) {
		global $lang, $phpbb_root_path, $board_config, $phpEx;

		$lang_file_path = $phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_gallery2.' . $phpEx;
		if (file_exists($lang_file_path)) {
			include($lang_file_path);
		}
		else {
			include($phpbb_root_path . 'language/lang_english/lang_gallery2.' . $phpEx);
		}

		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['OBTAIN_SETTINGS_FAILED'], __LINE__, __FILE__, $sql);
		}

		$this->_activeAdminId = $row['activeAdminId'];
		$this->_init_array = array(
			'embedUri' => $row['embedUri'], 
			'g2Uri' => $row['g2Uri'], 
			'apiVersion' => array($this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor)
		);

		if (!empty($row['utf8_translate'])) {
			define('UTF8_TRANSLATE', true);
		}

		if ($this->_activeAdminId == 0) {
			$this->errorHandler(GENERAL_ERROR, $lang['G2_AUTHADMIN_FAILED'], __LINE__, __FILE__);
		}
		else {
			require_once($row['fullPath']);
		}
	}

	function _initAdmin() {
		global $lang;

		$this->_init_array['activeUserId'] = $this->_activeAdminId;

		$ret = GalleryEmbed::init($this->_init_array);
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, $lang['G2_INITADMIN_FAILED'] . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		list ($ret, $this->_adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, $lang['G2_ADMINPARAMETER_FAILED'] . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}
	}

	function init($userdata) {
		global $lang;

		if ($userdata['user_id'] != ANONYMOUS) {
			$this->_initAdmin();

			$this->_init_array['activeUserId'] = $userdata['user_id'];

			$ret = GalleryEmbed::isExternalIdMapped($userdata['user_id'], 'GalleryUser');
			if (empty($ret)) {
				$this->done();

				$ret = GalleryEmbed::init($this->_init_array);
				if (isset($ret)) {
					$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_INITUSER_FAILED'], $userdata['username']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
				}
			}
			elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
				$this->_createUser($userdata['user_id'], $userdata['user_level'], array(
					'username' => $userdata['username'],
					'hashedpassword' => $userdata['user_password'],
					'hashmethod' => 'md5',
					'email' => $userdata['user_email'],
					'fullname' => $userdata['username'])
				);

				$this->done();

				$ret = GalleryEmbed::init($this->_init_array);
				if (isset($ret)) {
					$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_INITUSER_FAILED'], $userdata['username']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
				}
			}
			else {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $userdata['username']), __LINE__, __FILE__);
			}
		}
		else {
			$this->_init_array['activeUserId'] = 'guest';

			$ret = GalleryEmbed::init($this->_init_array);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_INITUSER_FAILED'], 'guest') . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
	}

	function done() {
		$ret = GalleryEmbed::done();
		if (isset($ret)) {
			global $lang;

			$this->errorHandler(GENERAL_ERROR, $lang['G2_TRANSACTION_FAILED'] . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}
	}

	function _createUser($id, $level, $newUserData) {
		global $db, $lang;

		$user_name = $newUserData['username'];
		$newUserData['fullname'] = $newUserData['username'] = $this->_utf8Translate($newUserData['username']);

		$ret = GalleryEmbed::createUser($id, $newUserData);
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_CREATEUSER_FAILED'], $id) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$g_sql = 'SELECT DISTINCT g.group_name FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug WHERE ug.user_id = $id AND ug.group_id = g.group_id AND g.group_name <> 'Anonymous' AND g.group_name <> 'Admin' AND g.group_name <> ''";
		if (!$g_result = $db->sql_query($g_sql)) {
			$this->errorHandler(CRITICAL_ERROR, $lang['FETCH_USERGROUPDATA_FAILED'], __LINE__, __FILE__, $g_sql);
		}

		while ($g_row = $db->sql_fetchrow($g_result)) {
			$groupName = $this->_utf8Translate($g_row['group_name']);

			$ret = GalleryEmbed::addUserToGroup($id, $groupName);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ADDUSERTOGROUP_FAILED'], $g_row['group_name'], $id) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}

		if ($level === ADMIN) {
			list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($newUserData['username']);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_FETCHUSERBYUSERNAME_FAILED'], $user_name) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $this->_adminGroupId);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ADDUSERTOGROUP_FAILED'], $this->_adminGroupId, $userId->getId()) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
	}

	function updateUser($id, $userName, $password, $email) {
		global $lang;

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			$userName = $this->_utf8Translate($userName);

			$ret = GalleryEmbed::updateUser($id, array(
				'username' => $userName,
				'email' => $email,
				'fullname' => $userName,
				'hashedpassword' => $password,
				'hashmethod' => 'md5')
			);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_UPDATEUSER_FAILED'], $id) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* User does not exist in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), __LINE__, __FILE__);
		}

		$this->done();
	}

	function deleteUser($id) {
		global $lang;

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			if ($this->_activeAdminId == $id) {
				list ($ret, $adminUsers) = GalleryCoreApi::fetchUsersForGroup($this->_adminGroupId, 2);
				if (isset($ret)) {
					$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_FETCHUSERSFORGROUP_FAILED'], $this->_adminGroupId) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
				}
				if (empty($adminUsers)) {
					$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_RETURNADMINS_FAILED'], $this->_adminGroupId), __LINE__, __FILE__);
				}

				$adminIds = array_keys($adminUsers);

				list ($ret, $entityIdMap) = GalleryEmbed::getExternalIdMap('entityId');
				if (empty($ret)) {
					$adminIds[0] = (isset($entityIdMap[$adminIds[0]])) ? $entityIdMap[$adminIds[0]]['externalId'] : $adminIds[0];
					$adminIds[1] = (isset($entityIdMap[$adminIds[1]])) ? $entityIdMap[$adminIds[1]]['externalId'] : $adminIds[1];
					if ($adminIds[0] == $id && count($adminIds) == 1) {
						$this->errorHandler(GENERAL_ERROR, $lang['G2_DELETEADMIN_FAILED'], __LINE__, __FILE__);
					}
					$validAdmin = ($adminIds[0] != $id) ? $adminIds[0] : $adminIds[1];
				}
				else {
					$this->errorHandler(GENERAL_ERROR, $lang['G2_GETEXTERNALIDMAP_FAILED'] . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$newActiveAdmin = $validAdmin;

				global $db;

				$sql = 'UPDATE ' . GALLERY2_TABLE . " SET activeAdminId = $newActiveAdmin";
				if (!$db->sql_query($sql)) {
					$this->errorHandler(CRITICAL_ERROR, $lang['UPDATE_ACTIVEADMINID_FAILED'], __LINE__, __FILE__, $sql);
				}

				$this->_activeAdminId = $newActiveAdmin;

				$this->done();

				$this->_initAdmin();
			}

			$ret = GalleryEmbed::deleteUser($id);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_DELETEUSER_FAILED'], $id) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* User does not exist in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), __LINE__, __FILE__);
		}

		$this->done();
	}

	function mapUserToAdmin($id) {
		global $db, $lang;

		$sql = 'SELECT username, user_password, user_email FROM ' . USERS_TABLE . " WHERE user_id = $id";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['OBTAIN_USERINFO_FAILED'], __LINE__, __FILE__, $sql);
		}

		$userName = $this->_utf8Translate($row['username']);

		$this->_initAdmin();
		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($userName);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_FETCHUSERBYUSERNAME_FAILED'], $row['username']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $this->_adminGroupId);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ADDUSERTOGROUP_FAILED'], $this->_adminGroupId, $userId->getId()) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$this->done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			$this->_createUser($id, ADMIN, array(
				'username' => $row['username'],
				'hashedpassword' => $row['user_password'],
				'hashmethod' => 'md5',
				'email' => $row['user_email'],
				'fullname' => $row['username'])
			);

			$this->done();
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), __LINE__, __FILE__);
		}
	}
	
	function mapAdminToUser($id) {
		global $db, $lang;

		$sql = 'SELECT username FROM ' . USERS_TABLE . " WHERE user_id = $id";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['OBTAIN_USERINFO_FAILED'], __LINE__, __FILE__, $sql);
		}

		$userName = $this->_utf8Translate($row['username']);

		$this->_initAdmin();

		list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($userName);
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_FETCHUSERBYUSERNAME_FAILED'], $row['username']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$ret = GalleryCoreApi::removeUserFromGroup($userId->getId(), $this->_adminGroupId);
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_REMOVEUSERFROMGROUP_FAILED'], $this->_adminGroupId, $userId->getId()) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$this->done();
	}
	
	function createGroup($groupName) {
		global $lang;

		$group_name = $groupName;
		$groupName = $this->_utf8Translate($groupName);

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if (empty($ret)) {
			$ret = GalleryEmbed::createGroup($groupName, $groupName);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_CREATEGROUP_FAILED'], $group_name) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret)) {
			/*
			* Group already exists in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $group_name), __LINE__, __FILE__);
		}

		$this->done();
	}

	function updateGroup($group_id, $newGroupName) {
		global $db, $lang, $gallery;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $group_id LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['FETCH_GROUPDATA_FAILED'], __LINE__, __FILE__, $sql);
		}

		$new_group_name = $newGroupName;
		$newGroupName = $this->_utf8Translate($newGroupName);
		$groupName = $this->_utf8Translate($row['group_name']);

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if (empty($ret)) {
			$ret = GalleryEmbed::updateGroup($groupName, array('groupname' => $newGroupName));
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_UPDATEGROUP_FAILED'], $new_group_name) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$ret = GalleryCoreApi::updateMapEntry('ExternalIdMap', array('externalId' => $groupName) , array('externalId' => $newGroupName));
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_UPDATEMAPENTRY_FAILED'], $row['group_name']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __LINE__, __FILE__);
		}

		$this->done();
	}

	function deleteGroup($groupId) {
		global $db, $lang;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . "	WHERE group_id = $groupId LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['FETCH_GROUPDATA_FAILED'], __LINE__, __FILE__, $sql);
		}

		$groupName = $this->_utf8Translate($row['group_name']);

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if (empty($ret)) {
			$ret = GalleryEmbed::deleteGroup($groupName);
			if (isset($ret)) {
				$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_DELETEGROUP_FAILED'], $row['group_name']) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __LINE__, __FILE__);
		}

		$this->done();
	}

	function addUserToGroup($members, $groupId) {
		global $db, $lang;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $groupId LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['FETCH_GROUPDATA_FAILED'], __LINE__, __FILE__, $sql);
		}

		$groupName = $this->_utf8Translate($row['group_name']);

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if (empty($ret)) {
			$id[] = explode(', ', $members);

			for ($i = 0; $i < count($id); $i++) {
				$ret = GalleryEmbed::isExternalIdMapped($id[$i][0], 'GalleryUser');
				if (empty($ret)) {
					$ret = GalleryEmbed::addUserToGroup($id[$i][0], $groupName);
					if (isset($ret)) {
						$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ADDUSERTOGROUP_FAILED'], $row['group_name'], $id[$i][0]) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
					}
				}
				elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
					/*
					* User does not exist in gallery, so do nothing!
					*/
				}
				else {
					$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id[$i][0]), __LINE__, __FILE__);
				}
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __LINE__, __FILE__);
		}

		$this->done();
	}

	function removeUserFromGroup($members, $groupId) {
		global $db, $lang;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $groupId LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, $lang['FETCH_GROUPDATA_FAILED'], __LINE__, __FILE__, $sql);
		}

		$groupName = $this->_utf8Translate($row['group_name']);

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if (empty($ret)) {
			$id[] = explode(', ', $members);

			for ($i = 0; $i < count($id); $i++) {
				$ret = GalleryEmbed::isExternalIdMapped($id[$i][0], 'GalleryUser');
				if (empty($ret)) {
					$ret = GalleryEmbed::removeUserFromGroup($id[$i][0], $groupName);
					if (isset($ret)) {
						$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_REMOVEUSERFROMGROUP_FAILED'], $groupId, $id[$i][0]) . $lang['G2_ERROR'] . $ret->getAsHtml(), __LINE__, __FILE__);
					}
				}
				elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
					/*
					* User does not exist in gallery, so do nothing!
					*/
				}
				else {
					$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id[$i][0]), __LINE__, __FILE__);
				}
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			$this->errorHandler(GENERAL_ERROR, sprintf($lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __LINE__, __FILE__);
		}

		$this->done();
	}

	function _utf8Translate($value) {
		return (defined('UTF8_TRANSLATE')) ? utf8_encode($value) : $value;
	}

	function utf8Untranslate($value) {
		return (defined('UTF8_TRANSLATE')) ? utf8_decode($value) : $value;
	}

	function errorHandler($code, $msg, $line, $file, $sql = '') {
		switch ($code) {
			case CRITICAL_ERROR :
				message_die(CRITICAL_ERROR, $this->utf8Untranslate($msg), '', $line, $file, $sql);
				break;

			case GENERAL_ERROR :
				message_die(GENERAL_ERROR, $this->utf8Untranslate($msg), '', $line, $file, $sql);
				break;
		}

		exit;
	}

}

?>