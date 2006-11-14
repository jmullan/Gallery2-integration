<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin formerly GL_Gallery2                                      |
// +---------------------------------------------------------------------------+
// | index.php                                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2005 Andy Maloney [asmaloney@users.sf.net]                  |
// | Adapted for Gallery 2.1 by Wayne Patterson [suprsidr@gmail.com]           |
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


// Only let admin users access this page
if ( (!SEC_inGroup('Root')) )
{
    // Someone is trying to illegally access this page
    COM_errorLog( "Someone has tried to illegally access the G2Bridge admin page.  "
    	. "User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: {$_SERVER['REMOTE_ADDR']}", 1 );
    $display = COM_siteHeader();
    $display .= COM_startBlock($LANG_G2B['access_denied']);
    $display .= $LANG_G2B['access_denied_msg'];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}

$display = '';

$action = (isset( $_POST['action'] )) ? COM_applyFilter( $_POST['action'] ) : '';
$just_synced = false;

if ( $action == 'save_prefs' )
{
	$gl_block_enabled = (isset( $_POST['gl_block'] ) && ($_POST['gl_block'] == 'on')) ? 1 : 0;
	$value = $gl_block_enabled ? 'geeklog' : 'gallery2';
	
	DB_change( $_TABLES['G2B_vars'], 'value', $value, 'name', 'control_block' );
	DB_change( $_TABLES['blocks'], 'is_enabled', $gl_block_enabled, 'name', 'G2B_block' );
}
else if ( $action == 'sync_users' )
{
	GL_MapUsersToG2();
	
	$just_synced = true;
}

// Sidebar
$display .= '<div style="margin: 8px; padding: 4px; border: solid 1px #CCC;">';
$display .= 'The Gallery2 sidebar can either be in a geeklog block, which defaults to displaying on the left, or it can be attached to your gallery when you view it [as when you view your gallery standalone].';
$display .= '<div style="margin: 8px; margin-bottom: 0px; padding: 4px; padding-bottom: 0px;">';

$checked = '';
if ( DB_getItem( $_TABLES['G2B_vars'], 'value', "name = 'control_block'" ) != 'gallery2' )
	$checked = ' checked=1';

$display .= "<form method='POST' action='{$_CONF['site_admin_url']}/plugins/G2Bridge/index.php'>
			<label><input type='checkbox' name='gl_block' $checked>Put Gallery2 sidebar in a geeklog block</label>
			<br><br><input type='submit' value='Save' name='Save'>
			<input type='hidden' value='save_prefs' name='action'>
			</form>";
$display .= '</div>';
$display .= '</div>';

// User sync
$display .= '<div style="margin: 8px; padding: 4px; border: solid 1px #CCC;">';
$display .= 'G2Bridge keeps users synced between Geeklog and Gallery2.  They are updated when you install this plugin, and whenever a user is added or updated in Geeklog.  <p>If they ever get out of sync for some reason, you can make sure all your users are mapped into Gallery2 using this button:';
$display .= '<div style="margin: 8px; margin-bottom: 0px; padding: 4px; padding-bottom: 0px;">';
if ( $just_synced )
{
	$display .= '[Users synced]';
}
else
{
	$display .= "<form method='POST' action='{$_CONF['site_admin_url']}/plugins/G2Bridge/index.php'>
			<input type='submit' value='Sync Users'>
			<input type='hidden' value='sync_users' name='action'>
			</form>";
}
$display .= '</div>';
$display .= '</div>';

$display .= '<div style="margin: 8px; padding: 4px; border: solid 1px #CCC;">';
$display .= 'Here are some resources:';
$display .= '<ul>';
$display .= '<li><a href="' . $_CONF['site_admin_url'] . '/plugins/G2Bridge/readme.html">G2Bridge readme</a>';
$display .= '<li><a href="' . $_CONF['site_url'] . '/' . $_G2B_CONF['public_dir'] . '/index.php?g2_view=core.SiteAdmin">Gallery2 config page</a></li>';
$display .= '<li><a href="http://codex.gallery2.org/index.php/Main_Page">Gallery2 documentation</a> [@gallery2.org]</li>';
$display .= '<li>Explanation of setting up <a href="http://codex.gallery2.org/index.php/Gallery2:Embedded_Rewrites">URL rewriting for Gallery2</a> [@gallery2.org]</li>';
$display .= '</ul>';
$display .= '</div>';

/**
* Main 
*/

$img_url = $_CONF['site_url'] . '/' . $_G2B_CONF['public_dir'] . '/images/G2Bridge.png';
$header = '<img src="' . $img_url . '" width=48 height=28 alt="G2Bridge pic" align=middle>&nbsp;&nbsp;' . $LANG_G2B['admin'] . ' [v' . plugin_chkVersion_G2Bridge() .']';
$readme_url = $_CONF['site_admin_url'] . '/plugins/G2Bridge/readme.html#config';

echo COM_siteHeader();
echo COM_startBlock( $header, $readme_url );

echo $display;

echo COM_endBlock();
echo COM_siteFooter( true );
?>
