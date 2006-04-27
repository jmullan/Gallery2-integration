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
* Gallery 2 administration helper file for phpBB2.
* @version $Revision$ $Date$
* @author Dariush Molavi <dari@nukedgallery.net>
* @author Scott Gregory
*/

class g2helper_admin {

	var $_fullPath;
	var $_embedUri;
	var $_g2Uri;
	var $_loginRedirect;
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
		$this->_loginRedirect = $row['loginRedirect'];
		$this->_activeAdminId = $row['activeAdminId'];
		$this->_init_array = array(
			'embedUri' => $this->_embedUri,
			'g2Uri' => $this->_g2Uri,
			'loginRedirect' => $this->_loginRedirect,
			'activeUserId' => $this->_activeAdminId,
			'fullInit' => $this->_fullInit
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
