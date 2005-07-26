<?php

if (eregi("block-G2_Sidebar.php", $_SERVER['SCRIPT_NAME'])) {
    Header("Location: index.php");
    die();
}

global $admin, $user, $cookie;

define("_G2_EMBED_PHP_FILE","embed.php");
define("_G2_CONFIGURATION_NOT_DONE","The module has not yet been configured.");

include("modules/gallery2/gallery2.cfg");

if ($g2configurationdone != "true") {
	$content = _G2_CONFIGURATION_NOT_DONE; 
	return;
}

require_once($g2embedparams['embedphpfile']."/"._G2_EMBED_PHP_FILE);

if (is_admin($admin)) {
	$uid='admin';
}
else {
	if (is_user($user)) {
		cookiedecode($user);
		$uid='';  
		if (is_user($user)) {
			$uid = $cookie[0];
		}
	} 
}

$ret = GalleryEmbed::init(array(
	'embedPath' => $g2embedparams['embedPath'],
	'embedUri' => $g2embedparams['embedUri'],
	'relativeG2Path' => $g2embedparams['relativeG2Path'],
	'loginRedirect' => $g2embedparams['loginRedirect'],
	'activeUserId' => "$uid",
	'fullInit' => true));

if ($g2mainparams['showSidebar']=="true") {
	$content = "The Gallery2 sidebar is enabled.<br>You should disable it before using this block.";
	return true;
}
   
$g2moddata = GalleryEmbed::handleRequest();
list($ret,$html, $head) = GalleryEmbed::getImageBlock(array('blocks'=>'randomImage', 'show'=>'title'));

if (!isset($g2moddata['isDone'])) {
  echo 'isDone is not defined, something very bad must have happened.';
  exit;
}

if ($g2moddata['isDone']) {
  exit; 
}

$content = "<center>".$html."</center>";

?>