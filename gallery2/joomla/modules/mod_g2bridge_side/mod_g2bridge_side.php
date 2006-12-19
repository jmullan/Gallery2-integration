<?php
/**
 * @package Mod_gallery2_side
 * @copyright (C) 4 The Web
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @version $Id$
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
global $option;
/* Check if the this is the correct page */
if($option == 'com_gallery2'){
	global $g2sidebar;
	if (isset($g2sidebar) && !empty($g2sidebar)) { 
		$text = core::decoded(implode('', $g2sidebar),false,false);
		$content = '<div id="gsSidebar" class="gcBorder1"> '.$text.'</div>';
	}
} else {
	$content = 'your not on the gallery embedded page!';
}
?>