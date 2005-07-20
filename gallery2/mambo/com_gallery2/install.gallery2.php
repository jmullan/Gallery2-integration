<?php
/*
 Install file: executes any once-off code on install
 */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

function com_install(){

	global $database, $mosConfig_absolute_path, $mosConfig_dbprefix, $mosConfig_live_site;
	//set image used in admin menu
	$ret = set_admin_images();
	//include version.php
	require_once( $mosConfig_absolute_path.'/components/com_gallery2/version.php' );
	require_once( $mosConfig_absolute_path.'/components/com_gallery2/userfuncs.php' );
			//get old data or a false if table isn't there!
			$query = "SELECT *"
			. "\n FROM #__gallery2";
			$database->setQuery( $query );
			$old_data = $database->query();
			if($old_data){
				//print 'Database present: <img src="/administrator/images/tick.png"><br />';
				if(mysql_field_name($old_data, 0) == 'field'){//old version 1 install, not usefull discard!!
					$old_data = false;
					$query = 'ALTER TABLE `#__gallery2` CHANGE `field` `key` VARCHAR(128) NOT NULL';
					$database->setQuery( $query );
					$drop = $database->query();
					$query = 'TRUNCATE TABLE `#__gallery2`';
					$database->setQuery( $query );
					$drop = $database->query();
					//print 'Old gallery2 version 1 install, remove config data and modified the database: <img src="/administrator/images/tick.png"><br />';
					$database_ready = 1;
				}
				//check if wrong full install and no config is present in db
				if(mysql_num_rows($old_data) < 0){
					$database_ready = 1;
					$old_data = false;
				}
			}
		//split up in new or update
		if(!$old_data){
			//print 'Fresh Install!<br />';
			//new install from here
			if($database_ready != 1){
				$query = 'CREATE TABLE `'.$mosConfig_dbprefix.'gallery2` (`key` varchar(128) NOT NULL, `value` text, PRIMARY KEY  (`key`))';
				$database->setQuery( $query );
				$new_databs_check = $database->query();
				if(!$new_databs_check) {
					print "Creation of DataBase:";
					print '<img src="/administrator/images/publish_x.png"><br />';
				}
			}
			//try to detect gallery2
			//print 'Gallery2 detected: ';
			clearstatcache ();
			$filepath_1 = substr(__FILE__, 0 , strpos(__FILE__, "administrator/components/com_gallery2/install.gallery2.php")-1);
			$file_check = array("config.php", "embed.php", "main.php", "init.inc", "modules/core/classes/GalleryEmbed.class");
			$detect_count_file = 0;
			$most_change = array("gallery2", "gallery", "g2", "G2", "Gallery2", "Gallery", "albums", "album", "Album", "Albums", "../gallery2", "../gallery");
			foreach($most_change as $file_path2){
				if(is_dir($filepath_1.'/'.$file_path2.'/')){
					foreach($file_check as $file_name){
						if (file_exists($filepath_1.'/'.$file_path2.'/'.$file_name)){
							$detect_count_file++;
							if($detect_count_file == count($file_check) AND $file_path2 != "../gallery2" AND $file_path2 != "../gallery"){
								$correct_path = $filepath_1.'/'.$file_path2.'/';
								$relativeG2Path = $file_path2;
							} elseif($detect_count_file == count($file_check)){
								$correct_path = rtrim(realpath($filepath_1.'/'.$file_path2.'/config.php'), "/config.php").'/';
								$relativeG2Path = $file_path2;
							}//detect count
						}//file_exists($filepath_1.'/'.$file_path2.'/'.$file_name)
					}//foreach($file_check as $file_name)
				}//if dir exist
			}//foreach($most_change as $file_path2)
			if($detect_count_file == count($file_check)){
					//print '<img src="/administrator/images/tick.png"><br />';
					// getting the rest
					$g2_Config['path'] = $correct_path;
					$g2_Config['relativeG2Path'] = $relativeG2Path;
					$g2_Config['embedPath']= '/'.ltrim(str_replace('http://'.$_SERVER['HTTP_HOST'], '', $mosConfig_live_site), '/');
					$g2_Config['loginredirect']='/index.php';
					$g2_Config['displaysidebar']='0';
					$g2_Config['displaylogin']='0';
					$g2_Config['mirrorUsers']='1';
					$g2_Config['userSetup']='0';
					//config data from gallery2
					//print 'Getting gallery2 config settings:';
					$g2_Config = config_gallery2_get($g2_Config);
					//checking if installed on same database?
					global $mosConfig_host, $mosConfig_user, $mosConfig_password, $mosConfig_db;
					if($mosConfig_host == $g2_Config['hostname'] AND $mosConfig_user == $g2_Config['username'] AND $mosConfig_password == $g2_Config['password'] AND $mosConfig_db == $g2_Config['database'] ){
						$g2_Config['databasequel'] = 1;
					} else {
						$g2_Config['databasequel'] = 0;
					}
					//check gallery2 config succes
					if(array_key_exists('username', $g2_Config) AND array_key_exists('password', $g2_Config)){
						//print '<img src="/administrator/images/tick.png"><br />';
					} else {
						print '<img src="/administrator/images/publish_x.png"><br />';
					}
					//store version for update check
					$g2_Config['version'] = $version['com'];
					//print 'Store everything in Database:';
					
					$error = putindb($g2_Config, "insert");
					if(!$error){
						print '<img src="/administrator/images/publish_x.png"><br />';
					} else {
						//print '<img src="/administrator/images/tick.png"><br />';
					}
					
					//extra info
					$extra_tekst = 'Here are the values the install program has mananged to capture.<br />'
					. '<strong>These might not be correct for your install.</strong> '
					. 'In most case these settings will be correct, try the settings and if '
					. 'something doesn\'t work correct them on the config page!<br />';
					$extra_tekst .='<table width="75%" border="0" cellpadding="4" class="adminform">';
					foreach($g2_Config as $k => $v){
						$extra_tekst .= '<tr><td width=200>'.$k.'</td><td width="100%">'.$v.'</td></tr>';	
					}
					$extra_tekst .='</table>';
					$extra_tekst .='<br /> Now we will check the version of Gallery2:';
					
					$g2_Config = check_g2($g2_Config);
					if(array_key_exists("g2version", $g2_Config)){
						
					$extra_tekst.='<table width="50%" border="0" cellpadding="4" class="adminform">'
								.'<tr><td>Min version:</td><td>Your version:</td></tr>';
							if(version_compare($g2_Config['g2version'], $version['g2_min']) < 0) {
								$extra_tekst.='<tr><td>'.$version['g2_min'].'</td><td><font color="#FF0000">'.$g2_Config['g2version'].'</font></td></tr>';
							} else {
								$extra_tekst.='<tr><td>'.$version['g2_min'].'</td><td><font color="#00FF00">'.$g2_Config['g2version'].'</font></td></tr>';		
							}
					$extra_tekst.='</table>';
					} else {
						$extra_tekst.='problem with determing your gallery2 version!<br />';
					}
					
					//end extra info
					
					$url = 'index2.php?option=com_gallery2&act=conf&task=first_run';
			} else {// geen gallery gevonden
					print '<img src="/administrator/images/publish_x.png"><br />';
					print 'Sorry you have to set all the config variables on the next page!<br /><br />';
					$extra_tekst ='';
					$url = 'index2.php?option=com_gallery2&act=conf&task=first_run';				
			}
			//end new install
		} else {
			//update install from here
			//retriving old db settings
			//print 'Loading the old data from your install.<br />';
			while ($row = mysql_fetch_assoc($old_data)) {
				$key = $row['key'];
			   $g2_Config["$key"]=$row['value'];
			}//while
			//let's check version but first maybe it is the old com
			//print 'Done with loading.<br />Checking versions for upgrade.<br />';
			$g2_Config_old = $g2_Config;
			if(version_compare($g2_Config['version'], $version['com']) < 0){
				//update the database
				unset($check);
				//print 'updating database.<br />';
				$check = update_db($g2_Config, $version['com']);
			}//version compare
			if($check){
				unset($g2_Config);
				$query = "SELECT *"
				. "\n FROM #__gallery2";
				$database->setQuery( $query );
				$new_data = $database->query();
				while ($row = mysql_fetch_assoc($new_data)) {
					$key = $row['key'];
				   $g2_Config["$key"]=$row['value'];
				}//while
				//print 'you\'re database is updated and ready<br />';
			//nieuwe data is geladen
			}//check
			//extra info
			$extra_tekst = 'Here are the values the install program has mananged to capture.<br />'
			. '<strong>These might not be correct for your install.</strong> '
			. 'In most case these settings will be correct, try the settings and if '
			. 'something doesn\'t work correct them on the config page!<br />';
			$extra_tekst .='<table width="75%" border="0" cellpadding="4" class="adminform">';
			foreach($g2_Config as $k => $v){
				$extra_tekst .= '<tr><td width=200>'.$k.'</td><td width="100%">'.$v.'</td></tr>';	
			}//foreach
			$extra_tekst .='</table>';
			$extra_tekst .='<br /> Now we will check the version of Gallery2:';
			
			$g2_Config = check_g2($g2_Config);
			if(array_key_exists("g2version", $g2_Config)){
				
			$extra_tekst.='<table width="50%" border="0" cellpadding="4" class="adminform">'
						.'<tr><td>Min version:</td><td>Your version:</td></tr>';
					if(version_compare($g2_Config['g2version'], $version['g2_min']) < 0) {
						$extra_tekst.='<tr><td>'.$version['g2_min'].'</td><td><font color="#FF0000">'.$g2_Config['g2version'].'</font></td></tr>';
					} else { //if
						$extra_tekst.='<tr><td>'.$version['g2_min'].'</td><td><font color="#00FF00">'.$g2_Config['g2version'].'</font></td></tr>';		
					} // else
			$extra_tekst.='</table>';
			} else { // if array_key_exist
				$extra_tekst.='problem with determing your gallery2 version!<br />';
			}// else
					
			
			//end extra info
			$url = 'index2.php?option=com_gallery2&act=conf&task=first_run';
			//end update install
		}
		//update or new install ended, time to display some resolt and check if it is working and version of gallery2 is up to dat
		print $extra_tekst.'<br />';
		
		//all is done redirect link with msg and vars to adjust config screen
		print '<a href="'.$url.'">Go to the config page!</a>';
		
} //end com_install

//update function
function update_db($g2_Config, $version_compare){
	
	
	//will reload $g2_Config, if false it wil not.	
	return true;
}//end update_db

//gallery2 config function
function config_gallery2_get($g2_Config){
		
		$fc = file ($g2_Config['path'].'/config.php');
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
					}//end if 2
				}//end if 1
		}//end foreach($fc as $line)	
	return $g2_Config;
}//end function

//function putting or updating something in database
function putindb($array, $type){
	global $database, $mosConfig_dbprefix;
	if($type == 'insert'){
		$c=0;
		foreach($array as $k => $v){
			$query = 'INSERT INTO `'.$mosConfig_dbprefix.'gallery2` (`key`, `value`) VALUES (\''.$k.'\', \''.$v.'\')';
			$database->setQuery( $query );
			$check = $database->query();
			$return[$c] = array($query => $check); 
			$c++;
		}//end foreach
	}//end insert
	
	//return valua
		return $check;
}//end function
//getting gallery2 version
function check_g2($g2_Config){
	$g2_Config['embedUri'] = $g2_Config['embedPath'].'index.php?option=com_gallery2';
	G2helperclass::embed($g2_Config);
	$user_id = '';
	$ret = G2helperclass::init_G2($user_id, 'true');
	$g2_Config['g2version']= G2helperclass::g2_version();
return $g2_Config;
}//edn function
//admin images
function set_admin_images(){
	//first
	global $database, $mosConfig_absolute_path;
	//then
	$database->setQuery( "UPDATE #__components SET admin_menu_img = '../administrator/components/com_gallery2/images/foto.png' WHERE admin_menu_link='option=com_gallery2'");
	$database->query();
	$database->setQuery( "UPDATE #__components SET admin_menu_img = 'js/ThemeOffice/config.png' WHERE admin_menu_link='option=com_gallery2&act=conf'");
	$database->query();
	$database->setQuery( "UPDATE #__components SET admin_menu_img = 'js/ThemeOffice/users.png' WHERE admin_menu_link='option=com_gallery2&act=user'");
	$database->query();
	$database->setQuery( "UPDATE #__components SET admin_menu_img = '../administrator/components/com_gallery2/images/tools.png' WHERE admin_menu_link='option=com_gallery2&act=tools'");
	$database->query();
	$database->setQuery( "UPDATE #__components SET admin_menu_img = 'js/ThemeOffice/sections.png' WHERE admin_menu_link='option=com_gallery2&act=album'");
	$database->query();
	$database->setQuery( "UPDATE #__components SET admin_menu_img = 'js/ThemeOffice/help.png' WHERE admin_menu_link='option=com_gallery2&act=help'");
	$database->query();
	//now do the sync pictures
	@copy($mosConfig_absolute_path .'/administrator/components/com_gallery2/images/reload.png', $mosConfig_absolute_path .'/administrator/images/reload.png');
	@copy($mosConfig_absolute_path .'/administrator/components/com_gallery2/images/reload_f2.png', $mosConfig_absolute_path .'/administrator/images/reload_f2.png');
}//end function
?>