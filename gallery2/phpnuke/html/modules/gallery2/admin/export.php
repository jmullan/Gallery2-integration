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
 * Gallery 2 integration for phpnuke.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

global $admin_file, $currentlang, $module_name;

if(!isset($admin_file)) {
	$admin_file = "admin";
}

if (!eregi("".$admin_file.".php", $_SERVER['PHP_SELF'])) { die ("Access Denied"); }

$failures = array();

$phpnuke2G2Lang = array (
	'danish'	=> 'da', 
	'dutch'		=> 'nl', 
	'german'	=> 'de', 
	'greek'		=> 'el', 
	'english'	=> 'en', 
	'american'	=> 'en', 
	'spanish'	=> 'es', 
	'finnish'	=> 'fi', 
	'french'	=> 'fr', 
	'irish'		=> 'ga', // not available
	'italian'	=> 'it', 
	'japanese'	=> 'ja', // not available
	'norwegian'	=> 'no', 
	'polish'	=> 'pl', 
	'portuguese'=> 'pt', 
	'swedish'	=> 'sv', 
	'chinese'	=> 'zh');

/*********************************************************/
/* Standalone Message Function                           */
/*********************************************************/

function g2_message($mess) {
	global $admin, $bgcolor2, $module_name, $admin_file;
	include ("header.php");
	OpenTable();
	echo	"<br><center><a href=\"".$admin_file.".php?op=gallery2\">".
				"<img alt='Gallery::your photos on your website' src='modules/$module_name/images/g2.png' border=0></a><H3>Gallery2 Module Administration</H3>".
				"<br/><a href=\"".$admin_file.".php?op=gallery2\">Return Home</center>";
	CloseTable();
	echo "<br/>";

	OpenTable();
	echo "<center><font class=\"option\">";
	echo $mess;
	echo "</font></center>";
	CloseTable();

	include ("footer.php");
}

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
function init($var) {
	global $currentlang, $gallery;
	// only init if not already done so 	 
	if (isInitiated()) { 	 
		return true; 	 
	} 	 

	require_once ($var['embedphpfile']."/"._G2_EMBED_PHP_FILE); 	 

	$g2currentlang = $phpnuke2G2Lang[$currentlang]; 	 

	$ret = GalleryEmbed :: init(array ( 	 
			'embedPath' => $var['embedPath'], 	 
			'embedUri' => $var['embedUri'], 	 
			'relativeG2Path' => $var['relativeG2Path'], 	 
			'loginRedirect' => $var['loginRedirect'], 	 
			'activeUserId' => '', 	 
			'activeLanguage' => $g2currentlang, 	 
			'fullInit' => 1)); 	 

	$gallery->guaranteeTimeLimit(300);

	if (!$ret->isSuccess()) {
		message_die(CRITICAL_ERROR,'G2 did not return a success status upon an init request. Here is the error message from G2: <br /> [#(1)]'.$ret->getAsHtml());
		return false;
	}
	isInitiated(true);
	return true;
}

/*********************************************************/
/* Export PHPNuke Users to Gallery 2                     */
/*********************************************************/
function userExport() {
	global $db, $gallery, $failures, $prefix;

	$sql = "SELECT * FROM ".$prefix."_g2config";
	$result = $db->sql_query($sql);
	list($embedphpfile, $embedUri, $relativeG2Path, $loginRedirect, $activeUserId, $embedPath, $cookiepath, $showSidebar, $g2configurationDone, $embedVersion) = $db->sql_fetchrow($result);

	require_once ($embedphpfile."/embed.php");
	require_once ($embedphpfile."/modules/core/classes/ExternalIdMap.class");

	// init G2 transaction, load G2 API, if not already done so
	$vars = array('embedphpfile' => $embedphpfile, 'embedUri' => $embedUri, 'relativeG2Path' => $relativeG2Path, 'loginRedirect' => $loginRedirect, 'activeUserId' => $activeUserId, 'embedPath' => $embedPath, 'cookiepath' => $cookiepath, 'showSidebar' => $showSidebar, 'g2configurationDone' => $g2configurationDone, 'embedVersion' => $embedVersion);
	if (!init($vars)) {
		return false;
	}

	// Load all existing phpnuke <-> G2 mappings
	list ($ret, $mapsByExternalId) = GalleryEmbed::getExternalIdMap('externalId');
	if ($ret->isError()) {
		return false;
	}

	// Map the ExternalMapId "admin" to the last phpnuke admin account found
	list ($ret, $adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
	if ($ret->isError()) {
		ob_flush();
		flush();
		g2_message('Unable to fetch the admin group. Here is the error message from G2: <br />'.$ret->getAsHtml());
	    return false;
	}

	// Grab all the existing G2 admins
	list ($ret, $adminList) = GalleryCoreApi::fetchUsersForGroup($adminGroupId);
	if ($ret->isError()) {
		ob_flush();
		flush();
		g2_message('Unable to fetch a member in the admin group. Here is the error message from G2: <br />'.$ret->getAsHtml());
	    return false;
	}

	end($adminList);
	$adminId = key($adminList);

	if (!isset ($mapsByExternalId["admin"])) {
		$ret = ExternalIdMap::addMapEntry(array('externalId'=>"admin", 'entityType'=>'GalleryUser', 'entityId'=>$adminId));
		if ($ret->isError()) {
			ob_flush();
			flush();
			return false;
		}
	}	

	// TODO: Update of the admin account if it already exists

	$sql = "SELECT count(*) AS ucount, MAX(user_id) AS max_user_id FROM " . $prefix . "_users";
	$user_count = $db->sql_fetchrow($db->sql_query($sql));		
	$ucount = intval($user_count['ucount']) - 1 ;
	$max_id = $user_count['max_user_id'];
	$users_imported = 0;

	for($cur_id = 2; $cur_id <= $max_id; $cur_id++) {
		$sql = "SELECT user_id, username, user_password, user_email, user_lang, user_regdate FROM ". $prefix . "_users WHERE user_id = $cur_id LIMIT 1";
		$result = $db->sql_query($sql);
		if($db->sql_numrows($result) != 0) {	
			$row = $db->sql_fetchrow($result);
			$user_id = $row['user_id'];
			$args = array('fullname'=> $row['username'], 'username'=> $row['username'], 'hashedpassword'=> $row['user_password'], 'hashmethod'=> 'md5' , 'email'=> $row['user_email'] , 'creationtimestamp'=> strtotime($row['user_regdate']));

			$users_imported++;
			$percentInDecimal = ($users_imported / $ucount);
			
			if($users_imported % 100 == 0) {
				print "<script type=\"text/javascript\">
				updateProgressBar(\"".$users_imported." users imported\", $percentInDecimal);
				</script>
				";
				ob_flush();
				flush();
			}

			// if the user exists, just update the user data
			if (isset ($mapsByExternalId[$user_id])) {
				$ret = GalleryEmbed::updateUser($user_id, $args);
				if (!$ret->isSuccess()) {
					$failures[] = $user_id;
				}
			}
			else { //  else we create the user
				$ret = GalleryEmbed::createUser($user_id, $args);
				if($ret->isError() && ($ret->getErrorCode() & ERROR_COLLISION)) {
					list($ret,$user) = GalleryCoreApi::fetchUserByUsername($row['username']);
					$g2userId = $user->getId();
					$ret = GalleryEmbed::addExternalIdMapEntry($row['user_id'],$g2userId,"GalleryUser");
					if($ret->isError()) {
						g2_message($ret->getAsHtml());
					}
				}
			}
		}
	}
	
	$ret = GalleryEmbed::done();

	$sql = "UPDATE ".$prefix."_g2config SET g2configurationDone = 1";
	$result = $db->sql_query($sql);

	$percentInDecimal = ($users_imported / $ucount) *100;
	print "<script> updateProgressBar(\"Export Complete\", $percentInDecimal);</script>";
	ob_flush();
	flush();
	if(count($failures) != 0) {
		echo "<br />The import of the following PHPNuke user_id's failed:<br />";
		foreach($failures as $bad_id) {
			echo $bad_id."<br />";
		}
		echo "The most common reasons for failed imports are:"
		."<ul><li>Duplicate PHPNuke usernames</li><li>A PHPNuke username of \"guest\"</li><li>A PHPNuke username consisting of only numbers</li></ul>"
		."Check the failed user_ids and re-run the export";
	}

	echo "<form><input type=\"button\" value=\"Close Window\" onclick=\"window.close()\"></form>";
}

?>

<html><head>
<script type="text/javascript">

function updateProgressBar(description, percentComplete) {
	var saveToGoDisplay = document.getElementById('progressToGo').style.display;
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
	userExport();
	echo "</p>";
?>