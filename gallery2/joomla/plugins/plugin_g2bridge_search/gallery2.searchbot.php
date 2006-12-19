<?php
defined( '_VALID_MOS' ) or die( 'Restricted access' );

$_MAMBOTS->registerFunction( 'onSearch', 'botSearchGallery2' );

function botSearchGallery2( $text, $phrase='', $ordering='' ) {
	global $database;

	// load mambot params info
	$query = "SELECT id"
	. "\n FROM #__mambots"
	. "\n WHERE element = 'gallery2.searchbot'"
	. "\n AND folder = 'search'"
	;
	$database->setQuery( $query );
	$id 	= $database->loadResult();
	$mambot = new mosMambot( $database );
	$mambot->load( $id );
	$botParams = new mosParameters( $mambot->params );
	
	$limit = $botParams->def( 'search_limit', 50 );
	
	/* start preparing */
	$text = trim( $text );
	if ( $text == '' ) {
		return array();
	}
	
	/* connect g2 Bridge */
	require_once(dirname(__FILE__).'/../../components/com_gallery2/init.inc');
	$ret = core::initiatedG2();
	core::classRequireOnce('utility');
	global $gallery;
	$urlGenerator = $gallery->getUrlGenerator();
	
	$searchModules = array('GalleryCoreSearch');
	if($botParams->def( 'search_comment', false )){
		$searchModules[] = 'comment';
	}
	
	foreach($searchModules as $moduleId){
		list(, $result[$moduleId]) = GalleryEmbed::search($text, $moduleId, 0, $limit);
	}
	
	$return = array();
	$sort = 'ASC';
	$sortArray = array();
	
	foreach ($result as $section => $resultArray) {
		if($resultArray['count'] == 0){
			continue;
		}
		
		foreach($resultArray['results'] as $array){
			
			$info = new stdClass();
			$info->href = $urlGenerator->generateUrl(array('view' => 'core.ShowItem', 'itemId' => $array['itemId']));
			list ($ret,	$item) = GalleryCoreApi::loadEntitiesById($array['itemId']);
			$info->title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
			$info->title = preg_replace('/\r\n/', ' ', $info->title);
			$info->title = core::decoded($info->title,true);
			$info->section = $section;
			$time = $item->getcreationTimestamp();
			$info->created = utility::g2DateToMambo($time);
			$description = core::decoded($item->getdescription(),true);
			$info->text = empty($description) ? core::decoded($item->getSummary(),true) : $description;
			$info->browsernav = 2;
			
			$item->getparentId();
			if($item->getparentId() != 0){
				list ($ret, $parent) = GalleryCoreApi::loadEntitiesById($item->getparentId());
				$parent = $parent->getTitle() ? $parent->getTitle() : $parent->getPathComponent();
				$info->section = preg_replace('/\r\n/', ' ', $parent);
				$info->section = core::decoded($info->section,true);
				if(strpos(strtolower($info->section), 'gallery') !== 0){
					$info->section = 'Gallery/'.$info->section;
				}
			}
			
			list(,$views) = GalleryCoreApi::fetchItemViewCount($array['itemId']);
			
			
			$return[] = $info;
			
			switch ( $ordering ) {
				case 'alpha':
					$sortArray[] = $info->title;
				break;
				case 'category':
					$sortArray[] = $info->section;
				break;
				case 'popular':
					$sortArray[] = $views;
					$sort = 'DESC';
				break;
				case 'newest':
				$sort = 'DESC';
				case 'oldest':
					$sortArray[] = $time;
				break;
				default:
					$sortArray[] = $info->title;
					$sort = 'DESC';
			}
		}
	}
	
	$ret = mergeResultSort($sortArray, $return, $sort, $limit);
	
	return $ret;
}

function mergeResultSort($sortArray, $return, $sort, $limit){
	$ret = array();
	
	if($sort == 'DESC'){
		arsort($sortArray);
	} else {
		asort($sortArray);
	}
	
	reset($sortArray);
	while (list($key) = each($sortArray)) {
   		$ret[] = $return[$key];
	}
	
	$ret = array_slice($ret, 0, $limit);

	return $ret;
}
?>