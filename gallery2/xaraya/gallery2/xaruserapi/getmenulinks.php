<?php
/**
 * File: $Id$
 * 
 * Standard function to get main menu links
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage gallery2 Module
 * @author Andy Staudacher <ast@gmx.ch>
 */
 
// Load the xarGallery2Helper class
include_once(dirname(__FILE__) .'/../xargallery2helper.php');

/**
 * Standard function to get main menu links
 *
 * @param none
 * @returns array the user menu links
 */
function gallery2_userapi_getmenulinks()
{
	$menulinks = array();
	// first check if the module has been configured
	if(!xarGallery2Helper::isConfigured()) {
		return $menulinks;
    }
	
    // Security Check
    if (xarSecurityCheck('ReadGallery2', 0)) {
		// put G2 menu links in here
		// TODO: get menu links from G2
	}
    return $menulinks;
}

?>