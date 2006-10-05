<?php

/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2006 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
/**
 * Gallery 2 integration for phpBB3.
 * @version $Revision$ $Date$
 * @author Scott Gregory <jettyrat@jettyfishing.com>
 */

define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.'.$phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

require($phpbb_root_path . 'g2helper.inc');
$g2h = new g2helper($db);
$g2h->init($user);

GalleryCapabilities::set('showSidebarBlocks', true);
$g2data = GalleryEmbed::handleRequest(); 
if ($g2data['isDone']) {
	exit;
}
elseif (isset($g2data['headHtml'])) {
	list($page_title, $css, $javascript) = GalleryEmbed::parseHead($g2data['headHtml']);
}
$css = ($css) ? implode("\n", $css) . "\n" : '';
$javascript = ($javascript) ? implode("\n", $javascript) . "\n" : '';
$bodyHtml = ($g2data['bodyHtml']) ? $g2data['bodyHtml'] : '';

$template->assign_vars(array(
	'GALLERY2_BODY' => $bodyHtml,
	'GALLERY2_CSS' => $css,
	'GALLERY2_JAVASCRIPT' => $javascript)
);

// Output page
page_header($page_title);

$template->set_filenames(array('body' => 'gallery2.html'));

page_footer();

?>