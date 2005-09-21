<?php
/**
 * File: $Id$
 * 
 * Xaraya gallery2 wrapper config setup
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
 * update the config of the gallery2 module
 *
 * @param none
 * @return TRUE on success, void on error
 * @throws Systemexception if it failed
 */
function gallery2_admin_updateconfig()
{
	// Confirm authorisation code.  
    if (!xarSecConfirmAuthKey()) { return; }
	
	// Security Check
    if(!xarSecurityCheck('AdminGallery2')) return;
	
	if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'setup', XARVAR_NOT_REQUIRED)) return;
	
    switch ($data['tab']) {
    case 'setup':		
		// Get parameters
		if (!xarVarFetch('path',   'list:str::', $path,   '', XARVAR_NOT_REQUIRED)) return;
		if (!xarVarFetch('sidebarInside',   'checkbox', $sidebarInside,   '', XARVAR_NOT_REQUIRED)) return;

		$sidebarInside = $sidebarInside ? 1 : 0;
		xarModSetVar('gallery2', 'g2.sidebarInside', $sidebarInside);
		if (!isset($path) || !is_array($path) || !isset($path['g2-relative-url'])) {
			$msg = xarML('Bad parameters for uploads_admin_updateconfig!');
			xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
			return;
		}
		// the relative URL
		// first replace all '\' by '/'
		$g2RelativeUrl = trim(str_replace("\\", "/", $path['g2-relative-url']));
		// then remove any trailing '/' if there is one and add a '/'
		$g2RelativeUrl = preg_replace('|/$|', '', $g2RelativeUrl);
		$g2RelativeUrl = $g2RelativeUrl . '/';
		// remove './' from the beginning
		$g2RelativeUrl = preg_replace('|^\./|', '', $g2RelativeUrl);
		// if the path is absolute, don't accept it.
		if (preg_match('|^/|', $g2RelativeUrl)) {
		  $msg = xarML('The relative G2 url path "[#(1)]" is not relative, it starts with a "/"! Please make it relative!', $g2RelativeUrl);
		  xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
		  return;
		}

		$g2IncludePath = null;
		// the absolute include path
		if (isset($path['g2-include-path']) && !empty($path['g2-include-path'])) {
		  $g2IncludePath = $path['g2-include-path'];
		}
			
		// the path seems to be ok, verify that we find a G2 installation and that it is configured
		list($ret, $error) = xarGallery2Helper::verifyConfig(true, true, $g2RelativeUrl, $g2IncludePath);
		if (!$ret) {
		  return;
		}
	
		// if the configuration wasn't ok before, this is a initial configuration and we
		// need to get the two user/group bases in sync'.
		if(!xarGallery2Helper::isConfigured()) {
		  /*
		   * Import / export xaraya / G2 users / groups
		   */
		  if (!xarGallery2Helper::g2xarUserGroupImportExport()) {
		    return;
		  }
		} // end if was not configured
	
		// update site wide language code in G2
		if (!xarGallery2Helper::g2setSiteDefaultLanguage()) {
		  return;
		}
		
		// set the module as configured
		xarGallery2Helper::isConfigured(true);
	
		// G2 login the current user, this very xaraya user which has admin rights.
		if (!xarGallery2Helper::g2login(xarUserGetVar('uid'))) {
		  return;
		}

		xarGallery2Helper::done();
		break;
    case 'importexport':
		/**
		 * Synchronize G2 and xaraya user/group management
		 * = Import / Export 
		 */
		if(!xarGallery2Helper::isConfigured()) {
		  return;
		}
		// Import / Export user and groups
		if (!xarGallery2Helper::g2xarUserGroupImportExport()) {
		  return;
		}
		xarGallery2Helper::done();
		break;
	}
	
	
	// let others know that we configured our module
    xarModCallHooks('module', 'updateconfig', 'gallery2', array('module' => 'gallery2'));
    
    xarResponseRedirect(xarModURL('gallery2', 'admin', 'modifyconfig', array('tab' => $data['tab'])));

    // Return
    return TRUE;
}

?>
