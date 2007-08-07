<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin  [v.2.0]                                |
// +---------------------------------------------------------------------------+
// | admin/index.php                                                               |
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
else if ( $action == 'mini_ss' )
{
	$title = '';
    if (isset ($_POST['title'])) {
        $title = $_POST['title'];
    }
	$on_left = '';
    if (isset ($_POST['on_left'])) {
        $on_left = $_POST['on_left'];
    }
	$tid = '';
    if (isset ($_POST['tid'])) {
        $tid = $_POST['tid'];
    }
	$width = '';
    if (isset ($_POST['width'])) {
        $width = $_POST['width'];
    }
	$height = '';
    if (isset ($_POST['height'])) {
        $height = $_POST['height'];
    }
	$args = '';
    if (isset ($_POST['modex']) && !empty($_POST['modex'])) {
		$args .= 'mode=dynamic';
    }
    if (isset ($_POST['g2_itemId']) && empty($_POST['modex'])) {
        $id = $_POST['g2_itemId'];
		$args .= 'g2_itemId='.$_POST['g2_itemId'];
    }
	if (isset ($_POST['g2_view']) && !empty($_POST['modex'])) {
        $id = $_POST['g2_view'];
		$args .= '&g2_view='.$_POST['g2_view'];
    }
    if (isset ($_POST['shuffle']) && $_POST['shuffle'] != "false") {
		$args .= '&shuffle='.$_POST['shuffle'];
    }
    if (isset ($_POST['showDropShadow']) && $_POST['showDropShadow'] != "false") {
		$args .= '&showDropShadow='.$_POST['showDropShadow'];
    }
    if (isset ($_POST['showTitle']) && $_POST['showTitle'] != "false") {
		$args .= '&showTitle='.$_POST['showTitle'];
    }
    if (isset ($_POST['delay'])) {
		$args .= '&delay='.$_POST['delay'];
    }
    if (isset ($_POST['titleColor']) && $_POST['showTitle'] != "false") {
		$args .= '&titleColor='.$_POST['titleColor'];
    }
    if (isset ($_POST['titleBgColor']) && $_POST['showTitle'] != "false") {
		$args .= '&titleBgColor='.$_POST['titleBgColor'];
    }
	if(!empty($title) && (!empty($_POST['g2_itemId']) || !empty($_POST['g2_view'])))
	{
		$display .= G2B_createBlock( $title, $id, $on_left, $tid, $width, $height, $args );
//		$display .= COM_startBlock ('Field Array', '', COM_getBlockTemplate ('_msg_block', 'header'));
//		$display .= $title.'-'.$id.'-'.$on_left.'-'.$tid.'-'.$width.'-'.$height.'-'.$args;
//		$display .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
	}else{
		$display .= COM_startBlock ($LANG_G2B['error'], '', COM_getBlockTemplate ('_msg_block', 'header'));
		$display .= $LANG_G2B['error_empty'];
		$display .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
	}	
	
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

// mini slideshow
$display .= '<script type="text/javascript">	
function toggle() {
    var theForm = document.mini.modex;
	var val = theForm.value;
    if(val == 0){
		document.getElementById(\'box2\').style.display = \'block\';
		document.getElementById(\'box1\').style.display = \'none\';
	}else{
		document.getElementById(\'box1\').style.display = \'block\';
		document.getElementById(\'box2\').style.display = \'none\';
	}
}
window.onload = function(e) {
	toggle();}
</script>';
$topic_options = COM_topicList('tid,topic', $A['tid'], 1, true);
$display .= '<div style="margin: 8px; padding: 4px; border: solid 1px #CCC;">';
$display .= '<span style="font-weight: bold;">The <a href="http://www.flashyourweb.com/dokuwiki/doku.php?id=xmlmini" target="_blank">XML MiniSlideShow</a> can be put in a block.</span>';
$display .= '<div style="margin: 8px; margin-bottom: 0px; padding: 4px; padding-bottom: 0px; text-align: left;">';

$display .= '            <form name=\'mini\' method=\'POST\' action=\''.$_CONF['site_admin_url'].'/plugins/G2Bridge/index.php\'>
                <table width="100%" border="0" cellspacing="0" cellpadding="3">
                    <tr>
                        <td width="160" class="alignright">Title:</td>
						<td>
						    <input type="text" size="48" name="title" value=""/>
						</td>
					</tr>
					<tr>
                        <td width="160" class="alignright">Side:</td>
						<td>
                            <select name="on_left">
                                <option value="1" >Left</option>
                                <option value="0" selected="selected">Right</option>
                            </select>
                        </td>
					</tr>
					<tr>
                        <td width="160" class="alignright">Topic:</td>
                        <td>
                            <select name="tid">
                                <option value="all" selected="selected">All</option>
                                <option value="homeonly" >Home Only</option>
                                '.$topic_options.'
                            </select>
                        </td>
                    </tr>
				</table>';
G2B_G2_init();
		//check to see if useralbum module is available
		list ($ret, $modules) = GalleryCoreApi::fetchPluginStatus('module');
		if ($ret) 
		{
			print "checking module:" . $ret->getAsHtml();
		}
		if($modules['dynamicalbum']['active'] && $modules['dynamicalbum']['available'] == 1){
            $display .= '			
			<table width="100%" border="0" cellspacing="0" cellpadding="3">
				<tr>
					<td width="160" class="alignright">Mode:</td>
					<td>
						<select name="modex" onChange="toggle();">
							<option selected="selected" value="0">Static</option>
							<option value="1">Dynamic</option>
						</select>
					</td>
				</tr>
			</table>
		<div id="box1" style="width:100%; display:none;">
			<table width="100%" border="0" cellspacing="0" cellpadding="3">
				<tr>
					<td width="160" class="alignright">Dynamic Album:</td>
					<td>
						<select name="g2_view">
							<option value="dynamicalbum.UpdatesAlbum" >UpdatesAlbum</option>
							<option value="dynamicalbum.PopularAlbum" >PopularAlbum</option>
							<option value="dynamicalbum.RandomAlbum" >RandomAlbum</option>
							<option value="" selected="selected">Select</option>
						</select>
					</td>
				</tr>
			</table>
		</div>';		
		}
$display .= '		<div id="box2" style="width:100%; display:block;">
			<table width="100%" border="0" cellspacing="0" cellpadding="3">
				<tr>
					<td width="160" class="alignright">Album Id</td>
					<td>
						<input type="text" size="4" name="g2_itemId" value=""/><span style="font-size: 10px;"> g2_itemId of the album you want to display.</span>
					</td>
				</tr>
			</table>
		</div>';
$display .= '			<table width="100%" border="0" cellspacing="0" cellpadding="3">
				<tr>
					<td width="160" class="alignright">Width:</td>
					<td><input type="text" size="4" name="width" value="160"/></td>
				</tr>
				<tr>
					<td width="160" class="alignright">Height:</td>
					<td><input type="text" size="4" name="height" value="160"/></td>
				</tr>
			</table>
			<span style="font-size: 12px; font-weight: bold;">Extra arguments to enhance the look and functionality.</span>
			<table width="100%" border="0" cellspacing="0" cellpadding="3">
				<tr>
                    <td width="160" class="alignright">Shuffle:</td>
					<td>
						<select name="shuffle">
                            <option value="true" >True</option>
                            <option value="false" selected="selected">False</option>
                        </select><span style="font-size: 10px;"> Default: False</span>
					</td>
				</tr>
				<tr>
                    <td width="160" class="alignright">showDropShadow:</td>
					<td>
						<select name="showDropShadow">
                            <option value="true" >True</option>
                            <option value="false" selected="selected">False</option>
                        </select><span style="font-size: 10px;"> Default: False</span>
					</td>
				</tr>
				<tr>
                    <td width="160" class="alignright">showTitle:</td>
					<td>
						<select name="showTitle">
                            <option value="top" >Top</option>
                            <option value="bottom" >Bottom</option>
                            <option value="false" selected="selected">False</option>
                        </select><span style="font-size: 10px;"> Default: False</span>
					</td>
				</tr>
                <tr>
                    <td width="160" class="alignright">Delay:</td>
					<td>
						<input type="text" size="2" name="delay" value="3"/><span style="font-size: 10px;"> Default: 3</span>
					</td>
				</tr>
				<tr>
                    <td width="160" class="alignright">titleColor:</td>
					<td><input type="text" size="6" name="titleColor" value="FFFFFF"/><span style="font-size: 10px;"> Default: FFFFFF(White)</span></td>
				</tr>
				<tr>
                    <td width="160" class="alignright">titleBgColor:</td>
					<td><input type="text" size="6" name="titleBgColor" value="333333"/><span style="font-size: 10px;"> Default: 333333(Charcoal)</span></td>
				</tr>
			</table>
			<table width="100%" border="0" cellspacing="0" cellpadding="3">
				<tr>
					<td><span style="font-size: 10px;">**After your block is created, it can be edited via the regular admin block editor to change/add to these parameters.<br />More parameter info can be found <a href="http://www.flashyourweb.com/dokuwiki/doku.php?id=xmlmini" target="_blank">here</a>.
						You can also copy the block\'s code to embed the mini in any webpage.</td>
				</tr>
				<tr>
					<td>
                        <input type="submit" value="Save" name="mode"/>
                        <input type="hidden" value="mini_ss" name="action"/>
                    </td>
				</tr>
			</table>
        </form>';
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
$header = '<img src="' . $img_url . '" width=48 height=28 alt="G2Bridge.png" align=middle>&nbsp;&nbsp;' . $LANG_G2B['admin'] . ' [v' . plugin_chkVersion_G2Bridge() .']';
$readme_url = $_CONF['site_admin_url'] . '/plugins/G2Bridge/readme.html#config';

$screen = '';
$screen .= COM_siteHeader();
$screen .= COM_startBlock( $header, $readme_url );
    $msg = 0;
    if (isset ($_POST['msg'])) {
        $msg = COM_applyFilter ($_POST['msg'], true);
    } else if (isset ($_GET['msg'])) {
        $msg = COM_applyFilter ($_GET['msg'], true);
    }
    if ($msg > 0) {
        $screen .= COM_showMessage ($msg);
    }
echo $screen;

echo $display;

echo COM_endBlock();
echo COM_siteFooter( true );
?>
