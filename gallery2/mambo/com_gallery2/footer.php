<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
include_once("components/com_gallery2/version.php" );
//print version and Debug info
	//print '<div align="center">Gallery2 Component version: '.$ver.'.'.$rel.'.'.$bug.'</div>';
//output for bug fix
global $debug;
if($debug==1){
	print '<br />'.$lang.' - '.$user_id; 
	print G2helperclass::debug('component');
}//end debug
?>