<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin  [v.2.0]                                |
// +---------------------------------------------------------------------------+
// | functions.inc                                                                |
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

$langfile = $_CONF['path'] . 'plugins/G2Bridge/language/' . $_CONF['language'] . '.php';

if ( file_exists( $langfile ) ) 
    include_once( $langfile );
else 
    require_once( $_CONF['path'] . 'plugins/G2Bridge/language/english.php' );

require_once( $_CONF['path'] . 'plugins/G2Bridge/config.php' );

G2Bridge();

function G2Bridge()
{
	global	$_CONF, $_G2B_CONF, $g2_data;
	
	// compute paths
	$num = preg_match( "/^http:\/\/[^\/]+\/?(.*)/i", $_CONF['site_url'], $match );

	if ( !$num )
		COM_errorLog( 'Cannot parse $_CONF[\'site_url\'].  Check you geeklog/config.php file.' );
	
	$_G2B_CONF['G2Bridge_path'] = $_G2B_CONF['embedUri'];

	$_G2B_CONF['G2_embed_path'] = $_G2B_CONF['G2_path'] .'/embed.php';
	
	$g2_data = '';
}

// +---------------------------------------------------------------------------+
// | Geeklog Plugin API Implementations                                        |
// +---------------------------------------------------------------------------+

/**
 * Returns the current plugin code version
 * 
 * @return string    plugin version
*/
function plugin_chkVersion_G2Bridge()
{
	return '2.0';
}

/**
* This will put an option for stats in the command and control block on
* moderation.php
*
*/
function plugin_cclabel_G2Bridge() 
{
	global $LANG_G2B, $_CONF, $_G2B_CONF;
	
	
	$retval = array();
	
	if ( SEC_hasRights('G2Bridge.admin') )
	{
		$retval = array( $LANG_G2B['admin_menu_title'],
			$_CONF['site_admin_url'] . '/plugins/G2Bridge/index.php',
			$_CONF['site_url'] . '/' . $_G2B_CONF['public_dir'] . '/images/G2Bridge.png' );
	}
	
	return $retval;
}

/**
* This function will inform all plugins when a new user account is created.
*
* @param     int     $uid     user id of the new user account
*
*/
function plugin_user_create_G2Bridge( $uid )
{
	G2B_CreateG2User( $uid );
}

/**
* This function will inform all plugins when a user account is deleted.
*
* @param     int     $uid     user id of the deleted user account
*
*/
function plugin_user_delete_G2Bridge( $uid )
{
	G2B_DeleteG2User( $uid );
}

/**                                                                                                                                                                    
 * This functions is called to inform plugins when a user's information                                                                                                 
 * (profile or preferences) has changed.                                                                                                                                
 *                                                                                                                                                                      
 * @param    int     $uid    User ID                                                                                                                                    
 *                                                                                                                                                                      
 */
function plugin_user_changed_G2Bridge( $uid )
{
	G2B_UpdateG2User( $uid );
}

/**
* This function will inform all plugins when a user logs in
*
* Note: This function is NOT called when users are re-authenticated by their
* long-term cookie. The global variable $_USER['auto_login'] will be set to
* 'true' in that case, however.
*
* @param     int     $uid     user id
*
*/
function plugin_user_login_G2Bridge( $uid )
{
}

/**
* This function will inform all plugins when a user logs out.
* Plugins should not rely on this ever being called, as the user may simply
* close the browser instead of logging out.
*
* @param     int     $uid     user id
*
*/
function plugin_user_logout_G2Bridge( $uid )
{		
	GalleryEmbed::logout( array('embedPath' => $_G2B_CONF['embedUri']) );
}

/**
* This functions is called to inform plugins when a group's information has
* changed or a new group has been created.
*
* @param    int     $grp_id     Group ID
* @param    string  $mode       type of change: 'new', 'edit', or 'delete'
*
*/
function plugin_group_changed_G2Bridge( $grp_id, $mode )
{
}

// Add the theme's CSS files and G2's Javascript if we are browsing a gallery2 page
function	plugin_getheadercode_G2Bridge()
{
//COM_errorLog( "plugin_getheadercode_G2Bridge" );
	global $_CONF, $_G2B_CONF, $g2_data, $_TABLES;

	// figure out a check to see if we actually need to init the gallery...
    $this_dir = basename( dirname( $_SERVER['SCRIPT_NAME'] ) );

	if ( $this_dir != 'G2Bridge' &&  $this_dir != $_G2B_CONF['public_dir'] )
	{
		// we aren't browsing a G2Bridge page, so check if a block is enabled
		
		$result = DB_query( "SELECT is_enabled
							FROM {$_TABLES['blocks']}
							WHERE name = 'G2B_block' OR name = 'G2B_rand_photo'
							LIMIT 2", 1 );

		if ( $result )
		{
			// IF we didn't find either plugin
			if ( DB_numRows( $result ) == 0 )
				return '';
			
			// Check that at least one is enabled
			$one_enabled = false;
			while ( $row = DB_fetchArray( $result ) )
			{
				if ( $row['is_enabled'] )			
				{
					$one_enabled = true;
					break;
				}
			}
		
			if ( !$one_enabled )
				return '';
		}
			
		// assume at least one is enabled if we can't find either block
		//	[it's safe to init the gallery anyway - just inefficient]
	}

 	$header_text = '';
	$needDone = false;
		
		 G2B_G2_init();
		$g2_data = GalleryEmbed::handleRequest();
		
		$needDone = true;
	
	// get the javascript and css links from the <head> html from G2
	if ( isset( $g2_data['headHtml'] ) )
	{
		$javascript = array();
		$css = array();

		list( $title, $css, $javascript ) = GalleryEmbed::parseHead( $g2_data['headHtml'] );
	
		foreach( $css as $stylesheet )
			$header_text .= $stylesheet."\n";
		
		foreach( $javascript as $script )
			$header_text .= $script."\n";
	}

	if ( $needDone )
		GalleryEmbed::done();
		
 	return $header_text;
}

/**
* returns the administrative option for this plugin
*
*/
function plugin_getadminoption_G2Bridge() 
{
    global $LANG_G2B, $_CONF;
    
    if ( SEC_hasRights( 'G2Bridge.admin' ) )
        return array( $LANG_G2B['admin_menu_title'], $_CONF['site_admin_url'] . '/plugins/G2Bridge/index.php', 0 );
}

/**
* returns the user option for this plugin
*
*/
function plugin_getuseroption_G2Bridge()
{
	global $_CONF, $_G2B_CONF, $_USER;

	if ( $_G2B_CONF['user_albums'] == true)
	{
		G2B_G2_init();
		//check to see if useralbum module is available
		list ($ret, $modules) = GalleryCoreApi::fetchPluginStatus('module');
		if ($ret) 
		{
			print "checking module:" . $ret->getAsHtml();
		}
		if($modules['useralbum']['active']&&$modules['useralbum']['available'] == 1){
			$username = $_USER['username'];
			list ($ret, $user) = GalleryCoreApi::fetchUserByUserName($username);
    			if ( !$ret)
    			{
               			 list ($ret, $albumId) = GalleryCoreApi::getPluginParameter('module', 'useralbum', 'albumId', $user->getId());
					if($albumId){
                					return array( 'My Gallery', $_CONF['site_url'].'/'.$_G2B_CONF['public_dir'].'/index.php?g2_itemId='.$albumId , 0 );
					}else{
						global $gallery;
						$urlGenerator =& $gallery->getUrlGenerator();
						$theUrl = $urlGenerator->generateUrl(
						array('controller' => 'useralbum.UserAlbum'),
						array('forceFullUrl' => true, 'forceSessionId' => true, 'htmlEntities' => false));
						return array( 'My Gallery', $theUrl , 0 );
					}
           		}
		}

	}else{
		return array( 'Gallery 2', $_CONF['site_url'] . '/' . $_G2B_CONF['public_dir'] . '/index.php', 0 );
	}
}

// upgrade to latest version of G2Bridge
function plugin_upgrade_G2Bridge()
{
	global $_TABLES;
	
	$res = DB_query( "SELECT pi_version FROM {$_TABLES['plugins']} WHERE pi_name = 'G2Bridge'" );
	$row = DB_fetchArray( $res, false );
	
	COM_errorLog( "Updating G2Bridge plugin from v" . $row['pi_version'] . ' to v' . plugin_chkVersion_G2Bridge(), 1 );
	
	return G2B_doUpgrades( $row['pi_version'] );
}

function G2B_doUpgrades( $current_G2Bridge_version )
{
	global $_TABLES, $_CONF, $_DB;

	$_DB->setDisplayError( true );


	$done = false;

	while ( $done == false )
	{
		switch ( $current_G2Bridge_version )
		{
			case '1.0':
			{
				$current_G2Bridge_version = '1.1';
				$done = true;
			}
			case '1.1':
			{
				$current_G2Bridge_version = '1.2';
				$done = true;
			}
			case '1.2':
			{
				$current_G2Bridge_version = '2.0';
				$done = true;
			}
				break;
				
			default:
				$done = true;
				break;
		}
	}

	DB_query( "UPDATE {$_TABLES['plugins']} SET pi_version = '{$current_G2Bridge_version}' WHERE pi_name = 'G2Bridge'" );
	
	return true;
}

function G2B_createBlock( $title, $id,  $on_left = 0, $tid, $width, $height, $args )
{
	global $_CONF, $_TABLES, $_USER;
	
	$content = G2B_generate_mini($width, $height, $args);
	$name = 'G2B_mini_'. $id;

	// add the block
	$block_id = DB_getItem( $_TABLES['blocks'], 'bid ', "name = '{$name}'" );
	
	if( $block_id > 0){
	 	$name .= +1;
		$block_id = '';
	}
	
	//COM_errorLog( "Adding block ($on_left, $title, $tid, $content), 1 );
	
    if ( $block_id == '' )
	{
		$block_title = addslashes( $title );		
	
		$sql = "INSERT INTO {$_TABLES['blocks']}
			( is_enabled, name, type, title, blockorder, onleft, tid, content, group_id, owner_id )
			VALUES( 1, '{$name}', 'normal', '{$block_title}', 10, $on_left, '{$tid}', '{$content}', 2, {$_USER['uid']} )
			";

		DB_query( $sql, 1 );
		
		if ( DB_error() )
			return false;
    }
	else
	{
		DB_query( "UPDATE {$_TABLES['blocks']} SET group_id = {$group_id} WHERE bid = $block_id LIMIT 1", 1 );
	}
	
	return COM_refresh ($_CONF['site_admin_url'] . '/plugins/G2Bridge/index.php?msg=11');
}

function G2B_generate_mini($width, $height, $args)
{
	global $_CONF, $_G2B_CONF;
	
	$display = '';
	$content = '';
	$content .= '<embed width="'. $width .'" height="'. $height .'" align="middle" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" name="minislide" wmode="transparent" quality="high" flashvars="xmlUrl='. $_CONF['site_url'].'/'.$_G2B_CONF['public_dir'].'/xml.php?'. $args .'" src="'. $_CONF['site_url'].'/'.$_G2B_CONF['public_dir'].'/minislideshow.swf"></embed>';
	$display .= '<div align="center">'. $content .'</div>';
	return $display;
}

function phpblock_G2B_block()
{
	global  $_CONF, $_USER, $_G2B_CONF,$LANG_G2B_block;

	if ( $_USER['uid'] < 2 && !$_G2B_CONF['allow_anon_access_gallery'] )
		return $LANG_G2B_block['access'];
	
	G2B_G2_init();
	global $gallery;
           $gallery->setConfig('showSidebarBlocks', false);
	$g2_data = GalleryEmbed::handleRequest();
	$needDone = true;


	if ( !isset( $g2_data['sidebarBlocksHtml'] ) )
	{
		$content .= 'You need to enable sidebar blocks in your Gallery 2 theme configuration.';
	}
	else
	{		
		$numSidebars = count( $g2_data['sidebarBlocksHtml'] );
		$sidebarHTML = array();
		
		// first remove blanks entries
		for ( $i = 0; $i < $numSidebars; $i++ )
		{
			$html = trim( $g2_data['sidebarBlocksHtml'][$i] );
			
			if ( $html == '' )
				continue;
			
			$sidebarHTML[] = $html;
		}

		$numSidebars = count( $sidebarHTML );

		$content = '<div id="gsSidebar">';
	
		// now display the ones left over
		for ( $i = 0; $i < $numSidebars; $i++ )
		{
			$content .= $sidebarHTML[$i];
			
			if ( $i != $numSidebars - 1  )
				$content .= '<hr size=1 width="90%" />';
		}
		
		$content .= '</div>';
	}

	if ( $needDone )
		GalleryEmbed::done();

	return $content;
}

function phpblock_G2B_albumSelect()
{
    $this_dir = basename( dirname( $_SERVER['SCRIPT_NAME'] ) );

	if ( $this_dir == '' )
	{
		// we are browsing webroot
	G2B_G2_init();
	$gallery->setConfig('showSidebarBlocks', false);		
	$g2_data = GalleryEmbed::handleRequest();	

	$content = '<div id="gsSidebar">';
	$content .= ($g2_data[sidebarBlocksHtml][1] );
	$content .= '</div>';

	GalleryEmbed::done();
	return $content;
	}
	return;
}

function phpblock_G2B_rand_photo()
{
	global $_CONF, $_USER, $_G2B_CONF, $g2_data, $LANG_G2B_rand_photo;

	if ( $_USER['uid'] < 2 && !$_G2B_CONF['allow_anon_access_random_photo'] )
		return $LANG_G2B_rand_photo['access'];
	
	$needDone = false;

	if ( $g2_data == '' )
	{
		 G2B_G2_init();

		$g2_data = GalleryEmbed::handleRequest();

		$needDone = true;
	}

	list( $ret, $content ) = GalleryEmbed::getImageBlock(
			array( 'blocks'=>'randomImage', 'show'=>$_G2B_CONF['random_photo_fields'] ) );	

	if ( $ret )
	{
		COM_errorLog( 'Failed to get random image.' );
		COM_errorLog( '   Here is the error message from G2:' );
		COM_errorLog( $ret->getAsText() );
	}

	if ( $needDone )
		GalleryEmbed::done();
    $display .= '<div align="center">'. $content .'</div>';
	return $display;
}


function G2B_remove_group( $name )
{
	global $_TABLES;
	
	$grp_id = DB_getItem( $_TABLES['groups'], 'grp_id', "grp_name = '{$name}'" );
    
    COM_errorLog( "Removing {$name} group", 1 );
    
    G2B_log_and_execute( "DELETE FROM {$_TABLES['groups']} WHERE grp_id = $grp_id", 1 );
    G2B_log_and_execute( "DELETE FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $grp_id", 1 );
}

function G2B_remove_feature( $name )
{
	global $_TABLES;
	
	$feat_id = DB_getItem($_TABLES['features'], 'ft_id', "ft_name = '{$name}'");

	COM_errorLog( "Removing feature '{$name}' and rights", 1 );

	G2B_log_and_execute( "DELETE FROM {$_TABLES['access']} WHERE acc_ft_id = $feat_id", 1 );
	G2B_log_and_execute( "DELETE FROM {$_TABLES['features']} WHERE ft_id = $feat_id", 1 );
}

function G2B_remove_block( $name )
{
	global $_TABLES;
	
    // remove block
    COM_errorLog( "Removing block $name", 1 );
    G2B_log_and_execute( "DELETE FROM {$_TABLES['blocks']} WHERE name='{$name}' LIMIT 1", 1 );
}

/**
* Removes the datastructures for this plugin from the Geeklog database
*/  
function plugin_uninstall_G2Bridge()
{
    global $_TABLES, $_USER;

    COM_errorLog( 'Removing the G2Bridge plugin...', 1 );

 	// Remove the exernal map we installed
    COM_errorLog( "Removing users from G2", 1 );
    
 	GL_RemoveGLUsersFromG2();
		
    // Unregister the plugin with Geeklog
    G2B_log_and_execute( "DELETE FROM {$_TABLES['plugins']} WHERE pi_name = 'G2Bridge' LIMIT 1" );

	G2B_remove_block( 'G2B_rand_photo' );
	G2B_remove_block( 'G2B_block' );
	
    // Remove Security for this plugin
    G2B_remove_group( 'G2Bridge Admin' );
    G2B_remove_group( 'G2Bridge View' );
 
 	G2B_remove_feature( 'G2Bridge.admin' );
 	G2B_remove_feature( 'G2Bridge.view' );
 
    // Drop tables we created
    COM_errorLog( "Removing tables", 1 );        
	G2B_log_and_execute( "DROP TABLE IF EXISTS G2B_vars" );

    COM_errorLog( 'The G2Bridge plugin has been removed', 1 );
    
    return true;
}

function G2B_log_and_execute( $sql )
{
    COM_errorLog( $sql, 1 );
    DB_query( $sql, 1 );
}

function G2B_MapLanguage( $GL_language )
{
	$GL_to_G2_language = array(
	//	'bosnian'			=>	'xx',	// *** No mapping
		'bulgarian'			=>	'bg',
		'chinese_big5'		=>	'zh',
		'chinese_gb2312'	=>	'zh',
		'chinese_simplified_utf-8'	=>	'zh_TW',
		'chinese_traditional_utf-8'	=>	'zh',
	//	'croatian_utf-8'	=>	'xx',	// *** No mapping
	//	'croatian'			=>	'xx',	// *** No mapping
		'czech'				=>	'cs',
		'danish'			=>	'da',
		'dutch'				=>	'nl',
		'english_utf-8'		=>	'en_GB',
		'english'			=>	'en_GB',
		'finnish_utf-8'		=>	'fi',
		'finnish'			=>	'fi',
		'french_canada'		=>	'fr',
		'french_france'		=>	'fr',
		'german_formal'		=>	'de',
		'german_utf-8'		=>	'de',
		'german'			=>	'de',
		'hebrew'			=>	'he',
		'hellenic'			=>	'el',
	//	'indonesian'		=>	'xx',	// *** No mapping
		'italian'			=>	'it',
		'japanese_utf-8'	=>	'ja',
		'japanese'			=>	'ja',
		'norwegian'			=>	'no',
		'polish'			=>	'pl',
		'portuguese_brazil'	=>	'pt_BR',
		'portuguese'		=>	'pt_PT',
	//	'romanian'			=>	'xx',	// *** No mapping
		'russian'			=>	'ru',
	//	'slovak'			=>	'xx',	// *** No mapping
		'slovenian'			=>	'sl',
		'spanish_argentina'	=>	'es',
		'spanish'			=>	'es',
		'swedish'			=>	'sv',
		'turkish'			=>	'tr'
		);

	$lang = $GL_to_G2_language[$GL_language];

	if ( $lang == '' )
		$lang = 'en_GB';	// default to English
		
	return( $lang );
}

function G2B_GetG2UserFromGL()
{
	global $_USER;
	if ( empty( $_USER['uid'] ) || $_USER['uid'] < 2 )
		$userID = '';
	else
		$userID = $_USER['uid'];
	return $userID;
}

function G2B_CreateG2User( $GL_uid )
{
	global $_TABLES;
	
	 G2B_G2_init();

	// make sure we are don't already have an user with the same name                                                                                                                    
	$ret = GalleryEmbed::isExternalIdMapped( $GL_uid, 'GalleryUser' );

	if ( !($ret && ( $ret->getErrorCode() & ERROR_MISSING_OBJECT )) )
			return;

	$sql = "SELECT uid, username, fullname, passwd, email, language, UNIX_TIMESTAMP( regdate ) AS registration_ts
				FROM {$_TABLES['users']} WHERE uid ='{$GL_uid}' LIMIT 1";
	$res = DB_query( $sql );
	$row = DB_fetchArray( $res );

	$args['fullname'] = $row['fullname'];
	$args['username'] = $row['username'];
	$args['hashedpassword'] = $row['passwd'];
	$args['hashmethod'] = 'md5';
	$args['email'] = $row['email'];
	$args['creationtimestamp']= $row['registration_ts'];
	$args['language'] = G2B_MapLanguage( $row['language'] );

	$ret = GalleryEmbed::createUser( $GL_uid, $args );
	
	if (($ret) && ($ret->getErrorCode() & ERROR_COLLISION ))
	{
                     GL_addExternalIdMap($GL_uid);
		return false;
           }		
	

	$debug = "username: [{$args['username']}]<br>
			fullname: [{$args['fullname']}]<br>
			hashedpassword: [{$args['hashedpassword']}]<br>
			email: [{$args['email']}]<br>
			creationtimestamp: [{$args['creationtimestamp']}]<br>
	";
	
	if ( $ret )
		return $debug . '<br>Failed to create G2 user with extId ['.$GL_uid.' - '.G2B_GetG2UserFromGL().'] 
						Here is the error message from G2: <br>' . $ret->getAsHtml();
}

function G2B_UpdateG2User( $GL_uid )
{
	global $_TABLES;
	
	G2B_G2_init();
	
	$sql = "SELECT uid, username, fullname, passwd, email, language, UNIX_TIMESTAMP( regdate ) AS registration_ts                                                                 
				FROM {$_TABLES['users']} WHERE uid ='{$GL_uid}' LIMIT 1";
	$res = DB_query( $sql );
	$row = DB_fetchArray( $res );
	
	$args['fullname'] = $row['fullname'];
	$args['username'] = $row['username'];
	$args['hashedpassword'] = $row['passwd'];
	$args['hashmethod'] = 'md5';
	$args['email'] = $row['email'];
	$args['creationtimestamp']= $row['registration_ts'];
	$args['language'] = G2B_MapLanguage( $row['language'] );

	
	$ret = GalleryEmbed::updateUser( $GL_uid, $args );
	
	$debug = "username: [{$args['username']}]<br>                                                                                                                                
		fullname: [{$args['fullname']}]<br>                                                                                                                          
		hashedpassword: [{$args['hashedpassword']}]<br>                                                                                                              
		email: [{$args['email']}]<br>                                                                                                                                
		creationtimestamp: [{$args['creationtimestamp']}]<br>                                                                                                         
	";
	
	if ($ret)
		COM_errorLog("GalleryEmbed::updateUser failed, G2 error: <br>". $ret->getAsHtml());
}

function G2B_DeleteG2User( $GL_uid )
{	
	G2B_G2_init();
	
	// make sure user we're going to delete from G2 is actually a user of G2.                                                                                                            
	$ret = GalleryEmbed::isExternalIdMapped( $GL_uid, 'GalleryUser' );
	
	if ( !($ret) )
	{
		$res = GalleryEmbed::deleteUser( $GL_uid );
		
		if ($res)
			COM_errorLog( "GalleryEmbed::deleteUser failed, G2 error: <br>". $res->getAsHtml() );
	}
}

function G2B_G2_init( $full = false )
{
	global	 $_CONF, $_G2B_CONF;
	
	static $already_init = false;
	
	if ( $full )
		$already_init = false;
		
	if ( $already_init )
		return null;
		
	require_once( $_G2B_CONF['G2_embed_path'] );

	$ret = GalleryEmbed::init( array(
		   'embedUri' => $_G2B_CONF['embedUri'],
		   'g2Uri' => $_G2B_CONF['g2Uri'],
		   'loginRedirect' => $_G2B_CONF['login_redirect'],
		   'activeLanguage' => G2B_MapLanguage( $_CONF['language'] ),
		   'fullInit' => $full ) );
		   
	if ( $ret )
          {  
	    COM_errorLog( 'Gallery2 failed to init' );
	    COM_errorLog( 'Here is the error message from G2:' );
	    COM_errorLog( $ret->getAsText() );
	} else {
	    $already_init = true;
	    checkUser();
	    return $ret;
	}
}

function checkUser() 
{
	$userId = G2B_GetG2UserFromGL();
	if ($userId > 1) {
    		$ret = GalleryEmbed::isExternalIdMapped($userId, 'GalleryUser');
		if (!$ret) {
			list ($ret, $user) = GalleryCoreApi::loadEntityByExternalId($userId, 'GalleryUser');
			if ($ret) {
				$ret = GalleryCoreApi::removeMapEntry('ExternalIdMap', array('externalId' => $userId, 'entityType' => 'GalleryUser'));
				$ret = "deleted";
			}
		}
		if ($ret) {
			G2B_CreateG2User($userId);
		}

		$ret = GalleryEmbed::checkActiveUser($userId);
		if ($ret) {
			$ret->getAsHtml();
			return $ret;
		}
	} else {
	// Otherwise Guest
		$ret = GalleryEmbed::checkActiveUser('');
		if ($ret) {
			$ret->getAsHtml();
			return $ret;
		}
	}
}

// Add user to Gallery Admin Group  

function G2B_addUserToGroup( $GL_uid )
{
    list( $ret, $user ) = GalleryCoreApi::loadEntityByExternalId( $GL_uid, 'GalleryUser' );
    $G2_uid = $user->getId(); 
    $res = GalleryCoreApi::addUserToGroup( $G2_uid, 3); //G2 admin groupid is 3
    if ( $res )
    {
	COM_errorLog( 'Failed to add user to admin group for GL uid ['.$GL_uid.']' );
	COM_errorLog( '   Here is the error message from G2:' );
	COM_errorLog( $res->getAsText() );
	return false;
    }
    return true;
}

function GL_MapUsersToG2()
{
	global $_TABLES, $_USER;
	
	// Get a listing of all UIDs
	$sql = "SELECT uid FROM {$_TABLES['users']} WHERE uid > 1";
	$result = DB_query( $sql );
	
	while ( $A = DB_fetchArray( $result ) )
	{
		$errStr = G2B_CreateG2User( $A['uid'] );		
	}
}

function GL_addExternalIdMap($GL_uid)
{
	global $_TABLES;
	
	          // Fetch username based on $GL_uid
    $sql = "SELECT username FROM {$_TABLES['users']} WHERE uid ='{$GL_uid}' LIMIT 1";
	$result = DB_query( $sql );
	$A = DB_fetchArray( $result );
	
	list( $ret, $user ) = GalleryCoreApi::fetchUserByUserName($A['username']);

	$res = GalleryEmbed::addExternalIdMapEntry($GL_uid, $user->getId(), 'GalleryUser');
	if ( $res )
    {
	          COM_errorLog( 'Failed to add externalIdMap for GL usename ['.$A['username'].']' );
	          COM_errorLog( '   Here is the error message from G2:' );
	          COM_errorLog( $res->getAsText() );
	          return false;
           }
}

function GL_RemoveGLUsersFromG2()
{
	global $_TABLES, $_USER;
	
	// Get a listing of all UIDs
	$sql = "SELECT uid FROM {$_TABLES['users']} WHERE uid > 1";
	$result = DB_query( $sql );
	
	while ( $A = DB_fetchArray( $result ) )
	{
		$errStr = G2B_DeleteG2User( $A['uid'] );		
	}
}

?>