<?php
/**
 * File: $Id$
 * 
 * Xaraya gallery2 wrapper admin page
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 *
 * @subpackage gallery2 Module
 * @author Andy Staudacher aka valiant
*/

/**
 * the main administration function
 * 
 * @author Andy Staudacher
 * @access public 
 * @param no $ parameters
 * @return true on success or void on falure
 * @throws XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 */
function gallery2_admin_main()
{ 
    // Security Check
    if (!xarSecurityCheck('AdminGallery2')) return;
	
	// define some template vars
	$data['xarEverybody'] = 'Everybody';
	$data['g2Everybody'] = 'Everybody';
	$data['xarAdministrators'] = 'Administrators';
	$data['g2Administrators'] = 'Site Admins';
	$data['xarDefaultGroup'] = xarModGetVar('roles', 'defaultgroup');
	$data['g2DefaultGroup'] = '?';
	$data['xarAnonymoususer'] = 'anonymous';
	$data['g2Anonymoususer'] = 'guest';

    if (xarModGetVar('adminpanels', 'overview') == 0) {
        // Return the output
        return $data;
    } else {
        xarResponseRedirect(xarModURL('gallery2', 'admin', 'modifyconfig'));
    } 
	
    // success
    return $data;
} 

?>
