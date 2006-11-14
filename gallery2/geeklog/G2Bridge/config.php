<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin formerly GL_Gallery2                                      |
// +---------------------------------------------------------------------------+
// | config.php                                                                |
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

global $_G2B_CONF, $_TABLES, $_CONF;


/* Here's where you can set the directory name for the plugin in the public_html directory.

	If you want to access this plugin at http://your.fancy.site.com/my_gallery instead of at
	http://your.fancy.site.com/G2Bridge, you should set this to 'my_gallery' and rename the
	G2Bridge directory in your $_CONF['path_html'] directory to 'my_gallery'.
	
	Note: If you change this after you've set up rewriting in Gallery, you'll have to set it up again.
*/
$_G2B_CONF['public_dir'] = 'G2Bridge';

/* I think with Gallery 2.1 we have finally done away with the path confusion. I have set these to the default with gallery2 in your public_html directory.
    You should only need to change these IF gallery2 is NOT in your public_html directory.
*/

$_G2B_CONF['G2_path'] = $_CONF['path_html'].'/gallery2'; // full system path to your gallery2 install no trailing /

$_G2B_CONF['embedUri'] = $_CONF['site_url'].'/'.$_G2B_CONF['public_dir'].'/index.php?';   // full url to your G2Bridge index.php with trailing ? 

$_G2B_CONF['g2Uri'] = $_CONF['site_url'].'/gallery2/';  // full url to you gallery2 with trailing /

/* What fields do we show in the random photo block?
	Can include title|date|views|owner|heading|fullSize or it can be 'none'.
	Note that 'heading' and 'fullSize' probably don't make sense here.
*/

$_G2B_CONF['random_photo_fields'] = 'title|date|views';


// Allow anonymous users to see the random photo block
$_G2B_CONF['allow_anon_access_random_photo'] = true;

// Allow anonynmous users to access the gallery
$_G2B_CONF['allow_anon_access_gallery'] = true;

// If you are using the G2 User Albums Module, set to true to display the 'My Gallery' link in the user menu. Default is false with a simple Gallery2 link.
$_G2B_CONF['user_albums'] = false;

// If you have a custom login page set it here. 
$_G2B_CONF['login_redirect'] = $_CONF['site_url'].'/users.php';

// Here you can choose to display GL leftblocks on your gallery pages. leave '' blank for yes show the blocks or 'none' don't show the blocks.
$_G2B_CONF['show_leftblocks'] = '';

// Here you can choose to display GL rightblocks on your gallery pages. leave 'true' for yes show the blocks or '' blank don't show the blocks.
$_G2B_CONF['show_rightblocks'] = 'true';

//---------------------------
// DO NOT CHANGE THE STUFF BELOW UNLESS YOU KNOW WHAT YOU ARE DOING
//---------------------------

$_G2B_table_prefix = $_DB_table_prefix . 'G2B_';

$_TABLES['G2B_vars']           = $_G2B_table_prefix . 'vars';

?>
