<?php
/**
 * Purpose of file:  G2 wrapper
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage gallery2 Module
 * @author Andy Staudacher <ast@gmx.ch>
 */

/*
 * NOTE: xaraya users have a unique "uname", xaraya groups don't have
 *       necesserally a "uname", but a unique "name", unique among all groups
 */
 
/*
 * NOTE: xaraya manages users/groups differently than most applications.
 *       A group can be a subgroup of another group and all roles (users, groups)
 *       are managed in a tree structure. So if you want to know if a user Y is in
 *       group X, you'll have to fetch the whole sub-tree above user Y. Etc, etc.
 *       
 *       To cut a long story short: Most applications have a "flat" user, group 
 *       map and don't manage a tree. If you want to integrate G2 with such 
 *       a "normal" application, you will end up with a lot less code.
 */

/**
 * xarGallery2Helper: class for the G2 wrapper functionality
 *
 * Use this class only statically !
 * Most class methods are there for convenience. i.e. for
 * error handling (get G2 error html and put it in xaraya exceptions)
 * During the development of this module, I had to request a lot of 
 * small changes in the xaraya roles module. Some of them are not implemented yet
 * and to minimize the changes I'll have to do, I've created some xaraya wrapper
 * methods which work around some limitations.
 *
 * @author Andy Staudacher <ast@gmx.ch>
 * @access public
 * @throws none
 * @static
 */
class xarGallery2Helper
{
  
  /**
   * isConfigured: check if the module has been configured.
   *
   * Returns true or false dependent on whether the gallery2
   * module is ready for production or not
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true or false
   */
  function isConfigured($value = null)
  {
    static $configured;
		if (isset($value) && (is_bool($value) || is_int($value))) {
		  xarModSetVar('gallery2','configured', (int)$value);
		  $configured = $value;
		}
		if (!isset($configured)) {
		  $configured = xarModGetVar('gallery2','configured');
		  if (!isset($configured) || !$configured) {
		    $configured = false;
		  } else {
		    $configured = true;
		  }
		}
		return $configured;
  }
  
  /**
   * isInitiated: static variable.
   *
   * True if init() was called, else false
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true or false
   */
  function isInitiated($newvalue = null)
  {
    static $initiated;
    if (!isset($initiated)) {
      $initiated = false;
    }
    if (isset($newvalue) && (is_bool($newvalue) || is_int($newvalue))) {
      $initiated = $newvalue;
    }
    return $initiated;
  }
  
  /**
   * init: call to GalleryEmbed::init()
   *
   * Wrapper function to ease the init call
   * and the error handling
   *
   * @author Andy Staudacher
   * @access public
   * @param bool whether to try to init even if 
   *        the module is not already configured
   * @return bool true on success or false
   * @throws Systemexception if it failed
     */
  function init($ignoreConfiguration = false, $initAsUser = false, $fullInit = true)
  {	
    // only init if not already done so
    if (xarGallery2Helper::isInitiated()) {
      return true;
    }
    
    if(!$ignoreConfiguration && !xarGallery2Helper::isConfigured()) {
      return false;
    }
    require_once(xarModGetVar('gallery2','g2.includepath') . 'embed.php');
    
    $g2LangCode = null; $uid = null;
    // only do the whole uid / language stuff if told so
    if ($initAsUser) {
      $xarLangCode = xarMLSGetCurrentLocale();
      $uid = xarUserGetVar('uid');
      if ($uid == _XAR_ID_UNREGISTERED) {
	$xarDefaultLangCode = xarConfigGetVar('Site.MLS.DefaultLocale'); 
	if ($xarDefaultLangCode == $xarLangCode) {
	  $xarLangCode = null;
	}
	$uid = '';
      } else { // non anonymous, registered user
	$xarDefaultLangCode = xarUserGetVar('locale');
	if ($xarDefaultLangCode == $xarLangCode) {
	  $xarLangCode == null;
	}
      }
      // translate language code to G2 langCode format 
      if (isset($xarLangCode) && !empty($xarLangCode)) {
	$g2LangCode = preg_replace('|(\..*)?$|', '', $xarLangCode);
      } 
    }

    // initiate G2 
    $ret = GalleryEmbed::init(array('embedUri' => xarModGetVar('gallery2','g2.basefile'),
				    'embedPath' => xarServerGetBaseURI(),
				    'relativeG2Path' => xarModGetVar('gallery2','g2.relativeurl'),
				    'loginRedirect' => xarModGetVar('gallery2','g2.loginredirect'),
				    'activeUserId' => $uid, 'activeLanguage' => $g2LangCode,
				    'fullInit' => $fullInit));

    if (!$ret->isSuccess()) {
      $msg = xarML('G2 did not return a success status upon an init request. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    xarGallery2Helper::isInitiated(true);
    return true;
  }
  
  /**
   * done: call to GalleryEmbed::done()
   *
   * complete the G2 transaction
   * Wrapper function to ease the done call
   * and the error handling
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function done()
  { 
    register_shutdown_function(array('xarGallery2Helper', 'g2done'));
  }

  function g2done()
  {
    // only end transactions if there's something initiated
    if (!xarGallery2Helper::isInitiated()) {
      return;
    }
    $ret = GalleryEmbed::done();
    if (!$ret->isSuccess()) {
      $msg = xarML('Could not complete the G2 transaction. Here is the error message from G2: <br /> [#(1)]', 
		   $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return;
    }
    xarGallery2Helper::isInitiated(false);
    return;
  }
  
  /**
   * g2createUser: create G2 user from xar role
   *
   * wrapper for GalleryEmbed::createUser()
   * $args is an array, not an object
   *
   * @author Andy Staudacher
   * @access public
   * @param integer user id
   * @param array userdata (name is required, rest is optional)
   *              ('uname' => string, 'name' => string, 'uid' => integer
   *				'email' => string, 'cryptpass' => string, 'date_reg' => integer)
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function g2createUser($uid, $args) 
  {
    // init if not already done so
    if (!xarGallery2Helper::init()) {
      return false;
    }
    // 1. create G2 user
    $args = xarGallery2Helper::translateXarUserAttributesToG2($uid, $args);		
    $ret = GalleryEmbed::createUser($uid, $args);
    if (!$ret->isSuccess()) {
      $msg = xarML('Failed to create G2 user with extId [#(1)]. Here is the error message from G2: <br /> [#(2)]', $uid,$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));	
      return false;
    }
    // 2. add user to groups
    /* First reset default G2 group memberships */
    list ($ret, $g2User) = xarGallery2Helper::g2loadEntityByExternalId($uid, 'GalleryUser');
    if (!$ret) {
      return false;
    }	
    $ret = GalleryCoreApi::removeUserFromAllGroups($g2User->getId());
    if ($ret->isError()) {
      $msg = xarML('Failed to remove user from all G2 groups. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    /* Then add the user to the xaraya groups */
    list($thisRole, $xarGroups) = xarGallery2Helper::xarGetAncestors(array('uname' => $args['uname'])); // an ancestor is a group per se
    if ($thisRole == null) return false;
    /*
     * If this function was called by the create user hook, then at this point, the xaraya role 
     * is not member of any group, because it is first created and then added to the default group.
     * And the modules/roles/xaruser/register.php bypasses all addmember hooks calls, sadly.
     * Thus, we have to get the defaultGroup here first and do the rest.
     *
     * The roles/xaradmin/addrole.php allows to specify a non default group as initial membership
     * but luckily it creates the membership before calling the create hooks.
     */ 
    /* ..and...*/
    /*
     * If a user was created in the admin->roles->add role interface, we probably have to add
     * it manually to the all users group in G2
     * (admins are not in the user group in xaraya*, but in G2) 
     */
     // get xaraya admin group id
    $defaultGroup = xarModAPIFunc('roles','user','get'
				  , array('name' => xarModGetVar('roles', 'defaultgroup'), 'type' => 1));
    $xarGroups[] = $defaultGroup;
    // Finally create the memberships in G2
    foreach ($xarGroups as $xarGroup) {			
      $ret = GalleryEmbed::addUserToGroup($uid, $xarGroup['uid']);
      if ($ret->isError()) {
	$msg = xarML('Failed to add g2 user  with extid [#(1)] to g2 group with extid [#(2)], uname [#(3)]. In total, we tried to add
						the user to [#(4)] groups. Here is the error message from G2: <br /> [#(5)]',
		     $uid,  $xarGroup['uid'], $xarGroup['uname'], count($xarGroups), $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
    }
    return true;
  }
	
  /**
   * translateXarUserAttributesToG2: translate user attributes
   *
   * translates uname to username, name to fullname, etc.
   *
   * @author Andy Staudacher
   * @access public
   * @param integer user id
   * @param array('uname' => string, 'name' => string, 'uid' => integer
   *				'email' => string, 'cryptpass' => string, 'pass' => string,
   *              'date_reg' => integer)
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function translateXarUserAttributesToG2($uid, $args, $update = false)
  {
    // 1. create user in G2
    if (isset($args['name'])) {
      $args['fullname'] = $args['name'];
    }
    if (isset($args['uname'])) {
      if (!$update || $update && (!isset($args['old.uname'])
				  || $args['uname'] != $args['old.uname'])) {
	$args['username'] = $args['uname'];
      }
    }
    // Get the hashed password
    if (!isset($args['cryptpass']) && !isset($args['pass'])) {
      $role = xarModAPIFunc('roles','user','get', array('uid' => $uid));
      $args['cryptpass'] = $args['pass'];
    }

    if (isset($args['cryptpass']) || isset($args['pass'])) {
      $args['hashedpassword'] = isset($args['cryptpass']) ? $args['cryptpass'] : $args['pass'];
      $args['hashmethod'] = 'md5';
    }
    
    if (isset($args['date_reg'])) {
      $args['creationtimestamp'] = $args['date_reg'];
    }
    
    if ($uid != _XAR_ID_UNREGISTERED) {
      $xarLangCode = xarUserGetVar('locale', $uid);
    }
    if (isset($xarLangCode) && !empty($xarLangCode)) {
			$args['language'] = xarGallery2Helper::xartranslateLanguageCode($xarLangCode);
    }
    
    return $args;
  }
  
  /**
   * g2updateUser: update G2 user from xar role
   *
   * wrapper for GalleryEmbed::updateUser()
   *
   * @author Andy Staudacher
   * @access public
   * @param integer user id
   * @param array('uname' => string, 'name' => string, 'uid' => integer
   *				'email' => string, 'cryptpass' => string, 'date_reg' => integer)
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function g2updateUser($uid, $args) 
  {
    // init if not already done so
    if (!xarGallery2Helper::init()) {
      return false;
    }
    $args = xarGallery2Helper::translateXarUserAttributesToG2($uid, $args, true);
    $ret = GalleryEmbed::updateUser($uid, $args);
    if (!$ret->isSuccess()) {
      $msg = xarML('Failed to update G2 user with extId [#(1)]. Here is the error message from G2: <br /> [#(2)]', $uid, $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));	
      return false;
    }
    return true;
  }
  
  /**
   * g2createGroup: create G2 group from xar role
   *
   * wrapper for GalleryEmbed::createGroup()
   * $args is an array, not an object
   *
   * @author Andy Staudacher
   * @access public
   * @param integer uid
   * @param array('uname' => string, 'name' => string, 'uid' => integer)
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function g2createGroup($uid, $args) 
  {
    // init if not already done so
    if (!xarGallery2Helper::init()) {
      return false;
    }
    $name = isset($args['name']) ? $args['name'] : $args['uname'];
    $ret = GalleryEmbed::createGroup($uid, $name);
    if (!$ret->isSuccess()) {
      $msg = xarML('Failed to create G2 group with extId [#(1)]. Here is the error message from G2: <br /> [#(2)]', $uid,$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));	
      return false;
    }
    return true;
  }
  
  /**
   * g2updateGroup: update G2 group from xar role
   *
   * wrapper for GalleryEmbed::updateGroup()
   * $args is an array, not an object
   *
   * @author Andy Staudacher
   * @access public
   * @param integer uid
   * @param array('uname' => string, 'name' => string)
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function g2updateGroup($uid, $args) 
  {
    // init if not already done so
    if (!xarGallery2Helper::init()) {
      return false;
    }
    if (!isset($args['uname']) && !isset($args['name'])) {
      $msg = xarML('g2updateGroup call without name/uname parameter!');
      xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      return false;
    }
    $newname = isset($args['name']) ? $args['name'] : $args['uname'];
    $ret = GalleryEmbed::updateGroup($uid, array('groupname' => $newname));
    if (!$ret->isSuccess()) {
      $msg = xarML('Failed to update G2 group with extId [#(1)]. Here is the error message from G2: <br /> [#(2)]', $uid,$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));	
      return false;
    }
    return true;
  }
  
  /**
   * g2login: login in G2
   *
   * wrapper for GalleryEmbed::login()
   * handles errors
   *
   * @author Andy Staudacher
   * @access public
   * @param integer uid
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function g2login($uid)
  {
    // init if not already done so
    if (!xarGallery2Helper::init()) {
      return false;
    }
    $ret = GalleryEmbed::login($uid);
    if ($ret->isError()) {
      $msg = xarML('Could not login user with extId [#(1)] in G2. Here is the error message from G2: <br /> [#(2)]', $uid,$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    return true;
  }
  
  /**
   * g2logout: logout from G2
   *
   * wrapper for GalleryEmbed::logout()
   * handles errors
   *
   * @author Andy Staudacher
   * @access public
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function g2logout()
  {
    // init if not already done so
    if (!xarGallery2Helper::init()) {
      return false;
    }
    $ret = GalleryEmbed::logout();
    if ($ret->isError()) {
      $msg = xarML('Failed to logout from G2 after sync process. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_USER_EXCEPTION, 'FUNCTION_FAILED', new DefaultUserException($msg));
      return false;
    }
    return true;	
  }
  
  /**
   * g2setSiteDefaultLanguage: synchronize site default language
   *
   * synchronizes the xaraya site wide default language to G2
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool success status
   */
  function g2setSiteDefaultLanguage()
  {
    // init G2 transaction, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return array(false, null);
    }
    global $gallery;
    $session =& $gallery->getSession();
    // the new lang code isn't available through the cached xarMLSGetSiteLocale() at this time
    $xarLangCode = xarConfigGetVar('Site.MLS.DefaultLocale'); 
    if (isset($xarLangCode) && !empty($xarLangCode)) {
      $xarLangCodeShort = xarGallery2Helper::xartranslateLanguageCode($xarLangCode);
      list ($g2languageCode) = GalleryTranslator::getSupportedLanguageCode($xarLangCodeShort);
      if (empty($g2languageCode)) {
	// can't help it, return true
	return true;
      }
      // got to update the parameter
      $pluginParameter = 'default.language';
      $ret = GalleryCoreApi::setPluginParameter('module', 'core', $pluginParameter, $g2languageCode);
      if ($ret->isError()) {
	$msg = xarML('Failed to update G2 site default language parameter [#(1)] to new value [#(2)]. Here is the error message from G2: <br /> [#(3)]',
		     $pluginParameter, $g2languageCode,$ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
    }
    return true;
  }
  
  /**
   * xartranslateLanguageCode: translate Xar lang code to G2
   *
   * translates xar language code to possible G2 lang code
   *
   * @author Andy Staudacher
   * @access public
   * @param string the language code
   * @return string the possible G2 lang code
   */
  function xartranslateLanguageCode($languageCode)
  {
    return preg_replace('|(\..*)?$|', '', $languageCode);
  }
  
  /**
   * g2loadEntityByExternalId: load G2 user/group by xar_uid
   *
   * Wrapper for GalleryCoreApi::loadEntityByExternalId
   * handles error messages and makes the entityType parameter 
   * optional
   *
   * @author Andy Staudacher
   * @access public
   * @param integer user id
   * @param string the G2 entityType (optional)
   * @return array (bool success, G2 entity)
   * @throws Systemexception if it failed
   */
  function g2loadEntityByExternalId($externalId, $entityType=null, $raiseError=true)
  {
    // init if not already done so
    if (!xarGallery2Helper::init(true)) {
      return array(false, null);
    }
    if ($entityType == null || empty($entityType))  {
      list($ret, $entityType) = xarGallery2Helper::g2getentitytypebyexternalid($externalId,$raiseError);
      if (!$ret) {
	return array(false, null);
      }
    }
    
    list ($ret, $g2role) = GalleryCoreApi::loadEntityByExternalId($externalId, $entityType);
    if ($ret->isError()) {
      if ($raiseError) {
	$msg = xarML('Failed to load G2 user/group by extId [#(1)] and entityType [#(2)].'.
		   'Here is the error message from G2: <br /> [#(3)]', $externalId, $entityType, $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, null);
    }	
    return array(true, $g2role);
  }
  
  
  /**
   * g2addexternalMapEntry: add an externalId map entry
   *
   * Add an entry in the G externalId, entityId map table
   *
   * @author Andy Staudacher
   * @access public
   * @param integer the uid
   * @param integer the entityId from G2
   * @param integer/string the roles type, 1 for groups, 0 for users, or the entityType string
   * @return bool true or false
   */
  function g2addexternalMapEntry($externalId, $entityId, $entityType)
  {
    // init G2 transaction, load G2 API, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return false;
    }		
    if (is_int($entityType)) {
      $entityType = $entityType == 0 ? 'GalleryUser' : 'GalleryGroup';
    }
    $ret = GalleryEmbed::addExternalIdMapEntry($externalId, $entityId, $entityType);

    if ($ret->isError()) {
      $msg = xarML('Failed to create a extmap entry for role uid [#(1)] and entityId [#(2)], entityType [#(3)]. Here is the error message from G2: <br />
				[#(4)]',$externalId, $entityId, $entityType, $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    return true;
  }
  
  /**
   * g2getentitytypebyexternalid: get G2 entityType by extId
   *
   * Get the entityType for a G2 entity by the mapped externalId
   *
   * @author Andy Staudacher
   * @access public
   * @param integer the uid
   * @return array(bool success, string G2 entityType)
   */
  function g2getentitytypebyexternalid($externalId, $raiseError=true)
  {	
    // init G2 transaction, load G2 API, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return array(false, null);
    }		
    global $gallery;
    
    $query = 'SELECT [ExternalIdMap::entityType]
		FROM [ExternalIdMap]
		WHERE [ExternalIdMap::externalId] = ?';
    
    list ($ret, $results) = $gallery->search($query, array($externalId));
    if ($ret->isError()) {
      if ($raiseError) {
	$msg = xarML('Failed to fetch the entityType for extId [#(1)]. Here is the error message from G2: <br /> [#(2)]',$externalId, $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, null);
    }
    if (!($result = $results->nextResult())) {
      if ($raiseError) {
	$msg = xarML('Failed to fetch the entityType for extId [#(1)]. There was no entry in the table. Here is the error message from G2: <br /> [#(2)]',$externalId, $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, null);
    }
    
    return array(true, $result[0]);
  }
  
  /**
   * g2getallexternalIdmappings: get all extId, entityId mappings
   *
   * get all extId, entityId mappings from G2
   * useful, i.e. for import/export synchronization update
   * used only by the import/export method
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return array(bool success, array(entityId => array(externalId => integer,
   *                             entityType => string, entityId => integer)))
   * @throws Systemexception if it failed
   */
  function g2getallexternalIdmappings($key)
  {
    // init G2 transaction, load G2 API, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return array(false,null);
    }		
    
    list($ret, $map) = GalleryEmbed::getExternalIdMap($key);
    if ($ret->isError()) {
      $msg = xarML('Failed to fetch a list of all extId maps fromG2. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return array(false, null); 
    }
    
    return array(true, $map);	
  }
  
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
      if ($ret->isError()) {
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
      if ($ret->isError()) {
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
    list($ret, $g2pendingusers) = xarGallery2Helper::g2getPendingUsers();
    if (!$ret) { return false; }
    
    // init G2 transaction, if not already done so
    if (!xarGallery2Helper::init(true)) {
      return false;
    }
    
    // delete the pending users from G2
    foreach (array_keys($g2pendingusers) as $g2pendinguserId) {
      $ret = GalleryCoreApi::deleteEntityById($g2pendinguserId);
      if ($ret->isError()) {
	$msg = xarML('Failed to delete G2 pending user. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }					
    }
    return true;
  }
  
  /**
   * verifyConfig: verify the configuration
   *
   * Verify the xaraya configuration parameters
   * and the G2 status.
   *
   * @author Andy Staudacher
   * @access public
   * @param bool if true, we throw exceptions, else 
   *             we return the error value.
   * @param bool if true, we set the configuration
   *             if it was verified successfully.
   * @param string [optional] the relative path from the xaraya url to the G2 url
   * @param string [optional] the path from to the G2 folder
   * @return array(bool true on success, error message)
   * @throws Systemexception if it failed
   */
  function verifyConfig($raiseexceptions = false, $setifcorrect = false, 
			$g2RelativeUrl = null, $g2IncludePath = null )
  {
/* obviously, the snapshot versions of xaraya don't follow the version dot
 * subversion pattern, therefore this check is omitted

    // Verify the dependency on a compatible xaraya version (roles module changes, event system changes)
    $xarVersionString = xarConfigGetVar('System.Core.VersionNum');
    $xarVersion = split('\.', $xarVersionString);
    $minXarVersionString = xarmodGetVar('gallery2', 'xar.minCoreVersion');
    $minXarVersion = split('\.', $minXarVersionString);
    if ($xarVersion[1] < $minXarVersion[1] || $xarVersion[2] < $minXarVersion[2]) { // min version nr is 0.9.11
      $msg = xarML('Your xaraya version is not compatible with this module. Your version is [#(1)], the minimum
            version number required is #[2]. Please upgrade your xaraya installation.', $xarVersionString, $minXarVersionString);
      if ($raiseexceptions) {
	xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      }
      return array(false, $msg);
    }
*/
    
    // Verify that we find a G2 installation
    // and that it is configured to be in embedded mode
    // the filesystem include path
    if (!isset($g2IncludePath) || empty($g2IncludePath)) {
      if (isset($g2RelativeUrl)) {
	// in php CGI, SCRIPT_FILENAME isn't what we need. But sometimes PATH_TRANSLATED isn't defined either.
	$g2IncludePath = realpath(dirname($_SERVER['PATH_TRANSLATED']) . '/' . $g2RelativeUrl) . '/';
      } else {
	$g2IncludePath = xarModGetVar('gallery2','g2.includepath');
      }
    } 
    // else = different paths for url and filesystem
   
    // the relative url
    if (!isset($g2RelativeUrl)) {
      $g2RelativeUrl = xarModGetVar('gallery2','g2.relativeurl');
    }
   
    if ($setifcorrect) {
      // get the name of the xaraya entry point file (usually index.php)
      $scriptName = preg_replace('|/([^/]+/)*|', '', $_SERVER['PATH_TRANSLATED']);
      
      // enable short url enabled?
      $shortUrlActive = xarConfigGetVar('Site.Core.EnableShortURLsSupport'); 
      if (isset($shortUrlActive) && $shortUrlActive) {
	$g2basefile = $scriptName .'/gallery2';
      } else {			
	$g2basefile = $scriptName .'?module=gallery2';
      }
      $g2loginredirect = $scriptName .'?module=roles&func=register';
    } else {
      $g2loginredirect = xarModGetVar('gallery2','g2.loginredirect');
      $g2basefile = xarModGetVar('gallery2','g2.basefile');
    }
   
    // return if the path is wrong or G2 is not installed
    if (!file_exists($g2IncludePath . 'embed.php')) {
      $msg = xarML('I could not find a G2 installation at "[#(1)]"! Please correct the path!', $g2IncludePath);
      if ($raiseexceptions) {
	xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      }
      return array(false, $msg);
    }

    require_once($g2IncludePath . 'embed.php');
    $ret = GalleryEmbed::init( array('embedUri' => $g2basefile,
				     'relativeG2Path' => $g2RelativeUrl,
				     'loginRedirect' => $g2loginredirect));
    if (!$ret->isSuccess()) {
      $msg = xarML('G2 did not return a success status upon an init request. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      if ($raiseexceptions) {
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, $msg);
    }
    
    xarGallery2Helper::isInitiated(true);

    // ok, G2 is installed, the path is correct, now check if G2 is in embedded mode
    global $gallery;
    
    if (!xarGallery2Helper::isConfigured() && !$gallery->getConfig('mode.embed.only')) {
      $msg = xarML("G2 is not in embedded mode! Please set gallery->setConfig('mode.embed.only', true); in config.php of G2.");
      if ($raiseexceptions) {
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, $msg);
    }

    /* Get the current G2 core version */
    list ($ret, $g2VersionString) = GalleryCoreApi::getPluginParameter('module', 'core', '_version');
    if ($ret->isError()) {
      $msg = xarML('G2 returned an error: [#(1)]', $ret->getAsHtml());
      if ($raiseexceptions) {
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      }
      return array(false, $msg);
    }

    $g2Version = split('\.', $g2VersionString);
    $g2VersionNum = 100*$g2Version[0] + 10*$g2Version[1] + $g2Version[2];
    $minG2VersionString = xarmodGetvar('gallery2', 'g2.minCoreVersion');
    $minG2Version = split('\.', $minG2VersionString );
    $minG2VersionNum = 100*$minG2Version[0] + 10*$minG2Version[1] + $minG2Version[2];
    // FIXME: before packaging and releasing the module
    if ($minG2VersionNum > $g2VersionNum) {
      $msg = xarML('Your G2 version is not compatible with this module. Your version is [#(1)], the minimum
            version number required is #(2). Please upgrade your G2 installation.', $g2VersionString, $minG2VersionString);
      if ($raiseexceptions) {
	xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      }
      return array(false, $msg);
    }
    
    if ($setifcorrect) {
      // set the paths
      // relative url from CMS root dir to G2 root dir
      xarModSetVar('gallery2','g2.relativeurl', $g2RelativeUrl);
      // absolute include path to /some/path/Gallery/
      xarModSetVar('gallery2','g2.includepath', $g2IncludePath);
      // the login redirect url
      xarModSetVar('gallery2','g2.loginredirect',$g2loginredirect);
      // the G2 basefile
      xarModSetVar('gallery2','g2.basefile',$g2basefile);

 /*
  G2 short url support changed from PathInfo (compatible) to mod_rewrite.
  For now, G2 short urls can not be supported in G2 embedded. We have to
  Figure out a way to bring back short urls to embedded G2.
     
      // set short urls in G2 on/off
      if (isset($shortUrlActive) && (is_bool($shortUrlActive) || is_int($shortUrlActive))) {
	$pluginParameter = 'misc.useShortUrls';
	$shortUrlActive = $shortUrlActive ? 'true' : 'false';
	$ret = GalleryCoreApi::setPluginParameter('module', 'core', $pluginParameter, $shortUrlActive);
	if ($ret->isError()) {
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
   * xargetAllGroups: get a list of all xaraya groups
   *
   * works like xarGetGroups() but caches the result
   *
   * @author Andy Staudacher
   * @access public
   * @param boolean whether to update the list if it's already cached
   * @return array name => groupdata, representing all the groups
   */
  function xargetAllGroups($update = false)
  {
    static $xarAllGroups;
    if (!isset($xarAllGroups) || $update) {
      $xarAllGroups = xarGetGroups();
    }
    $returnarray = array();
    foreach ($xarAllGroups as $xarGroup) {
      $returnarray[strtolower($xarGroup['name'])] = $xarGroup;
    }
    return $returnarray;
  }
  
  /**
   * xargetAllUser: get a list of all xaraya user
   *
   * API call + cache
   *
   * @author Andy Staudacher
   * @access public
   * @param boolean whether to update the list if it's already cached
   * @return array uname => userdata, representing all the users
   */
  function xargetAllUsers($update = false)
  {
    static $xarAllUsers;
    if (!isset($xarAllUsers) || $update) {
      $xarAllUsers = xarmodapifunc('roles', 'user', 'getall', array());;
    }
    $returnarray = array();
    foreach ($xarAllUsers as $xarUser) {
      $returnarray[strtolower($xarUser['uname'])] = $xarUser;
    }
    return $returnarray;
  }
  
  /**
   * xarGetChildUsers: get all xar users for a group 
   *
   * Returns a list of all (recursive) child users 
   * for a xaraya group
   *
   * @author Andy Staudacher
   * @access public
   * @param integer the uid of the group
   * @return array of arrays representing all the users
   */
	function xarGetChildUsers($uid) 
  {
    return xarModAPIFunc('roles','user','getall', array('group' => $uid));
  }
  
  /**
   * xarGetAncestors: get all xar groups this role is a member of 
   *
   * Returns a list of all (recursive) groups
   * for a xaraya role
   * Specify at least one of the args to identify the role
   *
   * @author Andy Staudacher
   * @access public
   * @param array('uid' => integer the uid of the role, 'uname' => string the uname of the role
   *				'name' => string the name of the role
   * @return array (object the role you specified, array of role objects of the ancestors)
   */
  function xarGetAncestors($args) 
  {
    
    // for convience, we allow different calls of this function, handle that
    if (isset($args['uid'])) {
      // this is ridiculous, the role get function defaults to type =1 if none was specified
      $role = xarModAPIFunc('roles','user','get', array('uid' => $args['uid'], 'type' => 0));
      if (!isset($role['type']) || $role['uid'] != $args['uid']) {
	$role = xarModAPIFunc('roles','user','get', array('uid' => $args['uid'], 'type' => 1));
      }
    } elseif (isset($args['uname'])) {
      $role = xarModAPIFunc('roles','user','get', array('uname' => $args['uname'], 'type' => 0));
      if (!isset($role['type']) || $role['uname'] != $args['uname']) {
	$role = xarModAPIFunc('roles','user','get', array('uname' => $args['uname'],'type' => 1));
      }
    } elseif (isset($args['name'])) {
      $role = xarModAPIFunc('roles','user','get', array('name' => $args['name'], 'type' => 1));
      if (!isset($role['type']) || $role['name'] != $args['name']) {
	$role = xarModAPIFunc('roles','user','get', array('name' => $args['name'], 'type' => 0));
      }
    } else {
      $msg = xarML('xarGetAncestors call without uid, uname or name!');
      xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      return array(null, null);
    }
    if (!isset($role['uid']) || $role['uid'] == 0) {
      $msg = xarML('xarGetAncestors: could not fetch base role!');
      xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
      return array(null, null);
    }
    
    // now all the ancestors
    $ancestors = xarModAPIFunc('roles','user','getancestors', array('uid' => $role['uid']));
    
    $ancestorData = array();
    foreach ($ancestors as $ancestor) {
      $ancestorData[$ancestor['uid']] = $ancestor;
    }
    return array($role, $ancestorData);
  }
  
  /**
   * g2updateSpecialRole: update the special roles
   *
   * Call this method to synchronize the special roles
   * i.e. to set the userName/groupName in g2 to the 
   * user/group name that is used in xaraya
   * call this for:
   * Administrators -> admin group, Everybody -> Everybody group,
   * Users -> All Users group, anonymous -> Guest user
   *
   * task:
   * create defaultgroup, if needed, update parameter if needed
   * add or update map entry for admins, everybody, anonmyous and defaultgroup
   * update group/user data
   *		
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true on success, else false
   */
  function g2updateSpecialRoles() 
  {
    // init if not already done so
    if (!xarGallery2Helper::init(true)) {
      return false;
    }
    foreach(array(array('Everybody', 'id.everybodyGroup'), array('Administrators', 'id.adminGroup'),
		  array(xarModGetVar('roles', 'defaultgroup'), 'id.allUserGroup'),
		  array('anonymous', 'id.anonymousUser')) as $specialRole) {
      $pluginParameter = $specialRole[1];
      $xarName = $specialRole[0];
      
      // the xaraya defaultGroup can be any group, including everybody and adminstrators
      // per default, it's a third group, but you can configure it to be Everybody or Administrators
      if (!xarGallery2Helper::xarIsDefaultGroupAThirdGroup())  {
	continue; // we already sync this group
	// -> "All Users" group in G2 isn't any special group anymore			
      }
      
      // get G2 entity id for this group/user
      list ($ret, $id) = GalleryCoreApi::getPluginParameter('module', 'core', $pluginParameter);
      if ($ret->isError()) {
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
      
      // The group with the groupName = defaultGroup could already exist in G2
      // If this the case, fetch this G2 group, set 'id.allUserGroup' = g2group->getId()
      if ($pluginParameter == 'id.allUserGroup') {
	// Xaraya calls it's all users group "Users" by default, try both, Users and "All Users"
	foreach (array("Users", "All Users") as $defaultgroupname) {
	  list($ret, $g2role) = GalleryCoreApi::fetchGroupByGroupName($defaultgroupname);
	  if ($ret->isError()) {
	    if (!$ret->getErrorCode() & ERROR_MISSING_OBJECT) { 
	      // a real error, not good
	      $msg = xarML('Failed to fetch group by groupname [#(1)] from G2 in g2updateSpecialRoles!', $defaultgroupname);
	      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	      return false;
	    }
	    // try the other 
	  } else { break; }
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
	  if ($ret->isError()) {
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
      if ($ret->isError()) {
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
	  $msg = xarML('Failed to update special G2 group by extId [#(1)]. Here is the error message from G2: <br /> [#(2)]', $roleData['uid'],$ret->getAsHtml());
	  xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	  return false;
	}
      } else { // user (anonymous user)
	$msg = "muh";
	if (!xarGallery2Helper::g2updateUser($roleData['uid'], $roleData)) {
	  $msg = xarML('Failed to update special G2 user by extId [#(1)]. Here is the error message from G2: <br /> [#(2)]',$roleData['uid'], $ret->getAsHtml());
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
   * g2xarUserGroupImportExport: user and group Import/Export
   *
   * Imports G2 users & groups to xaraya and
   * exports all xaraya roles to G2.
   * Initial user/group management synchronization.
   *
   * The whole process is based on userNames and groupNames.
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true on success, else false
   * @throws Systemexception if it failed
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
    if (!$ret->isSuccess()) {
      $msg = xarML('Could not fetch G2 group names. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    
    // we need the entityId of the groups
    $g2Groups = array();
    foreach ($g2GroupNames as $g2GroupName) {
      // Load the object Object
      list($ret, $g2Group) = GalleryCoreApi::fetchGroupByGroupName($g2GroupName);
      if (!$ret->isSuccess()) {
	$msg = xarML('Could not fetch a G2 group object. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      $g2Groups[strtolower($g2GroupName)] = $g2Group;
    }
    
    // Load a list of all G2 userNames
    list($ret, $g2UserNames) = GalleryCoreApi::fetchUsernames();
    if (!$ret->isSuccess()) {
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
      if (!$ret->isSuccess()) {
	$msg = xarML('Could not fetch a G2 user object. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      // load the existing group memberships
      list($ret, $g2MemberShips) = GalleryCoreApi::fetchGroupsForUser($g2User->getId());
      if (!$ret->isSuccess()) {
	$msg = xarML('Could not fetch G2 groups for G2 user. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
	xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	return false;
      }
      $g2Users[strtolower($g2UserName)] = array('user' => $g2User, 'memberships' => $g2MemberShips);
    }

    // Load a list of all G2 pending users
    list($ret, $g2pendingusers) = xarGallery2Helper::g2getPendingUsers();
    if (!$ret) {
      return false;
    }
    
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
      if (xarGallery2Helper::in_array_cin($g2Group->getGroupName(), array_keys($xarGroups))) {
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
      if (!xarGallery2Helper::g2addexternalMapEntry($newRole['uid'], $g2Group->getgroupname(), 1)) {
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
      if (xarGallery2Helper::in_array_cin($xarGroup['name'], $g2GroupNames)) {
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
	if (!xarGallery2Helper::in_array_cin($g2UserName, array_keys($xarUsers))) {
	  // add user to xaraya if there wasn't such a user
	  // create xar user
	  // if the G2 user has no email, generate a dummy email
	  $userEmail = $g2User->getemail();
	  $userEmail =  empty($userEmail) ? 'dummyEmail@G2Integration.xyz' : $userEmail;
	  $uid = xarmodapifunc('roles','admin','create',
			       array('uname' => $g2UserName,
				     'realname' => $g2User->getfullName(), 'email' => $userEmail,
				     'cryptpass' => $g2User->gethashedPassword(), 'date' => $g2User->getcreationTimestamp(),
				     'state' => ROLES_STATE_ACTIVE, 'valcode' => xarModAPIFunc('roles', 'user', 'makepass')));
	  if (!isset($uid) || !is_int($uid) || $uid <= 1) {
	    $msg = xarML("Could not create a xar role for a G2 user '$g2UserName'.");
	    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
	    return false;
	  }
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
      if (isset($mapsbyentityid[$g2User->getId()]) || xarGallery2Helper::in_array_cin($g2UserName, array_keys($xarUsers))) {
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
	if (xarGallery2Helper::in_array_cin($xarUser['uname'], $g2UserNames)) {
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
	if ($ret->isError()) {
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
    if (!xarGallery2Helper::g2deletePendingUsers()) {
      return false;
    }
    
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

?>
