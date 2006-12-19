<?php
/**
 * This file contains the version of this component.
 * 
 * @package g2bridge
 * @author Michiel Bijland
 * @copyright Copyright (C) 2005 - 2006 4 The Web. All rights reserved.
 * @version $Id$
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * Class that contains version information.
 *
 * @package g2bridge
 * @subpackage core
 */
class g2BridgeVersion {
	/** @var string Product */
	var $PRODUCT 	= 'Gallery 2 Bridge';
	/** @var int Main Release Level */
	var $RELEASE 	= '2.0';
	/** @var string Development Status */
	var $DEV_STATUS = 'Beta';
	/** @var int Sub Release Level */
	var $DEV_LEVEL 	= '14';
	/** @var int build Number */
	var $BUILD	 	= '$Rev$';
	/** @var string Codename */
	var $CODENAME 	= 'A New Dawn';
	/** @var string Date */
	var $RELDATE 	= '6 Dec 2006';
	/** @var string Time */
	var $RELTIME 	= '12:00';
	/** @var string Timezone */
	var $RELTZ 		= 'CET';
	/** @var string Copyright Text */
	var $COPYRIGHT 	= "Copyright (C) 2005 - 2006 4 The Web. All rights reserved.";
	/** @var string URL */
	var $URL 		= '';
	
	/**
	 * @return string Short version format
	 */
	function getShortVersion() {
		return $this->RELEASE .'.'. $this->DEV_LEVEL;
	}
	
	/**
	 * @return string Long format version
	 */
	function getLongVersion() {
		return $this->PRODUCT .' '. $this->RELEASE .'.'. $this->DEV_LEVEL .' '
			. $this->DEV_STATUS
			.' [ '.$this->CODENAME .' ] '. $this->RELDATE .' '
			. $this->RELTIME .' '. $this->RELTZ;
	}
}
?>