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
 * utility function pass individual menu items to the main menu
 *
 * @author Andy Staudacher
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function gallery2_adminapi_getmenulinks()
{
    $menulinks = array();
	
    // Security Check
    if (xarSecurityCheck('AdminGallery2',0)) {
        $menulinks[] = Array('url'   => xarModURL('gallery2',
                                                  'admin',
                                                  'modifyconfig'),
                             'title' => xarML('Modify the Gallery2 configuration'),
                             'label' => xarML('Modify Config'));
    }

    return $menulinks;
}
?>
