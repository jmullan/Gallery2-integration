<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin  [v.2.0]                                |
// +---------------------------------------------------------------------------+
// | public_html/xml.php                                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2006 Wayne Patterson [suprsidr@gmail.com]                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

require_once( '../lib-common.php');

    /**
     * Dynamic query for items
     * @param int $userId
     * @return array object GalleryStatus a status code
     *               array of item ids
     * @static
     */
    function getChildIds($userId, $param='date', $orderBy='creationTimestamp',
	    $orderDirection=ORDER_DESCENDING, $table='GalleryEntity', $id='id') {
	global $gallery;
	$storage =& $gallery->getStorage();
	list ($ret, $params) = GalleryCoreApi::fetchAllPluginParameters('module', 'dynamicalbum');
	if ($ret) {
	    return array($ret, null);
	}
	$size = $params['size.' . $param];
	$type = $params['type.' . $param];
	if (!$size) {
	    return array(GalleryCoreApi::error(ERROR_PERMISSION_DENIED), null);
	}

	list ($show, $albumId) = GalleryUtilities::getRequestVariables('show', 'albumId');
	if (!empty($show)) {
	    $type = $show;
	}
	switch ($type) {
	case 'data':
	    $class = 'GalleryDataItem';
	    break;
	case 'all':
	    $class = 'GalleryItem';
	    break;
	case 'album':
	    $class = 'GalleryAlbumItem';
	    break;
	default:
	    return array(GalleryCoreApi::error(ERROR_BAD_PARAMETER), null);
	}
	if (!isset($table)) {
	    $table = $class;
	}

	$query = '[' . $table . '::' . $id . '] IS NOT NULL';
	if (!empty($albumId)) {
	    list ($ret, $sequence) = GalleryCoreApi::fetchParentSequence($albumId);
	    if ($ret) {
		return array($ret, null);
	    }
	    if (!empty($sequence)) {
		$sequence = implode('/', $sequence) . '/' . (int)$albumId . '/%';
		$query = '[GalleryItemAttributesMap::parentSequence] LIKE ?';
		$table = 'GalleryItemAttributesMap';
		$id = 'itemId';
	    } else {
		$query = '[' . $table . '::' . $id . '] <> ' . (int)$albumId;
	    }
	}
	if ($table == $class) {
	    $class = null;
	}
	list ($ret, $query, $data) = GalleryCoreApi::buildItemQuery(
		$table, $id, $query, $orderBy, $orderDirection,
		$class, 'core.view', false, $userId);
	if ($ret) {
	    return array($ret, null);
	}
	if (empty($query)) {
	    return array(null, array());
	}
	if (!empty($sequence)) {
	    array_unshift($data, $sequence);
	}

	list ($ret, $searchResults) = $gallery->search($query, $data,
		array('limit' => array('count' => $size)));
	if ($ret) {
	    return array($ret, null);
	}
	$itemIds = array();
	while ($result = $searchResults->nextResult()) {
	    $itemIds[] = $result[0];
	}
// start item display loop
	if (!empty($itemIds)) 
    { 
        foreach( $itemIds as $value ) 
        {
            list ($ret, $childItem) = GalleryCoreApi::loadEntitiesById($value);
            if ($ret) 
		    {
                print "Error loading childItems:" . $ret->getAsHtml();
            }
			if(!($childItem->entityType == "GalleryAlbumItem")){

			    $currentID = $childItem->getId();
    		    list ($ret, $thumbnailList) = GalleryCoreApi::fetchThumbnailsByItemIds(array($currentID));
    		    if ($ret)
   			    {
        		    return array($ret->wrap(__FILE__, __LINE__), null);
    		    }
				$display .= "        <item>\n";
			    $display .= "            <title>" . getTitle($childItem) . "</title>\n";;
				$display .= "            <id>" . $childItem->getId() . "</id>\n";
				$display .= "            <link>" . getLink($childItem) . "</link>\n";
			    $display .= "            <view>" . getView($childItem) . "</view>\n";
			    $display .= "            <thumbUrl>" . getThumbUrl($childItem) . "</thumbUrl>\n";
			    $display .= "            <width>" . getWidth($childItem) . "</width>\n";
			    $display .= "            <height>" . getHeight($childItem) . "</height>\n";
				$display .= "            <mime>" . getMime($childItem) . "</mime>\n";
				if (!$ret && !empty($thumbnailList)) {
				$display .= "            <description><![CDATA[<a href=\"" . getLink($childItem) . "\"><img border=\"0\" src=\"" . getThumbUrl($childItem) . "\" width=\"" . getWidth($thumbnailList[$currentID]) . "\" height=\"" . getHeight($thumbnailList[$currentID]) . "\"/></a><br/>" . getTitle($childItem) . "]]></description>\n";
				}
				$display .= "            <guid isPermaLink=\"false\">" . getLink($childItem) . "</guid>\n";
				$display .= "            <pubDate>" . date('r', $childItem->getModificationTimestamp()) . "</pubDate>\n";
				$display .= "        </item>\n"; 
            }				
        }
		return $display;
    }
// end item display loop
} 

function getAlbumList ($id) 
{
	global $gallery;
	$display = "";
	$urlGenerator =& $gallery->getUrlGenerator();
    list ($ret, $Albums) = GalleryCoreApi::fetchAlbumTree();       
    list ($ret, $Albums) = GalleryCoreApi::loadEntitiesById(GalleryUtilities::arrayKeysRecursive($Albums));
    foreach ($Albums as $Albums){
		if (($Albums->canContainChildren == 1 && $Albums->parentId == $id) || ($Albums->canContainChildren == 1 && $Albums->getId() == $id) || ($Albums->canContainChildren == 1 && $Albums->parentId == 418) || empty($id)) 
		{
		    $display .="    <album>\n";
		    $display .= "        <title>" . cdata($Albums->getTitle()) . "</title>\n";
			$display .= "        <parentId>" . cdata($Albums->parentId) . "</parentId>\n";
			$display .= "        <owner>" . cdata(getOwner($Albums->ownerId)) . "</owner>\n";
			$display .= "        <id>" . cdata($Albums->getId()) . "</id>\n";
			$display .="    </album>\n";			
		} 
	}

	return $display;
}
		
function getItems ($id) 
{
    global $gallery;
	$display = "";

    list ($ret, $entity) = GalleryCoreApi::loadEntitiesById( $id );
    if ($ret) 
	{
        print "Error loading Entity:" . $ret->getAsHtml();
    }

    list ($ret, $childIds) = GalleryCoreApi::fetchChildItemIds($entity);
    if ($ret) 
	{
       print "Error finding child item ids:" . $ret->getAsHtml();
    }

    if (!empty($childIds)) 
    { 
        foreach( $childIds as $value ) 
        {
            list ($ret, $childItem) = GalleryCoreApi::loadEntitiesById($value);
            if ($ret) 
		    {
                print "Error loading childItems:" . $ret->getAsHtml();
            }
			if(!($childItem->entityType == "GalleryAlbumItem")){

			    $currentID = $childItem->getId();
				
    		    list ($ret, $thumbnailList) = GalleryCoreApi::fetchThumbnailsByItemIds(array($currentID));
    		    if ($ret)
   			    {
        		    return array($ret->wrap(__FILE__, __LINE__), null);
    		    }
				$display .= "        <item>\n";
			    $display .= "            <title>" . getTitle($childItem) . "</title>\n";
				$display .= "            <id>" . $childItem->getId() . "</id>\n";
				$display .= "            <link>" . getLink($childItem) . "</link>\n";
			    $display .= "            <view>" . getView($childItem) . "</view>\n";
			    $display .= "            <thumbUrl>" . getThumbUrl($childItem) . "</thumbUrl>\n";
			    $display .= "            <width>" . getWidth($childItem) . "</width>\n";
			    $display .= "            <height>" . getHeight($childItem) . "</height>\n";
				$display .= "            <mime>" . getMime($childItem) . "</mime>\n";
				if (!$ret && !empty($thumbnailList)) {
				$display .= "            <description>". cdata("<a href=\"" . getLink($childItem) . "\"><img border=\"0\" src=\"" . getThumbUrl($childItem) . "\" width=\"" . getWidth($thumbnailList[$currentID]) . "\" height=\"" . getHeight($thumbnailList[$currentID]) . "\"/></a><br/>" . getTitle($childItem)) ."</description>\n";
				}
				$display .= "            <guid isPermaLink=\"false\">" . getLink($childItem) . "</guid>\n";
				$display .= "            <pubDate>" . date('r', $childItem->getModificationTimestamp()) . "</pubDate>\n";
				$display .= "            <preferred>" . getPreferredLink($childItem) . "</preferred>\n";
				$display .= "        </item>\n"; 
            }				
        }
		return $display;
    }
}

function getResizes($item)
{
    $itemId = $item->getId();
    list ($ret, $resizes) = GalleryCoreApi::fetchResizesByItemIds(array($itemId));
	if ($ret) 
	{
        print "Error loading ResizesByItemIds:" . $ret->getAsHtml();
    }
	if (isset($resizes)) {
	    foreach ($resizes as $resized) {
				$display .= getView($resized[0]);
		}
	}else{
	    $display .= "none";
	}
	return $display;
}
function getOwner($id)
{
    list ($ret, $entity) = GalleryCoreApi::loadEntitiesById( $id );
	$owner = $entity->userName;
	return $owner;
}

function getTitle($item) {
    $title = $item->getTitle();
    GalleryCoreApi::requireOnce('lib/smarty_plugins/modifier.markup.php');
    $title = smarty_modifier_markup($title, 'strip');
    return $title;
}

function stripTags($tostrip) {
    GalleryCoreApi::requireOnce('lib/smarty_plugins/modifier.markup.php');
    $stripped = smarty_modifier_markup($tostrip, 'strip');
    return $stripped;
}

function getMime($item) {
	if(!($item->entityType == "GalleryAlbumItem")){
		return $item->getMimeType();
	} else {
		return "Album";
	}
}

function getWidth($item) {
	if(($item->entityType == "GalleryAnimationItem" || $item->entityType == "GalleryPhotoItem" || $item->entityType == "ThumbnailImage" || $item->entityType == "GalleryMovieItem" || $item->entityType == "GalleryDerivativeImage")){
		return $item->getWidth();
	} else {
		return 480;
	}
}

function getHeight($item) {
	if(($item->entityType == "GalleryAnimationItem" || $item->entityType == "GalleryPhotoItem" || $item->entityType == "ThumbnailImage" || $item->entityType == "GalleryMovieItem" || $item->entityType == "GalleryDerivativeImage")){
		return $item->getHeight();
	} else {
		return 160;
	}
}
	
function getRating($item)
{
    $itemId = $item->getId();
    $rating = '';
    GalleryCoreApi::requireOnce('modules/rating/classes/RatingHelper.class');
	list ($ret, $Ratings) = RatingHelper::fetchRatings($itemId, '');
	if(!empty ($Ratings)){
	$rating = $Ratings[$id]['rating'];
	return "            <rating>" . $rating . "</rating>\n";
	} else {
	return "            <rating>0</rating>\n";
	}
}

function getThumbUrl($item)
{
    global $gallery;
	$urlGenerator =& $gallery->getUrlGenerator();
    $itemId = $item->getId();
	list ($ret, $thumbnail) = GalleryCoreApi::fetchThumbnailsByItemIds(array($itemId));
	if (!$ret && !empty($thumbnail)) {
	    $thumbUrl = $urlGenerator->generateUrl(
		array('view' => 'core.DownloadItem', 'itemId' => $thumbnail[$itemId]->getId(),
		      'serialNumber' => $thumbnail[$itemId]->getSerialNumber()),
		array('forceFullUrl' => true, 'forceSessionId' => true, 'htmlEntities' => true));
	} else {
	    $thumbUrl = "";
	}
	return $thumbUrl;
}

function getLink($item)
{
    global $gallery;
	$urlGenerator =& $gallery->getUrlGenerator();
	$link = $urlGenerator->generateUrl(
	    array('view' => 'core.ShowItem', 'itemId' => $item->getId()),
		array('forceFullUrl' => true, 'forceSessionId' => true));
	return $link;
}

function getPreferredLink($item)
{
    global $gallery;
	$urlGenerator =& $gallery->getUrlGenerator();
	$link = $urlGenerator->generateUrl(
	    array('view' => 'core.ShowItem', 'itemId' => $item->getId(), 'imageViewsIndex' => 0),
		array('forceFullUrl' => true, 'forceSessionId' => true));
	return $link;
}

function getView($item)
{
    global $gallery;
	$urlGenerator =& $gallery->getUrlGenerator();
    $view = $urlGenerator->generateUrl(
	    array('view' => 'core.DownloadItem', 'itemId' => $item->getId(),
		    'serialNumber' => $item->getSerialNumber()),
		array('forceFullUrl' => true, 'forceSessionId' => true, 'htmlEntities' => true));
	return $view;
}

function cdata($text) {
    return '<![CDATA[' . $text . ']]>';
}

function xml() {
	G2B_G2_init();
	global $gallery;
	$userId = $gallery->getActiveUserId();
	if (isset ($_REQUEST['mode'])) {
        $mode = $_REQUEST['mode'];
    }
	if (isset ($_REQUEST['g2_itemId'])) {
        $g2_itemId = $_REQUEST['g2_itemId'];
    }
	if (isset ($_REQUEST['g2_view'])) {
        $g2_view = $_REQUEST['g2_view'];
    }
	$xml = '';
	$urlGenerator =& $gallery->getUrlGenerator();
    $link = $urlGenerator->generateUrl(array(), array('forceFullUrl' => true));
	$vm = $gallery->getPhpVm();
	list ($ret, $language) = GalleryTranslator::getDefaultLanguageCode( );
	if ($ret) 
	{
        $language = "en-us";
    }
	$gallery->locale = '';
	if ($gallery->locale == 0) {
	    $gallery->locale = 'ISO-8859-1';
	}
	if (!$vm->headers_sent()) {
	    $vm->header('Content-type: text/xml; charset=UTF-8');
	}
	echo "<?xml version=\"1.0\" encoding=\"" . $gallery->locale . "\"?>\n";	
	$xml .= "<rss version=\"2.0\">\n";
	$xml .= "    <channel>\n";
	$xml .= "        <title><![CDATA[ XML Mini SlideShow for Gallery2 ]]></title>\n";
	$xml .= "        <link>" . $link . "</link>\n";
	$xml .= "        <description>XML Mini SlideShow for Gallery2</description>\n";
	$xml .= "        <language>" .$language. "</language>\n";
	$xml .= "        <generator>4WiseGuys RSS Generator version 1.5.6</generator>\n";
	$xml .= "        <lastBuildDate>" . date('r', $vm->time()) . "</lastBuildDate>\n";
	$xml .= "        <ttl>120</ttl>\n";
	$xml .= getAlbumList ($g2_itemId);
	switch ($mode) {
		case 'dynamic':
	    	switch ($g2_view) {
		    	case 'dynamicalbum.UpdatesAlbum':
	    			$xml .= getChildIds($userId);
	    		break;
				case 'dynamicalbum.PopularAlbum':
	    			$xml .= getChildIds($userId, 'views', 'viewCount', ORDER_DESCENDING, 'GalleryItemAttributesMap', 'itemId');
	    		break;
				case 'dynamicalbum.RandomAlbum':
	    			$xml .= getChildIds($userId, 'random', 'random', ORDER_ASCENDING, null, 'id');
	    		break;
				default:
	    		$xml .= getChildIds($userId);
			}
	    break;
		default:
	    	$xml .= getItems($g2_itemId);
	}
	$xml .= "    </channel>\n";
	$xml .= "</rss>\n";
	echo $xml;
}

xml();
?>
