<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

// ensure user has access to this function
if (!($acl->acl_check('administration', 'edit', 'users', $my->usertype, 'components', 'all') | $acl->acl_check('administration', 'edit', 'users', $my->usertype, 'components', 'com_gallery2'))) {
    mosRedirect('index2.php', _NOT_AUTH);
}

require_once($mainframe->getPath('admin_html'));

$act = mosGetParam($_REQUEST, 'act', null);
$task = mosGetParam($_REQUEST, 'task', array(0));
$cid = mosGetParam($_POST, 'cid', array(0));
$userId = mosGetParam($_GET, 'id', null);
$albumId = mosGetParam($_GET, 'albumId', null);

if (!is_array( $cid )) {
    $cid = array(0);
}

switch($act) {
	case "conf":
		switch ($task) {
			case "save":
				saveSettings($option, $act);
			break;
			default:
				viewSettings($option, $act, $task);
			break;
		}
	break;
	case "user":
		switch($task) {
			case "user_edit":
				showUsersDetail($option, $act, $task, $userId);
			break;
			case "sync_users":
				tools($option, 'tools', $task, 'user');
			break;
			case 'save':
				saveUser();
			break;
			default:
				showUsers( $option, $act, $task );
			break;
		}
	break;
	case "album":
		switch($task) {
			case "save":
				savealbum($option, $act, $task, $return, $param);
			break;
			case "album_spec":
				showalbum($option, $act, $task, $albumId);
			break;
			default:
				showalbumtree($option, $act, $task);
			break;
		}
	break;
	case "tools":
		 tools($option, $act, $task, false);
	break;
	default:
		showHelp( $option, $act, $task, $anchor );
	break;
}

/* Displays Gallery component settings */
function viewSettings( $option, $act, $task ) {
    global $database, $my, $acl;
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();

    $params['displaysidebar'] = mosHTML::yesnoSelectList('g2_displaysidebar', 'class="inputbox" size="1"', $g2_Config['displaysidebar']);
    $params['displaylogin'] = mosHTML::yesnoSelectList('g2_displaylogin', 'class="inputbox" size="1"', $g2_Config['displaylogin']);
    $params['mirrorUsers'] = mosHTML::yesnoSelectList('g2_mirrorUsers', 'class="inputbox" size="1"', $g2_Config['mirrorUsers']);
    $params['userSetup'] = mosHTML::yesnoSelectList('g2_userSetup', 'class="inputbox" size="1"', 0);
    $params['enableAlbumCreation'] = mosHTML::yesnoSelectList('g2_enableAlbumCreation', 'class="inputbox" size="1"', $g2_Config['enableAlbumCreation']);
	
    HTML_content::showSettings($option, $params, $act, $g2_Config, $task);
}

/* Saves Gallery component settings */ 
function saveSettings( $option, $act ) {
    global $database, $mosConfig_dbprefix;
	require_once("../components/com_gallery2/userfuncs.php");

    if (mosGetParam($_POST, 'g2_userSetup', true))
    {
       RunUserSetup($params);
    }
	//First check the settings
	//pathcheck($which, $path);
	
	foreach ($_POST as $k=>$v){
		switch($k){
			case 'g2_path':
				$report = G2helperclass::g2_pathcheck("path", $v);
				if(!$report[1]){
					 mosRedirect('index2.php?option=com_gallery2&act=conf', 'Full path to Gallery2 is inorrect!');
				}
			break;
			case 'g2_relativeG2Path':
				$report = G2helperclass::g2_pathcheck("relativeG2Path", $v);
				$v = ltrim(rtrim($v, "/"), "/"); //clear end and begin slashes
				if(!$report[1]){
					 mosRedirect('index2.php?option=com_gallery2&act=conf', 'Relative path to Gallery2  incorrect!');
				}
			break;
			case 'g2_embedPath':
				$report = G2helperclass::g2_pathcheck("embedPath", $v);
				$v = '/'.ltrim(rtrim($v, '/'), '/');
				if(!$report[1]){
					 mosRedirect('index2.php?option=com_gallery2&act=conf', 'Path to mambo is incorrect!');
				}
			break;
			case 'g2_loginredirect':
				$v = '/'.ltrim($v, '/');
			break;			
		}
	//put them in the $g2_Config array
	if($k !='act' AND $k !='option' AND $k !='conf' AND $k !='task' AND $k !='g2_embedUri'){
		$k = substr( $k, 3);
		$g2_Config["$k"]=$v;
		}
	}
	//correct is seems let's get some more data
	$g2_Config = G2helperclass::config_gallery2_get($g2_Config);
	$g2_Config = G2helperclass::getG2setting($g2_Config);
	//then Save them to DB
		foreach($g2_Config as $k => $v){
			$query = 'INSERT INTO `'.$mosConfig_dbprefix.'gallery2` (`key`, `value`) VALUES (\''.$k.'\', \''.$v.'\')';
			$database->setQuery( $query );
			$check = $database->query();
			if(!$check){
				$query = 'UPDATE `'.$mosConfig_dbprefix.'gallery2` SET `value` =\''.$v.'\' WHERE `key`=\''.$k.'\'';
				$database->setQuery( $query );
				$check = $database->query();
			}
		}
	mosRedirect('index2.php?option=com_gallery2&act=conf', 'Configuration Saved Succesfully!');
}// end save config

function RunUserSetup($params)
{
    global $my;
	//start G2 embed
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
    G2helperclass::embed($g2_Config);
	$ret =  G2helperclass::init_G2($my->id, 'true');
	

    if ($ret->isError())
    {
        if ($ret->getErrorCode() & ERROR_MISSING_OBJECT)
        {
            // check if there's no G2 user mapped to the activeUserId
            $ret = GalleryEmbed::isExternalIdMapped($my->id, 'GalleryUser');
            if ($ret->getErrorCode() & ERROR_MISSING_OBJECT)
            {
                // We want to set the user calling this method as the G2 admin
                list ($ret, $g2user) = GalleryCoreApi::fetchUserByUserName('admin');
                if ($ret->isError())
                {
                    return false;
                }
                $ret = GalleryEmbed::addExternalIdMapEntry($my->id, $g2user->getId(), 'GalleryUser');
                if ($ret->isError())
                {
                    return false;
                }
            }
        }
    }
    return true;
}

function showUsers( $option, $act, $task ) { 
	global $database, $mainframe, $my, $acl, $mosConfig_list_limit;

	$filter_type	= $mainframe->getUserStateFromRequest( "filter_type{$option}", 'filter_type', 0 );
	$filter_logged	= $mainframe->getUserStateFromRequest( "filter_logged{$option}", 'filter_logged', 0 );
	$limit 			= $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', $mosConfig_list_limit );
	$limitstart 	= $mainframe->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 );
	$search 		= $mainframe->getUserStateFromRequest( "search{$option}", 'search', '' );
	$search 		= $database->getEscaped( trim( strtolower( $search ) ) );
	$where 			= array();

	if (isset( $search ) && $search!= "") {
		$where[] = "(a.username LIKE '%$search%' OR a.email LIKE '%$search%' OR a.name LIKE '%$search%')";
	}
	/*
	if ( $filter_type ) {
		if ( $filter_type == 'Public Frontend' ) {
			$where[] = "a.usertype = 'Registered' OR a.usertype = 'Author' OR a.usertype = 'Editor'OR a.usertype = 'Publisher'";
		} else if ( $filter_type == 'Public Backend' ) {
			$where[] = "a.usertype = 'Manager' OR a.usertype = 'Administrator' OR a.usertype = 'Super Administrator'";
		} else {
			$where[] = "a.usertype = LOWER( '$filter_type' )";
		}
	}*/
	/*
	if ( $filter_logged == 1 ) {
		$where[] = "s.userid = a.id";
	} else if ($filter_logged == 2) {
		$where[] = "s.userid IS NULL";
	}*/

	// exclude any child group id's for this user
	//$acl->_debug = true;
	$pgids = $acl->get_group_children( $my->gid, 'ARO', 'RECURSE' );

	if (is_array( $pgids ) && count( $pgids ) > 0) {
		$where[] = "(a.gid NOT IN (" . implode( ',', $pgids ) . "))";
	}

	$query = "SELECT COUNT(*)"
	. "\n FROM #__users AS a"
	. "\n LEFT JOIN #__session AS s ON s.userid = a.id"
	. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
	;
	$database->setQuery( $query );
	$total = $database->loadResult();

	require_once( $GLOBALS['mosConfig_absolute_path'] . '/administrator/includes/pageNavigation.php' );
	$pageNav = new mosPageNav( $total, $limitstart, $limit  );

	$query = "SELECT a.*, g.name AS groupname, s.userid AS loggedin"
	. "\n FROM #__users AS a"
	. "\n INNER JOIN #__core_acl_aro AS aro ON aro.value = a.id"	// map user to aro
	. "\n INNER JOIN #__core_acl_groups_aro_map AS gm ON gm.aro_id = aro.aro_id"	// map aro to group
	. "\n INNER JOIN #__core_acl_aro_groups AS g ON g.group_id = gm.group_id"
	. "\n LEFT JOIN #__session AS s ON s.userid = a.id"
	. (count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : "")
	. "\n GROUP BY a.id"
	. "\n LIMIT $pageNav->limitstart, $pageNav->limit"
	;
	$database->setQuery( $query );
	$rows = $database->loadObjectList();

	if ($database->getErrorNum()) {
		echo $database->stderr();
		return false;
	}

/*	// get list of Groups for dropdown filter
	$query = "SELECT name AS value, name AS text"
	. "\n FROM #__core_acl_aro_groups"
	. "\n WHERE name != 'ROOT'"
	. "\n AND name != 'USERS'"
	;
	$types[] = mosHTML::makeOption( '0', '- Select Group -' );
	$database->setQuery( $query );
	$types = array_merge( $types, $database->loadObjectList() );
	$lists['type'] = mosHTML::selectList( $types, 'filter_type', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_type" );
	*/
	/* get list of Log Status for dropdown filter
	$logged[] = mosHTML::makeOption( 0, '- Select Log Status - ');
	$logged[] = mosHTML::makeOption( 1, 'Logged In');
	$logged[] = mosHTML::makeOption( 2, 'Not Logged In');
	$lists['logged'] = mosHTML::selectList( $logged, 'filter_logged', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_logged" );
	*/
	$database->setQuery("SELECT * FROM #__gallery2");
	$param = $database->loadRowList();
	
	HTML_content::showUsers( $rows, $pageNav, $search, $option, $lists, $param );
}

function showUsersDetail($option, $act, $task, $userId){
global $database;
	//start G2 embed
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
    G2helperclass::embed($g2_Config);
	$ret = G2helperclass::init_G2($my->id, 'true');
//get user id				   				   
list($ret, $g2_user) = GalleryCoreApi::loadEntityByExternalId($userId, 'GalleryUser');
//getting groups
list($ret, $groupids_all) = GalleryCoreApi::fetchGroupNames();
list($ret, $groupids) = GalleryCoreApi::fetchGroupsForUser($g2_user->getId());
foreach($groupids_all as $key => $val){
	//check member or not
	if(array_key_exists($key, $groupids)){
		$switch = 1;
	} else {
		$switch = 0;
	}
	$groupswitch[$val] = mosHTML::yesnoRadioList('g2_group['.$key.']', 'class="inputbox" size="1"', $switch);
}
//all items of user
list($ret, $all_itemids) = GalleryCoreApi::fetchAllItemIdsByOwnerId($g2_user->getId());
$count_total = count($all_itemids);
$count_album = 0;
$album_ids = array();
//all albums
list($ret, $all_albumids) = GalleryCoreApi::fetchAllItemIds('GalleryAlbumItem');
foreach ($all_itemids as $id => $name) {
if (in_array($name, $all_albumids)) {
  	$count_album++;
   	$album_ids[] = $name;
	}	
}
$g2_id = $g2_user->getId();
HTML_content::showUserDetails( $option, $act, $task, $userId, $groupids_all, $count_total, $count_album, $album_ids, $groupswitch, $g2_id);
}

/*

*save user

*/
function saveUser(){
	//init g2
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
    G2helperclass::embed($g2_Config);
	global $my, $g2_id, $user_id;
	$ret = G2helperclass::init_G2($my->id, 'true');
	//start doing things
	while(list($key, $val) = each($_POST["g2_group"])){
		print $key.'=>'.$val.' and g2_id: '.$g2_id.'<br />';
		switch($val){
			case '1':
			$ret = GalleryCoreApi::addUserToGroup($g2_id, $key);
			break;
			case '0':
			$ret = GalleryCoreApi::removeUserFromGroup($g2_id, $key);
			break;
		}
	}
	
	mosRedirect( 'index2.php?option=com_gallery2&amp;act=user&amp;task=user_edit&amp;hidemainmenu=1&amp;id='.$user_id, 'Groups saved.' );
}

/*

*Tools page

*/
function tools($option, $act, $task, $from){
	global $database;
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
	G2helperclass::embed($g2_Config);
	switch($task){
		case 'sync_users':
			$ret = G2helperclass::init_G2($my->id, 'true');
			$database->setQuery( "SELECT * FROM #__users" );
			$rows = $database->query();
			//we have the list of users to sync
			$update = 0;
			$sync = 0;
			$error = 0;
			while($row = mysql_fetch_row($rows))
			{
			print $row[0];
			$row_username = $row[2];
			$ret = GalleryEmbed::isExternalIdMapped($row[0], 'GalleryUser');
					if (!$ret->_errorCode) {
							if($task == 'sync_users'){
							//hack for blocked users
							if($row[6] == 1){
							$pass = md5(mt_rand().'-'.time());
							$return =  G2helperclass::update_user($row[0], array('username' =>  $row[2], 
                                                'email' =>  $row[3], 'fullname' =>  $row[1], 
                                                'hashedpassword' =>  $pass, 'hashmethod' => 'md5'));
							} else {
							$return =  G2helperclass::update_user($row[0], array('username' =>  $row[2], 
                                                'email' =>  $row[3], 'fullname' =>  $row[1], 
                                                'hashedpassword' =>  $row[4], 'hashmethod' => 'md5'));
							}
							//$return gives false or true back depending on succes
								if(!$return){
									$error++;
								} else {
									$update++;
								}
							} else {					
							// nothing at the moment
							}
					}else{
						$gUser = GalleryCoreApi::fetchUserByUsername ($row_username->user_login);
						if (!$gUser[0]->_errorCode) {
							//Collision i think
							echo '<img src="/administrator/images/disable.png">';
						}else{
							//if no match and no external id
							//echo '<img src="/administrator/images/publish_x.png">';
							if($task == 'sync_users'){
							//hack for blocked users
							if($row[6] == 1){
							$pass = md5(mt_rand().'-'.time());
							$return = G2helperclass::new_user($row[0], array('username' =>  $row[2], 
                                                'email' =>  $row[3], 'fullname' =>  $row[1], 
                                                'hashedpassword' =>  $pass, 'hashmethod' => 'md5'));
							} else {
							$return = G2helperclass::new_user($row[0], array('username' =>  $row[2], 
                                                'email' =>  $row[3], 'fullname' =>  $row[1], 
                                                'hashedpassword' =>  $row[4], 'hashmethod' => 'md5'));
							}
							//$return gives false or true back depending on succes
								if(!$return){
									$error++;
								} else {
									$sync++;
								}
							}
						}
					}
			}//end while
			//dynamic redirect on $sync and $update
			
			//
			if($from == 'user'){
				mosRedirect( "index2.php?option=com_gallery2&act=user", 'Synced: '.$sync.' Updated: '.$update.' Errors: '.$error );
			} else {
				mosRedirect( "index2.php?option=com_gallery2&act=tools", 'Synced: '.$sync.' Updated: '.$update.' Errors: '.$error );
			}
		break;
		case 'sync_group':
		case 'sync_group_remove':
	   		$ret = G2helperclass::init_G2($my->id, 'true');
			//get the groups
			$database->setQuery("SELECT * FROM `#__core_acl_aro_groups` WHERE name != 'ROOT' AND name != 'USERS' AND name != 'Public Frontend' AND name != 'Public Backend'");
			$rows = $database->query();
			while($row = mysql_fetch_row($rows)){
			$ret = GalleryEmbed::isExternalIdMapped($row[0], 'GalleryGroup');
					if (!$ret->_errorCode) {
						//if external id found
						//echo $row[2].'<img src="/administrator/images/tick.png"><br />';
						if($task == 'sync_group_remove') {
						 	$return = G2helperclass::remove_group($row[0]);
						}
					}else{
						$gGroup = GalleryCoreApi::fetchGroupByGroupName ($row[2]);
						if (!$gGroup[0]->_errorCode) {
							//Collision i think
							//echo '<img src="/administrator/images/disable.png">';
							print 'col <br />';
						}else{
							//if no match and no external id
							//echo $row[2].'<img src="/administrator/images/publish_x.png"><br />';
							$name = $row[2].' Mambo';
							if($task == 'sync_group'){$return = G2helperclass::new_group($row[0], $name);
							// add_user_group($userid, $groupid)
							$database->setQuery("SELECT `id` FROM `#__users` WHERE `gid`=$row[0] AND block !='1'");
							$user_ids = $database->query();
							while($userid = mysql_fetch_row($user_ids)){
								$ret = G2helperclass::add_user_group($userid[0], $row[0]);
							}
							
							
							 }
						}
					}
			}//end while
			mosRedirect( "index2.php?option=com_gallery2&act=tools&mosmsg=The%20Groups%20are%20successfully%20Synced." );
		break;
		
		default:
			HTML_content::showTools( $option, $act, $task );
		break;
	}
}
/* help page */
function showHelp( $option, $act, $task, $anchor ){
	HTML_content::showHelp( $option, $act, $task, $anchor );
}//end help page

/* album page */
function showalbum($option, $act, $task, $albumId){
	//init g2
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
	G2helperclass::embed($g2_Config);
	global $my, $database;
	$ret = G2helperclass::init_G2($my->id, 'true');
	switch($task){
		case 'album_spec':
			//load by id
			list ($ret, $albumspecs) = GalleryCoreApi::loadEntitiesById($albumId);
			//let's get all the needed info
			$title = $albumspecs->getTitle() ? $albumspecs->getTitle() : $albumspecs->getPathComponent();
			$details['title'] = preg_replace('/\r\n/', ' ', $title);
			$details['summary']=$albumspecs->getsummary();
			$details['description']=$albumspecs->getdescription();
			$details['creationDate']=date("j-m-Y", $albumspecs->getcreationTimestamp());
			$details['lastmodified']=date("j-m-Y", $albumspecs->getmodificationTimestamp());
			$details['parentid']=$albumspecs->getparentId();
			if($details['parentid'] != 0){
			list ($ret, $parent) = GalleryCoreApi::loadEntitiesById($details['parentid']);
			$parent = $parent->getTitle() ? $parent->getTitle() : $parent->getPathComponent();
			$details['parentname'] = preg_replace('/\r\n/', ' ', $parent);
			}
			$details['ownerid']=$albumspecs->getownerId();
			//mambo id we need
			list($ret , $mamboid) = GalleryEmbed::getExternalIdMap('entityId');
			$details['mamboid']= $mamboid[$details['ownerid']]['externalId'];
			// now the name
			$database->setQuery( 'SELECT username FROM #__users WHERE id='.$details['mamboid']);
			$database->query();
			$details['mamboname'] = $database->loadResult();
			$details['viewedsince']=date("j-m-Y", $albumspecs->getviewedSinceTimestamp( ));
			$details['keywords']=$albumspecs->getkeywords();
			list ($ret, $details['views']) = GalleryCoreApi::fetchItemViewCount($albumId);
			list($ret, $details['childids']) = GalleryCoreApi::fetchChildItemIdsIgnorePermissions($albumspecs);
			list($ret, $details['childalbumids']) = GalleryCoreApi::fetchChildAlbumItemIds($albumspecs);
			if(count($details['childalbumids'])>0){
			list ($ret, $childalbum) = GalleryCoreApi::loadEntitiesById($details['childalbumids']);
			foreach($childalbum as $parent){
				$parent2 = $parent->getTitle() ? $parent->getTitle() : $parent->getPathComponent();
				$details['childname'][$parent->getid()] = preg_replace('/\r\n/', ' ', $parent2);
			}
			}
			//the thumb info	
			$array = array('show' => 'none', 'blocks' => 'specificItem', 'itemId' => $albumId);
			list ($ret, $details['thumbid']) = GalleryEmbed::getImageBlock($array);
			$count = strpos( $details['thumbid'], 'g2_itemId', strpos( $details['thumbid'], '<img'));
			$count2 = strpos( $details['thumbid'], '/>' , $count) - $count;
			$details['thumbid'] = substr($details['thumbid'], $count, $count2);
			
			HTML_content::showAlbum( $option, $act, $task, $albumId, $details);
		break;
	//HTML_content::showAlbum( $option, $act, $task, $albumId);
	}//end switch
}
function showalbumtree($option, $act, $task){
	//init g2
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
	G2helperclass::embed($g2_Config);
	global $my, $database;
	$ret = G2helperclass::init_G2($my->id, 'true');
	//fetch album tree
	$depth = 10;
	$itemid = 7;
	list ($ret, $tree) = GalleryCoreApi::fetchAlbumTree($itemId, $depth);
	    if ($ret->isError()) {
		if ($ret->getErrorCode() & ERROR_PERMISSION_DENIED) {
		    $tree = null;
		} else {
		    return array($ret->wrap(__FILE__, __LINE__), null);
		}
		}//end error
		list ($ret, $items) = GalleryCoreApi::loadEntitiesById(GalleryUtilities::arrayKeysRecursive($tree));
	    if ($ret->isError()) {
		return array($ret->wrap(__FILE__, __LINE__), null);
	    }
	    foreach ($items as $item) {
			$title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
			$titles[$item->getId()] = preg_replace('/\r\n/', ' ', $title);
			$keywords[$item->getId()] = $item->getkeywords();
			$summary[$item->getId()] = $item->getsummary();
			$description[$item->getId()] = $item->getdescription();
			list($ret, $childs[$item->getId()]) = GalleryCoreApi::fetchChildItemIdsIgnorePermissions($item);
			$last_modified[$item->getId()] = date("j-m-Y", $item->getmodificationTimestamp());
			if((time() - $item->getmodificationTimestamp()) < 86400){
				$last_modified[$item->getId()] .= ' Updated!';
			}
		
		}
		HTML_content::showAlbumTree( $option, $act, $task, $tree, $titles, $keywords, $summary, $description, $childs, $last_modified);
}

//save album
function savealbum($option, $act, $task, $return, $param){
	//init g2
	require_once("../components/com_gallery2/userfuncs.php" );
	$g2_Config = G2helperclass::g2_Config();
	G2helperclass::embed($g2_Config);
	global $my, $database;
	$ret = G2helperclass::init_G2($my->id, 'true');
	//load the data item
	global $title, $description, $keywords, $summary;
	list ($ret, $albumspecs) = GalleryCoreApi::loadEntitiesById($return);
	print $title.' '.$description.'<br />';
	$albumspecs->settitle($title);
	$albumspecs->setdescription($description);
	$albumspecs->setkeywords($keywords);
	$albumspecs->setsummary($summary);
	list($ret, $lockId) = GalleryCoreApi::acquireWriteLock($return);
	$ret = $albumspecs->save();
	$ret = GalleryCoreApi::releaseLocks($lockId);
	
mosRedirect( "index2.php?option=com_gallery2&amp;act=album&amp;task=album_spec&albumId=$return", 'Succesfully Saved' );
}
?>