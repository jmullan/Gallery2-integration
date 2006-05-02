<?php

/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2006 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
/**
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 * @author Scott Gregory 
 */

class g2helper_admin {

	var $_compatibleGalleryVersion = '2.1';
	var $_compatibleEmbedVersionMajor = 1;
	var $_compatibleEmbedVersionMinor = 1;
	var $_fullPath;
	var $_embedUri;
	var $_g2Uri;
	var $_activeAdminId;
	var $_fullInit = true;
	var $_init_array;
	var $_timeLimit = 300;

	function g2helper_admin($db) {
		$sql = 'SELECT * FROM ' . GALLERY2_TABLE . ' LIMIT 1';
		if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
			$this->errorHandler(CRITICAL_ERROR, 'Could not obtain config settings from ' . GALLERY2_TABLE . '.', __LINE__, __FILE__, $sql);
		}

		$this->_fullPath = $row['fullPath'];
		$this->_embedUri = $row['embedUri'];
		$this->_g2Uri = $row['g2Uri'];
		$this->_activeAdminId = $row['activeAdminId'];
		$this->_init_array = array(
			'embedUri' => $this->_embedUri, 
			'g2Uri' => $this->_g2Uri, 
			'activeUserId' => $this->_activeAdminId,
			'fullInit' => $this->_fullInit,
			'apiVersion' => array($this->_compatibleEmbedVersionMajor, $this->_compatibleEmbedVersionMinor)
		);

		require_once($this->_fullPath);
	}

	function init() {
		global $gallery;

		$ret = GalleryEmbed::init($this->_init_array);
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, 'G2 init failed. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}

		$gallery->guaranteeTimeLimit($this->_timeLimit);
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, 'G2 did not guarantee a time limit of ' . $this->_timeLimit . '. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}
	}

	function done() {
		$ret = GalleryEmbed::done();
		if (isset($ret)) {
			$this->errorHandler(GENERAL_ERROR, 'Failed to complete transaction. Here is the error message from G2: <br />' . $ret->getAsHtml(), __LINE__, __FILE__);
		}
	}

	function checkConfig() {
		/*
		* Check embedUri portions shamelessly 'borrowed' from Valiant's discovery utility
		*
		* Begin embedUri check
		*/

		if (strpos($this->_embedUri, 'http') !== 0) {
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
			$host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
			$this->_embedUri = sprintf('%s://%s%s', $protocol, $host, $this->_embedUri);
		}

		$components = @parse_url($this->_embedUri);
		if (!$components) {
			return array(false, 'Unable to parse URL ' . $this->_embedUri . '. Please check the URL path to your gallery2.php file.');
		}
		$port = empty($components['port']) ? 80 : $components['port'];
		if (empty($components['path'])) {
			$components['path'] = '/';
		}

		$fd = @fsockopen($components['host'], $port, $errno, $errstr, 1);
		if (empty($fd)) {
			return array(false, "Error $errno: '$errstr' retrieving " . $this->_embedUri);
		}

		$get = $components['path'];

		$ok = fwrite($fd, sprintf("GET %s HTTP/1.0\r\nHost: %s\r\n\r\n", $get, $components['host']));
		if (!$ok) {
			$errorStr = 'Verification of gallery2.php location failed. fwrite call failed for ' . $this->_embedUri;
			if ($ok === false) {
				$errorStr .= "\nreturn value was false";
			}
			return array(false, $errorStr);
		}
		$ok = fflush($fd);
		if (!$ok) {
			if (version_compare(phpversion(), '4.2.0', '>=')) {
				/* Ignore false returned from fflush on PHP 4.1 */
				return array(false, 'Verification of gallery2.php location failed. fflush call failed for ' . $this->_embedUri);
			}
		}

		$headers = array();
		$response = trim(fgets($fd, 4096));

		if (!preg_match("/^HTTP\/\d+\.\d+\s2\d{2}/", $response)) {
			return array(false, 'URL derived from ' . $this->_embedUri . ' is invalid');
		}

		/*
		* If we reach this point without error, the location of embedUri checks out ok
		*
		* End embedUri check
		*/

		if (is_callable('GalleryEmbed', 'getApiVersion')) {
			list ($major, $minor) = GalleryEmbed::getApiVersion();
			if ($major == $this->_compatibleEmbedVersionMajor && $minor >= $this->_compatibleEmbedVersionMinor) {
				/*
				* If we reach this point without error; g2Uri, fullPath, embedUri file checks have passed and version check has passed
				* Integration life is good!
				*/
				return array(true, 'Gallery integration settings and version checks passed.');
			}
			elseif ($major > $this->_compatibleEmbedVersionMajor) {
				/* GalleryEmbed module major version is newer, may or may not work */
				return array(false, 'Gallery integration settings and version checks passed, but GalleryEmbed module is newer and may be incompatible.<br /> Required module version ' . $this->_compatibleEmbedVersionMajor . '.' . $this->_compatibleEmbedVersionMinor . " - installed module version is $major.$minor");
			}
			else {
				/* GalleryEmbed module version is older, good chance will not work */
				return array(false, 'Gallery version check failed. Currently this integration is compatible with Gallery version ' . $this->_compatibleGalleryVersion . ' and up,<br />using GalleryEmbed module version ' . $this->_compatibleEmbedVersionMajor . '.' . $this->_compatibleEmbedVersionMinor . " - installed module version is $major.$minor");
			}
		}
		else {
			/* GalleryEmbed module version doesn't support getApiVersion. Gallery version is older than 2.1 */
			return array(false, 'Gallery version check failed. Currently this integration is compatible with Gallery version ' . $this->_compatibleGalleryVersion . ' and up.');
		}
	}

	function errorHandler($code, $msg, $line, $file, $sql = '') {
		switch ($code) {
			case CRITICAL_ERROR :
				message_die(CRITICAL_ERROR, $msg, '', $line, $file, $sql);
				break;

			case GENERAL_ERROR :
				message_die(GENERAL_ERROR, $msg, '', $line, $file, $sql);
				break;
		}

		exit;
	}

}

?>
