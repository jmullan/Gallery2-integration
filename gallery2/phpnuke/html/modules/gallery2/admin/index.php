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
 * Gallery 2 admin for PHPNuke.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */

define("MOD_NAME","gallery2");

global $prefix, $db, $g2config_error, $currentlang, $admin_file;

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
  	 
function init() { 	 
	// only init if not already done so 	 
	if (isInitiated()) { 	 
		return true; 	 
	} 	 

	include ("modules/".MOD_NAME."/gallery2.cfg"); 	 
	require_once ($g2embedparams['embedphpfile']."/"._G2_EMBED_PHP_FILE); 	 

	$g2currentlang = $phpnuke2G2Lang[$currentlang]; 	 

	$ret = GalleryEmbed :: init(array ( 	 
			'embedPath' => $g2embedparams['embedPath'], 	 
			'embedUri' => $g2embedparams['embedUri'], 	 
			'relativeG2Path' => $g2embedparams['relativeG2Path'], 	 
			'loginRedirect' => $g2embedparams['loginRedirect'], 	 
			'activeUserId' => '', 	 
			'activeLanguage' => $g2currentlang, 	 
			'fullInit' => $fullInit)); 	 

	if (!$ret->isSuccess()) { 	 
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
/* Save & Validate module config file                    */
/*********************************************************/

function SaveG2Config($var, $forceValidate=false) {
	if (file_exists("modules/".MOD_NAME."/gallery2.cfg")) {
		if (!is_writable("modules/".MOD_NAME."/gallery2.cfg")) {
			g2_message("<b>"._G2_ERROR."</b>: gallery2.cfg "._NOTWRITABLE);
		}
	}
	elseif (!is_writable("modules/".MOD_NAME."/")) {
		g2_message("<b>"._G2_ERROR."</b>: modules/".MOD_NAME."/ "._NOTWRITABLE);
	}
	include ("modules/".MOD_NAME."/gallery2.cfg");

	extract($var);

	if (!$g2embedparams) {
		$g2embedparams = array ();
	}
	if (!$g2mainparams) {
		$g2mainparams = array ();
	}

	$content = "<?php\n".'$g2embedparams = '.var_export($g2embedparams, TRUE).";\n".'$g2mainparams = '.var_export($g2mainparams, TRUE).";\n";
	if ($forceValidate=='true') {
		$content .='$g2configurationdone = \'true\';';
	}
	else {
		if ($g2configurationdone=='true') $content .='$g2configurationdone = \'true\';';
		else $content .='$g2configurationdone = \'false\';';
	}
	$content .= " \n?>";

	$handle = fopen("modules/".MOD_NAME."/gallery2.cfg", "w");
	fwrite($handle, $content);
	fclose($handle);
	
 	require_once ($g2embedparams['embedphpfile']."/"._G2_EMBED_PHP_FILE);
	init();
	$cookiepath = $g2embedparams['cookiepath'];
	$ret = GalleryCoreApi::setPluginParameter('module','core','cookie.path',$cookiepath);
}

/******************************************************************/
/* Check if config files contains valid parameters       		  */
/* TODO: Check G2 had been really installed and not just copied	  */
/******************************************************************/

function check_g2configerror($embedphpfile, $vars=NULL)
{
	include ("modules/".MOD_NAME."/gallery2.cfg");

	if (!file_exists($embedphpfile)) {
		g2_message ("<b>"._G2_ERROR." : "._PHPEMBEDFILE."</b><br/>"._PHPEMBEDFILE_ERROR);
	}
	if (isset($vars)) {
		extract($vars);
		require_once ($g2embedparams['embedphpfile']."/"._G2_EMBED_PHP_FILE);
		$ret = GalleryEmbed::init(array(
			'embedPath' => $g2embedparams['embedPath'],
			'embedUri' => $g2embedparams['embedUri'],
			'relativeG2Path' => $g2embedparams['relativeG2Path'],
			'loginRedirect' => $g2embedparams['loginRedirect'],
			'activeUserId' => "$uid",
			'activeLanguage' =>$g2currentlang));
		if($ret->isError()) {
			g2_message("<b>Error</b><br/>Either your Gallery2 installation has not been successfully completed or the values you specified in your integration settings are incorrect.");
		}
	}
}

/*********************************************************/
/* Display Main Admin Page                               */
/*********************************************************/

function DisplayMainPage() {
	global $admin, $bgcolor2, $prefix, $db, $currentlang, $multilingual, $admin_file, $module_name;

	include ("header.php");
	OpenTable();
	echo	"<br><center><a href=\"".$admin_file.".php?op=gallery2\">".
				"<img alt='Gallery::your photos on your website' src='modules/$module_name/images/g2.png' border=0></a><H3>Module Administration</H3>".
				"</center>";
	CloseTable();
	echo "<br/>";

	// display embed settings

	include ("modules/".MOD_NAME."/gallery2.cfg");

	$path_found = false;

	if (file_exists($g2embedparams['embedphpfile'])) {
		$path_found = true;
	}

	// --------------  embed settings

	OpenTable();

    if($g2configurationdone == 'false') {
		$path  = $_SERVER['SCRIPT_NAME'];
		$path = preg_replace('/[#\?].*/', '', $path);
		$path = preg_replace('/\.php\/.*$/', '', $path);
		if (substr($path, -1, 1) == '/') {
			$path .= 'dummy';
		}
		$path = dirname($path);
		//FIXME: This is VERY slow!!
		if (preg_match('!^[/\\\]*$!', $path)) {
			$path = '/';
		}
		
		$g2embedparams['embedPath'] = $path;
        $t_embedphpfile = dirname(__FILE__);
        $a_embedphpfile = explode("/",$t_embedphpfile);
        $dummy = array_pop($a_embedphpfile);
        $g2embedparams['embedphpfile'] = implode("/",$a_embedphpfile)."/";
        $g2embedparams['embedUri'] = "modules.php?name=".$module_name;
        $g2embedparams['relativeG2Path'] = "modules/".$module_name."/";
        $g2embedparams['loginRedirect'] = 'modules.php?name=Your_Account';
        $g2embedparams['activeUserId'] = 0;

		$c_result = $db->sql_query("SELECT config_value FROM ".$prefix."_bbconfig WHERE config_name='cookie_path'");
		list($cookie_path) = $db->sql_fetchrow($c_result);
		$g2embedparams['cookiepath'] = $cookie_path;
	}

	echo "<center><font class=\"option\"><b>Gallery2 Integration Settings</b></font></center><br/><center><font color=\"red\"><b>We have done our best to fill these values in for you.  However, you should double check them for correctness.</b></font></center><br/>"
		."<form action=\"".$admin_file.".php\" method=\"post\">"
		."<table border=\"0\"><tr><td>"._PHPEMBEDFILE.":</td>"
		."<td><input type=\"text\" name=\"embedphpfile\" size=\"60\" value=\"".$g2embedparams['embedphpfile']."\" maxlength=\"90\"></td></tr>"
		."<tr><td>"._EMBEDURI.":</td>"
		."<td><input type=\"text\" name=\"embedUri\" size=\"60\" value=\"".$g2embedparams['embedUri']."\" maxlength=\"90\"></td></tr>"
		."<tr><td>URL path from document root to embedURI:</td>"
		."<td><input type=\"text\" name=\"embedPath\" size=\"60\" value=\"".$g2embedparams['embedPath']."\" maxlength=\"90\"></td></tr>"
		."<tr><td>"._RELATIVEG2PATH.":</td>"
		."<td><input type=\"text\" name=\"relativeG2Path\" size=\"60\" value=\"".$g2embedparams['relativeG2Path']."\" maxlength=\"90\"></td></tr>"
		."<tr><td>"._LOGINREDIRECT.":</td>"
		."<td><input type=\"text\" name=\"loginRedirect\" size=\"60\" value=\"".$g2embedparams['loginRedirect']."\" maxlength=\"90\"></td></tr>"
		."<tr><td>"._ACTIVEUSERID.":</td>"
		."<td><input type=\"text\" name=\"activeUserId\" size=\"60\" value=\"".$g2embedparams['activeUserId']."\" maxlength=\"90\"></td></tr>"
		."<tr><td>Cookie Path:</td><td><input type=\"text\" name=\"cookiepath\" size=\"60\" value=\"".$g2embedparams['cookiepath']."\"></td></tr>";
	echo "<tr><td>&nbsp;</td></tr><input type=\"hidden\" name=\"op\" value=\"gallery2_update_embed\"><tr><td><input type=\"submit\" value=\""._UPDATEEMBEDSETTINGSG2."\"></td></tr></table></form>";
	CloseTable();

	// --------------  main settings

	OpenTable();
	echo "<center><font class=\"option\"><b>Gallery2 Main Settings</b></font></center><br/>";
	echo "<form action=\"".$admin_file.".php\" method=\"post\"><table border=\"0\"><td><input type=\"checkbox\" name=\"showsidebar\" value=\"1\"";
	if ($g2mainparams['showSidebar'] == "true") {
		echo " checked";
	}
	echo ">"._SHOWSIDEBAR."</td>";
	echo "<tr><td>&nbsp;</td></tr><input type=\"hidden\" name=\"op\" value=\"gallery2_update_main\"><tr><td><input type=\"submit\" value=\""._UPDATEMAINSETTINGSG2."\"></td></tr></table></form>";
	CloseTable();

	// -------------- user export settings
	// --- Dari (http://www.nukedgallery.net) addon: split user export in a multiple pass ---
	
	$hidden_input = "<input type=\"hidden\" name=\"startuser\" value=\"2\">";
	OpenTable();
	echo "<center><font class=\"option\"><b>Export Users to Gallery2</b></font></center><br/>";
	echo "<form action=\"".$admin_file.".php\" method=\"post\"><input type=\"submit\" value=\"Export Users\" onclick=\"window.open('','myWin',config='height=500, width=500, toolbar=no, menubar=no, scrollbars=yes, resizable=no, location=no, directories=no, status=no'); this.form.target='myWin';this.form.action='".$admin_file.".php'\"><input type=\"hidden\" name=\"op\" value=\"gallery2_user_export\"></form>";
	CloseTable();

	include ("footer.php");

}


 function form_g2UserExportSettings() { 	 
         include ("modules/".MOD_NAME."/gallery2.cfg"); 	 
  	 
         check_g2configerror($g2embedparams['embedphpfile']); 	 
  	 
         SaveG2Config(array(),'true'); 	 	 
 }

/*********************************************************/
/* Update Embed Settings                                  */
/*********************************************************/

function form_g2UpdateEmbedSettings() {
	$g2embedparams = array ();

	$g2embedparams['embedphpfile'] = $_POST['embedphpfile'];
	$g2embedparams['embedUri'] = $_POST['embedUri'];
	$g2embedparams['relativeG2Path'] = $_POST['relativeG2Path'];
	$g2embedparams['loginRedirect'] = $_POST['loginRedirect'];
	$g2embedparams['activeUserId'] = $_POST['activeUserId'];
	$g2embedparams['embedPath'] = $_POST['embedPath'];
	$g2embedparams['cookiepath'] = $_POST['cookiepath'];

	$vars = compact("g2embedparams");

	check_g2configerror($g2embedparams[embedphpfile], $vars);
	SaveG2Config($vars);

	g2_message(_CFG_UPDATED."<br><br>If this is your first time configuring Gallery2, you must export your PHPNuke users to Gallery2 before your configuration is complete.");
}

/*********************************************************/
/* Update Main Settings                                  */
/*********************************************************/

function form_g2UpdateMainSettings() {

	include ("modules/".MOD_NAME."/gallery2.cfg");

	$g2mainparams['showSidebar'] = $_POST['showsidebar'];

	$vars = compact("g2mainparams");
	
	check_g2configerror($g2embedparams['embedphpfile']);
	
	SaveG2Config($vars);

	g2_message(_CFG_UPDATED);
}

/// ------------------------------------------------------------------------------------------
/// ---------------------------------- Admin Page Start Here ---------------------------------
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
	}

} else {
	include ("header.php");

	OpenTable();
	echo "<center><b>"._ERROR."</b><br><br>You do not have administration permission for module \"$module_name\"</center>";
	CloseTable();
	include ("footer.php");
}
?>