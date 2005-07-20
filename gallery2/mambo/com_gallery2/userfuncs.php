<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
//make class
class G2helperclass {

/*
* init G2
*$user_id, '' for guest, login in as user 'int'
*$fullInit, true if now handelrequest is called.
* needs config and embed to be loaded
*/
function init_G2($user_id, $fullInit){
	static $isInitiated;
	//guest 0 check
	if($user_id == 0){ $user_id = ''; }
	if(empty($isInitiated)){
		//the actual code
		global $g2_Config, $lang;
		$isInitiated = GalleryEmbed::init(array(
			   'embedPath' => $g2_Config['embedPath'],
			   'relativeG2Path' => $g2_Config['relativeG2Path'],
			   'loginRedirect' => $g2_Config['loginredirect'],
			   'embedUri' => $g2_Config['embedUri'],
			   'activeUserId' => $user_id,
			   'fullInit' => $fullInit,
			   //'gallerySessionId' => $HTTP_COOKIE_VARS["sessioncookie"],
			   'activeLanguage' => $lang));
	}
	return $isInitiated;
}
//get config file if not allready done
function g2_Config(){
	static $set_config;
	global $g2_Config;
	if(empty($set_config) OR empty($g2_Config)){
		$g2_Config=array();
		global $Itemid, $mosConfig_absolute_path, $database, $option, $mosConfig_live_site, $act;
		//config from database
		$query = "SELECT * FROM #__gallery2";
				$database->setQuery( $query );
				$new_data = $database->query();
				while ($row = mysql_fetch_assoc($new_data)) {
					$key = $row['key'];
					$g2_Config["$key"]=$row['value'];
				}
		//itemid
		if($option != 'com_gallery2' OR $act=='conf'){
			$query = "SELECT id"
			. "\n FROM #__menu"
			. "\n WHERE link = 'index.php?option=com_gallery2'"
			. "\n AND published = '1'"
			. "\n ORDER BY `access`, `parent`, `ordering` ASC LIMIT 1";
			$database->setQuery( $query );
			$Itemid = $database->loadResult();		
		}

		//check out user input for embedPath
		$g2_Config['embedPath'] = rtrim($g2_Config['embedPath'], "/").'/';
		$g2_Config['embedUri'] = $g2_Config['embedPath'].'index.php?option=com_gallery2&amp;Itemid='.$Itemid;
		$set_config = true;
	}
	return $g2_Config;
}

/*
 * include embed.hpp
 */
function embed(){
	static $set_embed;
	if(empty($set_embed)){
		$g2_Config = G2helperclass::g2_Config();
		require_once( $g2_Config['path'] . 'embed.php');
		$set_embed = true;
	}
	return $set_embed;
}
//user functions
/*
functions to write:
user: new_user, del_user, update_user, add_to_group, remove_from-group
group: new_group, del_group, update_group, get_all_groups
helperfunctions: init, config, embed, user_vars_g2, done?, login?, logout?, check_iso_lang,loadEntityByExternalId, getentitytypebyexternalid

*/
/*
* new g2 user
* $userid = mambo userid
* $par = array('username' => $my->username, 
               'email' => $row->email,
			   'fullname' => $row->name, 
               'hashedpassword' => $row->password,
			   'hashmethod' => 'md5')
			   language can also be set
*/
function new_user($userid, $par){
		$ret = GalleryEmbed::createUser($userid, $par);		
		return $ret;
}
/*
* update g2 user
* $userid = mambo userid
* $par = array('username' => $my->username, 
               'email' => $row->email,
			   'fullname' => $row->name, 
               'hashedpassword' => $row->password,
			   'hashmethod' => 'md5')
				language can also be set
*/
function update_user($userid, $par){
		$ret = GalleryEmbed::updateUser($userid, $par);
		return $ret;
}
/*
* delete mambo user from G2 userbase
* $userid = mambo userid
*/
function del_user($userid){
		$ret = GalleryEmbed::deleteUser($userid);
		return true;
}
/*
* Add user to g2 group
* userid = mambo userid
* $groupid = mambo groupid
*/
function add_user_group($userid, $groupid){
		$ret = GalleryEmbed::addUserToGroup($userid, $groupid);		
		return $ret;
}
/*
* Remove user from g2 group
* userid = mambo userid
* $groupid = mambo groupid
*/
function remove_user_group($userid, $groupid){
		$ret = GalleryEmbed::removeUserFromGroup($userid, $groupid);
		return $ret;
}
/*
* Make a Group in g2
* $id = mambo group id
* $name =  name of mambo group
*/
function new_group($id, $name){
		$ret = GalleryEmbed::createGroup($id, $name);
		return $ret;
}
/*
*Remove a group in g2
* $id = mambo group id
*/
function remove_group($id){
		$ret = GalleryEmbed::deleteGroup($id);
		return $ret;
}
/*
* Update a group name in G2
* $id = mambo group id
* $newname = new name
*/
function update_group_name($id, $newname){
	$ret = GalleryEmbed::updateGroup($id, array('groupname' => $newname));
		return $ret;
}
/*
* Debug function
* $debug_option, not yet operational, makes specefiek debug for some module.
*/
function debug($debug_option){
	global $g2_Config, $user_id, $lang;
	$msg = '<strong>Debug info</strong><br />';
	if($debug_option == 'module_image_block'){
		global $array, $align, $block, $header, $title, $date, $views, $owner, $number, $max_size, $link_target, $moduleclass_sfx, $itemframe, $albumframe; 
		$msg .= print_r($array);
		$msg .= '<br />';
		$msg .= '$align:'.$align;
		$msg .= '<br />$block:'.$block;
		$msg .= '<br />$header:'.$header;
		$msg .= '<br />$title:'.$title;
		$msg .= '<br />$date:'.$date;
		$msg .= '<br />$views:'.$views;
		$msg .= '<br />$owner:'.$owner;
		$msg .= '<br />$number:'.$number;
		$msg .= '<br />$max_size:'.$max_size;
		$msg .= '<br />$link_target:'.$link_target;
		$msg .= '<br />$moduleclass_sfx:'.$moduleclass_sfx;
		$msg .= '<br />$itemframe:'.$itemframe;
		$msg .= '<br />$albumframe:'.$albumframe;
	}
	//$msg .= '<br />$user_id:'.$user_id;
	//$msg .= '<br />$lang:'.$lang;
	foreach($g2_Config as $k => $v){
		if($k!='password' AND $k!='username' AND $k!='database' AND $k!='hostname'){
			$msg .= '<br />'.$k.'=>'.$v;
		}
	}
	$msg .= '<br />Gallery2 version:'.G2helperclass::g2_version();
	//$msg .= '<br />Component version:'.com_version();
	return $msg;
}//end debug function

/*
 * get the g2 version number
 */
function g2_version(){
	list ($ret, $g2Version) = GalleryCoreApi::getPluginParameter('module', 'core', '_version');
	
	if ($ret->isError()) {
		return 'error';
	}
	return $g2Version;
}
/*
 *component gallery2 version, for checking with modules.
 */
function com_version(){
	require_once("components/com_gallery2/version.php" );
	$com_version = $version['version'];
	return $com_version;
}

/*
 *language function
 * $iso_client_lang = mabelfish iso valua, may be empty
 * $mosConfig_locale = default mambo language
 */
function switchlang($iso_client_lang, $mosConfig_locale){
	if(!empty($iso_client_lang)){
		$lang = strtolower($iso_client_lang);
		$lang = G2helperclass::landtolang($lang);
	} else {
		$lang = substr ($mosConfig_locale, 0, 2); //pick first 2	
	}
	return trim($lang);
}

/*
 * function for replacing land iso code with language iso code used by switchlang function
 * possible update to use string(5) iso code
 */
function landtolang($lang){
	//make a list of lands the have a not corresponding iso codes
	$array = array();
	$array['gb'] = 'en';
	$array['us'] = 'en';
	$array['gr'] = 'el';
	$array['mx'] = 'es';
	$array['AR'] = 'es';
	$array['br'] = 'pt';
	$array['cn'] = 'zh';
	$array['tw'] = 'zh';
	$array['jp'] = 'ja';
	$array['ie'] = 'ga';
	$array['se'] = 'sv';
	$array['il'] = 'he';
	$array['dk'] = 'da';
	$array['yu'] = 'sr';
	$array['si'] = 'sl';
	$array['cz'] = 'cs';
	$array['vn'] = 'vi';
	//lets match
	foreach($array as $land_iso => $lang_iso){
		if(strpos($lang, $land_iso) === 0 AND $gotit != 1){
			$lang = $lang_iso;
			$gotit = 1;
		}
	}
	return $lang;
}

/* path checking function
 * $which can be relativeG2Path, embedPath, path corrensponding to which one to be checked
 * $path the path to be checked
 * report is given back like this :
 * $report[1] = true/false
 * $report[2] = msg
 * $report[3] = path that was checked
 */
function g2_pathcheck($which, $path){
	switch($which){
		case 'path'://full path to gallery2
			//check if directory exist
			if(is_dir($path)){
				$detect_count_file = 0;
				$file_check = array("config.php", "embed.php", "main.php", "init.inc", "modules/core/classes/GalleryEmbed.class");
				//check if the 5 files are there?
				foreach($file_check as $file_name){
					if (file_exists($path.$file_name)){
						$detect_count_file++;
							if($detect_count_file == count($file_check)){
								$report[1] = true;
								$report[2] = 'path is correct';
							}
					}//file_exists($path.$file_name)
				}//foreach
			} else {
				$report[1] = false;
				$report[2] = 'Directory doesn\'t exist!';
			}
		break;
		case 'relativeG2Path':
			global $mosConfig_absolute_path;
			$check_this_path = $mosConfig_absolute_path.'/'.ltrim(rtrim($path, "/"), "/").'/';
			$report_internal = G2helperclass::g2_pathcheck("path", $check_this_path);
			if($report_internal[1] == true){
				$report[1] = true;
				$report[2] = 'relativeG2Path is correct';
			} else {
				$report[1] = false;
			}
		break;
		case 'embedPath':
			global $mosConfig_live_site;
			$check = '/'.ltrim(rtrim(str_replace('http://'.$_SERVER['HTTP_HOST'], '', $mosConfig_live_site), '/'), '/'); // /cms or /
			$path = '/'.ltrim(rtrim($path, '/'), '/');
			if($path == $check){
				$report[1] = true;
				$report[2] = 'EmbedPath is correct';
			} else {
				$report[1] = false;
			}
		break;
	}//end switch
	$report[3] = $path;
	
	return $report;
}//end pathcheck

/* getting the interesting gallery2 setups 
 * needs $g2_Config as input!
 *Returns $g2_Config with more settings from gallery2
 */
function getG2setting($g2_Config){
	//setting parameterName and Values to retrieve
		$get = array("default.language", "language.selector", "id.rootAlbum", "languages", "cookie.path", "cookie.domain");
		$sql = '(';
		$first = true;
		foreach($get as $parname){
			if($first){
				$sql .= ' '.$g2_Config['columnPrefix'].'parameterName=\''.$parname.'\'';
				$first = false;
			} else {
				$sql .= ' OR '.$g2_Config['columnPrefix'].'parameterName=\''.$parname.'\'';
			}
		}
		$sql .=')';
		
	//setting connection through mambo or new one
	if($g2_Config['databasequel']!=1){
		
	} else {
	global $database;	
		$query = 'SELECT '.$g2_Config['columnPrefix'].'parameterName, '.$g2_Config['columnPrefix'].'parameterValue'
			. ' FROM '.$g2_Config['tablePrefix'].'PluginParameterMap'
			. ' WHERE '.$g2_Config['columnPrefix'].'pluginType = \'module\''
			. " AND $sql";
			$database->setQuery( $query );
			$data = $database->query();
	}
	while ($row = mysql_fetch_row($data)) {
					$key = $row[0];
					$g2_Config["$key"]=$row[1];
	}

return $g2_Config;
}//end getG2setting

/*
 *pathway function, now includes title changes and keywords
 */
function pathway($g2_itemId){
	global $mainframe, $g2_Config, $mosConfig_absolute_path, $mosConfig_live_site;
	//first get parent albums
	list ($ret, $parents) = GalleryCoreApi::fetchParentSequence($g2_itemId);
	$parents[count($parents)+1]=$g2_itemId;
	$g2_s=0;
	//gogogo
	foreach($parents as $items){
	list ($ret,	$item) = GalleryCoreApi::loadEntitiesById($items);
		$title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
		$titles[$item->getId()] = preg_replace('/\r\n/', ' ', $title);
		//let's switch again
		if($g2_s == 0 AND count($parents) ==1){ //first and last
		
			$path = ' '.$titles[$item->getId()];
		} elseif($g2_s == 0 AND count($parents) !=1) {// first but not last
		
			$path = '<a href="'.$g2_Config['embedUri'].'&amp;g2_view=core.ShowItem&amp;g2_itemId='.$items.'" class="pathway">'.$titles[$item->getId()].'</a>';
		} elseif($g2_s == count($parents)-1 AND $g2_s == 1){// second and last
		 
			$path =' '.$titles[$item->getId()];
		} elseif($g2_s == count($parents)-1){ // not second but it is last
		
			$path =' '.$titles[$item->getId()];
		} elseif($g2_s == 1) {// everything in between
		
			$path =' <a href="'.$g2_Config['embedUri'].'&amp;g2_view=core.ShowItem&amp;g2_itemId='.$items.'" class="pathway">'.$titles[$item->getId()].'</a>';
		} else {
			$path =' <a href="'.$g2_Config['embedUri'].'&amp;g2_view=core.ShowItem&amp;g2_itemId='.$items.'" class="pathway">'.$titles[$item->getId()].'</a>';
		}
		if($g2_Config['id.rootAlbum'] != $item->getId()){
			$mainframe->AppendPathway($path);
		}
		$g2_s++;
	}
	$mainframe->appendMetaTag( 'description', $item->getdescription());
	$mainframe->appendMetaTag( 'keywords', $item->getkeywords());
	$mainframe->setPageTitle($titles[$item->getId()]);
	return 'succes';		
}//end pathway function

//get some gallery2 config settings
function config_gallery2_get($g2_Config){
		
		$fc = file (rtrim($g2_Config['path']).'/config.php');
		$key = '$storeConfig[';
		$key2 = 'type';
		$key3 = 'usePersistentConnections';
		foreach($fc as $line){
			if (strstr($line,$key)){ //look for $key in each line
				if(!strstr($line,$key2) AND !strstr($line,$key3)){ //we don't need those
						$adjust = ltrim(rtrim ($line, "\';"), "\$storeConfig["); 
						$temp_array = explode("=", $adjust);
						$count = strpos($temp_array[0], ']') - 2;
						$temp = substr($temp_array[0], 1, $count);
						$count2 = strpos($temp_array[1], ';') - 3;
						$temp2 = substr($temp_array[1], 2, $count2);
						$g2_Config[$temp]= $temp2;
						unset($temp_array);
					}
				}
		}//end foreach($fc as $line)
		//check if database is the same
		global $mosConfig_host, $mosConfig_user, $mosConfig_password, $mosConfig_db;
		if($mosConfig_host == $g2_Config['hostname'] AND $mosConfig_user == $g2_Config['username'] AND $mosConfig_password == $g2_Config['password'] AND $mosConfig_db == $g2_Config['database'] ){
				$g2_Config['databasequel'] = 1;
				//no need to store this info if it is the same
				unset($g2_Config['hostname']);
				unset($g2_Config['username']);
				unset($g2_Config['password']);
				unset($g2_Config['database']);
		} else {
				$g2_Config['databasequel'] = 0;
		}			
	return $g2_Config;
}

/*
*album functions
*/

/* album checking */
function g2_album_check(){
	global $my, $g2_Config, $task;
	if(empty($g2_Config['rootuseralbum'])){
		$g2_Config['rootuseralbum'] = $g2_Config['id.rootAlbum'];
	}
	
	if(!empty($my->username)){//user is logged in or not
		//first get the owner id
		list($ret, $g2_user) = GalleryCoreApi::loadEntityByExternalId($my->id, 'GalleryUser');
		//
		list ($ret, $rootalbum) = GalleryCoreApi::loadEntitiesById($g2_Config['rootuseralbum']);
		if ($ret->isError()){ 
		$return['print'] = 'error fetching root album';
		return $return;
		 }
		list ($ret, $childIds) = GalleryCoreApi::fetchChildAlbumItemIds($rootalbum);
		if ($ret->isError()){ 
		$return['print'] = 'error fetching child items';
		return $return;
		 }		
		//check if it is empty, no child albums
		if(!empty($childIds)){
			//not empty let's check if there is a album owned
			list ($ret, $items) = GalleryCoreApi::loadEntitiesById($childIds);
			if ($ret->isError()){ return 'error in loading childs'; }
			$found = false;
		    foreach ($items as $child){
				//check if they are owned by user
				if($child->getOwnerid() == $g2_user->getId()){
					//break we have found one
					$found = true;
					break;
				}
			}
			//cokie data
			$remind = $_COOKIE["albumreminder_$my->id"];
			if($found == false AND $remind != 1){
				//no album, 2 option create one or ask for it
				if($task == 'create_album'){
					G2helperclass::g2_user_album($g2_user->getId(), $rootalbum);
				} elseif($task == 'cookie'){
					setcookie("albumreminder_$my->id", '1', time()+3600);
				} else {
					$return['print'] = '<p>You currently don&#39;t have a photo album, but you&#39;re entitled to one. '
                           		     .'<a href="'.$mosConfig_live_site.$g2_Config['embedUri']
                            	     .'&amp;task=create_album' . '">Create my album!</a> <a href="'.$mosConfig_live_site.$g2_Config['embedUri'].'&amp;task=cookie' . '">Remind later!</a></p>' . "\n";
				return $return;
				}
			}
		}//end empty childIds
	}
}

/* album creation for user */
function g2_user_album($g2_userid, $rootAlbum){
	global $my;

            list ($ret, $album) = GalleryCoreApi::createAlbum($rootAlbum->getId(), $my->username, 
                                                          $my->username, $my->name . "'s album",
                                                          '', $my->username . " " . $my->name);
            if ($ret->isError())
            {
                return false;
            }

            // Set permissions so that the user can do everything except change permissions
            $permissions = array('core.viewAll','core.addAlbumItem','core.addDataItem','core.edit',
                                 'core.delete', 'comment.all');

            list ($ret, $user) = GalleryCoreApi::fetchUserByUsername($my->username);
            if ($ret->isError())
            {
                GalleryCoreApi::deleteEntityById($album->getId());
                return false;
            }

            foreach ($permissions as $permission)
            {
                $ret = GalleryCoreApi::addUserPermission($album->getId(), $user->getId(), $permission);
                if ($ret->isError())
                {
                    GalleryCoreApi::deleteEntityById($album->getId());
                    return false;
                }
            }
            return true;
}

}//end class

//compatiblity with older modules
function init_G2($user_id){
	$ret = G2helperclass::init_G2($user_id, 'true');
return $ret;
}
function g2_Config(){
	$g2_Config = G2helperclass::g2_Config();
return $g2_Config;
}
function embed($g2_Config){
	$set_embed = G2helperclass::embed();
return $set_embed;
}
function switchlang($iso_client_lang, $mosConfig_locale){
	$lang = G2helperclass::switchlang($iso_client_lang, $mosConfig_locale);
return $lang;
}
?>