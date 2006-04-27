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

$no_page_header = TRUE;
$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);
require('./g2helper_admin.inc');

$g2h_admin = new g2helper_admin($db);

$template->set_filenames(array(
	'body' => './admin/gallery2_export.tpl')
);

$template->assign_vars(array()
);

$template->pparse('body');

// grab list of phpBB groups for use later
$phpbbGroups = array();

$sql = 'SELECT DISTINCT group_name FROM ' . GROUPS_TABLE . " WHERE group_name <> 'Anonymous' AND group_name <> 'Admin' AND group_name <> ''";
if (!$result = $db->sql_query($sql)) {
	$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not get group data from ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
}

while($row = $db->sql_fetchrow($result)) {
	$phpbbGroups[] = $row['group_name'];
}

// grab G2 group parameters for use later
$g2h_admin->init();

list ($ret, $adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
if (isset($ret)) {
	$g2h_admin->errorHandler(GENERAL_ERROR, 'getPluginParameter id.adminGroup failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
}

list ($ret, $userGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.allUserGroup');
if (isset($ret)) {
	$g2h_admin->errorHandler(GENERAL_ERROR, 'getPluginParameter id.allUserGroup failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
}

list ($ret, $everybodyGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.everybodyGroup');
if (isset($ret)) {
	$g2h_admin->errorHandler(GENERAL_ERROR, 'getPluginParameter id.everybodyGroup failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
}

$g2h_admin->done();

// handle existing G2 users
if (count($_POST['user']) != 0)	{
	$g2h_admin->init();

	$user_password = md5(''); // we can't use the G2 password because of the salted md5 hash

	foreach ($_POST['user'] as $g2Id => $action) {
		switch ($action) {
			case '1': // export G2 user to phpbb
				if (empty($user_id) || empty($group_id)) {
					$sql = 'SELECT MAX(user_id) AS total FROM ' . USERS_TABLE;
					if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
						$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not obtain next user_id information from ' . USERS_TABLE . '.', __LINE__, __FILE__, $sql);
					}

					$user_id = $row['total'] + 1;

					$sql = 'SELECT MAX(group_id) AS total FROM ' . USER_GROUP_TABLE;
					if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
						$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not obtain next group_id information from ' . USER_GROUP_TABLE . '.', __LINE__, __FILE__, $sql);
					}

					$group_id = $row['total'] + 1;
				}
				else {
					$user_id++;
					$group_id++;
				}

				list ($ret, $groupsForUser) = GalleryCoreApi::fetchGroupsForUser($g2Id);
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, "fetchGroupsForUser failed for $g2Id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				if (in_array($adminGroupId, array_keys($groupsForUser))) {
					$user_level = ADMIN;
					$groupName = 'Admin';

					if (empty($admin_group_id)) {
						$sql = 'SELECT group_id FROM ' . GROUPS_TABLE . " WHERE group_name = '$groupName'";
						if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
							$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not obtain admin group_id information from ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
						}

						$admin_group_id = $row['group_id'];
					}

					$sql = 'INSERT INTO ' . USER_GROUP_TABLE . " (group_id, user_id, user_pending) VALUES ($admin_group_id, $user_id, 0)";
					if (!$db->sql_query($sql)) {
						$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not insert data into ' . USER_GROUP_TABLE . '.', __LINE__, __FILE__, $sql);
					}

					$groupsForUser = (count($groupsForUser) >= 3) ? ((count($groupsForUser) > 3) ? array_slice($groupsForUser, 3) : false) : false;
				}
				else {
					$user_level = USER;
					$groupName = '';

					$sql = 'INSERT INTO ' . GROUPS_TABLE . " (group_id, group_name, group_description) VALUES ($group_id, '$groupName', 'Personal User')";
					if (!$db->sql_query($sql)) {
						$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not insert data into ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
					}

					$sql = 'INSERT INTO ' . USER_GROUP_TABLE . " (group_id, user_id, user_pending) VALUES ($group_id, $user_id, 0)";
					if (!$db->sql_query($sql)) {
						$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not insert data into ' . USER_GROUP_TABLE . '.', __LINE__, __FILE__, $sql);
					}

					$groupsForUser = (count($groupsForUser) >= 2) ? ((count($groupsForUser) > 2) ? array_slice($groupsForUser, 2) : false) : false;
				}

				list ($ret, $entityId) = GalleryCoreApi::loadEntitiesById($g2Id);
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, "loadEntitiesById failed for $g2Id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$sql = 'INSERT INTO ' . USERS_TABLE . " (user_id, username, user_password, user_regdate, user_level, user_email) VALUES ($user_id, '" . $entityId->getuserName() . "', '$user_password', " . time() . ", '$user_level', '" . $entityId->getemail() . "')";
				if (!$db->sql_query($sql)) {
					$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not insert data into ' . USERS_TABLE . '.', __LINE__, __FILE__, $sql);
				}

				if (!empty($groupsForUser)) {
					foreach ($groupsForUser as $groupId => $groupName) {
						if (in_array($groupName, $phpbbGroups)) {
							$group_id++;
							$sql = 'INSERT INTO ' . GROUPS_TABLE . " (group_id, group_name, group_description) VALUES ($group_id, '$groupName', 'Personal User')";
							if (!$db->sql_query($sql)) {
								$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not insert data into ' . GROUPS_TABLE . '.', __LINE__, __FILE__, $sql);
							}

							$sql = 'INSERT INTO ' . USER_GROUP_TABLE . " (group_id, user_id, user_pending) VALUES ($group_id, $user_id, 0)";
							if (!$db->sql_query($sql)) {
								$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not insert data into ' . USER_GROUP_TABLE . '.', __LINE__, __FILE__, $sql);
							}
						}
					}
				}

				break;

			case ('2' || '3'): // delete user from G2
				list ($ret, $adminUsers) = GalleryCoreApi::fetchUsersForGroup($adminGroupId, 2);
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, "fetchUsersForGroup failed for $adminGroupId. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
				}
				if (empty($adminUsers)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, "No adminUsers were returned from fetchUsersForGroup $adminGroupId. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$adminUsers = array_keys($adminUsers);

				if ($adminUsers[0] == $g2Id && count($adminUsers) == 1) {
					$g2h_admin->errorHandler(GENERAL_ERROR, 'The only G2 admin cannot be deleted.', __LINE__, __FILE__);
				}

				$adminId = ($adminUsers[0] != $g2Id) ? $adminUsers[0] : $adminUsers[1];

				list ($ret, $entityId) = GalleryCoreApi::loadEntitiesById($adminId);
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, "loadEntitiesById failed for $adminId. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$gallery->setActiveUser($entityId);

				if (intval($action) == 2) { // delete items
					$ret = GalleryCoreApi::deleteUserItems($g2Id);
					if (isset($ret)) {
						$g2h_admin->errorHandler(GENERAL_ERROR, "deleteUserItems failed for $g2Id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
					}
				}
				else { // keep items
					$ret = GalleryCoreApi::remapOwnerId($g2Id, $user->getId());
					if (isset($ret)) {
						$g2h_admin->errorHandler(GENERAL_ERROR, "remapOwnerId $g2Id, $adminId failed. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
					}
				}

				$ret = GalleryCoreApi::deleteEntityById($g2Id);
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, "deleteEntityById failed for $g2Id. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				break;

			default: // do nothing!
		}
	}

	$g2h_admin->done();
}

// handle existing phpBB user groups
$groups_processed = $groups_existing = $groups_imported = 0;
	
if (count($phpbbGroups) > 0) {
	$g2h_admin->init();

	$group_flag = $failed = false;

	$groupG2List = $externalEntityIdMap = $group_failures = array();

	$query = 'SELECT [Group::id], [Group::groupName] FROM [Group]';
	list ($ret, $results) = $gallery->search($query, array());
	if (isset($ret)) {
		$g2h_admin->errorHandler(GENERAL_ERROR, 'Unable to fetch group information from the G2 group table. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
	}

	while($g2Result = $results->nextResult()) {	
		if ($g2Result[0] != $adminGroupId && $g2Result[0] != $userGroupId && $g2Result[0] != $everybodyGroupId) {
			$groupG2List[] = array('groupId' => $g2Result[0], 'groupName' => $g2Result[1]);
		}
	}

	list ($ret, $externalIdMap) = GalleryEmbed::getExternalIdMap('externalId');
	if (isset($ret)) {
		$g2h_admin->errorHandler(GENERAL_ERROR, 'getExternalIdMap "externalId" failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
	}

	foreach ($externalIdMap as $key => $entity) {
		if ($entity['entityType'] == 'GalleryGroup') {
			$externalEntityIdMap[] = array('entityId' => $entity['entityId'], 'externalId' => $entity['externalId']);
		}
	}

	foreach ($phpbbGroups as $groupName) {	
		for ($i = 0; $i < count($groupG2List); $i++) {
			if ($groupName == $groupG2List[$i]['groupName']) {
				for ($j = 0; $j < count($externalEntityIdMap); $j++) {
					if ($groupG2List[$i]['groupId'] == $externalEntityIdMap[$j]['entityId']) {
						$groups_existing++;
						$group_flag = true;
						break 2;
					}
				}
				$ret = GalleryEmbed::addExternalIdMapEntry($groupName, $groupG2List[$i]['groupId'], 'GalleryGroup');
				if (empty($ret)) {
					$groups_imported++;
					$group_flag = true;
					break;
				}
				else {
					$group_failures[] = $groupName;
					$failed = true;
					break;
				}
			}
		}
		if (empty($group_flag) && empty($failed)) {
			$ret = GalleryEmbed::createGroup($groupName, $groupName);
			if (empty($ret)) {
				$groups_imported++;
			}
			else {
				$group_failures[] = $groupName;
			}
		}

		$groups_processed++;
		$group_flag = $failed = false;
	}

	$g2h_admin->done();
}

// handle phpBB user import to G2
$sql = 'SELECT user_id, user_active, username, user_password, user_level, user_email, user_lang, user_regdate FROM ' . USERS_TABLE;

if ($_POST['export'] == 'later') {
	$sql .= ' WHERE user_level = ' . ADMIN . ' OR user_id = ' . ANONYMOUS;
}

if (!$result = $db->sql_query($sql)) {
	$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not obtain user information from ' . USERS_TABLE . '.', __LINE__, __FILE__, $sql);
}

$ucount = $db->sql_numrows($result);

$users_processed = $users_existing = $users_nonactive = $users_imported = $users_guest = $users_admin = 0;

$failures = $admins = array();

$failed = $guestIsSet = false;

$g2h_admin->init();

while($row = $db->sql_fetchrow($result)) {	
	$user_id = $row['user_id'];
	$args['fullname'] = $row['username'];
	$args['username'] = $row['username'];
	$args['hashedpassword'] = $row['user_password']; 
	$args['hashmethod'] = 'md5';
	$args['email'] = $row['user_email'];
	$args['creationtimestamp'] = $row['user_regdate'];

	if ($user_id != ANONYMOUS) {
		$ret = GalleryEmbed::isExternalIdMapped($user_id, 'GalleryUser');
		if (empty($ret)) {
			$users_existing++;
		}
		elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
			if ($row['user_active'] > 0) {
				list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($args['username']);
				if (empty($ret)) {
					$ret = GalleryEmbed::addExternalIdMapEntry($user_id, $userId->getId(), 'GalleryUser');
					if (isset($ret)) {
						$failures[] = $user_id . ' : ' . $args['username'];
						$failed = true;
					}
				}
				elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
					$ret = GalleryEmbed::createUser($user_id, $args);
					if (isset($ret)) {
						$failures[] = $user_id . ' : ' . $args['username'];
						$failed = true;
					}
				}
				else {
					$g2h_admin->errorHandler(GENERAL_ERROR, 'fetchUserByUserName failed for ' . $args['username'] . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				if (empty($failed)) {
					$g_sql = 'SELECT DISTINCT g.group_name FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug WHERE ug.user_id = $user_id AND ug.user_pending = 0 AND ug.group_id = g.group_id AND g.group_name <> 'Anonymous' AND g.group_name <> 'Admin' AND g.group_name <> ''";
					if (!$g_result = $db->sql_query($g_sql)) {
						$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not obtain user/group information from ' . GROUPS_TABLE . ' and/or ' . USER_GROUP_TABLE . '.', __LINE__, __FILE__, $g_sql);
					}

					while ($g_row = $db->sql_fetchrow($g_result)) {
						$ret = GalleryEmbed::addUserToGroup($user_id, $g_row['group_name']);
						if (isset($ret)) {
							$failures[] = "failed adding $user_id to " . $g_row['group_name'];
						}
					}

					if (intval($row['user_level']) === ADMIN) {
						$admins[$user_id] = $args['username'];
					}
				}

				$users_imported++;
				$failed = false;
			}
			else {
				$users_nonactive++;
			}
		}
		else {
			$g2h_admin->errorHandler(GENERAL_ERROR, "isExternalIdMapped failed for $user_id.", __LINE__, __FILE__);
		}
	}
	else {
		if (empty($guestIsSet)) {
			$ret = GalleryEmbed::isExternalIdMapped('guest', 'GalleryUser');
			if (empty($ret)) {
				$users_existing++;
			}
			elseif (isset($ret) && $ret->getErrorCode() & ERROR_MISSING_OBJECT) {
				list ($ret, $guestUserId) = GalleryCoreApi::fetchUserByUserName('guest');
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, 'fetchUserByUserName failed for "guest". Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$ret = GalleryEmbed::addExternalIdMapEntry('guest', $guestUserId->getId(), 'GalleryUser');
				if (isset($ret)) {
					$g2h_admin->errorHandler(GENERAL_ERROR, 'addExternalMapEntry failed for "guest". Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
				}

				$users_guest++;
				$users_imported++;
			}
			else {
				$g2h_admin->errorHandler(GENERAL_ERROR, 'isExternalIdMapped failed for "guest".', __LINE__, __FILE__);
			}

			$guestIsSet = true;
		}
	}

	$users_processed++;

	$percentInDecimal = $users_processed / $ucount;
	if ($users_processed % 100 == 0)	{
		print "<script type=\"text/javascript\">updateProgressBar(\"$users_processed users processed\", $percentInDecimal);</script>\n";
		flush();
	}
}

$g2h_admin->done();

// handle any admins gathered
if (count($admins) > 0) {
	$g2h_admin->init();
	$adminIsSet = false;
	$users_admin = 0;

	if (!GalleryCoreApi::isUserInSiteAdminGroup()) {
		list ($ret, $adminUser) = GalleryCoreApi::fetchUsersForGroup($adminGroupId, 1);
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, "fetchUsersForGroup $adminGroupId failed. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		if (empty($adminUser)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, "No adminUsers were returned from fetchUsersForGroup $adminGroupId.", __LINE__, __FILE__);
		}
		$validAdmin = array_keys($adminUser[0]);

		list ($ret, $entityId) = GalleryCoreApi::loadEntitiesById($validAdmin);
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, "loadEntitiesById failed for $validAdmin. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$gallery->setActiveUser($entityId);
	}

	foreach ($admins as $user_id => $user_name) {
		list ($ret, $userId) = GalleryCoreApi::fetchUserByUserName($user_name);
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, "fetchUserByUserName failed for $user_name. Here is the error message from G2: <br />" . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$ret = GalleryCoreApi::addUserToGroup($userId->getId(), $adminGroupId);
		if (isset($ret)) {
			$g2h_admin->errorHandler(GENERAL_ERROR, "addUserToGroup $adminGroupId failed for " . $userId->getId() . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		if (empty($adminIsSet)) {
			$sql = 'UPDATE ' . GALLERY2_TABLE . " SET activeAdminId = $user_id";
			if (!$db->sql_query($sql)) {
				$g2h_admin->errorHandler(CRITICAL_ERROR, 'Could not update activeAdminId in ' . GALLERY2_TABLE . '.', __LINE__, __FILE__, $sql);
			}

			$adminIsSet = true;
		}

		$users_admin++;
	}

	$g2h_admin->done();
}

$percentInDecimal = ($users_processed / $ucount) * 100;
print "<script type=\"text/javascript\"> updateProgressBar(\"Export Complete\", $percentInDecimal);</script>\n";
flush();

echo "<p>$groups_processed group(s) processed for import to Gallery 2 from phpBB2.</p>\n";

if ($groups_existing > 0) {
	echo "<p>$groups_existing group(s) already mapped to Gallery 2.</p>\n";
}

echo "<p>$groups_imported group(s) succeeded import to Gallery 2 from phpBB2.</p>\n";

if (count($group_failures) > 0) {
	echo '<p>' . count($group_failures) . " group(s) failed mapping to Gallery 2.</p>\n";
	echo "<p>The import of the following phpBB2 groups failed:<br />\n";
	foreach ($group_failures as $failed) {
		echo "$failed<br />\n";
	}
}

echo "<p>$users_processed user(s) processed for import to Gallery 2 from phpBB2.</p>\n";

if ($users_existing > 0) {
	echo "<p>$users_existing user(s) already mapped to Gallery 2.</p>\n";
}

if ($users_nonactive > 0) {
	echo "<p>$users_nonactive nonactive user(s) not mapped to Gallery 2.</p>\n";
}

if ($users_guest > 0) {
	echo "<p>$users_guest guest user mapped to Gallery 2.</p>\n";
}

if ($users_admin > 0) {
	echo "<p>$users_admin admin user(s) mapped to Gallery 2.</p>\n";
}

echo "<p>$users_imported user(s) succeeded import to Gallery 2 from phpBB2.</p>\n";

if (count($failures) > 0) {
	echo '<p>' . count($failures) . " user(s) failed mapping to Gallery 2.</p>\n";
	echo "<p>The import of the following phpBB2 users failed:<br />\n";
	foreach ($failures as $failed) {
		echo "$failed<br />\n";
	}
	echo "</p><p>The most common reasons for failed imports are:\n"
	. "<ul><li>Duplicate phpBB usernames</li>\n"
	. "<li>A phpBB username of \"guest\"</li>\n"
	. "<li>A phpBB username consisting of only numbers</li></ul>\n"
	. "Check the failed user_ids and re-run the export.</p>\n";
}

include('./page_footer_admin.' . $phpEx);

?>
