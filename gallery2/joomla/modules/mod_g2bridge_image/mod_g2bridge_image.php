<?php
/**
 * @package mod_gallery2_image
 * @copyright (C) 4 The Web
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @version $Id$
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once("components/com_gallery2/init.inc" );

//parameters
$align 		= $params->get( 'align' );
$block 		= $params->get( 'block' );
$header 	= $params->get( 'header' );
$title 		= $params->get( 'title' );
$date 		= $params->get( 'date' );
$views 		= $params->get( 'views' );
$owner 		= $params->get( 'owner' );
$itemId 	= (int) $params->get( 'itemId' );
$max_size 	= (int) $params->get( 'maxSize' );
$link_target= $params->get( 'link_target' );
$frame		= $params->get( 'frame' );
$strip_anchor= $params->get( 'strip_anchor' );
$count 		= (int) $params->get( 'count' );

/* make multiple image if needed */
if(!empty($count) && $count > 1){
	$tmp = $block;
	for ($i=1;$i < $count;$i++){
		$block .= '|'.$tmp;
	}
}

/* Create the show array */
$array['show'] = array();
if ($title == 1) {
	$array['show'][] = 'title';
}
if ($date == 1) {
	$array['show'][] = 'date';
}
if ($views == 1) {
	$array['show'][] = 'views';
}
if ($owner == 1) {
    $array['show'][] = 'owner';
} 
if ($header == 1) {
    $array['show'][] = 'heading';
} 
$array['show'] = (count($array['show']) > 0) ? implode('|', $array['show']) : 'none';

/* add itemId if set */
if(!empty($itemId)) {
	$array['itemId'] = $itemId; 
}

/* set the rest */
$array['blocks']	 = $block;
$array['maxSize'] 	 = !empty($max_size) ? $max_size : 150;
$array['linkTarget'] = $link_target;
$array['itemFrame']  = $frame;	

$content = '<div align="'.$align.'">';

if($block=="specificItem" AND empty($itemId)){
	$content .= '<strong>Error</strong><br />You have selected no "itemid" and this must be done if you select "Specific Picture"';
} else {
	core::initiatedG2();
	list ($ret, $imageBlockHtml, $headContent) = GalleryEmbed::getImageBlock($array);
	if ($ret) {
		print "<h2>Fatal G2 error</h2> Here's the error from G2:<br />" .$ret->getAsHtml();
	}
	
	/* add css and js */
	core::parseHead($headContent);
	/* utf8 */
	$imageBlockHtml = core::decoded($imageBlockHtml);
	
	$content .= ($strip_anchor == 1) ? strip_tags($imageBlockHtml, '<img><table><tr><td><div><h3>') : $imageBlockHtml;
	
	/* finish Gallery 2 */
	GalleryEmbed::done();
}

$content .= '</div>';
?>