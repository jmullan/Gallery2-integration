<?php
// +---------------------------------------------------------------------------+
// | GL_Gallery2 Plugin                                                        |
// +---------------------------------------------------------------------------+
// | functions.inc                                                             |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2005 Andy Maloney [asmaloney@users.sf.net]                  |
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

$langfile = $_CONF['path'] . 'plugins/GL_Gallery2/language/' . $_CONF['language'] . '.php';

if ( file_exists( $langfile ) ) 
    include_once( $langfile );
else 
    require_once( $_CONF['path'] . 'plugins/GL_Gallery2/language/english.php' );

require_once( $_CONF['path'] . 'plugins/GL_Gallery2/config.php' );

GL_Gallery2();

function GL_Gallery2()
{
	global	$_CONF, $_GLG_CONF, $g2_data;
	
	// compute paths
	$num = preg_match( "/^http:\/\/[^\/]+\/?(.*)/i", $_CONF['site_url'], $match );

	if ( !$num )
		COM_errorLog( 'Cannot parse $_CONF[\'site_url\'].  Check you geeklog/config.php file.' );
	
	$_GLG_CONF['GL_Gallery2_path'] = $match[1] . '/' . $_GLG_CONF['public_dir'];

	$_GLG_CONF['G2_embed_path'] = $_GLG_CONF['G2_path'] .'/embed.php';
	
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
function plugin_chkVersion_GL_Gallery2()
{
	return '0.5.0';
}

/**
* This will put an option for stats in the command and control block on
* moderation.php
*
*/
function plugin_cclabel_GL_Gallery2() 
{
	global $LANG_GLG, $_CONF, $_GLG_CONF;
	
	
	$retval = array();
	
	if ( SEC_hasRights('GL_Gallery2.admin') )
	{
		$retval = array( $LANG_GLG['admin_menu_title'],
			$_CONF['site_admin_url'] . '/plugins/GL_Gallery2/index.php',
			$_CONF['site_url'] . '/' . $_GLG_CONF['public_dir'] . '/images/GLG48.png' );
	}
	
	return $retval;
}

/**
* This function will inform all plugins when a new user account is created.
*
* @param     int     $uid     user id of the new user account
*
*/
function plugin_user_create_GL_Gallery2( $uid )
{
	GLG_CreateG2User( $uid );
}

/**
* This function will inform all plugins when a user account is deleted.
*
* @param     int     $uid     user id of the deleted user account
*
*/
function plugin_user_delete_GL_Gallery2( $uid )
{
	GLG_DeleteG2User( $uid );
}

/**                                                                                                                                                                    
 * This functions is called to inform plugins when a user's information                                                                                                 
 * (profile or preferences) has changed.                                                                                                                                
 *                                                                                                                                                                      
 * @param    int     $uid    User ID                                                                                                                                    
 *                                                                                                                                                                      
 */
function plugin_user_changed_GL_Gallery2( $uid )
{
	GLG_UpdateG2User( $uid );
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
function plugin_user_login_GL_Gallery2( $uid )
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
function plugin_user_logout_GL_Gallery2( $uid )
{
	global $_GLG_CONF;
	
	require_once( $_GLG_CONF['G2_embed_path'] );

	$ret = GLG_G2_init();
	
	if ( $ret->isError() )
		return;
		
	GalleryEmbed::logout( array('embedPath' => $_GLG_CONF['GL_Gallery2_path']) );
}

/**
* This functions is called to inform plugins when a group's information has
* changed or a new group has been created.
*
* @param    int     $grp_id     Group ID
* @param    string  $mode       type of change: 'new', 'edit', or 'delete'
*
*/
function plugin_group_changed_GL_Gallery2( $grp_id, $mode )
{
}

// Add the theme's CSS files and G2's Javascript if we are browsing a gallery2 page
function	plugin_getheadercode_GL_Gallery2()
{
//COM_errorLog( "plugin_getheadercode_GL_Gallery2" );
	global $_CONF, $_GLG_CONF, $g2_data, $_TABLES;

	// figure out a check to see if we actually need to init the gallery...
    $this_dir = basename( dirname( $_SERVER['SCRIPT_NAME'] ) );

	if ( $this_dir != 'GL_Gallery2' &&  $this_dir != $_GLG_CONF['public_dir'] )
	{
		// we aren't browsing a GL_Gallery2 page, so check if a block is enabled
		
		$result = DB_query( "SELECT is_enabled
							FROM {$_TABLES['blocks']}
							WHERE name = 'glg_block' OR name = 'glg_rand_photo'
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
 
 	require_once( $_GLG_CONF['G2_embed_path'] );

 	$header_text = '';
	$needDone = false;
	
	if ( $g2_data == '' )
	{	
		$ret = GLG_G2_init();
		
		if ( $ret->isError() )
		{
			COM_errorLog( 'Failed to init() G2.' );
			COM_errorLog( '   Here is the error message from G2:' );
			COM_errorLog( $ret->getAsText() );
			
			return;
		}
			
		$g2_data = GalleryEmbed::handleRequest();
		
		$needDone = true;
	}
	
	// get the javascript and css links from the <head> html from G2
	if ( isset( $g2_data['headHtml'] ) )
	{
		$javascript = array();
		$css = array();

		list( $title, $css, $javascript ) = GalleryEmbed::parseHead( $g2_data['headHtml'] );
	
		foreach( $css as $stylesheet )
			$header_text .= $stylesheet;
		
		foreach( $javascript as $script )
			$header_text .= $script;
	}

	if ( $needDone )
		GalleryEmbed::done();
		
 	return $header_text;
}

/**
* returns the administrative option for this plugin
*
*/
function plugin_getadminoption_GL_Gallery2() 
{
    global $LANG_GLG, $_CONF;
    
    if ( SEC_hasRights( 'GL_Gallery2.admin' ) )
        return array( $LANG_GLG['admin_menu_title'], $_CONF['site_admin_url'] . '/plugins/GL_Gallery2/index.php', 0 );
}

/**
* returns the user option for this plugin
*
*/
function plugin_getuseroption_GL_Gallery2() 
{
    global $_CONF, $_GLG_CONF;

	return array( 'Gallery 2', $_CONF['site_url'] . '/' . $_GLG_CONF['public_dir'] . '/index.php', 0 );
}

// upgrade to latest version of GL_Gallery2
function plugin_upgrade_GL_Gallery2()
{
	global $_TABLES;
	
	$res = DB_query( "SELECT pi_version FROM {$_TABLES['plugins']} WHERE pi_name = 'GL_Gallery2'" );
	$row = DB_fetchArray( $res, false );
	
	COM_errorLog( "Updating GL_Gallery2 plugin from v" . $row['pi_version'] . ' to v' . plugin_chkVersion_GL_Gallery2(), 1 );
	
	return GLG_doUpgrades( $row['pi_version'] );
}

function GLG_doUpgrades( $current_GL_Gallery2_version )
{
	global $_TABLES, $_CONF, $_DB;

	$_DB->setDisplayError( true );


	$done = false;

	while ( $done == false )
	{
		switch ( $current_GL_Gallery2_version )
		{
			case '0.4.0':
			case '0.4.1':
			{
				// no longer used
			    GLG_remove_group( 'GL_Gallery2 View' ); 
			 	GLG_remove_feature( 'GL_Gallery2.view' );
    				
				$current_GL_Gallery2_version = '0.4.2';
				$done = true;
			}
				break;
				
			case '0.4.2':
			{
				GL_MapUsersToG2();
				
				$current_GL_Gallery2_version = '0.5.0';
				$done = true;
			}
				break;
				
			default:
				$done = true;
				break;
		}
	}

	DB_query( "UPDATE {$_TABLES['plugins']} SET pi_version = '{$current_GL_Gallery2_version}' WHERE pi_name = 'GL_Gallery2'" );
	
	return true;
}

function phpblock_GLG_block()
{
//COM_errorLog( "phpblock_GLG_block" );

	global $_CONF, $_USER, $_GLG_CONF, $g2_data, $LANG_GLG_block;

	if ( empty( $_USER['username'] ) && !$_GLG_CONF['allow_anon_access_gallery'] )
		return $LANG_GLG_block['access'];

	$needDone = false;
	
	if ( $g2_data == '' )
	{	
		$ret = GLG_G2_init();
		
		if ( $ret->isError() )
		{
			COM_errorLog( 'Failed to init() G2.' );
			COM_errorLog( '   Here is the error message from G2:' );
			COM_errorLog( $ret->getAsText() );
			
			return;
		}
			
		$g2_data = GalleryEmbed::handleRequest();
		
		$needDone = true;
	}

	if ( !isset( $g2_data['sidebarBlocksHtml'] ) )
	{
		$content = 'You need to enable sidebar blocks in your Gallery 2 theme configuration.';
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

function phpblock_GLG_rand_photo()
{
//COM_errorLog( "phpblock_GLG_rand_photo" );

	global $_CONF, $_USER, $_GLG_CONF, $g2_data, $LANG_GLG_rand_photo;

	if ( empty( $_USER['username'] ) && !$_GLG_CONF['allow_anon_access_random_photo'] )
		return $LANG_GLG_rand_photo['access'];
	
	$needDone = false;

	if ( $g2_data == '' )
	{
		$ret = GLG_G2_init();

		if ( $ret->isError() )
		{
				COM_errorLog( 'Failed to init() G2.' );
				COM_errorLog( '   Here is the error message from G2:' );
				COM_errorLog( $ret->getAsText() );

				return;
		}

		$g2_data = GalleryEmbed::handleRequest();

		$needDone = true;
	}

	list( $ret, $content ) = GalleryEmbed::getImageBlock(
			array( 'blocks'=>'randomImage', 'show'=>$_GLG_CONF['random_photo_fields'] ) );	

	if ( $ret->isError() )
	{
		COM_errorLog( 'Failed to get random image.' );
		COM_errorLog( '   Here is the error message from G2:' );
		COM_errorLog( $ret->getAsText() );
	}

	if ( $needDone )
		GalleryEmbed::done();

	return $content;
}


function GLG_remove_group( $name )
{
	global $_TABLES;
	
	$grp_id = DB_getItem( $_TABLES['groups'], 'grp_id', "grp_name = '{$name}'" );
    
    COM_errorLog( "Removing {$name} group", 1 );
    
    GLG_log_and_execute( "DELETE FROM {$_TABLES['groups']} WHERE grp_id = $grp_id", 1 );
    GLG_log_and_execute( "DELETE FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $grp_id", 1 );
}

function GLG_remove_feature( $name )
{
	global $_TABLES;
	
	$feat_id = DB_getItem($_TABLES['features'], 'ft_id', "ft_name = '{$name}'");

	COM_errorLog( "Removing feature '{$name}' and rights", 1 );

	GLG_log_and_execute( "DELETE FROM {$_TABLES['access']} WHERE acc_ft_id = $feat_id", 1 );
	GLG_log_and_execute( "DELETE FROM {$_TABLES['features']} WHERE ft_id = $feat_id", 1 );
}

function GLG_remove_block( $name )
{
	global $_TABLES;
	
    // remove block
    COM_errorLog( "Removing block $name", 1 );
    GLG_log_and_execute( "DELETE FROM {$_TABLES['blocks']} WHERE name='{$name}' LIMIT 1", 1 );
}

/**
* Removes the datastructures for this plugin from the Geeklog database
*/  
function plugin_uninstall_GL_Gallery2()
{
    global $_TABLES, $_USER;

    COM_errorLog( 'Removing the GL_Gallery2 plugin...', 1 );

 	// Remove the exernal map we installed
//    COM_errorLog( "Removing users from G2", 1 );
    
 //	GL_RemoveGLUsersFromG2();

 	// Remove the exernal map we installed
	// 6 is the admin id for G2
//	$success = GLG_RemoveExternalMap( 6, $_USER['uid'] );

//	if ( !$success )
//		COM_errorLog( 'There was a problem removing the external map from your G2 installation.' );
		
    // Unregister the plugin with Geeklog
    GLG_log_and_execute( "DELETE FROM {$_TABLES['plugins']} WHERE pi_name = 'GL_Gallery2' LIMIT 1" );

	GLG_remove_block( 'glg_rand_photo' );
	GLG_remove_block( 'glg_block' );
	
    // Remove Security for this plugin
    GLG_remove_group( 'GL_Gallery2 Admin' );
    GLG_remove_group( 'GL_Gallery2 View' );
 
 	GLG_remove_feature( 'GL_Gallery2.admin' );
 	GLG_remove_feature( 'GL_Gallery2.view' );
 
    // Drop tables we created
    COM_errorLog( "Removing tables", 1 );        
	GLG_log_and_execute( "DROP TABLE IF EXISTS glg_vars" );

    COM_errorLog( 'The GL_Gallery2 plugin has been removed', 1 );
    
    return true;
}

function GLG_log_and_execute( $sql )
{
    COM_errorLog( $sql, 1 );
    DB_query( $sql, 1 );
}

function GLG_MapLanguage( $GL_language )
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

function GLG_GetG2UserFromGL()
{
	global	$_USER;
	
	if ( empty( $_USER['uid'] ) || $_USER['uid'] == 1 )
		$userID = '';
	else
		$userID = $_USER['uid'];
	
	return $userID;
}

function GLG_CreateG2User( $GL_uid )
{
	global $_TABLES;
	
	$ret = GLG_G2_init();

	// make sure we are don't already have an user with the same name                                                                                                                    
	$ret = GalleryEmbed::isExternalIdMapped( $GL_uid, 'GalleryUser' );

	if ( !($ret->isError() && ( $ret->getErrorCode() & ERROR_MISSING_OBJECT )) )
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
	$args['language'] = GLG_MapLanguage( $row['language'] );

	$res = GalleryEmbed::createUser( $GL_uid, $args );

	$debug = "username: [{$args['username']}]<br>
			fullname: [{$args['fullname']}]<br>
			hashedpassword: [{$args['hashedpassword']}]<br>
			email: [{$args['email']}]<br>
			creationtimestamp: [{$args['creationtimestamp']}]<br>
	";
	
	if ( !$res->isSuccess() )
		return $debug . '<br>Failed to create G2 user with extId ['.$GL_uid.' - '.GLG_GetG2UserFromGL().']. 
						Here is the error message from G2: <br>' . $res->getAsHtml();
}

function GLG_UpdateG2User( $GL_uid )
{
	global $_TABLES;
	
	$ret = GLG_G2_init();
	
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
	$args['language'] = GLG_MapLanguage( $row['language'] );

	
	$res = GalleryEmbed::updateUser( $GL_uid, $args );
	
	$debug = "username: [{$args['username']}]<br>                                                                                                                                
		fullname: [{$args['fullname']}]<br>                                                                                                                          
		hashedpassword: [{$args['hashedpassword']}]<br>                                                                                                              
		email: [{$args['email']}]<br>                                                                                                                                
		creationtimestamp: [{$args['creationtimestamp']}]<br>                                                                                                         
	";
	
	if ( !$res->isSuccess() )
		COM_errorLog("GalleryEmbed::updateUser failed, G2 error: <br>". $res->getAsHtml());
}

function GLG_DeleteG2User( $GL_uid )
{	
	$ret = GLG_G2_init();
	
	// make sure user we're going to delete from G2 is actually a user of G2.                                                                                                            
	$ret = GalleryEmbed::isExternalIdMapped( $GL_uid, 'GalleryUser' );
	
	if ( !($ret->isError()) )
	{
		$res = GalleryEmbed::deleteUser( $GL_uid );
		
		if ( !$res->isSuccess() )
			COM_errorLog( "GalleryEmbed::deleteUser failed, G2 error: <br>". $res->getAsHtml() );
	}
}

function GLG_G2_init( $full = false )
{
	global	$_CONF, $_GLG_CONF;
	
	static $already_init = false;
	
	if ( $full )
		$already_init = false;
		
	if ( $already_init )
		return( GalleryStatus::success() );
		
	require_once( $_GLG_CONF['G2_embed_path'] );

	$ret = GalleryEmbed::init( array(
		   'embedUri' => 'index.php',
		   'embedPath' => $_GLG_CONF['GL_Gallery2_path'],
		   'relativeG2Path' => $_GLG_CONF['G2_relative_path'],
		   'loginRedirect' => 'users.php',
		   'activeUserId' => GLG_GetG2UserFromGL(),
		   'activeLanguage' => GLG_MapLanguage( $_CONF['language'] ),
		   'fullInit' => $full ) );
		   
	if ( !$ret->isError() )
	{
		global $_TABLES;
	
		$already_init = true;
		
		$where = DB_getItem( $_TABLES['glg_vars'], 'value', "name = 'control_block'" );

		GalleryCapabilities::set( 'showSidebarBlocks', $where == 'gallery2' );
	}
		
	return( $ret );
}

function GLG_AddExternalMap( $G2_uid, $GL_uid )
{
	global	$_GLG_CONF;

    COM_errorLog( "GLG_AddExternalMap( $G2_uid, $GL_uid )" );
	
	require_once( $_GLG_CONF['G2_path'] . '/modules/core/classes/ExternalIdMap.class' );
	
	$res = ExternalIdMap::addMapEntry(
		array (	'externalId' => $GL_uid,
				'entityType' => 'GalleryUser',
				'entityId' => $G2_uid ) );
	
	if ( $res->isError() )
	{
		COM_errorLog( 'Failed to create an external map entry for GL uid ['.$GL_uid.'] and G2_uid ['.$G2_uid.'], entityType [GalleryUser].' );
		COM_errorLog( '   Here is the error message from G2:' );
		COM_errorLog( $res->getAsText() );
		
		return false;
	}
	
	return true;
}

function GLG_RemoveExternalMap( $G2_uid, $GL_uid )
{
	global	$_GLG_CONF;

    COM_errorLog( "GLG_RemoveExternalMap( $G2_uid, $GL_uid )" );
	
	$ret = GLG_G2_init();
	
	if ( $ret->isError() )
	{
		COM_errorLog( 'Failed to init() G2.' );
		COM_errorLog( '   Here is the error message from G2:' );
		COM_errorLog( $ret->getAsText() );
		
		return false;
	}

	require_once( $_GLG_CONF['G2_path'] . '/modules/core/classes/ExternalIdMap.class' );

	$res = ExternalIdMap::removeMapEntry(
		array (	'externalId' => $GL_uid,
				'entityType' => 'GalleryUser',
				'entityId' => $G2_uid ) );
	
	if ( $res->isError() )
	{
		COM_errorLog( 'Failed to remove an external map entry for GL uid ['.$GL_uid.'] and G2_uid ['.$G2_uid.'], entityType [GalleryUser].' );
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
	$myUID = $_USER['uid'];
	$sql = "SELECT uid FROM {$_TABLES['users']} WHERE uid > 1 AND uid != $myUID";
	$result = DB_query( $sql );
	
	while ( $A = DB_fetchArray( $result ) )
	{
		$errStr = GLG_CreateG2User( $A['uid'] );		
	}
}

function GL_RemoveGLUsersFromG2()
{
	global $_TABLES, $_USER;
	
	// Get a listing of all UIDs
	$myUID = $_USER['uid'];
	$sql = "SELECT uid FROM {$_TABLES['users']} WHERE uid > 1 AND uid != $myUID";
	$result = DB_query( $sql );
	
	while ( $A = DB_fetchArray( $result ) )
	{
		$errStr = GLG_DeleteG2User( $A['uid'] );		
	}
}

?>