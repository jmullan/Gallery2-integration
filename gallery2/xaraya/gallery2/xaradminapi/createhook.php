<?php
/**
 * File: $Id$
 * 
 * Xaraya gallery2 wrapper
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 *
 * @subpackage gallery2 Module
 * @author Andy Staudacher aka valiant
*/

// Load the xarGallery2Helper class
include_once(dirname(__FILE__) .'/../xargallery2helper.php');

/**
 * Hook function for roles create item hook calls
 *      (create roles (user, group), addmember)
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return extrainfo the updated extrainfo array
 */
function gallery2_adminapi_createhook($args)
{	
  // switch addmember / addrole + createrole hook calls
  if (isset($args['extrainfo']['uid'])) {
    // addmember hook call
    return _gallery2_adminapi_addmemberhook($args);
  } else {
    // create/add role hook call
    return _gallery2_adminapi_createrolehook($args);
  }
}

/**
 * Function for roles create role hook calls.
 * Creates the role (User/Group) in G2
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return extrainfo the updated extrainfo array
 */
function _gallery2_adminapi_createrolehook($args)
{
  // first check if the module has been configured
  if(!xarGallery2Helper::isConfigured()) {
    return $args['extrainfo'];
  }
  
  extract($args['extrainfo']);
  
  // we only accept roles module hook calls
  if (!isset($module) || $module != 'roles') {
    return $args['extrainfo'];
  }
  
  // we need itemid 
  if (!isset($itemid)) {
    $msg = xarML('createrolehook call without itemid!');
    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
    return $args['extrainfo'];
  }
  
  // roles admin create (user) doesn't provide an itemtype
  if (!isset($itemtype)) {
    $itemtype = 0;
  }
  
  // Load the role data
  $role = xarModAPIFunc('roles','user','get', array('uid' => $itemid, 'type' => $itemtype));
  if (empty($role['uid']) || $role['uid'] != $itemid) {
    $msg = xarML('Failed to get role [#(1)] for G2 synchronization!', $itemid);
    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
    return $args['extrainfo'];
  }
  
  // Start G2 transaction
  if(!xarGallery2Helper::init()) {
    return $args['extrainfo'];
  }
  
  // Switch between group and user creation
  if ($role['type'] == 1) {
    if (!xarGallery2Helper::g2createGroup($itemid, $role)) {
      return $args['extrainfo'];
    }
  } else { // it's a user
    if (!xarGallery2Helper::g2createUser($itemid, $role)) {
      return $args['extrainfo'];
    }
  } 
  
  // end G2 transaction
  xarGallery2Helper::done();
  return $args['extrainfo'];
}



/**
 * Function for roles addmember hook call
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return extrainfo the updated extrainfo array
 */
function _gallery2_adminapi_addmemberhook($args)
{
  // first check if the module has been configured
  if(!xarGallery2Helper::isConfigured()) {
    return $args['extrainfo'];
  }
  
  extract($args['extrainfo']);
  
  // we only accept roles module hook calls
  if (!isset($module) || $module != 'roles') {
    return $args['extrainfo'];
  }
  
  // we need both, the parent id (itemid) and the child id (uid)
  if (!isset($itemid) || !isset($uid)) {
    $msg = xarML('addmember hook call without group/user ids!');
    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
    return $args['extrainfo'];
  }
  
  // If child is a group, get all child users and add them to the parent groups
  // If child is a user, add it to all parent groups
  
  // the parent groups:
  // Load the parent role
  list($parentRole, $xarParentGroups) = xarGallery2Helper::xarGetAncestors(array('uid' => $itemid)); // an ancestor is a group per se
  $xarParentGroups[] = $parentRole;
  
  // this is ridiculous, the role get function defaults to type =1 if none was specified
  $childRole = xarModAPIFunc('roles','user','get', array('uid' => $uid));
  if (!isset($childRole['type']) || $childRole['uid'] != $uid) {
    $childRole = xarModAPIFunc('roles','user','get', array('uid' => $uid, 'type' => 1));
  }
  
  $xarChildUsers = array();
  if ($childRole['type'] == 1) { // it's a group
    $xarChildUsers = xarGallery2Helper::xarGetChildUsers($uid); // returns only users, no groups
  } else {
    $xarChildUsers[] = $childRole;
  }
  
  // Start G2 transaction
  if(!xarGallery2Helper::init()) {
    return $args['extrainfo'];
  }
  
  // Now add all child Roles to all Parent Roles
  // GalleryEmbed::addUserToGroup takes care that we don't add a user to a group if it's already a member
  foreach ($xarChildUsers as $child) {
    foreach ($xarParentGroups as $parent) {
      // make sure the group already exists in G2, if not, add it (there are some
      // rare scenarios where the group doesn't exist in G2. i.e. if the group was 
      // in deleted state in xaraya while we imported/exported the groups and now
      // this group is back, recalled. 
      // other CMS shouldn't care i guess, just a strange and rare issue
      list($ret, $g2Group) = GalleryCoreApi::loadEntityByExternalId($parent['uid'], 'GalleryGroup');
      if ($ret->isError()) {
	if ($ret->getErrorCode() & ERROR_MISSING_OBJECT) { 
	  // ok, we need to create this group first
	  if (!xarGallery2Helper::g2createGroup($parent['uid'], $parent)) {
	    return $args['extrainfo'];
	  }
	} else { // a real error, damn
	  $msg = xarML('Failed to fetch group extId [#(1)], name [#(2)] for addusertogroup synchronization! Here is
						the error message from G2: <br />[#(3)]', $parent['uid'], $parent['name'], $uid,$ret->getAsHtml());
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return $args['extrainfo'];
	}
      }
      
      // add user to group
      $ret = GalleryEmbed::addUserToGroup($child['uid'], $parent['uid']);
      if ($ret->isError()) {
	$msg = xarML('Failed to add g2 user to g2 group!');
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return $args['extrainfo'];
      }
    }	
  }
  
  // complete G2 transaction
  xarGallery2Helper::done();
  
  return $args['extrainfo'];
}


?>
