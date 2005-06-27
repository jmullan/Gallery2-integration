<?php
/**
 * File: $Id$
 *
 * Gallery2 Integration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * @subpackage gallery2 module
 * @author Andy Staudacher
 */
/**
 * Initialise the gallery2 module
 *
 * @author Andy Staudacher
 * @access publi
 * @param none $
 * @return true on success or void or false on failure
 * @throws 'DATABASE_ERROR'
 */
function gallery2_init()
{
  // Register Mask
  xarRegisterMask('ReadGallery2', 'All', 'gallery2', 'All', 'All:All:All:All', 'ACCESS_READ');
  xarRegisterMask('AdminGallery2', 'All', 'gallery2', 'All', 'All:All:All:All', 'ACCESS_ADMIN');
  
  // Register Module variables
  /* 
   * I checked the xarModGetVar() logic. if you getVar 1 of module A1
   * all other module variables of module A1 are loaded into the cache too
   * -> there's no overhead in using multiple module variables and checking them
   *    in each request.
   */
  // relative url from CMS root dir to G2 root dir
  xarModSetVar('gallery2','g2.relativeurl','');
  // absolute include path to /some/path/Gallery/
  xarModSetVar('gallery2','g2.includepath','');
  // the login redirect url
  xarModSetVar('gallery2','g2.loginredirect','');
  // the G2 basefile
  xarModSetVar('gallery2','g2.basefile','');
  // if true everything is configured and ready for production
  xarModSetVar('gallery2','configured',0);
  // short url support (disabled for now)
  xarModSetVar('gallery2', 'SupportShortURLs', 0);
  // minimum G2 version required
  xarmodSetVar('gallery2', 'g2.minCoreVersion', '0.9.24');
  // minimum xaraya core version
  xarmodSetVar('gallery2', 'xar.minCoreVersion', '1.0.0'); 


  // whether to display the sidebar menu within the module html 
  // (else, instantiate a sidebar block)
  xarModSetVar('gallery2', 'g2.sidebarInside', 1);
  
  /**
   * Register blocks
   */
  if (!xarModAPIFunc('blocks',
		     'admin',
		     'register_block_type',
		     array('modName'  => 'gallery2',
			   'blockType'=> 'sidebar'))) return;
  if (!xarModAPIFunc('blocks',
		     'admin',
		     'register_block_type',
		     array('modName'  => 'gallery2',
			   'blockType'=> 'image'))) return;
  
  // Register Hooks
  // "remove member" hook, userapi
  // "delete / purge user / group (roles)" hook
  if (!xarModRegisterHook('item', 'delete', 'API',
			  'gallery2', 'admin', 'deletehook')) {
    return false;
  }
  // "add member" hook, userapi
  // "create user / group (roles)" hook
  if (!xarModRegisterHook('item', 'create', 'API',
			  'gallery2', 'admin', 'createhook')) {
    return false;
  }
  // "update user / group (roles)" hook
  if (!xarModRegisterHook('item', 'update', 'API',
			  'gallery2', 'admin', 'updatehook')) {
    return false;
  }
  
  
  // Register search hook
  /* still in development
  if (!xarModRegisterHook('item', 'search', 'GUI', 'gallery2', 'user', 'search')) {
    return false;
  }
  */
  
  // Register a hook that listens to base module config changes (site wide language code)
  if (!xarModRegisterHook('module', 'updateconfig', 'API',
			  'gallery2', 'admin', 'updateconfighook')) {
    return false;
  }
	
  
  // Enable gallery2 hooks for search
  if (xarModIsAvailable('search')) {
    xarModAPIFunc('modules','admin','enablehooks',
		  array('callerModName' => 'search', 'hookModName' => 'gallery2'));
  }
  
  // Enable gallery2 hooks for roles
  if (xarModIsAvailable('roles')) {
    xarModAPIFunc('modules','admin','enablehooks',
		  array('callerModName' => 'roles', 'hookModName' => 'gallery2'));
  }
	
  // Enable the base module updateconfig hook
  if (xarModIsAvailable('base')) {
    xarModAPIFunc('modules','admin','enablehooks',
		  array('callerModName' => 'base', 'hookModName' => 'gallery2'));
  }
	
  return true;
}

/**
 * Upgrade the gallery2 module from an old version
 *
 * @author Andy Staudacher
 * @access public
 * @param  $oldVersion
 * @return true on success or false on failure
 * @throws no exceptions
 */
function gallery2_upgrade($oldversion)
{
    // Load the xarGallery2Helper class
    include_once(dirname(__FILE__) .'/xargallery2helper.php');
    
    switch($oldversion) {
    case '0.1':
    case '0.6.1':
    case '0.6.2':	
    case '0.6.3':
    case '0.6.4':
    case '0.6.5':
    case '0.6.6':
	/* update the login redirect path to be absolute */
	$xarayaPath = xarGallery2Helper::xarServerGetBaseURI();
	$length = strlen($xarayaPath);
	if ($length == 0 || $xarayaPath{$length-1} != '/') {
	    $xarayaPath .= '/';
	}
	xarModSetVar('gallery2','g2.loginredirect', $xarayaPath . xarModGetVar('gallery2','g2.loginredirect'));
	
	xarmodSetVar('gallery2', 'g2.minCoreVersion', '0.9.24');
	
	break;
    case '0.6.7':
	/*
	 * update the login redirect path to be absolute, again, since xarServerGetBaseURI() in
	 * 0.6.7 could have been empty, i.e. not absolute
	 */
	$xarayaPath = xarGallery2Helper::xarServerGetBaseURI();
	$length = strlen($xarayaPath);
	if ($length == 0 || $xarayaPath{$length-1} != '/') {
	    $xarayaPath .= '/';
	}
	xarModSetVar('gallery2','g2.loginredirect', $xarayaPath . xarModGetVar('gallery2','g2.loginredirect'));
	
	xarmodSetVar('gallery2', 'g2.minCoreVersion', '0.9.24');
	
	break;
    default:
	break;
    }
    return true;
}
/**
 * Delete the gallery2 module
 *
 * @author Andy Staudacher
 * @access public
 * @param no $ parameters
 * @return true on success or false on failure
 */
function gallery2_delete()
{
	
  // Remove all hooks
  
  // search hook
  /*
 if (!xarModUnregisterHook('item', 'search', 'GUI', 'gallery2', 'user', 'search')) {
    return false;
  }
  */ 
  // "delete / purge user / group (roles)" hook
  if (!xarModUnregisterHook('item', 'delete', 'API', 'gallery2', 'admin', 'deletehook')) {
    return false;
  }
  // "add member" hook, userapi
  // "create user / group (roles)" hook
  if (!xarModUnregisterHook('item', 'create', 'API', 'gallery2', 'admin', 'createhook')) {
    return false;
  }
  // "update user / group (roles)" hook
  if (!xarModUnregisterHook('item', 'update', 'API', 'gallery2', 'admin', 'updatehook')) {
    return false;
  }
  // unregister the base updateconfig hook
  // Register a hook that listens to base module config changes (site wide language code)
  if (!xarModUnregisterHook('module', 'updateconfig', 'API',  'gallery2', 'admin', 'updateconfighook')) {
    return false;
  }
	
  // Remove module variables	
  xarModDelVar('gallery2','g2.relativeurl');
  xarModDelVar('gallery2','g2.includepath');
  xarModDelVar('gallery2','g2.loginredirect');
  xarModDelVar('gallery2','g2.basefile');
  xarModDelVar('gallery2','configured');
  xarModDelVar('gallery2','SupportShortURLs');
  xarmodDelVar('gallery2', 'g2.minCoreVersion');
  xarmodDelVar('gallery2', 'xar.minCoreVersion');
  xarModDelVar('gallery2','g2.sidebarInside');
  // Remove Masks and Instances
  xarRemoveMasks('gallery2');
  
  return true;
}

?>
