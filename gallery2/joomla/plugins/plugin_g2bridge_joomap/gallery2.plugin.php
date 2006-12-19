<?php 
/**
 * Adds a Tree of all Gallery2 albums and pictures.
 * 
 * @package g2bridge
 * @author Michiel Bijland
 * @author Daniel Grothe
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

// Register with the Plugin Manager
$tmp = new Joomap_gallery2;
JoomapPlugins::addPlugin( $tmp );

/**
 * Adds a Tree of all Gallery2 albums and pictures.
 *
 * @package g2bridge
 * @subpackage plugins
 */
class Joomap_gallery2 {
	var $urlGenerator;

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $joomla
	 * @param unknown_type $parent
	 * @return unknown
	 */
	function isOfType( &$joomla, &$parent ) {
		if( strpos($parent->link, 'option=com_gallery2') ) {
			return true;
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $joomap
	 * @param unknown_type $parent
	 * @return unknown
	 * @todo check rootId permissions
	 */
	function &getTree( &$joomap, &$parent ) {
		require_once("components/com_gallery2/init.inc" );
		
		core::initiatedG2();
		
		global $gallery;
		$this->urlGenerator =& $gallery->getUrlGenerator();
		
		
		/* itemId of the root album */
		list ($ret, $rootId) = GalleryCoreApi::getDefaultAlbumId();
		if ($ret) {
			return null;
		}
		
		/* Fetch all items contained in the root album */
		list ($ret, $rootItems) =
			GalleryCoreApi::fetchChildItemIdsWithPermission($rootId, 'core.view');
		if ($ret)
			return null;
		
		/* Recurse through the whole album tree */
		return $this->_getTree( $rootItems );
	}	
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $items
	 * @return unknown
	 * @todo error handeling, load all entities in 1 go, check permissions carefully, 
	 */
	function _getTree( &$items ) {
		
		$albums = array();
		if( !$items )
			return null;
		
		foreach( $items as $itemId ) {
			
			// Fetch the details for this item
			list ($ret, $entity) = GalleryCoreApi::loadEntitiesById($itemId);
			if ($ret){
				// error, skip and continue, catch this error in next component version
				continue;
			}

			// Make sure it's an album
			if ($entity->getCanContainChildren()) {
				$node = new stdClass();
				$node->id 	= $entity->getId();
				$node->name = core::decoded($entity->getTitle(),true);
				$node->pid 	= $entity->getParentId();
				$node->modified = $entity->getModificationTimestamp();
				$node->type = 'separator'; //fool joomap in not trying to add $Itemid=
				$node->link = $this->urlGenerator->generateUrl(
					array('view' => 'core.ShowItem', 'itemId' => $node->id),
					array('forceSessionId' => false, 'forceFullUrl' => true)
				);
				
				// Get all child items contained in this album and add them to the tree
				list ($ret, $childIds) =
					GalleryCoreApi::fetchChildItemIdsWithPermission($node->id, 'core.view');
				if ($ret) {
					// error, skip and continue, catch this error in next component version
					continue;	
				}
				$node->tree = $this->_getTree( $childIds );

				$albums[] = $node;				
			}
		}
		
		return $albums;
	}
	
}
?>