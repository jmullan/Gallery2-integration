<?php
/**
 * This file loads and setups our framework for communicating with Gallery 2.
 * 
 * @package g2bridge
 * @subpackage core
 * @author Michiel Bijland
 * @copyright Copyright (C) 2005 - 2006 4 The Web. All rights reserved.
 * @version $Id$
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * defines that path to front-end of this component
 */
define('_G2BPATH', dirname(__FILE__));
/**
 * defines that path to back-end of this component
 */
define('_G2BPATH_ADMIN', realpath(dirname(__FILE__).'/../../administrator/components/com_gallery2'));

/**
 * Load language file
 */
global $mosConfig_lang;
if (file_exists(_G2BPATH_ADMIN.'/language/'.$mosConfig_lang.'.php')){
	include(_G2BPATH_ADMIN.'/language/'.$mosConfig_lang.'.php');
} else {
	include(_G2BPATH_ADMIN.'/language/english.php');
}

/**
 * Require our core class
 */
require_once( _G2BPATH . '/classes/core.class');

/**
 * Check and initiated Configuration
 */
$isConfigured = true;

/* include JoomlaLib */
global $mosConfig_absolute_path;
if(file_exists($mosConfig_absolute_path . '/components/com_joomlalib/jlcoreapi.inc')) {
	require($mosConfig_absolute_path . '/components/com_joomlalib/jlcoreapi.inc');
} else {
	die(_G2_JOOMLALIB_REQUIRED);
}

/* check serverpath and url against hash, this intercepts direct database changes. */
$g2Uri 	= core::getParam('core','url');
$g2Path = core::getParam('core','path');

/*$hash  	= core::getParam('core','hash');
//var_dump($g2Uri, $g2Path, $hash, md5($g2Uri.$g2Path));
if(md5($g2Uri.$g2Path) != $hash){
	$isConfigured = false;
}
*/

/* check if embed.php exists */
if(!file_exists($g2Path) || is_dir($g2Path) || strpos(strrev($g2Path),'php.debme') !== 0){
	$isConfigured = false;
}

/* set isConfigured */
core::isConfigured($isConfigured);

/**
 * Set our base url with correct Itemid
 * @note This method won't return sef url in back end but is more reliable
 */
global $database, $Itemid, $mosConfig_live_site;
$query 	= "SELECT id"
		. "\n FROM #__menu"
		. "\n WHERE link = 'index.php?option=com_gallery2'"
		. "\n AND published = '1'"
		. "\n ORDER BY `access`, `parent`, `ordering` ASC";
$database->setQuery( $query );
$ItemidList = $database->loadResultArray();
/* build url according to our current Itemid and our ItemidList */
if(!empty($ItemidList) && in_array($Itemid, $ItemidList)){
	$baseUrl = 'index.php?option=com_gallery2&amp;Itemid='.$Itemid;
} else if(!empty($ItemidList)){
	$baseUrl = 'index.php?option=com_gallery2&amp;Itemid='.(int) $ItemidList[0];
} else {
	$baseUrl = 'index.php?option=com_gallery2&amp;Itemid=99999999';
}
/* store base url */
if(function_exists('sefRelToAbs')){
	core::setParam('core', 'baseurl', sefRelToAbs($baseUrl),false);
} else {
	core::setParam('core', 'baseurl', rtrim($mosConfig_live_site, '/').'/'.$baseUrl,false);
}
/* store embedUri */
$tmp = parse_url(core::getParam('core', 'baseurl'));

$embedUri  = isset($tmp['path']) ? $tmp['path'] : '' ;
$embedUri .= isset($tmp['query']) ? '?'.$tmp['query'] : '' ;
core::setParam('core', 'embedUri', $embedUri,false);
?>