<?php
/**
 * Main Toolbar file.
 * 
 * @package g2bridge
 * @version $Revision$
 * @copyright Copyright (C) 2005 - 2007 4 The Web. All rights reserved.
 * @license GNU General Public License either version 2 of the License, or (at
 * your option) any later version.
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once( $mainframe->getPath( 'toolbar_html' ) );

switch ($act) {
	case 'conf':
		switch ($task){
			case 'wizardStepTwo':
				TOOLBAR_g2bridge::_WIZARDLAST();
				break;
			case 'wizardStepOne':
			case 'wizard':
				TOOLBAR_g2bridge::_WIZARD();
				break;
			default:
				TOOLBAR_g2bridge::_CONFIG();
				break;	
		}
		break;
	case 'user':
		switch ($task){
			case 'edit':
				TOOLBAR_g2bridge::_USER_EDIT();
				break;
			default:
				TOOLBAR_g2bridge::_USER_DEFAULT();
				break;
		}
		break;
	default:
		TOOLBAR_g2bridge::_DEFAULT();
		break;
}
?>