<?php

/************************************************************************/
/* PHP-NUKE: Web Portal System                                          */
/* ===========================                                          */
/*                                                                      */
/* Copyright (c) 2002 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/*                                                                      */
/************************************************************************/
/* Additional security checking code 2003 by chatserv                   */
/* http://www.nukefixes.com -- http://www.nukeresources.com             */
/************************************************************************/



if (!eregi("admin.php", $_SERVER['PHP_SELF'])) { die ("Access Denied"); }
global $prefix, $db;
$aid = substr("$aid", 0,25);
$row = $db->sql_fetchrow($db->sql_query("SELECT radminsuper FROM " . $prefix . "_authors WHERE aid='$aid'"));
if ($row['radminsuper'] == 1) {


// ---- TODO: Allow G2 control Admin panel on per user basis (from database)

/*if ( !defined('ADMIN_FILE') )
{
	die("Illegal File Access");
}
global $prefix, $db, $admin_file;
if (!eregi("".$admin_file.".php", $_SERVER['SCRIPT_NAME'])) { die ("Access Denied"); }
$aid = substr("$aid", 0,25);
$row = $db->sql_fetchrow($db->sql_query("SELECT title, admins FROM ".$prefix."_modules WHERE title='G2'"));
$row2 = $db->sql_fetchrow($db->sql_query("SELECT name, radminsuper FROM ".$prefix."_authors WHERE aid='$aid'"));
$admins = explode(",", $row['admins']);
$auth_user = 0;
for ($i=0; $i < sizeof($admins); $i++) {
    if ($row2['name'] == "$admins[$i]" AND $row['admins'] != "") {
        $auth_user = 1;	
    }
}

if ($row2['radminsuper'] == 1 || $auth_user == 1) {*/



/*********************************************************/
/* Standalone Message Function									         */
/*********************************************************/

function g2_message($mess)
{
		global $admin, $bgcolor2, $prefix, $db, $currentlang, $multilingual, $admin_file;
		include ("header.php");
		OpenTable();
		echo"<br><center><a href=\"admin.php?op=gallery2\">
		<img alt='Gallery::your photos on your website' src='$nukeurl/modules/gallery2/images/g2Logo.gif' border=0></a><H3>Gallery2 Module Administration</H3></center>";
		CloseTable();
		echo "<br/>";
		
		OpenTable();
		echo "<center><font class=\"option\">";
    echo $mess;
    echo "</font></center>";
		CloseTable();
		
		include("footer.php");
}


/*********************************************************/
/* Main Function                                      */
/*********************************************************/

function SaveG2Config($var)
{
    if (file_exists("modules/". MOD_NAME."/gallery2.cfg")) {
       if (!is_writable("modules/". MOD_NAME."/gallery2.cfg")) {
           g2_message("<b>"._G2_ERROR."</b>: gallery2.cfg "._NOTWRITABLE);
       }
    }
    elseif (!is_writable("modules/". MOD_NAME."/")) {
       g2_message("<b>"._G2_ERROR."</b>: modules/gallery2/ "._NOTWRITABLE);
    }
    include("modules/". MOD_NAME."/gallery2.cfg");

    extract($var);

    if (!$g2embedparams) { $g2embedparams = array(); }
    if (!$g2mainparams) { $g2mainparams = array(); }

    $content = "<?\n"
    .'$g2embedparams = '
    .var_export($g2embedparams, TRUE)
    .";\n"
    .'$g2mainparams = '
    .var_export($g2mainparams, TRUE)
    .";\n"
    .'$g2configurationdone = "true"';
    
    $content .= "?>";

    $handle = fopen("modules/". MOD_NAME."/gallery2.cfg", "w");
	fwrite($handle, $content);
	fclose($handle);
	
	
}



/*********************************************************/
/* Main Function                                      */
/*********************************************************/

function gallery2() 
{
		global $admin, $bgcolor2, $prefix, $db, $currentlang, $multilingual, $admin_file;
		include ("header.php");
		OpenTable();
		echo"<br><center><a href=\"admin.php?op=gallery2\">
		<img alt='Gallery::your photos on your website' src='$nukeurl/modules/gallery2/images/g2Logo.gif' border=0></a><H3>Gallery2 Module Administration</H3></center>";
		CloseTable();
		echo "<br/>";
		
		// display embed settings
		
		include("modules/gallery2/gallery2.cfg");

		$path_found=false;
		
		if (file_exists($g2embedparams[embedphpfile]))
		{
		  $path_found=true;
		} 
	
		// embed settings
		
	  	OpenTable();
	  	echo "<center><font class=\"option\"><b>Gallery2 Embeding settings</b></font></center><br/>";
		echo "<form action=\"admin.php\" method=\"post\">"
		."<table border=\"0\">"
		."<tr><td>" . _PHPEMBEDFILE . ":</td>"
		."<td colspan=\"3\"><input type=\"text\" name=\"embedphpfile\" size=\"60\" value=\"".$g2embedparams[embedphpfile]."\" maxlength=\"90\"> <font class=\"tiny\"></font></td></tr>"
		."<tr><td>" . _EMBEDURI . ":</td>"
		."<td colspan=\"3\"><input type=\"text\" name=\"embedUri\" size=\"60\" value=\"".$g2embedparams[embedUri]."\" maxlength=\"90\"> <font class=\"tiny\"></font></td></tr>"
		."<tr><td>" . _RELATIVEG2PATH . ":</td>"
		."<td colspan=\"3\"><input type=\"text\" name=\"relativeG2Path\" size=\"60\" value=\"".$g2embedparams[relativeG2Path]."\" maxlength=\"90\"> <font class=\"tiny\"></font></td></tr>"
		."<tr><td>" . _LOGINREDIRECT . ":</td>"
		."<td colspan=\"3\"><input type=\"text\" name=\"loginRedirect\" size=\"60\" value=\"".$g2embedparams[loginRedirect]."\" maxlength=\"90\"> <font class=\"tiny\"></font></td></tr>"
		."<tr><td>" . _ACTIVEUSERID . ":</td>"
		."<td colspan=\"3\"><input type=\"text\" name=\"activeUserId\" size=\"60\" value=\"".$g2embedparams[activeUserId]."\" maxlength=\"90\"> <font class=\"tiny\"></font></td></tr>";
		
		if ($path_found==false)
		{
			echo "<tr><td colspan=\"2\"><align=center><br/><center><b>" . _G2_ERROR . " : "._PHPEMBEDFILE."</b><br/>"
			._PHPEMBEDFILE_ERROR
			."</center></td></tr>";
		}
		echo "<tr><td>&nbsp;</td></tr>"
		."<input type=\"hidden\" name=\"op\" value=\"gallery2_update_embed\">"
		."<tr><td><input type=\"submit\" value=\"" . _UPDATEEMBEDG2 . "\"></td></tr>"
		."</table></form>";
		CloseTable();
		
		// main settings
		
	  	OpenTable();
	  	echo "<center><font class=\"option\"><b>Gallery2 Main Options</b></font></center><br/>";
		echo "<form action=\"admin.php\" method=\"post\">"
		."<table border=\"0\">"
		."<td><input type=\"checkbox\" name=\"showsidebar\" value=\"true\"";
		if ($g2mainparams[showSidebar]=="true")
		{
			echo "checked=\"true\"";
		}
		echo ">"._SHOWSIDEBAR."</td>";
		echo "<tr><td>&nbsp;</td></tr>"		
		."<input type=\"hidden\" name=\"op\" value=\"gallery2_update_main\">"
		."<tr><td><input type=\"submit\" value=\"" . _UPDATEMAINPARAMG2 . "\"></td></tr>"
		."</table></form>";
		CloseTable();
		
		include("footer.php");
	
}

/*********************************************************/
/* Update Main Settings                                  */
/*********************************************************/

function gallery2UpdateEmbedSettings()
{
		$g2embedparams = array();
		
		$g2embedparams[embedphpfile] = $_POST['embedphpfile'];
		$g2embedparams[embedUri] = $_POST['embedUri'];
		$g2embedparams[relativeG2Path] = $_POST['relativeG2Path'];
		$g2embedparams[loginRedirect] = $_POST['loginRedirect'];
		$g2embedparams[activeUserId] = $_POST['activeUserId'];

		//$g2mainparams = array();
		//$g2mainparams[showSidebar] = 'false';
	    //$vars = compact("g2embedparams","g2mainparams");
	    
	    $vars = compact("g2embedparams");

	    SaveG2Config($vars);
	    
	    g2_message(_CFG_UPDATED);
}

/*********************************************************/
/* Update Main Settings                                  */
/*********************************************************/

function gallery2UpdateMainSettings()
{
		$g2mainparams = array();
		
		//$g2mainparams[showSidebar] = 'true';
		$g2mainparams[showSidebar] = $_POST['showsidebar'];

	    $vars = compact("g2mainparams");
	    SaveG2Config($vars);
	    
	    g2_message(_CFG_UPDATED);
}


switch($op) 
{
	
    case "gallery2":
    gallery2();
    break;
    
    case "gallery2_update_embed":
    gallery2UpdateEmbedSettings();
    break;
    
    case "gallery2_update_main":
    gallery2UpdateMainSettings();
    break;
    
    
}

} 
else 
{
	include("header.php");

	OpenTable();
	echo "<center><b>"._ERROR."</b><br><br>You do not have administration permission for module \"$module_name\"</center>";
	CloseTable();
	include("footer.php");
}


?>
