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

function gallery2_admin_modifyconfig()
{	

    // Security check 
    if (!xarSecurityCheck('AdminGallery2')) return;
	if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'setup', XARVAR_NOT_REQUIRED)) return;
	
    // Generate a one-time authorisation code for this operation
    $data['authid']                        = xarSecGenAuthKey();
	
	// check what has not yet been configured
	
	switch($data['tab']) {
	case 'setup': 
		$data['path']['g2Uri']      = xarModGetVar('gallery2', 'g2Uri');
		$data['path']['embedUri']    = xarModGetVar('gallery2', 'embedUri');
		/* Auto-detect the embedUri if necessary */
		if (empty($data['path']['embedUri'])) {
		    $data['path']['embedUri'] = xargallery2helper::getDetectedEmbedUri();
		}
		$data['path']['g2-include-path'] = xarModGetVar('gallery2','g2.includepath');
		$data['sidebarInside'] = xarModGetVar('gallery2', 'g2.sidebarInside');
		list($ret, $error) = xarGallery2Helper::verifyConfig(false);
		if (!$ret) {
			$data['status'] = xarML('The current configuration could not be verified successfully. Here is the error message: <br /> [#(1)]', $error);	
			// set the module as not configured
			xarGallery2Helper::isConfigured(false);
		} else {
			if (xarGallery2Helper::isConfigured()) {
			  $data['status'] = xarML('The current configuration seems to be ok!');
			} else {
			  $data['status'] = xarML('The current paths seem to be correct. Hit save to synchronize users/groups.');
			}
		}
		xarGallery2Helper::done();
		break;
	case 'importexport':
		list($ret, $error) = xarGallery2Helper::verifyConfig(false);
		if (!$ret) {
			return;
		}
		xarGallery2Helper::done();
		break;
	}
	
	if(xarGallery2Helper::isConfigured()) {
		$data['showimportexport'] = 1;
	} else {
		$data['showimportexport'] = 0;
	}
	 
    // Return the template variables defined in this function
    return $data;
}
?>
