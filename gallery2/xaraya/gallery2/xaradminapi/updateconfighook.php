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
 * Hook function for base and roles updateconfig calls
 *
 * At the moment, we only update / synchronize
 * the site wide default language and the defaultGroup
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return extrainfo the updated extrainfo array
 */
function gallery2_adminapi_updateconfighook($args)
{
	// first check if the module has been configured
	if(!xarGallery2Helper::isConfigured()) {
		return $args['extrainfo'];
    }
	
	extract($args['extrainfo']);
	
	// we only accept base module updateconfig calls
	if (!isset($module)) {
		return $args['extrainfo'];
	}
	
	switch ($module) {
	case 'base':
		// update G2 language settings
		if (!xarGallery2Helper::g2setSiteDefaultLanguage()) {
			return;
		}

		// set short url on/off
		$ret = xarGallery2Helper::verifyConfig(true, true);
		if (!$ret) {
			return;
		}

		// complete G2 transaction
		xarGallery2Helper::done();
		break;
	case 'roles':
		// update G2 language settings
		if (!xarGallery2Helper::g2updateSpecialRoles()) {
			return;
		}

		// complete G2 transaction
		xarGallery2Helper::done();
		break;
	}
	
	
	
	return $args['extrainfo'];
}

?>
