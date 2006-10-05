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
 * Gallery 2 integration for phpBB3.
 * @version $Revision$ $Date$
 * @author Scott Gregory <jettyrat@jettyfishing.com>
 */

class acp_gallery2_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_gallery2',
			'title'		=> 'ACP_GALLERY2',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'options'		=> array('title' => 'ACP_GALLERY2', 'auth' => 'acl_a_board'),
//				'options'		=> array('title' => 'ACP_GALLERY2', 'auth' => 'acl_a_gallery2'),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>