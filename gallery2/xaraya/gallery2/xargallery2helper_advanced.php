<?php
/**
 * Purpose of file:  G2 wrapper, synchronization
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage gallery2 Module
 * @author Andy Staudacher <ast@gmx.ch>
 */
require_once(dirname(__FILE__) . '/xargallery2helper.php');

/**
 * xarGallery2Helper_Advanced: class dedicated for the initial synchronization 
 *
 * Use this class only statically !
 * This class handles the initial synchronization (read: import and export) of 
 * users and groups between Gallery 2 and Xaraya
 * 
 * @author Andy Staudacher <ast@gmx.ch>
 * @access public
 * @throws none
 * @static
 */
class xarGallery2Helper_Advanced {
	
  /**
   * g2getPendingUsers: get a list of all G2 pending users
   *
   * returns a list of all G2 pending users, caches the list
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return array(bool true on success, else false, array(entityId => data))
   * @throws Systemexception if it failed
   */
  function g2getPendingUsers()
  {	
    static $g2PendingUsers;
    
    // init G2 transaction, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return array(false, null);
    }
    
    if (!isset($g2PendingUsers)) {
      // check if G2 register module is active:
      list($ret, $plugins) = GalleryCoreApi::fetchPluginStatus('module'); 
      if ($ret) {
	$msg = xarML('Failed to fetch a list of all G2 plugins. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return array(false, null);
      }
      // FIXME: does $plugins['register']['active'] exist?
      if (!isset($plugins['register']) || !isset($plugins['register']['active']) ||
	  !$plugins['register']['active']) {
	// G2 register module is not active, good
	return array(true, array());
      }
      
      // there may be some pending users
      require_once(xarModGetVar('gallery2','g2.includepath') . 'modules/register/classes/GalleryPendingUserHelper.class');
      list($ret, $pendingUsers) = GalleryPendingUserHelper::fetchUserData();
      if ($ret) {
	$msg = xarML('Failed to fetch a list of all G2 pending users. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return array(false, null);
      }
      $g2PendingUsers = $pendingUsers;
    }
    return array(true, $g2PendingUsers);
  }

  /**
   * g2deletePendingUsers: delete all G2 pending users
   *
   * We could import these G2 pending users, but then, 
   * we had to email them their new validation code.
   * For now, just delete them.
   * TODO: maybe import G2 pending users
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true on success, else false
   * @throws Systemexception if it failed
   */			
  function g2deletePendingUsers()
  {	
    list($ret, $g2pendingusers) = xarGallery2Helper_advanced::g2getPendingUsers();
    if (!$ret) { return false; }
    
    // init G2 transaction, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return false;
    }
    
    // delete the pending users from G2
    foreach (array_keys($g2pendingusers) as $g2pendinguserId) {
      $ret = GalleryCoreApi::deleteEntityById($g2pendinguserId);
      if ($ret) {
	$msg = xarML('Failed to delete G2 pending user. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }					
    }
    return true;
  }
	
  /**
   * @see xarGallery2Helper::verifyConfig
   */
  function verifyConfig($raiseexceptions=false, $setifcorrect=false, $g2Uri=null, 
  			$embedUri=null, $g2IncludePath=null) {
      /*
       * Verify the dependency on a compatible xaraya version (roles module changes, event system
       * changes)
       * Don't use xarConfigGetVar('System.Core.VersionNumber'); as it doesn't follow the version
       * dot subversion format pattern in snapshot versions.
       */

      $xarVersionString =  xarConfigGetVar('System.Core.VersionNumber');
      /* e.g. 1.0.0-rc2, split of anything after the 1.0.0 */
      $xarVersionArray = split('-', $xarVersionString);
      $xarVersion = $xarVersionArray[0];
      $minXarVersion = xarModGetVar('gallery2', 'xar.minCoreVersion');
      if (version_compare($minXarVersion, $xarVersion) > 0) {
      $msg = xarML('Your xaraya version is not compatible with this module. Your version is [#(1)], the minimum
            version number required is [#(2)]. Please upgrade your xaraya installation.', $xarVersion, $minXarVersion);
      if ($raiseexceptions) {
	xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      }
      return array(false, $msg);
    }
    
    // Verify that we find a G2 installation
    // the filesystem include path
    if (empty($g2IncludePath)) {
	$g2IncludePath = xarModGetVar('gallery2','g2.includepath');
    } 
    // else = different paths for url and filesystem
   
    // the embed uri
    if (!isset($embedUri)) {
      $embedUri = xarModGetVar('gallery2','embedUri');
    }

    // the g2 uri
    if (!isset($g2Uri)) {
      $g2Uri = xarModGetVar('gallery2','g2Uri');
    }
   
    if ($setifcorrect) {
	/* Get the name of the xaraya entry point file (usually index.php) */
	$scriptName = xarCore_getSystemVar('BaseModURL', true);
	if (!isset($scriptName)) {
	    $scriptName  = 'index.php';
	}
      
      $g2loginredirect = xarGallery2Helper::xarServerGetBaseURI() . $scriptName .'?module=roles&func=register';
    } else {
      $g2loginredirect = xarModGetVar('gallery2','g2.loginredirect');
    }
   
    if (strpos($g2IncludePath, 'embed.php') === false) {
        if (substr($g2IncludePath, -1) != '/' && substr($g2IncludePath, -1) != "\\") {
            $g2IncludePath .= '/';
        } 
        $g2IncludePath .= 'embed.php';
    }
   
    // return if the path is wrong or G2 is not installed
    if (!file_exists($g2IncludePath)) {
      $msg = xarML('I could not find a G2 installation at "[#(1)]"! Please correct the path!', $g2IncludePath);
      if ($raiseexceptions) {
	xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      }
      return array(false, $msg);
    }
    
    $g2Uri = G2EmbedDiscoveryUtilities::normalizeG2Uri($g2Uri);
    $apiVersion = unserialize(xarModGetVar('gallery2','embedApiVersion'));
    require_once($g2IncludePath);
    $ret = GalleryEmbed::init( array('embedUri' => $embedUri,
				     'g2Uri' => $g2Uri,
				     'loginRedirect' => $g2loginredirect,
				     'apiVersion' => $apiVersion));

    
    if ($ret) {
      $msg = xarML('G2 did not return a success status upon an init request. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      if ($raiseexceptions) {
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, $msg);
    }
    
    xarGallery2Helper::isInitiated(true);

    // ok, G2 is installed, the path is correct, now check if G2 is in embedded mode
    global $gallery;

    /* Get the current G2 core API version */
    $coreApiVersion = unserialize(xarModGetVar('gallery2', 'coreApiVersion'));
    $actualCoreApiVersion = GalleryCoreApi::getApiVersion();
    if (!GalleryUtilities::isCompatibleWithApi($coreApiVersion, $actualCoreApiVersion)) {
      $msg = xarML('Your G2 version is not compatible with this module. Your version is [#(1)], the minimum
            version number required is #(2). Please upgrade your G2 installation.', $actualCoreApiVersion, $coreApiVersion);
      if ($raiseexceptions) {
	xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      }
      return array(false, $msg);
    }
    
    if ($setifcorrect) {	
      // set the paths
      xarModSetVar('gallery2','g2Uri', $g2Uri);
      xarModSetVar('gallery2','embedUri', $embedUri);
      // absolute include path to /some/path/Gallery/
      xarModSetVar('gallery2','g2.includepath', $g2IncludePath);
      // the login redirect url
      xarModSetVar('gallery2','g2.loginredirect',$g2loginredirect);

 /*
  G2 short url support changed from PathInfo (compatible) to mod_rewrite.
  For now, G2 short urls can not be supported in G2 embedded. We have to
  Figure out a way to bring back short urls to embedded G2.
     
      // set short urls in G2 on/off
      if (isset($shortUrlActive) && (is_bool($shortUrlActive) || is_int($shortUrlActive))) {
	$pluginParameter = 'misc.useShortUrls';
	$shortUrlActive = $shortUrlActive ? 'true' : 'false';
	$ret = GalleryCoreApi::setPluginParameter('module', 'core', $pluginParameter, $shortUrlActive);
	if ($ret) {
	  $msg = xarML('Failed to update plugin parameter [#(1)] to new value [#(2)]. Here is the error message from G2: <br /> [#(3)]',
		       $pluginParameter, $shortUrlActive,$ret->getAsHtml());
	  if ($raiseexceptions) {
	    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  } 
	  return array(false, $msg);
	}
      }
*/      
      // Enable gallery2 hooks for roles
      if (xarModIsAvailable('roles')) {
	xarModAPIFunc('modules','admin','enablehooks',
		      array('callerModName' => 'roles', 'hookModName' => 'gallery2'));
      } else {
	$msg = xarML('roles module is not active.');
	if ($raiseexceptions) {
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	}
	return array(false, $msg);
      }
    } else {
      // check if the roles module calls our hooks
      $moduleList = xarModAPIFunc('modules','admin','gethookedmodules',
				  array('hookModName' => 'gallery2'));
      if (!isset($moduleList['roles'])) {
	// try to reactivate the hooks for the roles module before returning the error
	$msg = xarML('roles module is not hooked to our gallery2 module.');
	if (xarModIsAvailable('roles')) {
	  $ret = xarModAPIFunc('modules','admin','enablehooks',
			       array('callerModName' => 'roles', 'hookModName' => 'gallery2'));
	  if (isset($ret) && $ret) {
	    $msg = xarML('roles module was not hooked to our gallery2 module, but that is fixed now.');
	  }
	} else {
	  $msg = xarML('roles module is not active.');
	  if ($raiseexceptions) {
	    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  }
	  return array(false, $msg);
	}
	if ($raiseexceptions) {
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	}
	return array(false, $msg);
      }
    }
    
    return array(true, null);	
  }
  
  /**
   * @see xarGallery2Helper::getDetectedEmbedUri
   */
  function getDetectedEmbedUri() {
    /* Task: find embedUri / normalize it 
     * Get the name of the xaraya entry point file (usually index.php) 
     * Get the name of the xaraya entry point file (usually index.php) */
    $scriptName = xarCore_getSystemVar('BaseModURL', true);
    if (!isset($scriptName)) {
        $scriptName  = 'index.php';
    }
    //  short urls enabled in xaraya?
    $shortUrlActive = xarConfigGetVar('Site.Core.EnableShortURLsSupport');
    /* Do we support short urls? */
    $thisModuleShortUrls =  xarModGetVar('gallery2','SupportShortURLs');
    if (isset($shortUrlActive) && $shortUrlActive && isset($thisModuleShortUrls) && $thisModuleShortUrls) {
        $scriptName .='/gallery2/';
    } else {			
        $scriptName .='?module=gallery2';
    }
    $embedUri = xargallery2helper::xarServerGetBaseURI() . $scriptName;
    
    return $embedUri;
  }
    
  /**
   * @see xarGallery2Helper::g2updateSpecialRoles
   */
  function g2updateSpecialRoles() 
  {
    // init if not already done so
    if (!xarGallery2Helper::init(true)) {
      return false;
    }
    $defaultgroupname = xarModGetVar('roles', 'defaultgroup');
    foreach(array(array('Everybody', 'id.everybodyGroup'), array('Administrators', 'id.adminGroup'),
		  array($defaultgroupname, 'id.allUserGroup'),
		  array('anonymous', 'id.anonymousUser')) as $specialRole) {
      $pluginParameter = $specialRole[1];
      $xarName = $specialRole[0];
      
      // the xaraya defaultGroup can be any group, including everybody and adminstrators
      // per default, it's a third group, but you can configure it to be Everybody or Administrators
      if ($pluginParameter == 'id.allUserGroup' && !xarGallery2Helper_Advanced::xarIsDefaultGroupAThirdGroup())  {
	continue; // we already sync this group
	// -> "All Users" group in G2 isn't any special group anymore			
      }
      
      // get G2 entity id for this group/user
      list ($ret, $id) = GalleryCoreApi::getPluginParameter('module', 'core', $pluginParameter);
      if ($ret) {
	$msg = xarML('Failed to get plugin parameter for special G2 user/group. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      
      // switch user / group
      if ($pluginParameter != 'id.anonymousUser') {
	$roleData = xarModAPIFunc('roles','user','get', array('name' => $xarName, 'type' => 1));
	$entityType = 'GalleryGroup';
      } else {
	$roleData = xarModAPIFunc('roles','user','get', array('uid' => _XAR_ID_UNREGISTERED, 'type' => 0));
	$entityType = 'GalleryUser';
      } 
      
      // The group with the groupName = defaultGroup could already exist in G2. 
      // Xaraya calls it's all users group "Users" by default. But you can choose other groups
      // as the default group in xaraya. In this case, check if a group with such a name exists in G2
      // and map it to it, else, create a new G2 group. and update the G2 all users config param.
      if ($pluginParameter == 'id.allUserGroup' && $defaultgroupname != 'Users') {
	list($ret, $g2role) = GalleryCoreApi::fetchGroupByGroupName($defaultgroupname);
	if ($ret) {
	  if (!$ret->getErrorCode() & ERROR_MISSING_OBJECT) { 
	    // a real error, not good
	    $msg = xarML('Failed to fetch group by groupname [#(1)] from G2 in g2updateSpecialRoles!', $defaultgroupname);
	    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	    return false;
	  }
	}
	if (!is_object($g2role)) { 
	  // ok, a group with this name doesn't exist in G2, good, let's create it now
	  if (!xarGallery2Helper::g2createGroup($roleData['uid'], $roleData)) {
	    return false;
	  } // this creates the ext id map too
	  // load new G2 group
	  list($ret, $g2role) = xarGallery2Helper::g2loadEntityByExternalId($roleData['uid'], $entityType);
	  if (!$ret) {
	    return false;
	  }
	} 
	// update G2 plugin parameter allusergroup
	$newParameterValue = $g2role->getId();
	if ($id != $newParameterValue) {
	  // got to update the parameter
	  $ret = GalleryCoreApi::setPluginParameter('module', 'core', $pluginParameter, $newParameterValue);
	  if ($ret) {
	    $msg = xarML('Failed to update plugin parameter [#(1)] for special G2 user/group [#(2)] for xaraya group [#(3)]. Old value [#(4)], new value [#(5)]. Here is the error message from G2: <br /> [#(6)]',
			 $pluginParameter, $g2role->getgroupName(),$xarName, $id,$newParameterValue,$ret->getAsHtml());
	    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	    return false;
	  }
	  $id = $newParameterValue;
	} 
      } // end if ($pluginParameter == 'id.allUserGroup')
      
      $ret = null; $g2role = null;
      // create extId, entityId map entry if not already existent 
      list($ret, $g2role) = GalleryCoreApi::loadEntityByExternalId($roleData['uid'], $entityType);
      if ($ret) {
	if (!$ret->getErrorCode() & ERROR_MISSING_OBJECT) { 
	  // a real error, not good
	  $msg = xarML('Failed to fetch special group/user by extId [#(1)] from G2 in g2updateSpecialRoles! Here is the error message from G2: <br /> [#(6)]',$roleData['uid'],$ret->getAsHtml());
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
	// ok, we didn't map this role to G2 groups/users yet, let's do it now
	// create map entry
	$ret = xarGallery2Helper::g2addexternalMapEntry($roleData['uid'], $id, $entityType);
	if (!$ret) {
	  return false;
	}
      }
      
      // update user/group data: switch user / group
      if ($pluginParameter != 'id.anonymousUser') { // group
	if (!xarGallery2Helper::g2updateGroup($roleData['uid'], $roleData)) {
	  $msg = xarML('Failed to update special G2 group by extId [#(1)].', $roleData['uid']);
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
      } else { // user (anonymous user)
	$msg = "muh";
	if (!xarGallery2Helper::g2updateUser($roleData['uid'], $roleData)) {
	  $msg = xarML('Failed to update special G2 user by extId [#(1)].',$roleData['uid']);
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
      }	
    } // end foreach

    return true;
  }
  
  /**
   * xarIsDefaultGroupAThirdGroup: checks if defaultGroup is a third group
   *
   * The Xaraya defaultGroup is per default "Users". But you can configure
   * it to be "Everybody" group or "Administrators" group (or any other group).
   * If this group is either Everybody or Administrators, we don't have to
   * map it additionally, because we already map these groups with G2.
   * If this is the case, the "All Users" group of G2 loses its meaning as a 
   * special group.
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true or false
   */
  function xarIsDefaultGroupAThirdGroup()
  {
    $defaultGroupName = xarModGetVar('roles', 'defaultgroup');
    if (strtoupper($defaultGroupName) == strtoupper('Everybody')
	|| strtoupper($defaultGroupName) == strtoupper('Administrators'))  {
      return false; // we already sync this group
      // -> "All Users" group in G2 isn't any special group anymore		
    }
    return true;
  }
    
  /**
   * @see xarGallery2Helper::g2xarUserGroupImportExport
   */
  function g2xarUserGroupImportExport()
  {
    // First disable the xaraya hooks to prevent synchronization loops
    $ret = xarModAPIFunc('modules','admin','disablehooks',
			 array('callerModName' => 'roles', 'hookModName' => 'gallery2'));
    if (!isset($ret) || !$ret) {
      $msg = xarML('Could not disable the hooks to the roles module.');
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    
    // init G2 transaction, load G2 API, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return false;
    }					
    
    // set the module = not configured during synchronization process
    $configValueBackup = xarGallery2Helper::isConfigured();
    xarGallery2Helper::isConfigured(false);

    // Flush the G2 filesystem cache
    global $gallery;
    $platform = $gallery->getPlatform();
    
    $dirs = array();
    foreach (array('data.gallery.cache') as $param) {
      $dir = $gallery->getConfig($param);
      if (!empty($dir)) {
	$dirs[] = $dir;
      }
    }

    foreach ($dirs as $dir) {
      
      if (empty($dir)) {
	$ret = GalleryStatus::error(ERROR_BAD_PATH, __FILE__, __LINE__);
	$msg = xarML('Error during synchronization. Error during Flush G2 filesystem cache. Here is the rror from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      
      if ($platform->file_exists($dir)) {
	$ret = $platform->recursiveRmdir($dir);
	if (!$ret) {
	  $ret = GalleryStatus::error(ERROR_BAD_PATH, __FILE__, __LINE__);
	  $msg = xarML('Error during synchronization. Error during Flush G2 filesystem cache. Here is the rror from G2: <br /> [#(1)]', $ret->getAsHtml());
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
      }
      
      $ret = $platform->mkdir($dir);
      if (!$ret) {
	$ret = GalleryStatus::error(ERROR_BAD_PATH, __FILE__, __LINE__);
	$msg = xarML('Error during synchronization. Error during Flush G2 filesystem cache. Here is the rror from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
    }
    

    /*
     * First Synchronize the special groups and the anonymous user
     * That means: update the groupName / userName / display Name in G2
     */
    if(!xarGallery2Helper::g2updateSpecialRoles()) {
      return false;
    }
    
    /*
     * Load the current state of G2 and xaraya, get a list of all users and groups
     *
     * If we load most stuff before we actually change something, we minimize the 
     * probability that something goes wrong in between the import/export process and
     * that we end up with a corrupted state.
     */
    
    // Load a list of all xaraya groups and a list of all groupNames
    if (!xarModAPILoad('roles', 'user')) { return false; }
    $xarGroups = xarGallery2Helper::xargetAllGroups();
    // Load a list of all xaraya users
    $xarUsers = xarGallery2Helper::xargetAllUsers();

    // fetch the xaraya Everybody and the default group
    $xarEverybodyGroup = xarModAPIFunc('roles','user','get', array('uname' => 'Everybody', 'type' => 1));
    $xarDefaultGroup = xarModAPIFunc('roles','user','get'
				     , array('name' => xarModGetVar('roles', 'defaultgroup'), 'type' => 1));
    if (!isset($xarEverybodyGroup['uid']) || !$xarEverybodyGroup['uid']
	|| !isset($xarDefaultGroup['uid']) || !$xarDefaultGroup['uid']) {
      $msg = xarML('Could not fetch the xaraya everybody/default groups.');
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    
    // Load the list of all G2 groupNames
    list($ret, $g2GroupNames) = GalleryCoreApi::fetchGroupNames();
    if (!empty($ret)) {
      $msg = xarML('Could not fetch G2 group names. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    
    // we need the entityId of the groups
    $g2Groups = array();
    foreach ($g2GroupNames as $g2GroupName) {
      // Load the object Object
      list($ret, $g2Group) = GalleryCoreApi::fetchGroupByGroupName($g2GroupName);
      if (!empty($ret)) {
	$msg = xarML('Could not fetch a G2 group object. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      $g2Groups[strtolower($g2GroupName)] = $g2Group;
    }
    
    // Load a list of all G2 userNames
    list($ret, $g2UserNames) = GalleryCoreApi::fetchUsernames();
    if (!empty($ret)) {
      $msg = xarML('Could not fetch G2 user names. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    
    // We will need the existing group memberships for all G2 users
    // and for G2 users that don't exist in xaraya, we need the userdata
    // load the G2 user object
    $g2Users = array(); // array('user' => $g2UserObject, 'memberships' => array(groupId => grouName))
    foreach ($g2UserNames as $g2UserName) {
      // Load the user Object
      list($ret, $g2User) = GalleryCoreApi::fetchUserByUserName($g2UserName);
      if (!empty($ret)) {
	$msg = xarML('Could not fetch a G2 user object. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      // load the existing group memberships
      list($ret, $g2MemberShips) = GalleryCoreApi::fetchGroupsForUser($g2User->getId());
      if ($ret) {
	$msg = xarML('Could not fetch G2 groups for G2 user. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      $g2Users[strtolower($g2UserName)] = array('user' => $g2User, 'memberships' => $g2MemberShips);
    }

    // Load a list of all G2 pending users
    /*
     * deactivate pending user stuff. it doesn't work in multisite + embedded g2.

    list($ret, $g2pendingusers) = xarGallery2Helper_advanced::g2getPendingUsers();
    if (!$ret) {
      return false;
    }
    */
    
    // Load all existing xaraya <-> G2 mappings
    list($ret, $mapsbyentityid) = xarGallery2Helper::g2getallexternalIdmappings('entityId');
    if (!$ret) {
      return false;
    }
    
    
    /**********************************************************************************/
    
    /*
     * 1. import G2 groups to xaraya
     */
    foreach ($g2Groups as $g2Group) {
      // check if we already mapped this group
      if (isset($mapsbyentityid[$g2Group->getId()])) {
	continue; // already mapped
      }
      // check if a group with this name already exists in xaraya
      if (xarGallery2Helper_Advanced::in_array_cin($g2Group->getGroupName(), array_keys($xarGroups))) {
	if (!xarGallery2Helper::g2addexternalMapEntry($xarGroups[strtolower($g2Group->getGroupName())]['uid'],	$g2Group->getId(), 1)) {
	  return false;
	}
	continue;
      }
      // else: add this group to xaraya: gname and name, because there's a change from 0.9.11 to 0.9.12
      $ret = xarmodapifunc('roles','admin','addgroup', array('gname' => $g2Group->getgroupname(), 'name' => $g2Group->getgroupname(),));
      if (!isset($ret) || !$ret) {
	$msg = xarML("Could not create a xar role for a G2 group [#(1)].", $g2Group->getgroupname());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      // add the group to the Everybody group 
      $newRole = xarModAPIFunc('roles','user','get', array('name' => $g2Group->getgroupname(), 'type' => 1));
      if (!isset($newRole['uid'])) {
	$msg = xarML("Could not retrieve the role for the newly created/imported G2 group [#(1)]", $g2Group->getgroupname());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      $ret = xarmodapifunc('roles','admin','addmember', array('gid' => $xarEverybodyGroup['uid'], 'uid' => $newRole['uid']));				
      if (!isset($ret) || !$ret) {
	$msg = xarML("Could not add the imported G2 group [#(1)] with uid [#(2)] to the Everybody group with uid [#(3)] in xaraya.",
		     $g2GroupName, $newRole['uid'], $xarEverybodyGroup['uid']);
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      // add the map entry to the G2 externalId, entityId table
      if (!xarGallery2Helper::g2addexternalMapEntry($newRole['uid'], $g2Group->getId(), 1)) {
	return false;
      }
    }
    
    // update map caches:
    list($ret, $mapsbyexternalid) = xarGallery2Helper::g2getallexternalIdmappings('externalId');
    if (!$ret) {
      return false;
    }
    
    /* 
     * 2. export xaraya roles of type 1 = groups to G2
     */
    foreach ($xarGroups as $xarGroup) {
      // if it's already in the map table, the groupName may be out of sync
      if (isset($mapsbyexternalid[$xarGroup['uid']])) {
	if (!xarGallery2Helper::g2updateGroup($xarGroup['uid'], $xarGroup)) {
	  return false;
	}
	continue;
      }			
      
      // if the group already exists in G2, but we don't have a mapping yet, create the map 
      if (xarGallery2Helper_advanced::in_array_cin($xarGroup['name'], $g2GroupNames)) {
	if (!xarGallery2Helper::g2addexternalMapEntry($xarGroup['uid'], $g2Groups[strtolower($xarGroup['name'])]->getId(), 1)) {
	  return false;
	}
	// there's no group data to update, the names are already the same
	continue;
      }
      
      // else create group
      if(!xarGallery2Helper::g2createGroup($xarGroup['uid'], $xarGroup)) { 
	return false;
      } // this creates the extId map too
    }
    
    // update map caches:
    list($ret, $mapsbyentityid) = xarGallery2Helper::g2getallexternalIdmappings('entityId');
    if (!$ret) {
      return false;
    }
    
    
    /*
     * 3. import G2 users to xaraya
     */

    // First disable "each user has a unique email" in xaraya, because G2 has not this requirement
    xarModSetVar('roles','uniqueemail',0);
    // Foreach user: a) create if nonexistent, b) add group memberships
    foreach ($g2UserNames as $g2UserName) {
      $g2User = $g2Users[strtolower($g2UserName)]['user'];
      // check if we already mapped this user
      if (!isset($mapsbyentityid[$g2User->getId()])) {
	// check if a user with this name already exists
	if (!xarGallery2Helper_advanced::in_array_cin($g2UserName, array_keys($xarUsers))) {
	  // add user to xaraya if there wasn't such a user
	  // create xar user
	  // if the G2 user has no email, generate a dummy email
	  $userEmail = $g2User->getemail();
	  $userEmail =  empty($userEmail) ? 'dummyEmail@G2Integration.xyz' : $userEmail;
	  /* xaraya doesn't accept empty full names */
	  $userFullName = $g2User->getfullName();
	  $userFullName = empty($userFullName) ? $g2UserName : $userFullName;
	  $uid = xarmodapifunc('roles','admin','create',
			       array('uname' => $g2UserName,
				     'realname' => $userFullName, 'email' => $userEmail,
	                             /* we can't provide the cleartext password -> users have to get a new password */
				     'pass' => $g2User->gethashedPassword(), 'date' => $g2User->getcreationTimestamp(),
				     'state' => ROLES_STATE_ACTIVE, 'valcode' => xarModAPIFunc('roles', 'user', 'makepass')));
	  if (empty($uid)) {
	    $msg = xarML("Could not create a xar role for a G2 user '$g2UserName'.");
	    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	    return false;
	  }
	  $uid = intval($uid);
	} else {
	  // get the $uid of the existing xar role
	  $uid = $xarUsers[strtolower($g2UserName)]['uid'];
	}
	
	// and add the map entry 
	if (!xarGallery2Helper::g2addexternalMapEntry($uid, $g2User->getId(), 0)) {
	  return false;
	}
      } else {
	$uid = $mapsbyentityid[$g2User->getId()]['externalId'];
      }
      
      // add the user to groups he was member of in G2
      // luckily he must have been member of Everybody and All Users group, nothing to add
      $g2MemberShips = array_keys($g2Users[strtolower($g2UserName)]['memberships']);
      
      // old xaraya users just need the new memberships from G2
      if (isset($mapsbyentityid[$g2User->getId()]) || xarGallery2Helper_advanced::in_array_cin($g2UserName, array_keys($xarUsers))) {
	list ($oldxarUser, $existingMemberships) = xarGallery2Helper::xarGetAncestors(array('uid' => $uid));
	if ($oldxarUser == null) {
	  return false;
	}
      } else {
	$existingMemberships = array();
      }
      // filter only new memberships and translate entityIds to externalIds = uids
      $newg2MemberShips = array();
      foreach ($g2MemberShips as $g2GroupId) {
	if (isset($mapsbyentityid[$g2GroupId])) { // get uid from the map
	  $newgroupuid = $mapsbyentityid[$g2GroupId]['externalId'];
	} else {
	  $msg = xarML('Could not get extId for g2groupid [#(1)].', $g2GroupId);
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
	if (!in_array($newgroupuid, array_keys($existingMemberships))) {
	  $newg2MemberShips[$newgroupuid] = $newgroupuid;
	}
      }
      $g2MemberShips = $newg2MemberShips;
      
      // check if this xaraya user is in the xaraya admin group
      list($thisRole, $thisRolesMemberships) = xarGallery2Helper::xarGetAncestors(array('uid' => $uid));
      $xarIsAdmin = 0;
      foreach ($thisRolesMemberships as $xarMembership) {
	if ($xarMembership['name'] == 'Administrators') {
	  $xarIsAdmin = 1;
	  break;
	}
      }

      // add the user to some groups
      foreach ($g2MemberShips as $membershipuid) {
	// don't add the user directly to the Everybodygroup, as he's a member of that group
	// through recursion
	if (count($g2MemberShips) > 1 && $membershipuid == $xarEverybodyGroup['uid']) {
	  continue;
	} 
	// don't add admin user in xaraya to defaultGroup	
	if ($xarIsAdmin == 1 and $membershipuid == $xarDefaultGroup['uid']) {
	  continue;
	}
	// add user to group
	$ret = xarmodapifunc('roles','admin','addmember',
			     array('gid' => $membershipuid, 'uid' => $uid));
	if (!isset($ret) ||!$ret) {
	  $msg = xarML('Could not add the new xar role [#(1)] to a xar group [#(2)].', $uid, $membershipuid);
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
      } 
    } // end for each G2 user
    
    
    // update map caches:
    list($ret, $mapsbyexternalid) = xarGallery2Helper::g2getallexternalIdmappings('externalId');
    if (!$ret) {
      return false;
    }
    
    /*  
     * 4. export xaraya roles of type 0 = users to G2 or update G2 users
     */
    foreach ($xarUsers as $xarUser) {
      // if the map exists, just update the user data
      if (isset($mapsbyexternalid[$xarUser['uid']])) {
	if (!xarGallery2Helper::g2updateUser($xarUser['uid'], $xarUser)) {
	  return false;
	}
      } else {
	// if the user already exists in G2, create a mapping and update the data
	if (xarGallery2Helper_advanced::in_array_cin($xarUser['uname'], $g2UserNames)) {
	  $g2User = $g2Users[strtolower($xarUser['uname'])]['user'];
	  
	  // and add the map entry 
	  if (!xarGallery2Helper::g2addexternalMapEntry($xarUser['uid'], $g2User->getId(), 0)) {
	    return false;
	  }
	  // update the user data
	  if (!xarGallery2Helper::g2updateUser($xarUser['uid'], $xarUser)) {
	    return false;
	  }
	} else { // create user in G2
	  if (!xarGallery2Helper::g2createUser($xarUser['uid'], $xarUser)) {
	    return false;
	  }
	}
      }
      // Add xaraya group memberships in G2
      list($unused, $xarMemberships) = xarGallery2Helper::xarGetAncestors(array('uid' => $xarUser['uid']));
      foreach ($xarMemberships as $xarGroup) {			
	// no need to add it to everybody/default group, already member of these groups
	if ($xarGroup['uid'] == $xarEverybodyGroup['uid'] || $xarGroup['uid'] ==  $xarDefaultGroup['uid']) {
	  continue;
	}
	$ret = GalleryEmbed::addUserToGroup($xarUser['uid'], $xarGroup['uid']);
	if ($ret) {
	  $msg = xarML('Failed to add g2 user  with extid [#(1)] to g2 group with extid [#(2)], uname [#(3)]. In import/export step 4. Here is the error message from G2: <br /> [#(4)]',
		       $xarUser['uid'],  $xarGroup['uid'], $xarGroup['uname'], $ret->getAsHtml());
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
      }
    }
    /*
     * 5. delete G2 pending users
     */
    /*
     * doesn't work in multisite + embedded g2. therefore remove it from the xaraya integration
     * we should add it again in the centralized g2 user synchronization

    if (!xarGallery2Helper_advanced::g2deletePendingUsers()) {
      return false;
    }
    */
    
    // The import/export was successful
    // Enable gallery2 hooks for roles again
    $ret = xarModAPIFunc('modules','admin','enablehooks',
			 array('callerModName' => 'roles', 'hookModName' => 'gallery2'));
    if (!isset($ret) || !$ret) {
      $msg = xarML('Could not re enable the hooks to the roles module.');
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    
    // restore config value
    xarGallery2Helper::isConfigured($configValueBackup);
    return true;
  }
  
  /**
   * in_array_cin: case-insensitive in_array
   *
   * case-insensitive version of php function in_array()
   * returns true if param 1 is in array param 2
   *
   * @author Andy Staudacher
   * @access public
   * @param var the search argument
   * @param array of vars to search in
   * @return bool success status
   */
  function in_array_cin($strItem, $arItems)
  {
    foreach ($arItems as $strValue)
      {
	if (strtoupper($strItem) == strtoupper($strValue))
	  {
	    return true;
	  }
      }
    return false;
  }
  
  
}

/*
 * $RCSfile$
 *
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
 * @version $Revision$ $Date$
 * @package GalleryCore
 * @author Andy Staudacher <ast@gmx.ch>
 */

/**
 * A collection of useful G2Embed related utilities to find the correct GalleryEmbed::init 
 * parameters
 *
 * @package GalleryCore
 * @subpackage GalleryEmbed
 * @static
 */
class G2EmbedDiscoveryUtilities {
    /**
     * Documentation:
     * To use GalleryEmbed and its GalleryEmbed::init method to initialize G2, you need:
     *   a) the absolute filesystem path to embed.php
     *   b) embedUri, the URL of the entry point to your embedding application / embedded G2
     *      e.g. http://example.com/ or just / , or http://example.com/index.php?mod=gallery
     *   c) g2Uri, the URL path to G2, e.g. http://example.com/gallery2/ or just /gallery2/
     *
     * Methods to finding out the path to embed.php:
     * ============================================
     *
     *   - It's a good assumption that you can find out or define embedUri easily
     *   - g2Uri must be entered by the admin that configures the integration, just copy and paste 
     *     the URL of G2
     *   - finding out embed.php is a little tricky.
     *
     *   We offer two methods to get embed.php. Do NOT call them for each request. Call them once
     *   when configuring / installing your integration. Else you get a performance penalty.
     *
     *    1. If you ask the user to enter the g2Uri and the G2 setup password, you can call:
     *        list ($success, $embedPhpPath, $errorString) =
     *            G2EmbedDiscoveryUtilities::getG2EmbedPathByG2UriAndPassword($g2Uri, $password);
     *        if (!$success) {
     *            print $errorString;
     *            /* Tell the admin to enter the correct input
     *        } else {
     *            /* embedPhpPath is correct and you can store it in your config for later use 
     *        }
     *
     *    2. If you ask only for the g2Uri, you also need to provide the filesystem path to the
     *       entry point (the filesystem path to the file that embedUri points to)
     *       list ($success, $embedPhpPath, $errorString) =
     *                  G2EmbedDiscoveryUtilities::getG2EmbedPathByG2UriEmbedUriAndLocation(
     *                             $g2Uri, $embedUri, dirname(dirname(__FILE__)));
     *        if (!$success) {
     *            print $errorString;
     *            /* Tell the admin to enter the correct input
     *        } else {
     *            /* embedPhpPath is correct and you can store it in your config for later use 
     *        }
     *       Disadvantage of this method: it's less reliable (can't handle apache alias, ...)
     *
     *
     * Method to normalize the g2Uri and embedUri before using them in GalleryEmbed::init:
     * ==================================================================================
     *
     *   Do NOT call them on each request. Call them once to verify / sanitize user input
     *   and then store them in your configuration.
     *   - These methods try their best to be tolerant to common user mistakes and return a
     *     string that GalleryEmbd::init accepts
     *   - You don't have to call these methods before calling the above methods to get
     *     embed.php, since it does that already internally
     *
     *   1. $g2Uri = G2EmbedDiscoveryUtilities::normalizeG2Uri($g2Uri);
     *   2. $embedUri = G2EmbedDiscoveryUtilities::normalizeG2Uri($embedUri);
     */

    /**
     * The format for g2Uri accepted by GalleryEmbed::init is quite strict and well defined
     * missing traling / leading slashes have a meaning.
     * This function is more tolerant for incorrect user input and tries to normalize the
     * given g2Uri to a value that is probably what the user meant to provide
     *
     * The returned URI is either a server-relative URI (e.g. /gallery2/) or an absolute URI
     * including the schema (e.g. http://example.com/gallery/)
     *
     * The file / query string part is always removed)
     *
     * @param string g2Uri
     * @return string normalized g2Uri
     */
    function normalizeG2Uri($g2Uri) {
	list ($schemaAndHost, $path, $file, $queryString, $fragment) =
	    G2EmbedDiscoveryUtilities::_normalizeUri($g2Uri);

	return $schemaAndHost . $path;
    }

    /**
     * @see normalizeG2Uri
     *
     * Very similar, but file / query string is kept in the result
     */
    function normalizeEmbedUri($embedUri) {
	list ($schemaAndHost, $path, $file, $queryString, $fragment) =
	    G2EmbedDiscoveryUtilities::_normalizeUri($embedUri);

	return $schemaAndHost . $path . $file . $queryString . $fragment;
    }
 
    /**
     * Find the absolute filesystem path to G2's embed.php when given the g2Uri
     * and the G2 setup password
     *
     * Returns false if the g2Uri or the password is wrong
     *
     * @param string the g2Uri, a full URL or a server-relative URI
     * @param string the G2 setup password (see config.php)
     * @return array boolean success,
     *               string filesystem path of embed.php
     *               string error string
     */
    function getG2EmbedPathByG2UriAndPassword($g2Uri, $password) {
	$g2Uri = trim($g2Uri);
	if (empty($g2Uri) || empty($password)) {
	    return array (false, null, "BAD PARAMETER");
	}

	$g2Uri = G2EmbedDiscoveryUtilities::normalizeG2Uri($g2Uri);
	
	/* Add a schema / host part to the g2Uri if necessary */
	if (strpos($g2Uri, 'http') !== 0) {
	    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
	    $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
	    $g2Uri = sprintf('%s://%s%s', $protocol, $host, $g2Uri);
	}

	$components = @parse_url($g2Uri);
	if (!$components) {
	    return array(false, null, "Unable to parse normalized URL $g2Uri");
	}
	$port = empty($components['port']) ? 80 : $components['port'];
	if (empty($components['path'])) {
	    $components['path'] = '/';
	}

	$fd = @fsockopen($components['host'], $port, $errno, $errstr, 1);
	if (empty($fd)) {
	    return array(false, null, "Error $errno: '$errstr' retrieving $url");
	}

	$get = $components['path'];
	$get .= 'embed.php?embedSetupPassword=' . $password;

	/* Read the web page into a buffer */	
	$ok = fwrite($fd, sprintf("GET %s HTTP/1.0\r\n" .
				  "Host: %s\r\n" .
				  "\r\n",
				  $get,
				  $components['host']));
	if (!$ok) {
	    /* Zero bytes written or false was returned */
	    $errorStr = "fwrite call failed in fetchPage($g2Uri)";
	    if ($ok === false) {
		$errorStr .= "\nreturn value was false";
	    }
	    return array(false, null, $errorStr);
	}
	$ok = fflush($fd);
	if (!$ok) {
	    if (version_compare(phpversion(), '4.2.0', '>=')) {
		/* Ignore false returned from fflush on PHP 4.1 */
		return array(false, null, "fflush call failed in fetchPage($g2Uri)");
	    }
	}

	/*
	 * Read the response code. fgets stops after newlines.
	 * The first line contains only the status code (200, 404, etc.).
	 */
	$headers = array();
	$response = trim(fgets($fd, 4096));

	/* if the HTTP response code did not begin with a 2 this request was not successful */
	if (!preg_match("/^HTTP\/\d+\.\d+\s2\d{2}/", $response)) {
	    return array(false, null, "URL derived from $g2Uri is invalid");
	}

	/* Read the headers. */
	while (!feof($fd)) {
	    $line = trim(fgets($fd, 4096));
	    if (empty($line)) {
		break;
	    }
	    /* Normalize the line endings */
	    $line = str_replace("\r", '', $line);

	    list ($key, $value) = explode(':', $line, 2);
	    $headers[$key] = trim($value);
	}

	$embedPhpPath = '';
	if (isset($headers['X-G2-EMBED-PATH'])) {
	    $embedPhpPath = $headers['X-G2-EMBED-PATH'];
	} else {
	    return array(false, null, "Invalid password or wrong embed.php for $g2Uri");
	}
	
	if (empty($embedPhpPath)) {
	    return array(false, null, "Correct URL and correct password, but returned " .
			 "embed.php is empty (server error?!)");
	}

	/* Verify path */
	list ($ok, $errorString) = @G2EmbedDiscoveryUtilities::isFileReadable($embedPhpPath);
	if (!$ok) {
	    return array(false, null, $errorString);
	} else {
	    return array(true, $embedPhpPath, null);
	}
    }

    /**
     * Get the absolute filesystem path to embed.php from the given g2Uri, embedUri and the
     * absolute filesystem path of the entry point file of your embedding application
     *
     * Can be unreliable if short URLs are entered or if apache alias / symlinks are used
     *
     * @param string g2Uri
     * @param string embedUri
     * @param string the dirname of the location of the entry point of your embedding application
     *        e.g. dirname(__FILE__) if your embedUri points right to your wrapper file or if your
     *        wrapper file is in the same directory as the entry point to your emApp
     *        e.g. dirname(dirname(dirname(__FILE__))) if your wrapper is in a
     *        modules/g2integration/wrapper.inc.php file, which is 2 subdirectories deeper than
     *        the actual entry point that embedUri is pointing to
     * @return array boolean success,
     *               string absolute filesystem path to embed.php,
     *               string errorString
     */
    function getG2EmbedPathByG2UriEmbedUriAndLocation($g2Uri, $embedUri, $dirnameOfEmApp) {
	if (empty($dirnameOfEmApp)) {
	    return array(false, null, 'dirname of embedding application is empty');
	}
	/* Normalize g2Uri, embedUri */
	list ($schemaAndHost, $path, $file, $queryString, $fragment) =
	    G2EmbedDiscoveryUtilities::_normalizeUri($g2Uri);
	$g2Path = $path;
	list ($schemaAndHost, $path, $file, $queryString, $fragment) =
	    G2EmbedDiscoveryUtilities::_normalizeUri($embedUri);
	$embedPath = $path;
	
	/* Normalize path separators */
	$dirnameOfEmApp = str_replace(DIRECTORY_SEPARATOR, '/', $dirnameOfEmApp);
	/* Remove trailing slash */
	if (substr($dirnameOfEmApp, -1) == '/') {
	    $dirnameOfEmApp = substr($dirnameOfEmApp, 0, strlen($dirnameOfEmApp) - 1);
	}
	
	/*
	 * Do some directory traversal to translate g2Path + embedPath + dirnameOfEmApp
	 * to path to embed.php
	 * path
	 * Example: g2Path = /baz/bar/gallery2/ , embedPath = /baz/cms/foo/ ,
	 *          dirnameOfEmApp = /home/john/www/cms/foo/
	 * 1. Remove as many dirs from the end of dirnameOfEmApp as embedPath has
	 * 2. append g2Path to dirnameOfEmApp
	 */
	$numberOfSubDirs = count(explode('/', $embedPath));
	/* Don't count the one before the leading and after the traling slash */
	$numberOfSubDirs -= 2;

	$pathElements = explode('/', $dirnameOfEmApp);
	$max = 30; /* prevent infinite loop */
	while ($numberOfSubDirs-- > 0 && $max-- > 0) {
	    array_pop($pathElements);
	}

	$embedPhpPath = join('/', $pathElements) . $g2Path . 'embed.php';

	/* Convert / back to platform specific directory separator */
	$embedPhpPath = str_replace('/', DIRECTORY_SEPARATOR, $embedPhpPath);

	/* Verify path */
	list ($ok, $errorString) = @G2EmbedDiscoveryUtilities::isFileReadable($embedPhpPath);
	if (!$ok) {
	    return array(false, null, $errorString);
	} else {
	    return array(true, $embedPhpPath, null);
	}
    }

    /**
     * Helper function for normalizeG2Uri and normalizeEmbedUri
     *
     * @access private
     */
    function _normalizeUri($uri) {
	$uri = trim($uri);
	if (empty($uri)) {
	    return array('', '/', '', '', '');
	}
	$schema = $host = $schemaAndHost = $path = $file = '';
	$fragment = $queryString = '';
	
	/* Normalize path separators */
	$uri = str_replace("\\", '/', $uri);
	
	/*
	 * With schema (http://) -> easy to identify host
	 * A single slash:
	 *     www.example.com/
	 *     www.example.com/gallery2
	 *     www.example.com/index.php
	 *     gallery2/
	 *     /
	 *     /gallery2
	 *     /index.php
	 *     gallery2/index.php
	 * Multiple slashes:
	 *     www.example.com/gallery2/
	 *     /gallery2/
	 *     ....
	 * Problem: Differentiate between host, path and file
	 *   @files: .php|.html? is recognized as file
	 *   @host:  (?:\w+:\w+@)[\w\.]*\w+\.\w{2-4}(?::\d+) is most likely a host string
	 *           localhost or other host strings without a dot are impossible to
	 *           differentiate from path names ->only http://localhost accepted
	 *   @path:  everything that is not a file or a host
	 */

	/* Remove fragment / query string */
	if (($pos = strpos($uri, '#')) !== false) {
	    $fragment = substr($uri, $pos);
	    $uri = substr($uri, 0, $pos);
	}
	if (($pos = strpos($uri, '?')) !== false) {
	    $queryString = substr($uri, $pos);
	    $uri = substr($uri, 0, $pos);
	}

	/* Handle and remove file part */
	if (preg_match('{(.*/)?([\w\.]+\.(?:php|html?))$}i', $uri, $matches)) {
	    $uri = empty($matches[1]) ? '/' : $matches[1];
	    $file = $matches[2];
	}
		
	/* Get the schema and host for absolute URLs */
	if (preg_match('{^(https?://)([^/]+)(.*)$}i', $uri, $matches)) {
	    $schema = strtolower($matches[1]);
	    $host = $matches[2];
	    $schemaAndHost = $schema . $host;
	    $uri = empty($matches[3]) ? '/' : $matches[3];
	    $uri = $uri{0} != '/' ? '/' . $uri : $uri;
	} else {
	    /* Get the host string, e.g. from www.example.com/foo or www.example.com */
	    if (preg_match('{^((?:\w+:\w+@)?[\w\.]*\w+\.\w+(?::\d+)?)(.*)$}', $uri, $matches)) {
		$host = $matches[1];
		$schema = 'http://';
		if ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		    $schema = 'https://';
		}
		$schemaAndHost = $schema . $host;;
		$uri = empty($matches[2]) ? '/' : $matches[2];
		$uri = $uri{0} != '/' ? '/' . $uri : $uri;
	    }
	}

	/* Add leading / trailing slash to path */
	$path = $uri{0} != '/' ? '/' . $uri : $uri;
	$path .= substr($path, -1) != '/' ? '/' : '';

	return array($schemaAndHost, $path, $file, $queryString, $fragment);
    }
    
    function isFileReadable($path) {
	if (@file_exists($path) && @is_readable($path)) {
	    return array(true, null);
	} else if (@G2EmbedDiscoveryUtilities::_isRestrictedByOpenBaseDir($path)) {
	    return array(false, "file $path is restricted by PHP open_basedir");
	} else if (@file_exists($path) && !@is_readable($path)) {
	    return array(false, "file $path exists but is not readable");
	} else {
	    return array(false, "file $path does not exist");
	}
    }
    
    /**
     * Return true if the path provided is not allowed by the current open_basedir configuration.
     *
     * Copied from GalleryPlatform and adjusted to be independent of the G2 framework
     *
     * @return true if the path is restricted
     */
    function _isRestrictedByOpenBaseDir($path) {
	$slash = DIRECTORY_SEPARATOR;
	if (!strncasecmp(PHP_OS, 'win', 3)) {
	    $separator = ';';
	    $caseSensitive = false;
	} else {
	    $separator = ':';
	    $caseSensitive = true;
	}

	$openBasedir = @ini_get('open_basedir');
	if (empty($openBasedir)) {
	    return false;
	}

	if (($realpath = realpath($path)) === false) {
	    /*
	     * PHP's open_basedir will actually take an invalid path, resolve relative
	     * paths, parse out .. and . and then check against the dir list..
	     * Here we do an ok job of doing the same, though it isn't perfect.
	     */
	    $s = '\\\/';  /* do this by hand because preg_quote() isn't reliable */
	    if (!preg_match("{^([a-z]+:)?[$s]}i", $path)) {
		$path = getcwd() . $slash . $path;
	    }
	    for ($realpath = $path, $lastpath = ''; $realpath != $lastpath;) {
		$realpath = preg_replace("#[$s]\.([$s]|\$)#", $slash, $lastpath = $realpath);
	    }

	    for ($lastpath = ''; $realpath != $lastpath;) {
		$realpath = preg_replace("#[$s][^$s]+[$s]\.\.([$s]|\$)#",
					 $slash, $lastpath = $realpath);
	    }
	}

	$function = $caseSensitive ? 'strncmp' : 'strncasecmp';
	foreach (explode($separator, $openBasedir) as $baseDir) {
	    if (($baseDirMatch = realpath($baseDir)) === false) {
		$baseDirMatch = $baseDir;
	    } else if ($baseDir{strlen($baseDir)-1} == $slash) {
		/* Realpath will remove a trailing slash.. add it back to avoid prefix match */
		$baseDirMatch .= $slash;
	    }
	    /* Add slash on path so /dir is accepted if /dir/ is a valid basedir */
	    if (!$function($baseDirMatch, $realpath . $slash, strlen($baseDirMatch))) {
		return false;
	    }
	}

	return true;
    }
    
    /**
     * Split a URI string into file, path, host and protocol substrings and normalize them
     *
     * Used to allow overriding auto-detected protocol, host, path and default base file
     * Also used to accept various embedUri formats 
     *
     * @param string URI
     * @return array $protocol, $host, $path, $file (incl. query string)
     * @access privat
     */
    function parseUri($uri) {
	/*
	 * baseUri / g2Uri can have the following patterns:
	 *    - everything between the two last '/' is interpreted as path
	 *    - thus URI with leading '/' are inerpreted as path (+ optional file)
	 *    - if there is a '/' and no http://, everything before the first '/' is
	 *      interpreted as host string
	 *    - if there it starts with http://, everything after it up to the first '/'
	 *      is interpreted as host string
	 *    - everything after the last '/' is interpreted as file + query string
	 * Examples of allowed URI strings:
	 *    http://www.example.com
	 *    http://example.com/gallery2/
	 *    https://127.0.0.1/gallery2/main.php
	 *    www.example.com/main.php
	 *    /gallery2/
	 *    /gallery2/main.php 
	 *    /main.php
	 *    main.php
	 * Note:
	 *    www.example.com (no '/' -> interpreted as file and not as host)
	 *    main.php/ (everything before the last '/' is interpreted as path)
	 */
	$file = $uri;
	$path = null;
	$host = null;
	$protocol = null;
	if (strpos($file, '/') !== false) {
	    /* Check if it's a URL including host and optional protocol part */
	    if (preg_match('{^(?:(https?)://)?([^/]+)(.*)$}', $file, $matches)) {
		$protocol = $matches[1];
		$host = $matches[2];
		$file = $matches[3];
	    }
	    /* $file = '/...', '/.../', '/.../...' or '...' '*/
	    if (preg_match('{^(/(?:.*/)?)?([^/]*)$}', $file, $matches)) {
		$path = $matches[1];
		$file = $matches[2];
	    }
 	}
	
	return array($protocol, $host, $path, $file);
    }
}
?>
