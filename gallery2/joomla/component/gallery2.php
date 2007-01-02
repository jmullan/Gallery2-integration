<?php
/**
 * Mother file of the component
 * 
 * @package g2bridge
 * @subpackage core
 * @version $Revision$
 * @copyright Copyright (C) 2005 - 2007 4 The Web. All rights reserved.
 * @license GNU General Public License either version 2 of the License, or (at
 * your option) any later version.
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );	

/* Verify that we are on a valid menu */
$g2Menu = new mosMenu( $database );
$g2Menu->load($Itemid);
if(strpos($g2Menu->link, 'com_gallery2')===false) {
	mosErrorAlert(_G2_WRONG_ITEMID);
}

require_once(dirname(__FILE__) . '/init.inc');

global $my;

$ret = core::init(false,(core::getParam('user', 'mirror') == 1)); 
if(!$ret){
	print _G2_NOT_CONFIGURED;
	exit;
}

/* Should we display the side bar? */
GalleryCapabilities::set('showSidebarBlocks', (core::getParam('display', 'sidebar') == 1));
    
/* Should we display the login */
GalleryCapabilities::set('login', (core::getParam('display', 'login') == 1));

/* handle the G2 request */
$g2moddata = GalleryEmbed::handleRequest($my->username);

/* show error message if isDone is not defined, is this needed? */
if (!isset($g2moddata['isDone'])) {
	print 'isDone is not defined, something very bad must have happened.';
	exit;
}

/* die if it was a binary data (image) request */
if ($g2moddata['isDone']) {
	exit;
}

/* Set Meta Data */
core::parseHead($g2moddata['headHtml']);

/* Set Path Way */
$ret = core::setPathway();
if($ret){
	print "An error occurred while trying to setPathWay <br>";
	print $ret->getAsHtml();
	exit;
}

/* Save sidebar in global so we can call it in the module */
$GLOBALS['g2sidebar'] = isset($g2moddata['sidebarBlocksHtml']) ? $g2moddata['sidebarBlocksHtml'] : null;

/* Print gallery content */
print core::decoded($g2moddata['bodyHtml']);

/**
 * @todo Update footer and put in a new text, also add css file to be used by Modules and component.
 * If you want to remove the footer, please consider donating to support this component!
 */
print '<div class="footer" align="center">Powered by <a href="http://trac.4theweb.nl/g2bridge" target="_blank">4 The Web</a> V 2.0.14</div>';
?>