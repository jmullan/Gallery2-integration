<?php
/**
 * File: $Id$
 *
 * Gallery2 Wrapper Event API
 *
 * @package xaraya
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage gallery2
 * @author Alan Harder <alan.harder@sun.com> | Andy Staudacher <ast@gmx.ch>
*/

// Load the xarGallery2Helper class
include_once(dirname(__FILE__) .'/xargallery2helper.php');

/**
 * Login the authenticated user in Gallery2
 * 
 * We choose another Session synchronization with xaraya
 * instead of event synchronization, we give the
 * usernamer to G2 on each request. 
 * For that reaaon this function is outcommented
 *
 * @author Alan Harder <alan.harder@sun.com> / Andy Staudacher
 * @returns bool
 */
/*
function gallery2_eventapi_OnUserLogin($value) {
    // $value contains the userid in this event
    // first check if the module has been configured
	if(!xarGallery2Helper::isConfigured()) {
		return true;
    }
    $user = xarModAPIFunc('roles','user','get',
                          array('uid' => $value));
    if (empty($user['uname'])) { return false; }
	// Start G2 transaction
	if(xarGallery2Helper::init()) {
		$ret = GalleryEmbed::login($user['uname']);
		//???
    }
    return true;
}
*/

/**
 * Logout the authenticated user from Gallery2
 * 
 * @author Alan Harder <alan.harder@sun.com>
 * @returns bool
 */
function gallery2_eventapi_OnUserLogout($value) {
    // $value contains the userid in this event
    // first check if the module has been configured
	if(!xarGallery2Helper::isConfigured()) {
		return true;
    }
    require_once(xarModGetVar('gallery2','g2.includepath') . 'embed.php');
	$ret = GalleryEmbed::logout();
    return true;
}

?>
