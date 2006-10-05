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

class g2helper {
	var $_compatibleEmbedVersionMajor = 1;
	var $_compatibleEmbedVersionMinor = 1;

	var $_activeAdminId;

	var $_init_array;

	var $_adminGroupId;

	function g2helper($db) {
		global $user;

		$user->add_lang('acp/gallery2');

		$sql = 'SELECT fullPath, embedUri, g2Uri, activeAdminId FROM ' . GALLERY2_TABLE;
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['OBTAIN_SETTINGS_FAILED'], __FILE__, __LINE__);
		}

		$this->_activeAdminId = $row['activeAdminId'];

		$this->_init_array = array(
			'embedUri' => $row['embedUri'], 
			'g2Uri' => $row['g2Uri'], 
			'apiVersion' => array($this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor)
		);

		if ($this->_activeAdminId == 0) {
			msg_handler(E_G2_ERROR, $user->lang['G2_AUTHADMIN_FAILED'], __FILE__, __LINE__);
		}
		else {
			require_once($row['fullPath']);
		}
	}

	function _initAdmin() {
		global $user;

		$this->_init_array['activeUserId'] = $this->_activeAdminId;

		$ret = GalleryEmbed::init($this->_init_array);
		if (isset($ret)) {
			msg_handler(E_G2_ERROR, $user->lang['G2_INITADMIN_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}

		list ($ret, $this->_adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
		if (isset($ret)) {
			msg_handler(E_G2_ERROR, $user->lang['G2_ADMINPARAMETER_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}
	}

	function init($user) {
		if ($user->data['username'] != 'Anonymous') {
			$this->_initAdmin();

			$this->_init_array['activeUserId'] = $user->data['user_id'];

			$ret = GalleryEmbed::isExternalIdMapped($user->data['user_id'], 'GalleryUser');
			if (empty($ret)) {
				$this->_mapGalleryLink($user->data['user_id'], $user->data['user_gallery']);

				$this->done();

				$ret = GalleryEmbed::init($this->_init_array);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_INITUSER_FAILED'], $user->data['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}
			}
			elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
				$this->_createUser($user->data['user_id'], $user->data['group_id'], array(
					'username' => $user->data['username'],
					'hashedpassword' => $user->data['user_password'],
					'email' => $user->data['user_email'],
					'fullname' => $user->data['username'],
					'language' => $user->data['user_lang'],
					'creationtimestamp' => time(),
					'hashmethod' => 'md5')
				);

				$this->done();

				$ret = GalleryEmbed::init($this->_init_array);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_INITUSER_FAILED'], $user->data['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}
			}
			else {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $user->data['username']), __FILE__, __LINE__);
			}
		}
		else {
			$this->_init_array['activeUserId'] = 'guest';

			$ret = GalleryEmbed::init($this->_init_array);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_INITUSER_FAILED'], 'guest') . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}
	}

	function done() {
		$ret = GalleryEmbed::done();
		if (isset($ret)) {
			global $user;

			msg_handler(E_G2_ERROR, $user->lang['G2_TRANSACTION_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}
	}

	function _mapGalleryLink($userId, $userGallery) {
		global $db, $gallery, $user;

		if (isset($userGallery)) {
			list ($ret, $entityId) = GalleryCoreApi::loadEntityByExternalId($userId, 'GalleryUser');
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_LOADENTITYBYEXTID_FAILED'], $userId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			list ($ret, $itemIds) = GalleryCoreApi::fetchAllItemIdsByOwnerId($entityId->getId());
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHITEMSBYOWNER_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			if (in_array($userGallery, $itemIds)) {
				return;
			}
		}

		if (empty($entityId)) {
			list ($ret, $entityId) = GalleryCoreApi::loadEntityByExternalId($userId, 'GalleryUser');
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_LOADENTITYBYEXTID_FAILED'], $userId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}

		if (empty($itemIds)) {
			list ($ret, $itemIds) = GalleryCoreApi::fetchAllItemIdsByOwnerId($entityId->getId());
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHITEMSBYOWNER_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}

		$query = "SELECT [Entity::id] FROM [Entity] WHERE [Entity::entityType] = ?";
		list ($ret, $results) = $gallery->search($query, array('GalleryAlbumItem'));
		if (isset($ret)) {
			msg_handler(E_G2_ERROR, $user->lang['G2_FETCHENTITYID_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}

		while ($g2Result = $results->nextResult()) {
			if (in_array($g2Result[0], $itemIds)) {
				$sql = 'UPDATE ' . USERS_TABLE . ' SET user_gallery = ' . $g2Result[0] . " WHERE user_id = $userId";
				if (!$db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['UPDATE_GALLERYUSER_FAILED'], __FILE__, __LINE__);
				}

				return;
			}
		}

		$sql = 'UPDATE ' . USERS_TABLE . " SET user_gallery = NULL WHERE user_id = $userId";
		if (!$db->sql_query($sql)) {
			msg_handler(E_G2_ERROR, $user->lang['UPDATE_GALLERYUSER_FAILED'], __FILE__, __LINE__);
		}
	}

	function mapAllGalleryLinks($userId, $albums, $limit) {
		$this->_initAdmin();

		global $gallery, $user;

		$ret = GalleryEmbed::isExternalIdMapped($userId, 'GalleryUser');
		if (empty($ret)) {
			list ($ret, $entityId) = GalleryCoreApi::loadEntityByExternalId($userId, 'GalleryUser');
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_LOADENTITYBYEXTID_FAILED'], $userId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			list ($ret, $itemIds) = GalleryCoreApi::fetchAllItemIdsByOwnerId($entityId->getId());
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHITEMSBYOWNER_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			list ($ret, $descendentCounts) = GalleryCoreApi::fetchDescendentCounts($itemIds, $entityId->getId());
			if ((count($descendentCounts) < 1) || (isset($ret) && $ret->getErrorCode() & ERROR_STORAGE_FAILURE)) {
				/*
				* User has no items to display!
				*/
				$this->done();
				return NULL;
			} 	
			elseif (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHDESCENDCOUNTS_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$itemIds = (empty($albums)) ? array_diff($itemIds, array_keys($descendentCounts)) : array_keys($descendentCounts);

			$count = count($itemIds);

			$itemIds = ($count > $limit) ? array_slice($itemIds, -$limit, $limit) : $itemIds;

			list ($ret, $thumbNails) = GalleryCoreApi::fetchThumbnailsByItemIds($itemIds);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHTHUMBSBYIDS_FAILED'], $entityId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$itemLinks = array();

			$urlGenerator =& $gallery->getUrlGenerator();

			foreach ($itemIds as $id) {
				$itemLinks[$id] = $urlGenerator->generateUrl(array('view' => 'core.DownloadItem', 'itemId' => $thumbNails[$id]->getId()), array('fullUrl' => true));
			}

			$this->done();

			return array($count, $itemLinks);
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* User does not exist in gallery, so do nothing!
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $userId), __FILE__, __LINE__);
		}

		$this->done();

		return NULL;
	}

	function _createUser($id, $groupId, $newUserData) {
		global $db, $user;

		$ret = GalleryEmbed::createUser($id, $newUserData);
		if (isset($ret)) {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_CREATEUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
		}

		$sql = 'SELECT group_id, FROM ' . GROUPS_TABLE . " WHERE group_name = 'ADMINISTRATORS'";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['FETCH_GROUPDATA_FAILED'], __FILE__, __LINE__);
		}

		$administratorsGroupId = $row['group_id'];

		$phpbbDefaultGroups = array('"GUESTS"', '"INACTIVE"', '"INACTIVE_COPPA"', '"REGISTERED"', '"REGISTERED_COPPA"', '"SUPER_MODERATORS"', '"ADMINISTRATORS"', '"BOTS"');

		$g_sql = 'SELECT DISTINCT g.group_name FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug WHERE ug.user_id = $id AND ug.group_id = g.group_id AND g.group_name NOT IN (" . implode(', ', $phpbbDefaultGroups) . ')';
		if (!$g_result = $db->sql_query($g_sql)) {
			msg_handler(E_G2_ERROR, $user->lang['FETCH_USERGROUPDATA_FAILED'], __FILE__, __LINE__);
		}

		while ($g_row = $db->sql_fetchrow($g_result)) {
			$ret = GalleryEmbed::addUserToGroup($id, $g_row['group_name']);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $g_row['group_name'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}

		if ($groupId === $administratorsGroupId) {
			list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($newUserData['username']);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $newUserData['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $this->_adminGroupId);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $this->_adminGroupId, $userId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}
	}

	function updateUser($id, $userData) {
		global $user;

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			$ret = GalleryEmbed::updateUser($id, array(
				'username' => (isset($userData['username'])) ? $userData['username'] : null,
				'hashedpassword' => (isset($userData['user_password'])) ? $userData['user_password'] : null,
				'hashmethod' => (isset($userData['user_password'])) ? 'md5' : null,
				'email' => (isset($userData['user_email'])) ? $userData['user_email'] : null)
			);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_UPDATEUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* user doesn't exist in gallery, do nothing
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), __FILE__, __LINE__);
		}

		$this->done();
	}

	function deleteUser($id) {
		global $user;

		$this->_initAdmin();

		$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
		if (empty($ret)) {
			if ($this->_activeAdminId == $id) {
				list ($ret, $adminUsers) = GalleryCoreApi::fetchUsersForGroup($this->_adminGroupId, 2);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERSFORGROUP_FAILED'], $this->_adminGroupId) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}
				if (empty($adminUsers)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_RETURNADMINS_FAILED'], $this->_adminGroupId), __FILE__, __LINE__);
				}

				$adminIds = array_keys($adminUsers);

				list ($ret, $entityIdMap) = GalleryEmbed::getExternalIdMap('entityId');
				if (empty($ret)) {
					$adminIds[0] = (isset($entityIdMap[$adminIds[0]])) ? $entityIdMap[$adminIds[0]]['externalId'] : $adminIds[0];
					$adminIds[1] = (isset($entityIdMap[$adminIds[1]])) ? $entityIdMap[$adminIds[1]]['externalId'] : $adminIds[1];
					if ($adminIds[0] == $id && count($adminIds) == 1) {
						msg_handler(E_G2_ERROR, $user->lang['G2_DELETEADMIN_FAILED'], __FILE__, __LINE__);
					}
					$validAdmin = ($adminIds[0] != $id) ? $adminIds[0] : $adminIds[1];
				}
				else {
					msg_handler(E_G2_ERROR, $user->lang['G2_GETEXTERNALIDMAP_FAILED'] . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				$newActiveAdmin = $validAdmin;

				global $db;

				$sql = 'UPDATE ' . GALLERY2_TABLE . " SET activeAdminId = $newActiveAdmin";
				if (!$db->sql_query($sql)) {
					msg_handler(E_G2_ERROR, $user->lang['UPDATE_ACTIVEADMINID_FAILED'], __FILE__, __LINE__);
				}

				$this->_activeAdminId = $newActiveAdmin;

				$this->done();

				$this->_initAdmin();
			}

			$ret = GalleryEmbed::deleteUser($id);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_DELETEUSER_FAILED'], $id) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* user doesn't exist in gallery, do nothing
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), __FILE__, __LINE__);
		}

		$this->done();
	}

	function mapUserToAdmin($ids) {
		global $db, $user;

		$this->_initAdmin();

		foreach ($ids as $id) {
			$sql = 'SELECT group_id, user_regdate, username, user_password, user_email, user_lang FROM ' . USERS_TABLE . " WHERE user_id = $id";
			if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
				msg_handler(E_G2_ERROR, $user->lang['OBTAIN_USERINFO_FAILED'], __FILE__, __LINE__);
			}

			$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
			if (empty($ret)) {
				list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($row['username']);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $row['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $this->_adminGroupId);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $this->_adminGroupId, $userId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}
			}
			elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
				$this->_createUser($id, $row['group_id'], array(
					'username' => $row['username'],
					'hashedpassword' => $row['user_password'],
					'hashmethod' => 'md5',
					'email' => $row['user_email'],
					'creationtimestamp' => $row['user_regdate'],
					'language' => $row['user_lang'],
					'fullname' => $row['username'])
				);
			}
			else {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id), __FILE__, __LINE__);
			}
		}

		$this->done();
	}
	
	function mapAdminToUser($ids) {
		global $db, $user;

		$this->_initAdmin();

		foreach ($ids as $id) {
			$sql = 'SELECT username FROM ' . USERS_TABLE . " WHERE user_id = $id";
			if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
				msg_handler(E_G2_ERROR, $user->lang['OBTAIN_USERINFO_FAILED'], __FILE__, __LINE__);
			}

			$ret = GalleryEmbed::isExternalIdMapped($id, 'GalleryUser');
			if (empty($ret)) {
				list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($row['username']);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_FETCHUSERBYUSERNAME_FAILED'], $row['username']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}

				$ret = GalleryCoreApi::removeUserFromGroup($userId->getId(), $this->_adminGroupId);
				if (isset($ret)) {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_REMOVEUSERFROMGROUP_FAILED'], $this->_adminGroupId, $userId->getId()) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
				}
			}
			elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
				/*
				* user doesn't exist in gallery, do nothing
				*/
			}
		}

		$this->done();
	}

	function createGroup($groupName) {
		global $user;

		$ret = GalleryEmbed::isExternalIdMapped($groupName, 'GalleryGroup');
		if (empty($ret)) {
			$this->_initAdmin();

			$ret = GalleryEmbed::createGroup($groupName, $groupName);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_CREATEGROUP_FAILED'], $groupName) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$this->done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group already exists in gallery, so do nothing!
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $groupName), __FILE__, __LINE__);
		}
	}

	function updateGroup($group_id, $newGroupName) {
		global $db, $user;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $group_id";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['FETCH_GROUPDATA_FAILED'], __FILE__, __LINE__);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret)) {
			$this->_initAdmin();

			$ret = GalleryEmbed::updateGroup($row['group_name'], array('groupname' => $newGroupName));
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_UPDATEGROUP_FAILED'], $newGroupName) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$ret = GalleryCoreApi::updateMapEntry('ExternalIdMap', array('externalId' => $row['group_name']) , array('externalId' => $newGroupName));
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_UPDATEMAPENTRY_FAILED'], $row['group_name']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$this->done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __FILE__, __LINE__);
		}
	}

	function deleteGroup($groupId) {
		global $db, $user;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . "	WHERE group_id = $groupId";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['FETCH_GROUPDATA_FAILED'], __FILE__, __LINE__);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret)) {
			$this->_initAdmin();

			$ret = GalleryEmbed::deleteGroup($row['group_name']);
			if (isset($ret)) {
				msg_handler(E_G2_ERROR, sprintf($user->lang['G2_DELETEGROUP_FAILED'], $row['group_name']) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
			}

			$this->done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __FILE__, __LINE__);
		}
	}

	function addUserToGroup($members, $groupId) {
		global $db, $user;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $groupId";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['FETCH_GROUPDATA_FAILED'], __FILE__, __LINE__);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret)) {
			$id[] = explode(', ', $members);

			$this->_initAdmin();

			for ($i = 0; $i < count($id); $i++) {
				$ret = GalleryEmbed::isExternalIdMapped($id[$i][0], 'GalleryUser');
				if (empty($ret)) {
					$ret = GalleryEmbed::addUserToGroup($id[$i][0], $row['group_name']);
					if (isset($ret)) {
						msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ADDUSERTOGROUP_FAILED'], $row['group_name'], $id[$i][0]) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
					}
				}
				elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
					/*
					* User does not exist in gallery, so do nothing!
					*/
				}
				else {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id[$i][0]), __FILE__, __LINE__);
				}
			}

			$this->done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __FILE__, __LINE__);
		}
	}

	function removeUserFromGroup($members, $groupId) {
		global $db, $user;

		$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . " WHERE group_id = $groupId";
		if (!$row = $db->sql_fetchrow($db->sql_query_limit($sql, 1))) {
			msg_handler(E_G2_ERROR, $user->lang['FETCH_GROUPDATA_FAILED'], __FILE__, __LINE__);
		}

		$ret = GalleryEmbed::isExternalIdMapped($row['group_name'], 'GalleryGroup');
		if (empty($ret)) {
			$id[] = explode(', ', $members);

			$this->_initAdmin();

			for ($i = 0; $i < count($id); $i++) {
				$ret = GalleryEmbed::isExternalIdMapped($id[$i][0], 'GalleryUser');
				if (empty($ret)) {
					$ret = GalleryEmbed::removeUserFromGroup($id[$i][0], $row['group_name']);
					if (isset($ret)) {
						msg_handler(E_G2_ERROR, sprintf($user->lang['G2_REMOVEUSERFROMGROUP_FAILED'], $groupId, $id[$i][0]) . $user->lang['G2_ERROR'] . $ret->getAsHtml(), __FILE__, __LINE__);
					}
				}
				elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
					/*
					* User does not exist in gallery, so do nothing!
					*/
				}
				else {
					msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $id[$i][0]), __FILE__, __LINE__);
				}
			}

			$this->done();
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			/*
			* Group does not exist in gallery, so do nothing!
			*/
		}
		else {
			msg_handler(E_G2_ERROR, sprintf($user->lang['G2_ISEXTERNALIDMAPPED_FAILED'], $row['group_name']), __FILE__, __LINE__);
		}
	}

}

?>