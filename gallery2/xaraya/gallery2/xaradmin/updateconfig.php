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
require_once(dirname(__FILE__) . '/../xargallery2helper_advanced.php');

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
		if (!isset($path) || !is_array($path) || !isset($path['g2Uri']) || !isset($path['embedUri'])) {
			$msg = xarML('Bad parameters for uploads_admin_updateconfig!');
			xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
			return;
		}

		/* Task: find embedUri / normalize it */
		$embedUri = $path['embedUri'];
		$xarayaPathPart = '';
		if (empty($embedUri)) {
		    $embedUri = xargallery2helper::getDetectedEmbedUri();
		} else {
		    $embedUri = G2EmbedDiscoveryUtilities::normalizeEmbedUri($embedUri);
		    list ($protocol, $host, $xarayaPathPart, $file) = G2EmbedDiscoveryUtilities::parseUri($embedUri);
		    if (empty($xarayaPathPart)) {
			/* The path cannot be empty */
			$msg = xarML('The URL to embedded G2 cannot have an empty path part.');
			xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
			return;
		    }
		}

		$g2Uri = $path['g2Uri'];
		$g2PathPart = '';
		if (empty($g2Uri)) {
		    $msg = xarML('The URL to the Gallery 2 installation cannot be empty.');
		    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
		    return;
		} else {
		    $g2Uri = G2EmbedDiscoveryUtilities::normalizeG2Uri($g2Uri);
		    list ($protocol, $host, $g2PathPart, $file) = G2EmbedDiscoveryUtilities::parseUri($g2Uri);
		    if (empty($g2PathPart)) {
			/* The path cannot be empty */
			$msg = xarML('The URL to your Gallery 2 installatoin cannot have an empty path part.');
			xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
			return;
		    }
		}
		
		/* Task: find includePath for G2's embed.php */
		$includePath = '';
		if (!isset($path['g2-include-path']) || empty($path['g2-include-path'])) {
		    $xarayaBasePath = dirname(dirname(dirname(dirname(__FILE__))));
		    /* A embedUri that is certainly not a short-url, something that we can work with */
		    $pseudoEmbedUri = xargallery2helper::xarServerGetBaseURI() . 'index.php';
		    list ($ok, $embedPhpPath, $errorString) = 
		          G2EmbedDiscoveryUtilities::getG2EmbedPathByG2UriEmbedUriAndLocation($g2Uri, $pseudoEmbedUri, $xarayaBasePath);
		    if (!$ok) {
		    	$msg = xarML('There was an error during the detection of the embed.php path. Details: [#(1)]', $errorString);
		        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
		        return;
		    }
		    $includePath = $embedPhpPath;
		} else {
		    $includePath = $path['g2-include-path'];
		}
		    
		/* Check if the directory exists */
		if (!file_exists($includePath)) {
		    $msg = xarML('The file or directory "[#(1)]" does not exist!', $includePath);
		    xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM', new DefaultUserException($msg));
		    return;
		}
			
		// the path seems to be ok, verify that we find a G2 installation and that it is configured
		list($ret, $error) = xarGallery2Helper::verifyConfig(true, true, $g2Uri, $embedUri, $includePath);
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