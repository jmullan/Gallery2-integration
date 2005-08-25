<?php

/*
 * $RCSfile$
 *
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2005 Bharat Mediratta
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
/**
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

define('IN_PHPBB', 1);

if( !empty($setmodules) )
{
	$filename = basename(__FILE__);
	$module['Forums']['Gallery_2'] = $filename;

	return;
}

$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);

if( isset($HTTP_GET_VARS['mode']) || isset($HTTP_POST_VARS['mode']) )
{
	$mode = ($HTTP_GET_VARS['mode']) ? $HTTP_GET_VARS['mode'] : $HTTP_POST_VARS['mode'];
	$mode = htmlspecialchars($mode);
}
else 
{
	if( isset($HTTP_POST_VARS['save']) )
	{
		$mode = "save";
	}
	else if( isset( $HTTP_POST_VARS['config']) )
	{
		$mode = "config";
	}
	else if( isset($HTTP_POST_VARS['sync_intro']) )
	{
		$mode = "sync_intro";
	}
	else
	{
		$mode = "";
	}
}

if( $mode == "save" )
{
	$embedPath = ( isset($HTTP_POST_VARS['embedpath']) ) ? trim($HTTP_POST_VARS['embedpath']) : "";
	$fullPath = ( isset($HTTP_POST_VARS['fullpath']) ) ? trim($HTTP_POST_VARS['fullpath']) : "";
	$embedURI = ( isset($HTTP_POST_VARS['embeduri']) ) ? trim($HTTP_POST_VARS['embeduri']) : "";
	$relativePath = ( isset($HTTP_POST_VARS['relativepath']) ) ? trim($HTTP_POST_VARS['relativepath']) : "";
	$loginPath = ( isset($HTTP_POST_VARS['loginpath']) ) ? trim($HTTP_POST_VARS['loginpath']) : "";
	$cookiePath = ( isset($HTTP_POST_VARS['cookiepath']) ) ? trim($HTTP_POST_VARS['cookiepath']) : "";
	$activeUserID = ( isset($HTTP_POST_VARS['activeuserid']) ) ? intval($HTTP_POST_VARS['activeuserid']) : 0;

	if( $embedPath == "" || $fullPath == "" || $embedURI == "" || $relativePath == "" || $loginPath == "" || $cookiePath == "")
	{
		message_die(GENERAL_MESSAGE, "One or more fields are blank. Please go back and correct.");
	}

	$sql = "SELECT * FROM phpbb_gallery2";
	if(!$result = $db->sql_query($sql))
	{
		$sql = "INSERT INTO phpbb_gallery2 (fullPath, embedPath, embedURI, relativePath, loginPath, cookiePath, activeUserID) 
			VALUES ('" . $fullPath . "', '" . $embedPath ."', '" . $embedURI . "', '" . $relativePath . "','" . $loginPath . "','" . $cookiePath . "',$activeUserID)";
	}
	else
	{
		$sql = "UPDATE phpbb_gallery2 SET fullPath = '" . $fullPath ."', embedPath = '" . $embedPath . "', embedURI = '" . $embedURI . "', relativePath = '" . $relativePath ."', loginPath = '" . $loginPath ."', cookiePath = '" . $cookiePath . "', activeUserID = " . $activeUserID;
	}

	$message = "Configuration data successfully saved.";
	
	if(!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, "Could not insert data into Gallery 2 table", $lang['Error'], __LINE__, __FILE__, $sql);
	}

	$message .= "<br /><br />" . sprintf('Click %sHere%s to return to the Gallery 2 admin page', "<a href=\"" . append_sid("admin_gallery2.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

	message_die(GENERAL_MESSAGE, $message);
}
else if( $mode == "config" )
{
	$template->set_filenames(array(
		"body" => "admin/gallery2_config_body.tpl")
	);

	$sql = "SELECT * FROM phpbb_gallery2";
	if(!$result = $db->sql_query($sql))
	{
		$sql = "SELECT * 
			FROM " . CONFIG_TABLE . " WHERE config_name='cookie_path'";
		
		if(!$result = $db->sql_query($sql))
		{
			message_die(CRITICAL_ERROR, "Could not query config information in admin_board", "", __LINE__, __FILE__, $sql);
		}
		$row = $db->sql_fetchrow($result);
		$cookie_path = $row['config_value'];
		$path = $_SERVER['SCRIPT_FILENAME'];
		$path = explode("/",$path);
		for($i = 0; $i < (count($path) - 2); $i++)
		{
			$fullpath .= $path[$i] . "/";
		}
		$fullpath .= "gallery2/";
		$embedpath = "/";
		$relativepath = "/gallery2";
		$embeduri = "gallery2.php";
		$loginpath = "login.php";
		$activeuserid = 0;
	}
	else
	{
		$row = $db->sql_fetchrow($result);
		$fullpath = $row['fullPath'];;
		$embedpath = $row['embedPath'];
		$relativepath = $row['relativePath'];
		$embeduri = $row['embedURI'];
		$loginpath = $row['loginPath'];
		$activeuserid = intval($row['activeUserID']);
		$cookie_path = $row['cookiePath'];
	}

	$template->assign_vars(array(
		'S_FULLPATH' => $fullpath,
		'S_EMBEDPATH' => $embedpath,
		'S_RELATIVEPATH' => $relativepath,
		'S_EMBEDURI' => $embeduri,
		'S_LOGINPATH' => $loginpath,
		'S_COOKIEPATH' => $cookie_path,
		'S_ACTIVEUSERID' => $activeuserid,
		'S_SAVECONFIG' => append_sid("admin_gallery2.$phpEx"),
		'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),

		'L_SUBMIT' => $lang['Submit'],
		'L_EMBEDPATH' => 'URL path from you webroot to gallery2.php: ',
		'L_EMBEDURI' => 'URL to the gallery2.php file: ',
		'L_RELATIVEPATH' => 'Relative file path to your Gallery 2 directory: ',
		'L_LOGINPATH' => 'URL to your login.php file: ',
		'L_COOKIEPATH' => 'Cookie path: ',
		'L_ACTIVEUSERID' => 'Active User ID: ',
		'L_FULLPATH' => 'Full file path to your Gallery 2 directory: ',
		'L_CONFIG_EXPLAIN' => 'We have done our best to fill these values in for you. However, you should double check them for correctness.',
		'L_CONFIG_TITLE' => 'Gallery 2 Integration Settings')
	);

	$template->pparse("body");

	include('./page_footer_admin.'.$phpEx);

}
else if( $mode == "sync_intro" )
{
	$template->set_filenames(array(
		"body" => "admin/gallery2_sync_intro_body.tpl")
	);

	$template->assign_vars(array(
		'S_G2_ACTION' => append_sid("gallery2_export.$phpEx"),
		'L_SYNC_TITLE' => 'Export phpBB Users to Gallery 2',
		'L_SYNC_EXPLAIN' => 'This will export your current phpBB2 users to Gallery 2.  Note that for large numbers of users, this may take some time.  Once you click the button, a new window will pop up showing you the progress of the export.',
		'L_SYNC' => 'Begin user export')
	);

	$template->pparse("body");

	include('./page_footer_admin.'.$phpEx);
}
else
{
	$template->set_filenames(array(
		"body" => "admin/gallery2_show_body.tpl")
	);

	$template->assign_vars(array(
		'S_G2_ACTION' => append_sid("admin_gallery2.$phpEx"),
		'L_CONFIG' => 'Configure Gallery 2 Integration',
		'L_SYNC' => 'Synchronize phpBB2 Users to Gallery 2',
		'G2_TITLE' => 'Gallery 2 Administration',
		'G2_ADMIN_TASK' => 'Choose your Gallery 2 Adminstration Task')
	);

	$template->pparse("body");

	include('./page_footer_admin.'.$phpEx);
}




?>