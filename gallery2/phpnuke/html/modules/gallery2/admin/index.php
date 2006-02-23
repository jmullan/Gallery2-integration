<?php

/*
 * $RCSfile$
 *
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
 * Gallery 2 admin for PHPNuke.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */
global $prefix, $db, $g2config_error, $currentlang, $admin_file, $module_name;

$embedVersion = "0.5.5";

if(!isset($admin_file)) {
	$admin_file = "admin";
}

if (!eregi("".$admin_file.".php", $_SERVER['PHP_SELF'])) {
	die("Access Denied");
}

// --------------------------------------------------------
// Mapping between Phpnuke and Gallery2 language definition
// --------------------------------------------------------

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
	'portuguese'	=> 'pt', 
	'swedish'	=> 'sv', 
	'chinese'	=> 'zh');

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

	// only init if not already done so 	 
	if (isInitiated()) { 	 
		return true; 	 
	} 	 

	extract($var);
	require_once (substr($g2embedparams['g2Uri'],1)._G2_EMBED_PHP_FILE); 	 

	$g2currentlang = $phpnuke2G2Lang[$currentlang]; 	 

	$ret = GalleryEmbed :: init(array ( 	 
			'embedUri' => $g2embedparams['embedUri'], 	 
			'g2Uri' => $g2embedparams['g2Uri'], 	 
			'loginRedirect' => $g2embedparams['loginRedirect'], 	 
			'activeUserId' => $g2embedparams['activeUserId'], 	 
			'activeLanguage' => $g2currentlang, 	 
			'fullInit' => $fullInit)); 	 

	if ($ret) { 	 
		g2_message('G2 did not return a success status upon an init request. Here is the error message from G2: <br /> [#(1)]'.$ret->getAsHtml()); 	 
		return false; 	 
	} 	 
	isInitiated(true); 	 
	return true; 	 
}

/*********************************************************/
/* Standalone Message Function                           */
/*********************************************************/

function g2_message($mess) {
	global $admin, $module_name, $admin_file;
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
/* Save & Validate module config file                    */
/*********************************************************/

function SaveG2Config($var, $installed) {
	
	global $db, $prefix, $embedVersion;
	extract($var);

	if (!$g2embedparams) {
		$g2embedparams = array ();
	}
	if (!$g2mainparams) {
		$g2mainparams = array ();
	}

	if($installed) {
		$sql = "UPDATE ".$prefix."_g2config SET embedUri = '".$g2embedparams['embedUri']."', g2Uri = '".$g2embedparams['g2Uri']."', loginRedirect = '".$g2embedparams['loginRedirect']."', activeUserId = ".$g2embedparams['activeUserId'].", cookiepath = '".$g2embedparams['cookiepath']."', embedVersion = '".$embedVersion."'";
		$result = $db->sql_query($sql);
	}
	else {	
		$sql = "INSERT INTO ".$prefix."_g2config VALUES ('".$g2embedparams['embedUri']."', '".$g2embedparams['g2Uri']."', '".$g2embedparams['loginRedirect']."', ".$g2embedparams['activeUserId'].", '".$g2embedparams['cookiepath']."',0,0,'".$embedVersion."')";
		$result = $db->sql_query($sql) or die(mysql_error());	
	}	
	require_once (substr($g2embedparams['g2Uri'],1)._G2_EMBED_PHP_FILE);
	init($var);
	$cookiepath = $g2embedparams['cookiepath'];
	$ret = GalleryCoreApi::setPluginParameter('module','core','cookie.path',$cookiepath);
}

/******************************************************************/
/* Check if config files contains valid parameters       		  */
/* TODO: Check G2 had been really installed and not just copied	  */
/******************************************************************/

function check_g2configerror($embedphpfile, $vars=NULL)
{
	if (isset($vars)) {
		extract($vars);
		require_once (substr($embedphpfile,1)._G2_EMBED_PHP_FILE);
		$ret = GalleryEmbed::init(array(
			'embedUri' => $g2embedparams['embedUri'],
			'g2Uri' => $g2embedparams['g2Uri'],
			'loginRedirect' => $g2embedparams['loginRedirect'],
			'activeUserId' => "$uid",
			'activeLanguage' =>$g2currentlang));
		if($ret) {
			g2_message("<b>Error</b><br/>Either your Gallery2 installation has not been successfully completed or the values you specified in your integration settings are incorrect.");
		}
	}
}

/*********************************************************/
/* Update config db for new integration package			 */
/*********************************************************/
function update_database() {
	global $prefix, $db, $dbname,$embedVersion;

	$current_config = "SELECT * FROM ".$prefix."_g2config";
	$current_config_result = $db->sql_query($current_config);
	
	$row = $db->sql_fetchrow($current_config_result);

	$g2embedparams = array();

	$g2embedparams['g2Uri'] = "/".$row['relativeG2Path'];
	$g2embedparams['embedUri'] = "/".$row['embedUri'];
	$g2embedparams['loginRedirect'] = "/".$row['loginRedirect'];
	$g2embedparams['activeUserId'] = $row['activeUserId'];
	$g2embedparams['cookiepath'] = $row['cookiepath'];

	$vars = compact("g2embedparams");
	$delete_sql = "DROP TABLE ".$prefix."_g2config";
	$delete_res = $db->sql_query($delete_sql);

	$setup_sql = "CREATE TABLE ".$prefix."_g2config (
				embedUri VARCHAR( 255 ) NOT NULL ,
				g2Uri VARCHAR( 255 ) NOT NULL ,
				loginRedirect VARCHAR( 255 ) NOT NULL ,
				activeUserId INT( 10 ) NOT NULL ,
				cookiepath VARCHAR( 255 ) NOT NULL ,
				showSidebar TINYINT( 1 ) NOT NULL ,
				g2configurationDone TINYINT( 1 ) NOT NULL ,
				embedVersion VARCHAR( 255 ) NOT NULL
				)";
	$setup_result = $db->sql_query($setup_sql);

	check_g2configerror($g2embedparams['g2Uri'], $vars);
	SaveG2Config($vars, 0);
	$misc_sql = "UPDATE ".$prefix."_g2config SET g2configurationDone = 1";
	$db->sql_query($misc_sql);
	g2_message(_CFG_UPDATED."<br><br>Structure updated.");
}

/*********************************************************/
/* Display Main Admin Page                               */
/*********************************************************/

function DisplayMainPage() {
	global $admin, $prefix, $db, $currentlang, $admin_file, $module_name, $dbname, $embedVersion;

	include ("header.php");

	OpenTable();
	echo	"<br><center><a href=\"".$admin_file.".php?op=gallery2\">".
				"<img alt='Gallery::your photos on your website' src='modules/$module_name/images/g2.png' border=0></a><H3>Module Administration</H3>".
				"</center>";
	CloseTable();
	echo "<br/>";

	// Display the version information
	OpenTable();
	echo "<center><font class=\"option\"><b>Integration Package Status</b></font><br/>";
	$exist_sql = "SHOW TABLES FROM ".$dbname." LIKE '".$prefix."_g2config'";
	$exist_result = $db->sql_query($exist_sql);
	if($db->sql_numrows($exist_result) == 0) {
		$installed = 0;
		$setup_sql = "CREATE TABLE ".$prefix."_g2config (
					embedUri VARCHAR( 255 ) NOT NULL ,
					g2Uri VARCHAR( 255 ) NOT NULL ,
					loginRedirect VARCHAR( 255 ) NOT NULL ,
					activeUserId INT( 10 ) NOT NULL ,
					cookiepath VARCHAR( 255 ) NOT NULL ,
					showSidebar TINYINT( 1 ) NOT NULL ,
					g2configurationDone TINYINT( 1 ) NOT NULL ,
					embedVersion VARCHAR( 255 ) NOT NULL
					)";
		$setup_result = $db->sql_query($setup_sql);
		$version_text .= '<p style="color:green">You are currently installing the integration package for the first time.</p></center>';
	}
	else {
		$installed = 1;
		$config_sql = "SELECT embedVersion FROM ".$prefix."_g2config";
		$config_result = $db->sql_query($config_sql);

		list($currentEmbedVersion) = $db->sql_fetchrow($config_result);

		$current_version = explode('.', $currentEmbedVersion);

		$embedVersion_array = explode('.', $embedVersion);

		if( (int)$current_version[1] < (int) $embedVersion_array[1] ) {
			$update_button = "<p><center>This version (<b>".$embedVersion."</b>) of the integration package requires that you update your configuration database structure.  Click the button below to perform this operation.<br>Further configuration is disabled until you update your database structure.<br><b><font color=\"red\">This version of the integration package is only compatible with Gallery 2.1 and greater.</font></b><br>";
			$update_button .= "<form action=\"".$admin_file.".php\" method=\"post\">\n";
			$update_button .= "<input type=\"hidden\" name=\"op\" value=\"gallery2_update_database\">";
			$update_button .= "<input type=\"submit\" value=\"Update Database\"></form></center></p>";
			$update_needed = "disabled=\"true\"";
		}

		$minor_revision = (int) $current_version[2];

		$errno = 0;
		$errstr = $version_info = '';

		if ($fsock = @fsockopen('www.nukedgallery.net', 80, $errno, $errstr, 10)) {
			@fputs($fsock, "GET /upgradecheck/upgrade.txt HTTP/1.1\r\n");
			@fputs($fsock, "HOST: www.nukedgallery.net\r\n");
			@fputs($fsock, "Connection: close\r\n\r\n");

			$get_info = false;
			while (!@feof($fsock)) {
				if ($get_info) {
					$version_info .= @fread($fsock, 1024);
				}
				else {
					if (@fgets($fsock, 1024) == "\r\n")	{
						$get_info = true;
					}
				}
			}
			@fclose($fsock);

			$version_info = explode("\n", $version_info);
			$latest_head_revision = (int) $version_info[0];
			$latest_minor_revision = (int) $version_info[2];
			$latest_version = (int) $version_info[0] . '.' . (int) $version_info[1] . '.' . (int) $version_info[2];

			// UPDATE ME WHEN CHANGING MAJOR REV
			if ($latest_head_revision == 0 && $minor_revision == $latest_minor_revision) {	
				$version_text .= '<p style="color:green">You have the most current integration package.</p></center>';
			}
			else {
				$version_text .= '<p style="color:red">Your integration package is <b>not</b> up to date.';
				$version_text .= '<br />Latest version available is <b>' . $latest_version . '</b>.  Your installed version is <b>' . $currentEmbedVersion . '</b><br />';
				$version_text .= 'To see what has changed, read the ChangeLog here: <a href="http://www.nukedgallery.net/postp10252.html#10252">http://www.nukedgallery.net/postp10252.html#10252</a>.<br />';
				$version_text .= 'You can download the latest integration package from <a href="http://www.nukedgallery.net/downloads-cat11.html">http://www.nukedgallery.net/downloads-cat11.html</a>.</p></center>';
			}
		}
		else {
			if ($errstr) {
				$version_text .= '<p style="color:red">Socket connection error: ' . $errstr . '</p></center>';
			}
			else {
				$version_text .= '<p>PHP socket functions have been disabled.</p></center>';
			}
		}
	}

	echo $update_button;

	echo $version_text;
	CloseTable();
	echo "<br />";
	// End of version information display

	// Display integration settings
	OpenTable();
    if($installed == 0) {
        $embedUri = "/modules.php?name=".$module_name;
        $g2Uri = "/modules/".$module_name."/";
        $loginRedirect = '/modules.php?name=Your_Account';
        $activeUserId = 0;

		$c_result = $db->sql_query("SELECT config_value FROM ".$prefix."_bbconfig WHERE config_name='cookie_path'");
		list($cookie_path) = $db->sql_fetchrow($c_result);
		$cookiepath = $cookie_path;
	}
	else {
		$config_sql = "SELECT * FROM ".$prefix."_g2config";
		$config_result = $db->sql_query($config_sql);
		list($embedUri, $g2Uri, $loginRedirect, $activeUserId, $cookiepath, $showSidebar, $g2configurationDone, $embedVersion) = $db->sql_fetchrow($config_result);
	}


	echo "<center><font class=\"option\"><b>Gallery 2 Integration Settings</b></font></center><br/>";
	if($installed == 0) {
		echo "<center><font color=\"red\"><b>We have done our best to fill these values in for you.  However, you should double check them for correctness.</b></font></center><br/>";
	}
		echo"<form action=\"".$admin_file.".php\" method=\"post\">"
		."<table border=\"0\">"
		."<tr><td>URL to your embedded Gallery:</td>"
		."<td><input type=\"text\" $update_needed name=\"embedUri\" size=\"60\" value=\"".$embedUri."\" maxlength=\"90\"></td></tr>"
		."<tr><td>URL to your standalone Gallery:</td>"
		."<td><input type=\"text\" $update_needed name=\"g2Uri\" size=\"60\" value=\"".$g2Uri."\" maxlength=\"90\"></td></tr>"
		."<tr><td>"._LOGINREDIRECT.":</td>"
		."<td><input type=\"text\" $update_needed name=\"loginRedirect\" size=\"60\" value=\"".$loginRedirect."\" maxlength=\"90\"></td></tr>"
		."<tr><td>"._ACTIVEUSERID.":</td>"
		."<td><input type=\"text\" $update_needed name=\"activeUserId\" size=\"60\" value=\"".$activeUserId."\" maxlength=\"90\"></td></tr>"
		."<tr><td>Cookie Path:</td><td><input type=\"text\" $update_needed name=\"cookiepath\" size=\"60\" value=\"".$cookiepath."\"></td></tr>";
	echo "<tr><td>&nbsp;</td></tr><input type=\"hidden\" name=\"op\" value=\"gallery2_update_embed\"><input type=\"hidden\" name=\"installed\" value=".$installed."><tr><td><input type=\"submit\" $update_needed value=\""._UPDATEEMBEDSETTINGSG2."\"></td></tr></table></form>";
	CloseTable();

	// Sidebar settings
	OpenTable();
	echo "<center><font class=\"option\"><b>Gallery 2 Sidebar Settings</b></font></center><br/>";
	echo "<form action=\"".$admin_file.".php\" method=\"post\"><table border=\"0\"><td><input type=\"checkbox\" $update_needed name=\"showsidebar\" value=\"1\"";
	if ($showSidebar == 1) {
		echo " checked";
	}
	echo ">"._SHOWSIDEBAR."</td>";
	echo "<tr><td>&nbsp;</td></tr><input type=\"hidden\" name=\"op\" value=\"gallery2_update_main\"><tr><td><input type=\"submit\" $update_needed  value=\""._UPDATEMAINSETTINGSG2."\"></td></tr></table></form>";
	CloseTable();

	// Export Users
		OpenTable();
		echo "<center><font class=\"option\"><b>Export Users to Gallery 2</b></font></center><br/>";
		echo "<form action=\"".$admin_file.".php\" method=\"post\"><input type=\"submit\" $update_needed value=\"Export Users\" onclick=\"window.open('','myWin',config='height=500, width=500, toolbar=no, menubar=no, scrollbars=yes, resizable=no, location=no, directories=no, status=no'); this.form.target='myWin';this.form.action='".$admin_file.".php'\"><input type=\"hidden\" name=\"op\" value=\"gallery2_user_export\"></form>";
		CloseTable();
	

	include ("footer.php");

}

/*********************************************************/
/* Dummy, actual export is in export.php				 */
/*********************************************************/
 function form_g2UserExportSettings() { 
	return true;
 }

/*********************************************************/
/* Update Embed Settings                                  */
/*********************************************************/

function form_g2UpdateEmbedSettings() {
	$g2embedparams = array ();

	$g2embedparams['embedUri'] = $_POST['embedUri'];
	$g2embedparams['g2Uri'] = $_POST['g2Uri'];
	$g2embedparams['loginRedirect'] = $_POST['loginRedirect'];
	$g2embedparams['activeUserId'] = $_POST['activeUserId'];
	$g2embedparams['cookiepath'] = $_POST['cookiepath'];

	$vars = compact("g2embedparams");
	$installed = $_POST['installed'];
	check_g2configerror($g2embedparams['g2Uri'], $vars);
	SaveG2Config($vars, $installed);
	if($installed == 0) {
		g2_message(_CFG_UPDATED."<br><br><b>You must export your PHPNuke users to Gallery2 before your configuration is complete.</b>");
	}
	else {
		g2_message(_CFG_UPDATED);
	}
}

/*********************************************************/
/* Update Main Settings                                  */
/*********************************************************/

function form_g2UpdateMainSettings() {
	global $db, $prefix;

	if($_POST['showsidebar']) {
		$checked = 1;
	}
	else {
		$checked = 0;
	}
	$sql = "UPDATE ".$prefix."_g2config SET showSidebar = ".$checked;
	$result = $db->sql_query($sql);

	g2_message(_CFG_UPDATED);
}

/// ------------------------------------------------------------------------------------------
/// ---------------------------------- Admin Page Starts Here --------------------------------
/// ------------------------------------------------------------------------------------------

// ---- TODO: Allow G2 control Admin panel access on a per admin basis (from database)

$aid = substr("$aid", 0, 25);
$row = $db->sql_fetchrow($db->sql_query("SELECT radminsuper FROM ".$prefix."_authors WHERE aid='$aid'"));
if ($row['radminsuper'] == 1) {

	switch ($op) {
		case "gallery2" :
			DisplayMainPage();
			break;

		case "gallery2_update_embed" :
			form_g2UpdateEmbedSettings();
			break;

		case "gallery2_update_main" :
			form_g2UpdateMainSettings();
			break;

		case "gallery2_user_export" : 	 
			form_g2UserExportSettings(); 	 
			break;

		case "gallery2_update_database":
		    update_database();
		    break;

	}

} else {
	include ("header.php");

	OpenTable();
	echo "<center><b>"._ERROR."</b><br><br>You do not have administration permission for module \"$module_name\"</center>";
	CloseTable();
	include ("footer.php");
}
?>