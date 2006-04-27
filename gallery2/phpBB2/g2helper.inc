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
 * @author Mike Rollins
 * @author Scott Gregory
 */

class g2helper {

	var $_fullPath;
	var $_embedUri;
	var $_g2Uri;
	var $_loginRedirect;
	var $_activeAdminId;
	var $_init_array;
	var $_adminGroupId;

	function g2helper($db) {
		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not obtain config settings from ' . GALLERY2_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$this->_fullPath = $row['fullPath'];
		$this->_embedUri = $row['embedUri'];
		$this->_g2Uri = $row['g2Uri'];
		$this->_loginRedirect = $row['loginRedirect'];
		$this->_activeAdminId = $row['activeAdminId'];
		$this->_init_array = array(
			'embedUri' => $this->_embedUri, 
			'g2Uri' => $this->_g2Uri, 
			'loginRedirect' => $this->_loginRedirect 
		);

		if ($this->_activeAdminId == 0) {
			$this->_errorHandler(GENERAL_ERROR, 'Active admin is not valid. Gallery 2 appears not to be integrated properly. Please run the user sychronization at least once.', __LINE__, __FILE__);
		}
		else {
			require_once($this->_fullPath);
		}
	}

	function _initAdmin() {
		$this->_init_array['activeUserId'] = $this->_activeAdminId;

		$ret = GalleryEmbed::init($this->_init_array);
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, 'initAdmin failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		list ($ret, $this->_adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, 'getPluginParameter id.adminGroup failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}
	}

	function init($userdata) {
		if ($userdata['user_id'] != ANONYMOUS) {
			$this->_initAdmin();

			$this->_init_array['activeUserId'] = $userdata['user_id'];

			$ret = GalleryEmbed::isExternalIdMapped($userdata['user_id'], 'GalleryUser');
			if (empty($ret)) {
				if (empty($userdata['user_gallery'])) {
					$this->_mapGalleryLink($userdata['user_id']);
				}

				$this->_done();

				$ret = GalleryEmbed::init($this->_init_array);
				if (isset($ret)) {
					$this->_errorHandler(GENERAL_ERROR, 'init failed for ' . $userdata['username'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
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

				$this->_done();

				$ret = GalleryEmbed::init($this->_init_array);
				if (isset($ret)) {
					$this->_errorHandler(GENERAL_ERROR, 'init failed for ' . $userdata['username'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
				}
			}
			else {
				$this->_errorHandler(GENERAL_ERROR, 'isExternalIdMapped failed for ' . $userdata['user_id'] . '.', __LINE__, __FILE__);
			}
		}
		else {
			$this->_init_array['activeUserId'] = 'guest';

			$ret = GalleryEmbed::init($this->_init_array);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'init failed for "guest". Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
	}

	function _done() {
		$ret = GalleryEmbed::done();
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, 'Failed to complete transaction. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}
	}

	function _mapGalleryLink($userId) {
		global $db, $gallery;

		$sql = 'SELECT user_gallery FROM ' . USERS_TABLE . " WHERE user_id = '$userId' LIMIT 1";
		if ($row = $db->sql_fetchrow($db->sql_query($sql))) {
			list ($ret, $entityId) = GalleryCoreApi::loadEntityByExternalId($userId, 'GalleryUser');
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, "loadEntityByExternalId failed for $userId. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			list ($ret, $itemIds) = GalleryCoreApi::fetchAllItemIdsByOwnerId($entityId->getId());
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'fetchAllItemIdsByOwnerId failed for ' . $entityId->getId() . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$query = "SELECT [Entity::id] FROM [Entity] WHERE [Entity::entityType] = ?";
			list ($ret, $results) = $gallery->search($query, array('GalleryAlbumItem'));
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'Unable to fetch entity information from the G2 entity table. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			while ($g2Result = $results->nextResult()) {
				if (in_array($g2Result[0], $itemIds)) {
					$sql = 'UPDATE ' . USERS_TABLE . ' SET user_gallery = ' . $g2Result[0] . " WHERE user_id = $userId";
					if (!$db->sql_query($sql)) {
						$this->_errorHandler(CRITICAL_ERROR, 'Could not update user_gallery in ' . USERS_TABLE . '.', __LINE__, __FILE__, $sql);
					}

					return;
				}
			}
		}
	}

	function _createUser($id, $level, $newUserData) {
		global $db;

		$ret = GalleryEmbed::createUser($id, $newUserData);
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, "createUser failed for $id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$g_sql = 'SELECT DISTINCT g.group_name FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug WHERE ug.user_id = $id AND ug.group_id = g.group_id AND g.group_name <> 'Anonymous' AND g.group_name <> 'Admin' AND g.group_name <> ''";
		if (!$g_result = $db->sql_query($g_sql)) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not obtain user/group information from ' . GROUPS_TABLE . ' and/or ' . USER_GROUP_TABLE . '.', __LINE__, __FILE__, $g_sql);
		}

		while ($g_row = $db->sql_fetchrow($g_result)) {
			$ret = GalleryEmbed::addUserToGroup($id, $g_row['group_name']);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, "addUserToGroup failed adding $id to " . $g_row['group_name'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}

		if ($level === ADMIN) {
			list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($newUserData['username']);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'fetchUserByName failed for ' . $newUserData['username'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $this->_adminGroupId);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'addUserToGroup ' . $this->_adminGroupId . ' failed for ' . $userId->getId() . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
	}

	function changePassword($id, $password) {
		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			$ret = GalleryEmbed::updateUser($id, array(
				'hashedpassword' => $password,
				'hashmethod' => 'md5')
			);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, "updateUser failed for $id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) { 
			//$this->_errorHandler(GENERAL_ERROR, "updateUser failed for $id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__); 
		} 
		else {
			$this->_errorHandler(GENERAL_ERROR, "isExternalIdMapped failed for $id.", __LINE__, __FILE__);
		}

		$this->_done();
	}

	function deleteUser($id) {
		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			if ($this->_activeAdminId == $id) {
				list ($ret, $adminUsers) = GalleryCoreApi::fetchUsersForGroup($this->_adminGroupId, 2);
				if (isset($ret)) {
					$this->_errorHandler(GENERAL_ERROR, 'fetchUsersForGroup ' . $this->_adminGroupId . ' failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
				}
				if (empty($adminUsers)) {
					$this->_errorHandler(GENERAL_ERROR, 'No adminUsers were returned from fetchUsersForGroup ' . $this->_adminGroupId . '.', __LINE__, __FILE__);
				}

				$adminIds = array_keys($adminUsers);

				list ($ret, $entityIdMap) = GalleryEmbed::getExternalIdMap('entityId');
				if (empty($ret)) {
					$adminIds[0] = (isset($entityIdMap[$adminIds[0]])) ? $entityIdMap[$adminIds[0]]['externalId'] : $adminIds[0];
					$adminIds[1] = (isset($entityIdMap[$adminIds[1]])) ? $entityIdMap[$adminIds[1]]['externalId'] : $adminIds[1];
					if ($adminIds[0] == $id && count($adminIds) == 1) {
						$this->_errorHandler(GENERAL_ERROR, 'The only G2 admin cannot be deleted.', __LINE__, __FILE__);
					}
					$validAdmin = ($adminIds[0] != $id) ? $adminIds[0] : $adminIds[1];
				}
				else {
					$this->_errorHandler(GENERAL_ERROR, 'getExternalIdMap failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$newActiveAdmin = $validAdmin;

				global $db;

				$sql = 'UPDATE ' . GALLERY2_TABLE . " SET activeAdminId = $newActiveAdmin";
				if (!$db->sql_query($sql)) {
					$this->_errorHandler(CRITICAL_ERROR, 'Could not update activeAdminId in ' . GALLERY2_TABLE . '.', __LINE__, __FILE__, $sql);
				}

				$this->_activeAdminId = $newActiveAdmin;

				$this->_done();

				$this->_initAdmin();
			}

			$ret = GalleryEmbed::deleteUser($id);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, "deleteUser failed for $id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) { 
			//$this->_errorHandler(GENERAL_ERROR, "deleteUser failed for $id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__); 
		}
		else {
			$this->_errorHandler(GENERAL_ERROR, "isExternalIdMapped failed for $id.", __LINE__, __FILE__);
		}

		$this->_done();
	}

	function mapUserToAdmin($id) {
		global $db;

		$sql = 'SELECT username, user_password, user_email FROM ' . USERS_TABLE . " WHERE user_id = $id";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not get user data from ' . USERS_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$this->_initAdmin();
		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($row['username']);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'fetchUserByName failed for ' . $row['username'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $this->_adminGroupId);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'addUserToGroup ' . $this->_adminGroupId . ' failed for ' . $userId->getId() . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}

			$this->_done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			$this->_createUser($id, ADMIN, array(
				'username' => $row['username'],
				'hashedpassword' => $row['user_password'],
				'hashmethod' => 'md5',
				'email' => $row['user_email'],
				'fullname' => $row['username'])
			);

			$this->_done();
		}
		else {
			$this->_errorHandler(GENERAL_ERROR, "isExternalIdMapped failed for $id.", __LINE__, __FILE__);
		}
	}
	
	function mapAdminToUser($id) {
		global $db;

		$sql = 'SELECT username FROM ' . USERS_TABLE . " WHERE user_id = $id";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not get user data from ' . USERS_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$this->_initAdmin();

		list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($row['username']);
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, 'fetchUserByName failed for ' . $row['username'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$ret = GalleryCoreApi::removeUserFromGroup($userId->getId(), $this->_adminGroupId);
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, 'addUserToGroup ' . $this->_adminGroupId . ' failed for ' . $userId->getId() . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$this->_done();
	}
	
	function createGroup($groupName) {
		$this->_initAdmin();

		$ret = GalleryEmbed::createGroup($groupName, $groupName);
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, "createGroup failed for $groupName. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$this->_done();
	}

	function updateGroup($group_id, $newGroupName) {
		global $db;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $group_id LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not get group data from ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$this->_initAdmin();

		$ret = GalleryEmbed::updateGroup($row['group_name'], array('groupname' => $newGroupName));
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, "updateGroup $newGroupName failed. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$this->_done();
	}

	function deleteGroup($groupId) {
		global $db;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . "	WHERE group_id = $groupId LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not get group data from ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$this->_initAdmin();

		$ret = GalleryEmbed::deleteGroup($row['group_name']);
		if (isset($ret)) {
			$this->_errorHandler(GENERAL_ERROR, 'deleteGroup failed for ' . $row['group_name'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$this->_done();
	}

	function addUserToGroup($members, $groupId) {
		global $db;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $groupId LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not get group data from ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$id[] = explode(', ', $members);

		$this->_initAdmin();

		for ($i = 0; $i < count($id); $i++) {
			$ret = GalleryEmbed::addUserToGroup($id[$i][0], $row['group_name']);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, 'addUserToGroup failed for ' . $row['group_name'] . ' : ' . $id[$i][0] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}

		$this->_done();
	}

	function removeUserFromGroup($members, $groupId) {
		global $db;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $groupId LIMIT 1";
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->_errorHandler(CRITICAL_ERROR, 'Could not get group data from ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$id[] = explode(', ', $members);

		$this->_initAdmin();

		for ($i = 0; $i < count($id); $i++) {
			$ret = GalleryEmbed::removeUserFromGroup($id[$i][0], $row['group_name']);
			if (isset($ret)) {
				$this->_errorHandler(GENERAL_ERROR, "removeUserFromGroup failed for $groupId : " . $id[$i][0] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
			}
		}

		$this->_done();
	}

	function _errorHandler($code, $msg, $line, $file, $sql = '') {
		switch ($code) {
			case CRITICAL_ERROR :
				message_die(CRITICAL_ERROR, $msg, '', $line, $file, $sql);
				break;

			case GENERAL_ERROR :
				message_die(GENERAL_ERROR, $msg, '', $line, $file, $sql);
				break;
		}

		exit;
	}

}

?>