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

$no_page_header = TRUE;
$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require('./pagestart.' . $phpEx);

$failures = array();

$sync_type = ($_GET['type'] == "user") ? "user" : "group";
$sync_title = ($_GET['type'] == "user") ? "Users" : "Groups";

/*********************************************************/
/* True if init() was called, else false                 */
/*********************************************************/

function isInitiated($newvalue = null) {
	static $initiated;
	if (!isset ($initiated)) {
		$initiated = false;
	}
	if (isset ($newvalue) && (is_bool($newvalue) || is_int($newvalue))) {
		$initiated = $newvalue;
	}
	return $initiated;
}

/*********************************************************/
/* Init G2 API                                           */
/*********************************************************/

function init() {
	global $db, $gallery;

	// only init if not already done so
	if (isInitiated()) {
		return true;
	}

	$sql = "SELECT * FROM phpbb_gallery2";

	if(!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, "Could not retrieve data from Gallery 2 table", $lang['Error'], __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);
	require_once ($row['fullPath']."/embed.php");
	$fullpath = $row['fullPath'];;
	$embedpath = $row['embedPath'];
	$relativepath = $row['relativePath'];
	$embeduri = $row['embedURI'];
	$loginpath = $row['loginPath'];
	$activeuserid = intval($row['activeUserID']);
	$cookie_path = $row['cookiePath'];

	$ret = GalleryEmbed :: init(array (
			'embedPath' => $embedpath,
			'embedUri' => $embeduri, 
			'relativeG2Path' => $relativepath,
			'loginRedirect' => $loginpath,
			'activeUserId' => '', 
			'fullInit' => $fullInit));

	$gallery->guaranteeTimeLimit(300);
	
	if (!$ret->isSuccess()) {
		message_die(CRITICAL_ERROR,'G2 did not return a success status upon an init request. Here is the error message from G2: <br /> [#(1)]'.$ret->getAsHtml());
		return false;
	}
	isInitiated(true);
	return true;
}

/**
 * g2addexternalMapEntry: add an externalId map entry
 *
 * Add an entry in the G externalId, entityId map table
 *
 * @author Andy Staudacher
 * @access public
 * @param integer the uid
 * @param integer the entityId from G2
 * @param integer/string the roles type, 1 for groups, 0 for users, or the entityType string
 * @return bool true or false
 */
function g2addexternalMapEntry($externalId, $entityId, $entityType) 
{
	global $db, $failures; 

	$sql = "SELECT * FROM phpbb_gallery2";

	if(!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, "Could not retrieve data from Gallery 2 table", $lang['Error'], __LINE__, __FILE__, $sql);
	}

	$row = $db->sql_fetchrow($result);
	
	// init G2 transaction, load G2 API, if not already done so
	if (!init()) {
		return false;
	}
	if (is_int($entityType)) {
		$entityType = $entityType == 0 ? 'GalleryUser' : 'GalleryGroup';
	}
	
	require_once ($row['fullPath']."/".'modules/core/classes/ExternalIdMap.class');
	
	$ret = ExternalIdMap :: addMapEntry(array ('externalId' => $externalId, 'entityType' => $entityType, 'entityId' => $entityId));
	if ($ret->isError()) {
		$failures[] = $externalId;
	}
	return true;
}


/**
 * g2getallexternalIdmappings: get all extId, entityId mappings
 *
 * get all extId, entityId mappings from G2
 * useful, i.e. for import/export synchronization update
 * used only by the import/export method
 *
 * @author Andy Staudacher
 * @access public
 * @param none
 * @return array(bool success, array(entityId => array(externalId => integer,
 *                             entityType => string, entityId => integer)),
 *                             array(externalId => array(externalId => integer,
 *                             entityType => string, entityId => integer)))
 * @throws Systemexception if it failed
 */
function g2getallexternalIdmappings() {
	// init G2 transaction, load G2 API, if not already done so
	if (!init()) {
		return array (false, null, null);
	}
	global $gallery;

	$query = 'SELECT [ExternalIdMap::entityId], [ExternalIdMap::externalId], [ExternalIdMap::entityType]
			FROM [ExternalIdMap]';

	list ($ret, $results) = $gallery->search($query, array ());
	if ($ret->isError()) {
		g2_message('Failed to fetch a list of all extId maps fromG2. Here is the error message from G2: <br /> [#(1)]'.$ret->getAsHtml());
		return array (false, null, null);
	}
	$mapsbyentityid = array ();
	$mapsbyexternal = array ();
	while ($result = $results->nextResult()) {
		$entry = array ('externalId' => $result[1], 'entityId' => $result[0], 'entityType' => $result[2]);
		$mapsbyentityid[$result[0]] = $entry;
		$mapsbyexternal[$result[1]] = $entry;
	}

	return array (true, $mapsbyentityid, $mapsbyexternal);
}

function userExport() 
{
	global $db, $gallery, $failures;

	// init G2 transaction, load G2 API, if not already done so
	if (!init()) {
		return false;
	}

	// Load all existing phpnuke <-> G2 mappings
	list ($ret, $mapsbyentityid, $mapsbyexternalid) = g2getallexternalIdmappings();
	if (!$ret) {
		return false;
	}

	// Map the ExternalmapId "admin" to the last phpnuke admin account found
	// TODO: Mapping for multiple admins

	list ($ret, $adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
	if ($ret->isError()) {
		flush();
		message_die(CRITICAL_ERROR,'Unable to fetch the admin group. Here is the error message from G2: <br />'.$ret->getAsHtml());
	    return false;
	}
	
	list ($ret, $adminList) = GalleryCoreApi::fetchUsersForGroup($adminGroupId, 5);

	if ($ret->isError()) {
		flush();
		message_die(CRITICAL_ERROR,'Unable to fetch a member in the admin group. Here is the error message from G2: <br />'.$ret->getAsHtml());
	    return false;
	}

	foreach ($adminList as $adminId => $adminName) {
	}

	if (!isset ($mapsbyexternalid["admin"])) {
		if (!g2addexternalMapEntry("admin", $adminId, 0)) {
			flush();
 			return false;
		}
	}

	// TODO: Update of the admin account if it already exists
	$sql = "SELECT count(*) AS ucount FROM " . USERS_TABLE;
	$user_count = $db->sql_fetchrow($db->sql_query($sql));			
	$ucount = intval($user_count['ucount']) - 1 ;
	$sql = "SELECT user_id, username, user_password, user_email, user_lang, user_regdate FROM ". USERS_TABLE;

	if(!$result = $db->sql_query($sql))
	{
		message_die(GENERAL_ERROR, "Could not retrieve data from Gallery 2 table", $lang['Error'], __LINE__, __FILE__, $sql);
	}

	$users_imported = 0;
	
	while($row= $db->sql_fetchrow($result)) 
	{	
		if($row['user_id'] != ANONYMOUS) {
			$user_id = $row['user_id'];
			$args['fullname']  	=	$row['username'];
			$args['username'] 	= 	$row['username'];
			$args['hashedpassword'] =	$row['user_password']; 
			$args['hashmethod'] = 	'md5';
			$args['email'] 		=	$row['user_email'];
			$args['creationtimestamp']	=	$row['user_regdate'];

			$users_imported++;
			$percentInDecimal = ($users_imported / $ucount);
			if($users_imported % 100 == 0)
			{
				print "<script type=\"text/javascript\">
				updateProgressBar(\"".$users_imported." users imported\", $percentInDecimal);
				</script>
				";
				flush();
			}
			// if the map exists, just update the user data
			if (isset ($mapsbyexternalid[$user_id])) {
				$ret = GalleryEmbed :: updateUser($user_id, $args);
				if (!$ret->isSuccess()) {
					$failures[] = $user_id;
				}
			}
			//  else we create the user
			else {
				$ret = GalleryEmbed :: createUser($user_id, $args);
				if (!$ret->isSuccess()) {
					$failures[] = $user_id;
				}
					
				if (!g2addexternalMapEntry($row['username'], $user_id, 0)) {
					$failures[] = $user_id;
				}
			}
		}
	}
	$percentInDecimal = ($users_imported / $ucount) *100;
	print "<script> updateProgressBar(\"Export Complete\", $percentInDecimal);</script>";
	flush();
	if(count($failures) != 0)
	{
		echo "<br />The import of the following phpBB2 user_id's failed:<br />";
		foreach($failures as $bad_id)
		{
			echo $bad_id."<br />";
		}
		echo "The most common reasons for failed imports are:"
		."<ul><li>Duplicate phpBB usernames</li><li>A phpBB username of \"guest\"</li><li>A phpBB username consisting of only numbers</li></ul>"
		."Check the failed user_ids and re-run the export";
	}
	echo "<form><input type=\"button\" value=\"Close Window\" onclick=\"window.close()\"></form>";
}

function groupExport() 
{
	global $db, $gallery, $failures;

	// init G2 transaction, load G2 API, if not already done so
	if (!init()) {
		return false;
	}

	// Load all existing phpnuke <-> G2 mappings
	list ($ret, $mapsbyentityid, $mapsbyexternalid) = g2getallexternalIdmappings();
	if (!$ret) {
		return false;
	}

	$sql = "SELECT group_id, group_name FROM " . GROUPS_TABLE . " WHERE group_single_user = 0";
	$result = $db->sql_query($sql);
	while( $row = $db->sql_fetchrow($result) ) 
	{
		$ret = GalleryEmbed :: createGroup($row['group_id'],$row['group_name']);
		if (!$ret->isSuccess()) {
			$failures[] = $row['group_name'];
		}
		$g_sql ="SELECT u.username, ug.user_id FROM " . USERS_TABLE . " u, " . USER_GROUP_TABLE . " ug WHERE ug.user_id = u.user_id AND ug.group_id = " .$row['group_id'];
		$g_result = $db->sql_query($g_sql) or die(mysql_error());
		while( $g_row = $db->sql_fetchrow($g_result) ) 
		{
			$ret = GalleryEmbed :: addUserToGroup($g_row['user_id'],$row['group_id']);
			if (!$ret->isSuccess()) {
				$failures[] = $row['group_name'];
			}
		}
	}
	if(count($failures) != 0)
	{
		echo "<br />The import of the following phpBB2 groups failed:<br />";
		foreach($failures as $bad_id)
		{
			echo $bad_id."<br />";
		}
	}
	echo "<form><input type=\"button\" value=\"Close Window\" onclick=\"window.close()\"></form>";
}

?>
<html><head>
<script type="text/javascript">
var saveToGoDisplay = document.getElementById('progressToGo').style.display;
function updateProgressBar(description, percentComplete) {
	document.getElementById('progressDescription').innerHTML = description;
    var progressMade = Math.round(percentComplete * 100);
    var progressToGo = document.getElementById('progressToGo');

    if (progressMade == 100) {
      progressToGo.style.display = 'none'; 
    } else {
      progressToGo.style.display = saveToGoDisplay;
      progressToGo.style.width = (100 - progressMade) + "%";
    }

    document.getElementById('progressDone').style.width = progressMade + "%";
  }
</script>

<style type="text/css">
<!--

 /* General page style. The scroll bar colours only visible in IE5.5+ */
body {
	background-color: #E5E5E5;
	scrollbar-face-color: #DEE3E7;
	scrollbar-highlight-color: #FFFFFF;
	scrollbar-shadow-color: #DEE3E7;
	scrollbar-3dlight-color: #D1D7DC;
	scrollbar-arrow-color:  #006699;
	scrollbar-track-color: #EFEFEF;
	scrollbar-darkshadow-color: #98AAB1;
}

/* General font families for common tags */
font,p { font-family: Verdana, Arial, Helvetica, sans-serif }
p, td		{ font-size : 11; color : #000000; }
a:link,a:active,a:visited { color : #006699; }
a:hover		{ text-decoration: underline; color : #DD6900; }
.gbBlock {
    padding: 0.7em;
    border-width: 0 0 1px 0;
    border-style: inherit;
    border-color: inherit;
    /* IE can't inherit these */
    border-style: expression(parentElement.currentStyle.borderStyle);
    border-color: expression(parentElement.currentStyle.borderColor);
}
h1,h2 { font-family: "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif; font-size : 22px; font-weight : bold; text-decoration : none; line-height : 120%; color : #000000;}
#ProgressBar #progressDone {
    background-color: #fd6704;
    border: thin solid #ddd;
}

#ProgressBar #progressToGo {
    background-color: #eee;
    border: thin solid #ddd;
}
#gallery h2, #gallery h3, #gallery h4 {
    font-family: "Trebuchet MS", Arial, Verdana, Helvetica, sans-serif;
}
.giTitle, #gallery h2, #gallery h3, #gallery h4 {
    font-size: 1.3em;
    font-weight: bold;
}
#gallery .gbBlock h3 {
    margin-bottom: 0.5em;
}
@import url("../templates/subSilver/formIE.css");
-->
</style>
</head><body>
<h1>Exporting Users</h1><br />
<div id="ProgressBar" class="gbBlock">
  <p id="progressDescription">
    &nbsp;
  </p>

  <table width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td id="progressDone" style="display: inline-block; width:0%">&nbsp;</td>
      <td id="progressToGo" style="display: inline-block; width:100%; border-left: none">&nbsp;</td>
    </tr>
  </table>
</div>
<?php
	echo "<p>";
	$sync_type == "user" ? userExport() : groupExport();
	echo "</p>";
?>

</body></html>