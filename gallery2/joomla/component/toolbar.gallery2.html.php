<?php
/**
 * Toolbar html.
 * 
 * @package g2bridge
 * @version $Revision$
 * @copyright Copyright (C) 2005 - 2007 4 The Web. All rights reserved.
 * @license GNU General Public License either version 2 of the License, or (at
 * your option) any later version.
 */

defined( '_VALID_MOS' ) or die( 'Restricted access' );

/**
 * @package g2bridge
 * @subpackage core
 */
class TOOLBAR_g2bridge {
	function _DEFAULT() {
		mosMenuBar::startTable();
		mosMenuBar::back();
		mosMenuBar::endTable();
	}
	
	function _CONFIG(){
		mosMenuBar::startTable();
		mosMenuBar::customX('wizard','extensions.png', 'extensions_f2.png', 'Wizard', false);
		mosMenuBar::spacer();
		mosMenuBar::save();
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.config', true );
		mosMenuBar::endTable();
	}
	
	function _WIZARD(){
		mosMenuBar::startTable();
		mosMenuBar::back();
		mosMenuBar::spacer();
		mosMenuBar::customX('wizardStepTwo','next.png', 'next_f2.png', 'Next', false);
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.wizard', true );
		mosMenuBar::endTable();
	}
	
	function _WIZARDLAST(){
		mosMenuBar::startTable();
		mosMenuBar::custom('wizardSave','save.png', 'save_f2.png', 'Save', false);
		mosMenuBar::spacer();
		mosMenuBar::back();
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.wizard', true );
		mosMenuBar::endTable();
	}
	
	function _USER_DEFAULT(){
		mosMenuBar::startTable();
		mosMenuBar::custom( 'sync', 'copy.png', 'copy_f2.png', 'Sync', false );
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.user', true );
		mosMenuBar::endTable();
	}
	
	function _USER_EDIT(){
		mosMenuBar::startTable();
		mosMenuBar::save();
		mosMenuBar::spacer();
		mosMenuBar::apply();
		mosMenuBar::spacer();
		mosMenuBar::cancel( 'cancel', 'Close' );
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.user.edit', true );
		mosMenuBar::endTable();
	}
}
?>