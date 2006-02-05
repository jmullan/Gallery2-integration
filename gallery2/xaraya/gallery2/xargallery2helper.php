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
    require_once(xarModGetVar('gallery2','g2.includepath'));
    
    $g2LangCode = null; $uid = null;
    // only do the whole uid / language stuff if told so
    if ($initAsUser) {
	// if anonymous user, set g2 activeUser to null
	// if language code = default, set it to null for g2
	// the language can only be different from default, if the user 
	// uses a different language for this session only
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
	    $g2LangCode = xarGallery2Helper::xartranslateLanguageCode($xarLangCode);
	} 
    }

    // initiate G2 
    $ret = GalleryEmbed::init(array('embedUri' => xarModGetVar('gallery2','embedUri'),
				    'g2Uri' => xarModGetVar('gallery2','g2Uri'),
				    'loginRedirect' => xarModGetVar('gallery2','g2.loginredirect'),
				    'activeUserId' => $uid, 'activeLanguage' => $g2LangCode,
				    'fullInit' => $fullInit,
				    'apiVersion' => unserialize(xarModGetVar('gallery2','embedApiVersion'))));

    if (!empty($ret)) {
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
   * Previously, done() did register a shutdown function g2done(), which did
   * the actual GalleryEmbed::done(). But these led to nested transactions with
   * nexted COMMIT; statements (xaraya/G2) and it didn't work (postgres).
   *
   * @author Andy Staudacher
   * @access public
   * @param none
   * @return bool true on success or false
   * @throws Systemexception if it failed
   */
  function done()
  { 
    // don't do it indirectly via a shutdownfunction. but keep the code for now
    // register_shutdown_function(array('xarGallery2Helper', 'g2done'));

    // only end transactions if there's something initiated
    if (!xarGallery2Helper::isInitiated()) {
      return true;
    }
    $ret = GalleryEmbed::done();
    if ($ret) {
      $msg = xarML('Could not complete the G2 transaction. Here is the error message from G2: <br /> [#(1)]', 
		   $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    return true;
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
    if (!empty($ret)) {
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
    if ($ret) {
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
      if ($ret) {
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
    if (!empty($ret)) {
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
    if (!empty($ret)) {
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
    if (!empty($ret)) {
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
    if ($ret) {
      $msg = xarML('Could not login user with extId [#(1)] in G2. Here is the error message from G2: <br /> [#(2)]', $uid,$ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return false;
    }
    return xarGallery2Helper::done();
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
    if(!xarGallery2Helper::isConfigured()) {
        return true;
    }
    require_once(xarModGetVar('gallery2','g2.includepath'));
    $ret = GalleryEmbed::logout(array('embedUri' => xarGallery2Helper::xarServerGetBaseURI()));
    if ($ret) {
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
      if ($ret) {
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
    if ($ret) {
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

    if ($ret) {
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
    if ($ret) {
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
    if ($ret) {
      $msg = xarML('Failed to fetch a list of all extId maps fromG2. Here is the error message from G2: <br /> [#(1)]', $ret->getAsHtml());
      xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', new SystemException($msg));
      return array(false, null); 
    }
    
    return array(true, $map);	
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
   * @param string [optional] the g2Uri (see GalleryEmbed::init() for docs)
   * @param string [optional] the embedUri (URL to embedded G2), specify to override the auto-detection
   * @param string [optional] the absolute filesystem path to the G2 folder
   * @return array(bool true on success, error message)
   * @throws Systemexception if it failed
   */
  function verifyConfig($raiseexceptions=false, $setifcorrect=false, $g2Uri=null, $embedUri=null, $g2IncludePath=null) {
    require_once(dirname(__FILE__) . '/xargallery2helper_advanced.php');
    return xarGallery2Helper_Advanced::verifyConfig($raiseexceptions, $setifcorrect, $g2Uri, $embedUri, $g2IncludePath);
  }

  /**
   * detectEmbedUri: auto-detect the embedUri (URL to embedded G2)
   *
   * @author Andy Staudacher
   * @access public
   * @return string embedUri
   */
  function getDetectedEmbedUri() {
    require_once(dirname(__FILE__) . '/xargallery2helper_advanced.php');
    return xarGallery2Helper_Advanced::getDetectedEmbedUri();
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
   * xarServerGetBaseURI() wrapper for G2 embedPath compatibility
   *
   * xarServerGetBaseURI() can be empty, in this case set it to /
   *
   * @author Andy Staudacher
   * @access public
   * @return string fixed xarServerGetBaseURI()
   */
  function xarServerGetBaseURI()
  {
    $path = xarServerGetBaseURI();
    $length = strlen($path);
    if ($length == 0 || $path{$length-1} != '/') {
	$path .= '/';
    }
    return $path;
  }
  
  /**
   * g2updateSpecialRoles: update the special roles
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
    require_once(dirname(__FILE__) . '/xargallery2helper_advanced.php');
    return xarGallery2Helper_Advanced::g2updateSpecialRoles();
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
    require_once(dirname(__FILE__) . '/xargallery2helper_advanced.php');
    return xarGallery2Helper_Advanced::g2xarUserGroupImportExport();
  }
}

?>
