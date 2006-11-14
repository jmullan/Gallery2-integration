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

require_once( '../lib-common.php' );
require_once( $_G2B_CONF['G2_embed_path'] );

if ( empty( $_USER['username'] ) && !$_G2B_CONF['allow_anon_access_gallery'] )
{
    $display = COM_siteHeader( '' );
    $display .= COM_startBlock ($LANG_LOGIN[1], '',
                                COM_getBlockTemplate ('_msg_block', 'header'));
                                
    $login = new Template($_CONF['path_layout'] . 'submit');
    $login->set_file (array ('login'=>'submitloginrequired.thtml'));
    $login->set_var ('login_message', $LANG_LOGIN[2]);
    $login->set_var ('site_url', $_CONF['site_url']);
    $login->set_var ('lang_login', $LANG_LOGIN[3]);
    $login->set_var ('lang_newuser', $LANG_LOGIN[4]);
    $login->parse ('output', 'login');
    $display .= $login->finish ($login->get_var('output'));
    
    $display .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
    $display .= COM_siteFooter();
    echo $display;
    
    exit;
}

$ret = G2B_G2_init();
	   
if ( $ret )
{
	if ( $ret->getErrorCode() & ERROR_MISSING_OBJECT )
	{
		$errStr = G2B_CreateG2User( G2B_GetG2UserFromGL() );
		
		if ( $errStr )
		{
			echo $errStr;
			
			return( false );
		}

		// Full G2 reinit with the new created user
		$ret = G2B_G2_init( true );

		if ( $ret )
			$display = 'Reinit failed: ' . $ret->getAsHtml();
	}
	else
	{
		$display = 'G2 returned an error: <br>' . $ret->getAsHtml();
	}
	
	if ( $display )
	{
		$display = COM_siteHeader() . $display;
		$display .= COM_siteFooter();
		echo $display;

		exit;
	}
}

$g2data = GalleryEmbed::handleRequest();

if ( $g2data['isDone'] )
	exit;

GalleryEmbed::done();
  
$display = COM_siteHeader($_G2B_CONF['show_leftblocks']);
$display .= $g2data['bodyHtml'];
$display .= COM_siteFooter($_G2B_CONF['show_rightblocks']);

echo $display;

?>