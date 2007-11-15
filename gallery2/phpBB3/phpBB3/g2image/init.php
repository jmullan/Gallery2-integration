<?php
/*
    Gallery 2 Image Chooser
    Version 3.0.2 - updated 01 OCT 2007
    Documentation: http://g2image.steffensenfamily.com/

    Author: Kirk Steffensen with inspiration, code snipets,
        and assistance as listed in CREDITS.HTML

    Released under the GPL version 2.
    A copy of the license is in the root folder of this plugin.

    See README.HTML for installation info.
    See CHANGELOG.HTML for a history of changes.

    Modified and adapted to phpbb3 by Scott Gregory - jettyrat@jettyfishing.com
    Updated 03 NOV 2007
    NOTE: We do not use config.php for the phpBB3 integration.
    All setup parameters and phpBB3 initialization are contained here.
*/

// Set the language for the main g2image popup window.
// There must be a corresponding xx.mo file in the g2image/langs/ directory.
// If there is not a corresponding xx.mo file, English will be used.

$g2ic_language = 'en';

// Change this for more/fewer images per page.

$g2ic_images_per_page = 15;

// This sets the default view.  If set to TRUE, titles, summaries, and
// descriptions will be displayed.  If set to FALSE, only the thumbnails will
// be displayed.

$g2ic_display_filenames = FALSE;

// This sets the default URL for the Custom URL option.

$g2ic_custom_url = 'http://';

// Change this to change the default "How to Insert" option.  Valid options are
// 'thumbnail_image', 'thumbnail_album', 'thumbnail_custom_url', 'thumbnail_only',
// 'link_image', 'link_album', 'drupal_g2_filter', 'thumbnail_lightbox',
// 'fullsize_image', and 'fullsize_only'.

$g2ic_default_action = 'thumbnail_image';

// Change this to change the default sort order.  Valid options are 'title_asc',
// 'title_desc', 'orig_time_desc' (origination time, newest first),
// 'orig_time_asc' (origination time, oldest first), 'mtime_desc' (modification
// time, newest first), and 'mtime_asc' (modification time, oldest first).

$g2ic_sortby = 'title_asc';

// ====( Initialize Variables )=================================
$g2ic_options = array();
$g2ic_options['current_page'] = 1;
$g2ic_options['images_per_page'] = $g2ic_images_per_page;
$g2ic_options['display_filenames'] = $g2ic_display_filenames;
$g2ic_options['custom_url'] = $g2ic_custom_url;
$g2ic_options['default_action'] = $g2ic_default_action;
$g2ic_options['sortby'] = $g2ic_sortby;

// Determine gettext locale
if (file_exists('./langs/' . $g2ic_language . '.mo')) {
	$locale = $g2ic_language;
}
else {
	$locale = 'en';
}

// gettext setup
require_once('gettext.inc');
T_setlocale(LC_ALL, $locale);

// Set the text domain as 'default'
T_bindtextdomain('default', 'langs');
T_bind_textdomain_codeset('default', 'UTF-8');
T_textdomain('default');

/*
// ====( Initialize phpBB3 embedded mode )=======================
*/
define('IN_PHPBB', true);
$phpbb_root_path = '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.'.$phpEx);

$user->session_begin();

require($phpbb_root_path . 'g2helper.inc');
$g2h = new g2helper($db);

if ($g2h->fetchPluginStatus('module', 'imageblock') == false) {
	echo T_('Either the imageblock module is not installed or it is not activated.');
	exit;
}

$g2h->init($user);

?>
