<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin  [v.2.0]                                |
// +---------------------------------------------------------------------------+
// | admin/install.php                                                                |
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

require_once('../../../lib-common.php');
require_once( $_CONF['path'] . 'plugins/G2Bridge/functions.inc' );


// Only let Root users access this page
if ( !SEC_inGroup( 'Root' ) )
{
    // Someone is trying to illegally access this page
    COM_errorLog( "Someone has tried to illegally access the G2Bridge install page.  "
    	. "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: {$_SERVER['REMOTE_ADDR']}", 1 );

    echo COM_siteHeader();
    echo COM_startBlock( $LANG_G2B['access_denied'] );
    echo $LANG_G2B['access_denied_msg'];
    echo COM_endBlock();
    echo COM_siteFooter( true );
    exit;
}

$pi_name = 'G2Bridge';
$pi_version = plugin_chkVersion_G2Bridge();
$gl_version = '1.4.1';
$pi_url = 'http://www.flashyourweb.com';

function G2B_create_group( $name, $description )
{
	global $_TABLES;

    $group_id = DB_getItem( $_TABLES['groups'], 'grp_id ', "grp_name = '{$name}'" );
    
    if ( $group_id == '' )
    {
		COM_errorLog( 'Creating '.$name.' group', 1 );
		
		DB_query( "INSERT INTO {$_TABLES['groups']} (grp_name, grp_descr)
					VALUES ('{$name}', '{$description}')", 1 );
		
		if ( DB_error() )
			return false;
		
		$result = DB_query( "SELECT LAST_INSERT_ID() AS group_id" );
		
		if ( DB_error() )
			return false;

		$row = mysql_fetch_assoc( $result );
		
		$group_id = $row['group_id'];
    }
	else
	{
		DB_query( "UPDATE {$_TABLES['groups']} SET grp_gl_core = 0 WHERE grp_id = $group_id", 1 );
	}
	
 	COM_errorLog( " {$name} group ID is $group_id", 1 );

	return( $group_id );
}

function G2B_create_feature( $name, $description, $admin_group_id )
{
    global $_TABLES;
    
	$feat_id = DB_getItem( $_TABLES['features'], 'ft_id ', "ft_name = '$name'" );

	if ( $feat_id == '' )
	{
		COM_errorLog( "Adding $name feature", 1 );
	
		DB_query( "INSERT INTO {$_TABLES['features']} (ft_name, ft_descr) 
					VALUES ('$name','$description')", 1 );
					
		if ( DB_error() )
		{
			COM_errorLog( "Failure adding $name feature", 1 );

			return false;
		}
				
		$result = DB_query( "SELECT LAST_INSERT_ID() AS feat_id" );
		
		if ( DB_error() )
			return false;

		$row = mysql_fetch_assoc( $result );
		
		$feat_id = $row['feat_id'];
	}
	else
	{
		DB_query( "UPDATE {$_TABLES['features']} SET ft_gl_core = 0 WHERE ft_id = $feat_id", 1 );
	}
   
	COM_errorLog( "Feature '$name' has ID $feat_id", 1 );
   
	if ( $admin_group_id != '' )
	{
		COM_errorLog( "Adding $name feature to admin group [$admin_group_id]", 1 );
		
		DB_query( "INSERT IGNORE INTO {$_TABLES['access']} (acc_ft_id, acc_grp_id)
			VALUES ($feat_id, $admin_group_id)", 1 );
		
		if ( DB_error() )
		{
			COM_errorLog( "Failure adding $feature feature to admin group", 1 );
		
			return '';
		}
	}
	
	return $feat_id;
}

function G2B_add_feature_to_group( $feature_id, $group_id )
{
    global $_TABLES;

	COM_errorLog( "Adding feature [$feature_id] to group [$group_id]", 1 );
	
	DB_query( "INSERT IGNORE INTO {$_TABLES['access']} (acc_ft_id, acc_grp_id)
		VALUES ($feature_id, $group_id)", 1 );
	
	if ( DB_error() )
	{
		COM_errorLog( "Failure adding feature [$feature_id] to group [$group_id]", 1 );
	
		return false;
	}
	
	return true;
}

function G2B_addblock( $name, $title, $function_name, $group_id, $enabled = 1, $on_left = 0 )
{
	global $_TABLES;

	// add the block
	$block_id = DB_getItem( $_TABLES['blocks'], 'bid ', "phpblockfn = '{$function_name}'" );
	
	COM_errorLog( "Adding block ($name, $title, $function_name, $group_id) : [$block_id]", 1 );
	
    if ( $block_id == '' )
	{
		$block_title = addslashes( $title );
	
		$sql = "INSERT INTO {$_TABLES['blocks']}
			( is_enabled, name, type, title, blockorder, onleft, phpblockfn, group_id, owner_id )
			VALUES( {$enabled}, '{$name}', 'phpblock', '{$block_title}', 10, {$on_left}, '{$function_name}', {$group_id}, 2 )
			";

		DB_query( $sql, 1 );
		
		if ( DB_error() )
			return false;
    }
	else
	{
		DB_query( "UPDATE {$_TABLES['blocks']} SET group_id = {$group_id} WHERE bid = $block_id LIMIT 1", 1 );
	}
	
	return true;
}

function G2B_createDatabaseStructures()
{
    global $_CONF, $_DB, $_TABLES;

    $_DB->setDisplayError( true );

	require_once( $_CONF['path'] . 'plugins/G2Bridge/sql/G2Bridge.php');

	// build tables
	for ( $i = 1; $i <= count( $_SQL ); $i++ )
	{
		DB_query( current( $_SQL ) );
		next( $_SQL );
	}

	// insert data
    for ( $i = 1; $i <= count( $_DATA ); $i++ )
    {
        DB_query( current( $_DATA ) );
        next( $_DATA );
    }
}

function plugin_install_G2Bridge()
{
    global $pi_version, $gl_version, $pi_url, $_TABLES, $_CONF, $_USER, $_G2B_CONF, $LANG_G2B_block, $LANG_G2B_rand_photo;

    COM_errorLog( 'Installing the G2Bridge plugin', 1 );

	$_FEATURE = array();
	$_FEATURE['G2Bridge.admin'] = "G2Bridge Admin";

    G2B_createDatabaseStructures();
    
    // Create the plugin admin security group
    $group_id['G2Bridge Admin'] = G2B_create_group( 'G2Bridge Admin', 'Users in this group can administer the G2Bridge plugin' );

	$feature_id['G2Bridge.admin'] = G2B_create_feature( 'G2Bridge.admin', 'Administer the G2Bridge plugin', $admin_group_id );

	G2B_add_feature_to_group( $feature_id['G2Bridge.admin'], $group_id['G2Bridge Admin'] );
 
 	// Add the random photo block
 	$success = G2B_addblock( 'G2B_rand_photo', $LANG_G2B_rand_photo['title'], 'phpblock_G2B_rand_photo', 2 );
	
	if ( !$success )
		return false ;
		
 	// Add the block which contains the sidebars
 	$success = G2B_addblock( 'G2B_block', $LANG_G2B_block['title'], 'phpblock_G2B_block', 2, 0, 1 );
	
	if ( !$success )
		return false ;
		
    // Now give Root users access to the admin group NOTE: Root group should always be 1
    COM_errorLog( "Giving all users in Root group access to G2Bridge admin group", 1 );
    
    DB_query( "INSERT IGNORE INTO {$_TABLES['group_assignments']} VALUES ({$group_id['G2Bridge Admin']}, NULL, 1)" );
    
    if ( DB_error() )
        return false;

    // Register the plugin with Geeklog
    COM_errorLog( "Registering G2Bridge plugin with Geeklog", 1 );
    
    DB_query( "DELETE FROM {$_TABLES['plugins']} WHERE pi_name = 'G2Bridge'");
    DB_query( "INSERT INTO {$_TABLES['plugins']} (pi_name, pi_version, pi_gl_version, pi_homepage, pi_enabled)
				VALUES ('G2Bridge', '$pi_version', '$gl_version', '$pi_url', 1)");

    if ( DB_error() )
        return false;

    COM_errorLog( "Mapping users from GL into G2", 1 );

	require_once( $_G2B_CONF['G2_embed_path'] );

	// now mapp users to G2	
	$ret = GalleryEmbed::init( array(
		   'embedUri' => $_G2B_CONF['embedUri'],
		   'g2Uri' => $_G2B_CONF['g2Uri'],
		   'loginRedirect' => $_G2B_CONF['login_redirect'],
		   'activeUserId' => '') );

	if ( $ret )
	{
		COM_errorLog( 'Failed to init() G2.' );
		COM_errorLog( '   Here is the error message from G2:' );
		COM_errorLog( $ret->getAsText() );
		
		return( false );
	}

	GL_MapUsersToG2();
	// now add this user to the admin group of the G2 install		
	G2B_addUserToGroup( $_USER['uid'] );
	
    COM_errorLog( "Succesfully installed the G2Bridge Plugin!", 1 );
    return true;
}

/* 
* Main Function
*/
$display = COM_siteHeader();
$display .= COM_startBlock( $LANG_G2B['install_header'] );

if ( $_REQUEST['action'] == 'install' )
{
    if ( plugin_install_G2Bridge() )
    {
    	$img_url = $_CONF['site_url'] . '/' . $_G2B_CONF['public_dir'] . '/images/G2Bridge.png';
        $blockManager = $_CONF['site_admin_url'] . '/block.php';
        $admin_url = $_CONF['site_admin_url'] . '/plugins/G2Bridge/index.php';
		$readme_url = $_CONF['site_admin_url'] . '/plugins/G2Bridge/readme.html';
      	$gallery_url = $_CONF['site_url'] . '/' . $_G2B_CONF['public_dir'] . '/';
      
        $display .= "<img align=left src=\"$img_url\" alt='G2Bridge Icon' width=50 height=31>";
        $display .= '<p>I have created the necessary database entries for G2Bridge. The current Geeklog user has been mapped to the G2 admin user.';
        $display .= "<p>I also created two blocks to display Gallery2 data: a random photo block and a Gallery2 control block.  The random photo block is enabled by default, the control block is currently disabled.  See the <a href=\"{$admin_url}\">admin page</a> for more about the control block.  <b>Do not remove these blocks - let this plugin manage them.</b>";

        $display .= "<p>The <a href=\"{$admin_url}\">admin page</a> also has links to resources you may find useful.";
        $display .= "<p>If you haven't already configured the plugin, check out the <a href=\"{$readme_url}#config\">configuration section</a> of the README page.";
		
		$display .= "<p>If you would like to support development of this plugin, there are some suggestions on the  
			<a href=\"{$readme_url}#you\">README page</a>.";
    }
    else
    {
        $display .= 'For some reason, installation failed.  Please check your error logs.';
        plugin_uninstall_G2Bridge();
    }
}

$display .= COM_endBlock();
$display .= COM_siteFooter(true);

echo $display;
?>
