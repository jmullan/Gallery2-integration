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
/************************************************************************/

global $admin_file;

if(!isset($admin_file)) {
	$admin_file = "admin";
}

if (!eregi("".$admin_file.".php", $_SERVER['PHP_SELF'])) { die ("Access Denied"); }
$module_name = "gallery2";
include_once("modules/$module_name/admin/language/lang-".$currentlang.".php");


switch($op) {

    case "gallery2":
    include("modules/$module_name/admin/index.php");
    break;
    
    case "gallery2_update_embed":
    include("modules/$module_name/admin/index.php");
    break;
    
    case "gallery2_update_main":
    include("modules/$module_name/admin/index.php");
    break;
    
    case "gallery2_user_export":
    include("modules/$module_name/admin/index.php");
    break;
    

}

?>
