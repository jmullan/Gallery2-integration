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
 * @author Andy Staudacher / valiant
*/

// Load the xarGallery2Helper class
include_once(dirname(__FILE__) .'/../xargallery2helper.php');

/**
 * Hook function for roles update item hook calls
 * Update G2 group / user
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return extrainfo the updated extrainfo array
 */
function gallery2_adminapi_updatehook($args)
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
  
  // FIXME: as soon as we get a itemtype, use it
  // we need the itemid 
  if (!isset($itemid)) {
    $msg = xarML('update role hook call without itemid/itemtype!');
    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
    return $args['extrainfo'];
  }
  
  // Start G2 transaction
  if(!xarGallery2Helper::init()) {
    return $args['extrainfo'];
  }
  
  // load new state
  for ($itemtype = 0; $itemtype <= 1; $itemtype++) {
    $role = xarModAPIFunc('roles','user','get', array('uid' => $itemid, 'type' => $itemtype));
    if (isset($role['uid']) && $role['uid'] == $itemid) {
      break;
    } elseif ($itemtype == 1) { // ok, didn't find the role, very bad.
      $msg = xarML('update role hook from gallery2 module failed, it could not find the role in the xaraya
			database. Please report this error to the developers. We are sorry for this behaviour, your G2 and
			xaraya databases are now out of sync (at least for the role you tried to update).');
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return $args['extrainfo'];
    }
  }
  
  // load old state
  list($ret, $entityType) = xarGallery2Helper::g2getentitytypebyexternalid($itemid);
  if (!$ret) {
    return $args['extrainfo'];
  }
  list($ret, $g2role) = xarGallery2Helper::g2loadEntityByExternalId($itemid, $entityType);
  if (!$ret) {
    return $args['extrainfo'];
  }
  
  $oldtype = $entityType == 'GalleryUser' ? 0 : 1;
  
  /*
   * possible updates:
   *    group -> user
   *    user  -> group
   *    group -> group: groupname only
   *    user  -> user: user data
   */
  
  // if itemtype was not supplied, it's a user
  if ($role['type'] == 1 && $oldtype == 0) {
    // it was a user and is now a group
    // first delete the user, then create the group with the same memberships
    
    // delete G2 user
    $ret = GalleryEmbed::deleteUser($role['uid']);
    if (!$ret->isSuccess()) {
      $msg = xarML('Failed to delete G2 user with extId [#(1)]! Here is the error message from G2: <br
				/>[#(2)]', $role['uid'],$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return $args['extrainfo'];
    }
    // create G2 group
    if (!xarGallery2Helper::g2createGroup($role['uid'], $role['name'])) {
      return $args['extrainfo'];
    }
    // FIXME: copy permissions from old G2 user to new group
    
    // User -> Group, that means this group can't have children at this moment -> don't add groups in G2
  } elseif ($role['type'] == 1 && $oldtype == 1) {
    // group: probably group Name changed
    if (!xarGallery2Helper::g2updateGroup($role['uid'], $role['name'])) {
      return $args['extrainfo'];
    }
  } elseif ($role['type'] == 0 && $oldtype == 1) {
    // role was a group, is now a user 
    // delete G2 group
    $ret = GalleryEmbed::deleteGroup($role['uid']);
    if (!$ret->isSuccess()) {
      $msg = xarML('Failed to delete G2 group with extId [#(1)]! Here is the error message from G2: <br
			/>[#(2)]', $role['uid'],$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return $args['extrainfo'];
    }
    // create G2 user
    if (!xarGallery2Helper::g2createUser($role)) {
      return $args['extrainfo'];
    }
    // FIXME: copy G2 permissions from old group to new user
        
  } else {
    // role was a user, is now a user, just update
    if (!xarGallery2Helper::g2updateUser($role['uid'], $role)) {
      return $args['extrainfo'];
    }
  }
  
  // complete G2 transaction
  xarGallery2Helper::done();
  
  return $args['extrainfo'];
}

?>
