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
/* Standalone Message Function                           */
/*********************************************************/

function g2_message($mess) {
	global $admin, $bgcolor2, $module_name, $admin_file;
	include ("header.php");
	OpenTable();
	echo	"<br><center><a href=\"".$admin_file.".php?op=gallery2\">".
				"<img alt='Gallery::your photos on your website' src='modules/$module_name/images/g2Logo.gif' border=0></a><H3>Gallery2 Module Administration</H3>".
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

function init() {

	// only init if not already done so
	if (isInitiated()) {
		return true;
	}

	include ("modules/gallery2/gallery2.cfg");
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
		include ("modules/gallery2/gallery2.cfg");
		
		// init G2 transaction, load G2 API, if not already done so
		if (!init()) {
			return false;
		}
		if (is_int($entityType)) {
			$entityType = $entityType == 0 ? 'GalleryUser' : 'GalleryGroup';
		}
		
		require_once ($g2embedparams['embedphpfile']."/".'modules/core/classes/ExternalIdMap.class');
		
		$ret = ExternalIdMap :: addMapEntry(array ('externalId' => $externalId, 'entityType' => $entityType, 'entityId' => $entityId));
		if ($ret->isError()) {
			g2_message('Failed to create a extmap entry for role uid ['.$externalId.'] and entityId ['.$entityId.'], entityType ['.$entityType.']. Here is the error message from G2: <br />'.$ret->getAsHtml());
			return false;
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

/*********************************************************/
/* Init G2 API                                           */
/* Exports phpnuke users to g2 and 			             */
/* Initial user/group management synchronization.        */
/* Output: False if error
/*         user info text to display if success					*/
/*********************************************************/

define("NB_USER_TO_EXPORT_BY_PASS", 2000);

function g2_phpnukeTog2UserExport() 
{
	global $db, $user_prefix,$phpnuke2G2Lang, $admin_file;	
	
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
		g2_message('Enable to fetch the admin group. Here is the error message from G2: <br />'.$ret->getAsHtml());
	    return false;
	}
	list ($ret, $adminList) = GalleryCoreApi::fetchUsersForGroup($adminGroupId, 5);
	if ($ret->isError()) {
		g2_message('Enable to fetch a member in the admin group. Here is the error message from G2: <br />'.$ret->getAsHtml());
	    return false;
	}
	
	foreach ($adminList as $adminId => $adminName) {
	}
	
	if (!isset ($mapsbyexternalid["admin"])) {
		if (!g2addexternalMapEntry("admin", $adminId, 0)) {
 			return false;
		}
		$outputtext .="Admin account created<br/><br/>";
	}
	
	// TODO: Update of the admin account if it already exists
			
	// Export all standard phpnuke users (except anonymous: id=1) to G2 defaut group
	// --- dari (http://www.nukedgallery.net) addon (multiple pass)
	$sql = "SELECT count(*) AS ucount FROM ".$user_prefix."_users";
	$user_count = $db->sql_fetchrow($db->sql_query($sql));
	$nextpage = 0;
	
	$startuser = $_POST['startuser'];
	
	for($i = $startuser; $i <= $user_count['ucount']; $i++) {
  	
		if($i == $startuser+NB_USER_TO_EXPORT_BY_PASS) {
			$nextpage = 1;
			break;
		}
			
		$outputtext .= "UserId($i) ";

		$query='SELECT user_id, name, username, user_password, user_email, user_lang, user_regdate FROM '.$user_prefix."_users WHERE user_id = $i LIMIT 1"; 
			
		$result=$db->sql_query($query);
			
		if($db->sql_numrows($result) != 0) { 
			$sqluserdata		= $db->sql_fetchrow($result);
			$nukeuser_id 		= $sqluserdata['user_id'];
			$nukeuser_uname		= $sqluserdata['username'];
			$nukeuser_name		= $sqluserdata['name'];
			$nukeuser_cryptpass	= $sqluserdata['user_password'];
			$nukeuser_email		= $sqluserdata['user_email'];
			$nukeuser_lang		= $sqluserdata['user_lang'];
			$g2nukeuser_lang 	= $phpnuke2G2Lang[$nukeuser_lang];
			$nukeuser_regdate	= $sqluserdata['user_regdate'];
				
			list( $regmonth, $regday, $regyear ) = split( " ", $nukeuser_regdate );
			$regphpusertimestamp = mktime( 0, 0, 0, $regmonth, $regday, $regyear );
		
			// Get Arguments for the new user:
			$args['fullname']  	=	$nukeuser_name;
			$args['username'] 	= 	$nukeuser_uname;
			$args['hashedpassword'] =	$nukeuser_cryptpass; 
			$args['hashmethod'] = 	'md5';
			$args['email'] 		=	$nukeuser_email;
			$args['language']	=	$g2nukeuser_lang;
			$args['creationtimestamp']	=	$regphpusertimestamp;
			
			// if the map exists, just update the user data
			if (isset ($mapsbyexternalid[$nukeuser_id])) {
				$ret = GalleryEmbed :: updateUser($nukeuser_id, $args);
				if (!$ret->isSuccess()) {
					g2_message('Failed to update G2 user with extId ['.$nukeuser_id.']. Here is the error message from G2: <br />'.$ret->getAsHtml());
					return false;
				}
				else {
					$outputtext .= $nukeuser_uname.' (has been updated)<br/>';
				}
			}
			//  else we create the user
			else {
				$ret = GalleryEmbed :: createUser($nukeuser_id, $args);
				if (!$ret->isSuccess()) {
					g2_message('Failed to create G2 user with extId ['.$nukeuser_id.']. Here is the error message from G2: <br />'.$ret->getAsHtml());
					return false;
				}
					
				if (!g2addexternalMapEntry($nukeuser_uname, $nukeuser_id, 0)) {
					return false;
				}
				else {
					$outputtext .= $nukeuser_uname.' (added to g2 users)<br/>';
				} 			
			}
		}
	}
	
	$outputtext .= "<br/>".($i-$startuser)." users imported...<br/><br/>";
	
	// Eveything is ok till now, so ask for the next page if needed

	if($nextpage == 1) {
	  $startUserNextPage = "<input type=\"hidden\" name=\"startuser\" value=\"$i\">";
			$outputtext .="<form action=\"".$admin_file.".php\" method=\"post\"><table border=\"0\">";
			$outputtext .="<input type=\"hidden\" name=\"op\" value=\"gallery2_user_export\"><tr><td><input type=\"submit\" value=\""._G2_NEXT_PAGE."\">$startUserNextPage</td></tr></table></form>";
	}
	else {
		$outputtext .= _USER_EXPORT_COMPLETED."<br/>";
	}
	return $outputtext;
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
		g2_message("<b>"._G2_ERROR."</b>: modules/gallery2/ "._NOTWRITABLE);
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
	include ("modules/gallery2/gallery2.cfg");

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
				"<img alt='Gallery::your photos on your website' src='modules/$module_name/images/g2Logo.gif' border=0></a><H3>Module Administration</H3>".
				"</center>";
	CloseTable();
	echo "<br/>";

	// display embed settings

	include ("modules/gallery2/gallery2.cfg");

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
	echo "<form action=\"".$admin_file.".php\" method=\"post\"><table border=\"0\">";
	echo "<input type=\"hidden\" name=\"op\" value=\"gallery2_user_export\"><tr><td><input type=\"submit\" value=\""._G2USEREXPORT."\">$hidden_input</td></tr></table></form>";
	CloseTable();

	include ("footer.php");

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

	include ("modules/gallery2/gallery2.cfg");

	$g2mainparams['showSidebar'] = $_POST['showsidebar'];

	$vars = compact("g2mainparams");
	
	check_g2configerror($g2embedparams['embedphpfile']);
	
	SaveG2Config($vars);

	g2_message(_CFG_UPDATED);
}

/*********************************************************/
/* Export phpNuke users to Gallery2                      */
/*********************************************************/

function form_g2UserExportSettings() {
	include ("modules/gallery2/gallery2.cfg");

	check_g2configerror($g2embedparams[embedphpfile]);
	
	$output = g2_phpnukeTog2UserExport();
	if ($output==false) {
		g2_message(_USER_EXPORT_FAILED);
	}
	
	SaveG2Config(array(),'true');

	g2_message($output);
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