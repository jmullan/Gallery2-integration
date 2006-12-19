<?php
/**
 * Main backend file
 * 
 * @package g2bridge
 * @subpackage core
 * @author Michiel Bijland
 * @copyright Copyright (C) 2005 - 2006 4 The Web. All rights reserved.
 * @version $Id$
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/* check access */
if (!($acl->acl_check('administration', 'edit', 'users', $my->usertype, 'components', 'all') | $acl->acl_check('administration', 'edit', 'users', $my->usertype, 'components', 'com_gallery2'))) {
	mosRedirect('index2.php', _NOT_AUTH);
}

require_once($mainframe->getPath('admin_html'));

require_once("../components/com_gallery2/init.inc" );
/* redirect if not configured */
if(!core::isConfigured() && $act == 'user'){
	mosRedirect("index2.php?option=$option&act=conf", 'Please Configure Compoent First.');
}

switch($act) {
	/* configuration page */
	case 'conf':
		switch($task){
			case 'wizardSave':
				/* save wizard results and redirect */
				global $mainframe;
				$urlToGallery  = $mainframe->getUserState( "urlToGallery{$option}"  );
				$pathToGallery = $mainframe->getUserState( "pathToGallery{$option}" );

				core::setParam('core', 'url', $urlToGallery, true);
				core::setParam('core', 'path', $pathToGallery, true);
				//core::setParam('core', 'hash', md5($url.$pathToGallery), true);

				mosRedirect("index2.php?option=$option&act=conf", 'Wizard Run Succesfully');
				break;
			case 'wizardStepTwo':
			case 'wizardStepOne':
			case 'wizard':
				viewWizard($option, $act, $task);
				break;
			default:
				/* include g2bridgeJLConf */
				core::classRequireOnce('g2bridge.jlconf');
				$g2bJLC = new g2bridgeJLConf();

				/* Load it from the database so that we start from the latest good snapshot */
				if (!$g2bJLC->loadFromDB()) {
					print 'Could\'t load from Database!';
				}

				if($task=='save'){
					/* load BSQCfg from posted form */
					if (!$g2bJLC->loadFromForm()) {
						print 'Fix the above errors!';
					} else {
						$g2bJLC->saveConfiguration();
						mosRedirect("index2.php?option=$option&act=conf", 'Settings Saved Succesfully');
					}
				}

				HTML_content::configuration($option, $act, $task, $g2bJLC);
				break;
		}
		break;
		/* user page */
	case 'user':
		//check if user are mirrored?
		if((core::getParam('user', 'mirror') != 1)){
			mosRedirect( "index2.php?option=com_gallery2&act=conf", 'You have to set User Mirror to use this Function');
		}
		switch($task) {
			case 'cancel':
				mosRedirect( 'index2.php?option='.$option.'&task='.$task);
				break;
			case 'apply':
			case 'save':
				userSave($option, $task);
				break;
			case 'sync':
				sync($option, $act);
				break;
			case 'edit':
				userEdit($option, $task);
				break;
			default:
				userList( $option, $act, $task );
				break;
		}
		break;
		/* Information page */
	default:
		require_once(_G2BPATH.'/version.php');
		$version = new g2BridgeVersion();
		HTML_content::about($version);
		break;
}

/**
 * Enter description here...
 *
 * @param string $option
 * @param string $task
 */
function userList($option, $act){
	global $database, $mainframe, $my, $acl, $mosConfig_list_limit;

	$filter_type	= $mainframe->getUserStateFromRequest( "filter_type{$option}", 'filter_type', 0 );
	$filter_logged	= $mainframe->getUserStateFromRequest( "filter_logged{$option}", 'filter_logged', 0 );
	$limit 			= $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', $mosConfig_list_limit );
	$limitstart 	= $mainframe->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 );
	$search 		= $mainframe->getUserStateFromRequest( "search{$option}", 'search', '' );
	$search 		= $database->getEscaped( trim( strtolower( $search ) ) );
	$where 			= array();

	if (isset( $search ) && $search != "") {
		$where[] = "(a.username LIKE '%$search%' OR a.email LIKE '%$search%' OR a.name LIKE '%$search%')";
	}

	if ( $filter_type ) {
		if ( $filter_type == 'Public Frontend' ) {
			$where[] = "a.usertype = 'Registered' OR a.usertype = 'Author' OR a.usertype = 'Editor'OR a.usertype = 'Publisher'";
		} else if ( $filter_type == 'Public Backend' ) {
			$where[] = "a.usertype = 'Manager' OR a.usertype = 'Administrator' OR a.usertype = 'Super Administrator'";
		} else {
			$where[] = "a.usertype = LOWER( '$filter_type' )";
		}
	}


	if ( $filter_logged == 1 ) {
		$where[] = "s.userid = a.id";
	} else if ($filter_logged == 2) {
		$where[] = "s.userid IS NULL";
	}

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

	// get list of Groups for dropdown filter
	$query = "SELECT name AS value, name AS text"
	. "\n FROM #__core_acl_aro_groups"
	. "\n WHERE name != 'ROOT'"
	. "\n AND name != 'USERS'";

	$types[] = mosHTML::makeOption( '0', '- Select Group -' );
	$database->setQuery( $query );
	$types = array_merge( $types, $database->loadObjectList() );
	$lists['type'] = mosHTML::selectList( $types, 'filter_type', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_type" );

	// get list of Log Status for dropdown filter
	$logged[] = mosHTML::makeOption( 0, '- Select Log Status - ');
	$logged[] = mosHTML::makeOption( 1, 'Logged In');
	$logged[] = mosHTML::makeOption( 2, 'Not Logged In');
	$lists['logged'] = mosHTML::selectList( $logged, 'filter_logged', 'class="inputbox" size="1" onchange="document.adminForm.submit( );"', 'value', 'text', "$filter_logged" );

	/* init G2 */
	core::init(true,false);

	/* check users */
	$errors = array();
	foreach($rows as $row){
		$ret = GalleryEmbed::isExternalIdMapped($row->id, 'GalleryUser');
		if($ret){
			$errors[$row->id] = "User doesn't exist in Gallery 2 or isn't mapped.";
		} else {
			list($ret, $user) = GalleryCoreApi::loadEntityByExternalId($row->id, 'GalleryUser');
			if($ret){
				$errors[$row->id] = "Corrupt Map entry.";
			} else {
				/* compare the users, if blocked don't compare password */

			}
		}
	}

	HTML_content::userList($option, $act, $lists, $pageNav, $search, $rows, $errors);
}

/**
 * Show user details and Adjust groups
 *
 * @param string $option
 * @param string $task
 */
function userEdit($option, $act){
	/* Initiated Gallery 2 as guest. */
	core::init(true,false);

	/* get correct Gallery 2 user */
	$juid = (int) mosGetParam($_GET, 'uid', null);

	list($ret, $user) = GalleryCoreApi::loadEntityByExternalId($juid, 'GalleryUser');
	if($ret){
		HTML_content::error($ret->getAsHtml(),$option , $task);
		return;
	}

	/* fetch all the Gallery 2 groups */
	list($ret, $groups) = GalleryCoreApi::fetchGroupNames();
	if($ret){
		HTML_content::error($ret->getAsHtml(),$option , $task);
		return;
	}
	/* fetch all the user groups */
	list($ret, $userGroups) = GalleryCoreApi::fetchGroupsForUser($user->getId());
	if($ret){
		HTML_content::error($ret->getAsHtml(),$option , $task);
		return;
	}

	/* Make a yes/no entry for each group */
	foreach($groups as $gid => $name){
		$value = (isset($userGroups[$gid])) ? 1 : 0;
		$list[$name] = mosHTML::yesnoRadioList('gids['.$gid.']', 'class="inputbox" size="1"', $value);
	}

	HTML_content::userEdit($option, $act, $user, $juid, $list);
}

/**
 * Will adjust the user groups
 *
 * @param string option
 * @param string task
 * @param integer id of the user.
 */
function userSave($option, $act){
	/* Initiated Gallery 2 as guest. */
	core::init(true,false);

	/* get joomla user to be saved */
	$juid = (int) mosGetParam($_POST, 'uid', null);

	/* loop through list */
	while(list($gid, $val) = each($_POST["gids"])){
		/* Joomla gid */
		list($ret, $gid) = utility::getJoomlaId($gid);
		if($ret){
			HTML_content::error($ret->getAsHtml(),$option , $task);
			return;
		}
		switch($val){
			case '1':
				$ret = user::addUserGroup($juid,$gid);
				if($ret){
					HTML_content::error($ret->getAsHtml(),$option , $task);
					return;
				}
				break;
			case '0':
				$ret = user::removeUserGroup($juid,$gid);
				if($ret){
					HTML_content::error($ret->getAsHtml(),$option , $task);
					return;
				}
				break;
		}
	}

	/* redirect correctly */
	$msg = 'The User Details have been updated';
	switch ( $task ) {
		case 'apply':
			mosRedirect( 'index2.php?option='.$option.'&act='.$act.'&task=edit&uid='.$juid.'&hidemainmenu=1', $msg );
			break;
		case 'save':
		default:
			mosRedirect( 'index2.php?option=com_gallery2&act='.$act, $msg );
			break;
	}
}

function viewWizard($option, $act, $task){
	global $mainframe;

	/* include G2EmbedDiscoveryUtilities */
	core::classRequireOnce('G2EmbedDiscoveryUtilities');

	/* get our variables */
	$urlToGallery  = $mainframe->getUserStateFromRequest( "urlToGallery{$option}",  'urlToGallery',  '' );
	$pathToGallery = $mainframe->getUserStateFromRequest( "pathToGallery{$option}", 'pathToGallery', '' );
	$urlType	   = $mainframe->getUserStateFromRequest( "urlType{$option}",		'urlType',		 '' );

	/* display step */
	switch ($task){
		case 'wizardStepTwo':
			/* clean user supplied url, should be done only in this step */
			$urlToGallery = G2EmbedDiscoveryUtilities::normalizeG2Uri($urlToGallery);
			/* make sure we set our cleaned url into the session */
			$mainframe->setUserState( "urlToGallery{$option}", $urlToGallery );

			/* check URL, redirect back if not good */
			list ($success, $pathToGallery, $msg) =
			G2EmbedDiscoveryUtilities::getG2EmbedPathByG2Uri($urlToGallery);
			if(!$success){
				/* redirect back */
				mosRedirect( 'index2.php?option=com_gallery2&act='.$act.'&task=wizardStepOne' , $msg );
			}
			/* safe our server path */
			$mainframe->setUserState( "pathToGallery{$option}", $pathToGallery );

			HTML_content::wizardStepTwo($option, $act, $task, $urlToGallery, $pathToGallery);
			break;
		case 'wizardStepOne':
		default:
			/* start */
			HTML_content::wizardStepOne($option, $act, $task, $urlToGallery);
			break;
	}
}

function sync($option, $act) {
	if(!core::isInitiated()){
		core::init(true,false);
	}
	core::classRequireOnce('user');
	
	global $database, $gallery;

	/* load our progressbar */
	HTML_content::progressbar();

	/* fetch gallery 2 users */
	list($ret, $g2Usernames) = GalleryCoreApi::fetchUsernames();
	if($ret){
		/* error report */
		print $ret->getAsHtml();
	}
	$g2UserById = $g2UserByUsername = array();

	$i = 0;
	$total = count($g2Usernames);
	$increment = min(100, $total * 0.10);
	$title = "Preprocessing Gallery 2 Users.";

	/* load entities */
	updateProgressBar($title, 'Loading Gallery 2 Users', 0);
	list($ret, $galleryUsers) = GalleryCoreApi::loadEntitiesById(array_keys($g2Usernames));
	if($ret){
		/* error report */
		print $ret->getAsHtml();
	}

	foreach($galleryUsers as $user){
		$i++;
		if($i % $increment == 0 || $i == $total){
			$gallery->guaranteeTimeLimit(5);
			$description = sprintf('processing %d of %d.', $i, $total);
			updateProgressBar($title, $description, $i/$total);
		}
		/* build */
		$g2UserById[$user->getId()] = $user;
		$g2UserByUsername[strtolower($user->getuserName())] = $user;
	}
	/* safe memory */
	unset($galleryUsers,$user,$g2Usernames);

	/* load Joomla users */
	resetProgressBarStats();
	$gallery->guaranteeTimeLimit(10);

	$sql = "SELECT `id`, `name` as fullname, `username`, `email`,
					   `password` AS hashedpassword, `block`, `gid` 
				FROM `#__users`";
	$database->setQuery($sql);
	$joomlaUsers = $database->loadAssocList('id');
	if(!$joomlaUsers){
		/* error message */
	}

	$jUserById = $jUserByUsername = array();

	$i = 0;
	$total = $database->getNumRows();
	$increment = min(100, $total * 0.10);
	$title = "Preprocessing Joomla Users.";

	foreach($joomlaUsers as $id => $user){
		$i++;
		if($i % $increment == 0 || $i == $total){
			$gallery->guaranteeTimeLimit(5);
			$description = sprintf('processing %d of %d.', $i, $total);
			updateProgressBar($title, $description, $i/$total);
		}
		/* build */
		$user['hashmethod'] = 'md5';
		$jUserById[$id] = $user;
		$jUserByUsername[strtolower($user['username'])] = $user;
	}
	/* safe memory */
	unset($joomlaUsers);

	/* external id map */
	list($ret, $mapEntityId) = GalleryEmbed::getExternalIdmap('entityId');
	if($ret){
		/* error report */
		print $ret->getAsHtml();
	}

	$i = 0;
	$total = count($mapEntityId);
	$increment = min(100, $total * 0.10);
	$title = "Cleaning External Id Map";
	resetProgressBarStats();

	foreach($mapEntityId as $entityId => $mapEntry){
		$i++;
		if($i % $increment == 0 || $i == $total){
			$gallery->guaranteeTimeLimit(5);
			$description = sprintf('processing %d of %d.', $i, $total);
			updateProgressBar($title, $description, $i/$total);
		}
		/* first check if it's not a group id */
		if($mapEntry['entityType'] != 'GalleryUser'){
			/* do group checking as well? */
			continue;
		}

		$externalId = $mapEntry['externalId'];
		/* gallery2 side */
		if(!isset($g2UserById[$entityId])){
			/* it doesn't exist */
			$ret = GalleryCoreApi::removeMapEntry('ExternalIdMap',
			array('entityId' => $entityId, 'entityType' => 'GalleryUser'));
			if($ret){
				/* error report */
				print $ret->getAsHtml();
			}
			continue;
		}

		if(!isset($jUserById[$externalId])){
			/* doesn't exist */
			$ret = GalleryCoreApi::removeMapEntry('ExternalIdMap',
			array('externalId' => $externalId, 'entityType' => 'GalleryUser'));
			if($ret){
				/* error report */
				print $ret->getAsHtml();
			}
			continue;
		}
	}
	/* safe memory */
	unset($mapEntityId);

	/* reload externalIdMap */
	list($ret, $mapByExternalId) = GalleryEmbed::getExternalIdmap('externalId');
	if($ret){
		/* error report */
		print $ret->getAsHtml();
	}

	$i = 0;
	$total = count($jUserById);
	$increment = min(100, $total * 0.10);
	$title = 'Syncing Joomla Users to G2.';

	foreach($jUserById as $externalId => $juser){
		$i++;
		if($i % $increment == 0 || $i == $total){
			$gallery->guaranteeTimeLimit(5);
			$description = sprintf('processing %d of %d.', $i, $total);
			updateProgressBar($title, $description, $i/$total);
		}
		/* check if map exists */
		if(isset($mapByExternalId[$externalId])){
			/* exists we need to update */
			$ret = user::updateUser($externalId, $juser);
			if($ret){
				/* error report */
				print $ret->getAsHtml();
			}
		} else {
			/* doesn't exist look for the username in Gallery list */
			if(!isset($g2UserByUsername[$juser['username']])){
				/* no user with that username, we can safely create */
				$ret = user::newUser($externalId, $juser);
				if($ret){
					/* error report */
					print $ret->getAsHtml();
				}
			} else {
				/* user exists but aren't linked */
				$compare = user::compareUsers($g2UserByUsername[$user['username']],$juser);
				if(empty($compare)){
					/* we can safely map the two users */
					$entityId = $g2UserByUsername[$juser['username']]->getId();
					$ret = GalleryEmbed::addExternalIdMapEntry($externalId, $entityId, 'GalleryUser');
					if($ret){
						/* error report */
						print $ret->getAsHtml();
					}
					/* also update the user */
					$ret = user::updateUser($externalId, $juser);
					if($ret){
						/* error report */
						print $ret->getAsHtml();
					}
				} else {
					/* same name but not compatible */
					/* error report */
					continue;
				}
			}
		}
		/* check group */
		$ret = user::addUserToGroup($externalId,$juser['gid']);
		if($ret){
			/* error report */
			print $ret->getAsHtml();
		}
	}

	/* complete our progressbar */
	updateProgressBar($title, $description, 1);

	$url = "index2.php?option=$option&act=$act";
	completeProgressBar($url);

	/* finish gallery 2 and exit; */
	core::done();
	exit;
}

/* progres bar functions */

/**
     * Update progress bar
     * 
     * Taken from gallery.menalto.com and modified to run inside Joomla
     * 
     * @param string $title top heading
     * @param string $description subheading
     * @param float $percentComplete from 0 to 1
     */
function updateProgressBar($title, $description, $percentComplete) {
	static $coreModule;
	if (!isset($coreModule)) {
		list ($ret, $coreModule) = GalleryCoreApi::loadPlugin('module', 'core');
		if ($ret) {
			/*
			* Unlikely this will ever be used.. but do update it if exact form of
			* translate() calls (with text+arg1+arg2) in this function ever change.
			*/
			eval('class GalleryTemplateAdapterFallbackCoreModule {
			    	function translate($x) {
					return sprintf($x[\'text\'], $x[\'arg1\'], $x[\'arg2\']);
			    	}
				}');
			$coreModule = new GalleryTemplateAdapterFallbackCoreModule();
		}
	}
	/* this cache should be replaced by JLCache */
	$cache =& core::_getCache();

	/* check if startTime set */
	if (!isset($cache['progressStartTime'])) {
		$cache['progressStartTime'] = time();
	}

	$startTime = $cache['progressStartTime'];

	/*
	* Calculate the time remaining
	*
	* TODO: Use a weighted measurement to provide a balanced estimate.  Consider the case
	* where the first 50% goes really quickly and the second 50% goes really slowly; the
	* estimate will be wildly inaccurate at the transition.
	*/
	if ($percentComplete > 0 &&
	$percentComplete < 1 && time() > $startTime) {
		$elapsed = (int)(time() - $startTime);
		$timeRemaining = ($elapsed / $percentComplete) - $elapsed;
		$timeRemaining = $coreModule->translate(
		array('text' => 'Estimated time remaining: %d:%02d',
		'arg1' => (int)($timeRemaining / 60),
		'arg2' => $timeRemaining % 60));
	} else {
		$timeRemaining = '';
	}

	/* it is possible to not have this function compiled into php */
	$memoryUsed = (function_exists('memory_get_usage')) ? memory_get_usage() : 0;

	/* A disabled memory_limit is -1, 0 crashes php */
	$memoryTotal = (0 < ini_get('memory_limit')) ? ini_get('memory_limit') : 0;

	/*
	* Ensure that percentComplete is in a dotted-decimal format.  Since the immediateView
	* is dealing in percentages, anything beyond two decimal places is unnecessary.
	*/
	$percentComplete = GalleryUtilities::roundToString($percentComplete, 2);

	/* Need to escape for javascript (backslash, ..) */
	GalleryCoreApi::requireOnce('lib/smarty/plugins/modifier.escape.php');
	$title = smarty_modifier_escape($title, 'javascript');
	$description = smarty_modifier_escape($description, 'javascript');

	$memoryInfo = $coreModule->translate(
	array('text' => 'Memory used: %s, total: %s',
	'arg1' => $memoryUsed,
	'arg2' => $memoryTotal));
	/* Newline needed or Opera 9.02 won't show updates */
	printf('<script type="text/javascript">'
	. 'updateProgressBar("%s", "%s", "%s", "%s", "%s");</script>%s',
	$title, $description, $percentComplete, $timeRemaining, $memoryInfo, "\n");
	flush();
}

/**
     * Reset progress bar timing stats.
     */
function resetProgressBarStats() {
	/* this cache should be replaced by JLCache */
	$cache =& core::_getCache();
	/* check if startTime set */
	$cache['progressStartTime'] = null;
}

/**
     * Display error progress bar.
     * @param string html
     */
function errorProgressBar($html) {
	static $errors;
	if(!isset($errors)){
		$errors = array();
	}
	/* add error */
	$errors[] = $html;
	/* rebuild html */
	$html = implode("<br />\n", $errors);
	printf('<script type="text/javascript">errorProgressBar("%s");</script>', $html);
	flush();
}

/**
     * Complete progress bar.
     */
function completeProgressBar($continueUrl) {
	$continueUrl = str_replace('&amp;', '&', $continueUrl);
	printf('<script type="text/javascript">completeProgressBar("%s");</script>', $continueUrl);
	flush();
}
?>