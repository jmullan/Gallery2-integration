<?php
/**
 * Installation file.
 * 
 * @package g2bridge
 * @subpackage core
 * @author Michiel Bijland
 * @copyright Copyright (C) 2005 - 2006 4 The Web. All rights reserved.
 * @version $Id$
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * Joomla calls this function and start the installation.
 *
 */
function com_install(){
	global $database;
	
	/* remove old database tables */
	$database->setQuery( "DROP TABLE IF EXISTS #__gallery2");
	$result = $database->query();
	if(!$result){
		print $database->getQuery();
	}
	$database->setQuery( "DROP TABLE IF EXISTS #__gallery2_useralbum");
	$result = $database->query();
	if(!$result){
		print $database->getQuery();
	}
	
	/* load JoomlaLib */
	global $mosConfig_absolute_path;
	
	if(file_exists($mosConfig_absolute_path . '/components/com_joomlalib/jlcoreapi.inc')) {
		require($mosConfig_absolute_path . '/components/com_joomlalib/jlcoreapi.inc');
		/* load our config file */
		require($mosConfig_absolute_path . '/components/com_gallery2/classes/g2bridge.jlconf.class');
		/* run serialize to database once */
		$g2bridgeJLConf = new g2bridgeJLConf(null, null, null, null);
		$g2bridgeJLConf->loadFromDB();
		$g2bridgeJLConf->saveConfiguration();
	} else {
		return 'You must <b>install</b> Joomlalib before using Gallery 2 Bridge component!';
	}
	return 'Installed Successfully';
}
?>