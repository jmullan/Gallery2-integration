<?php
/*
 * $RCSfile$
 *
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2005 Bharat Mediratta
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
/**
 * @version $Revision$ $Date$
 * @package MatrixTheme
 * @author Bharat Mediratta <bharat@menalto.com>
 * @author Ross Reyman
 */

/**
 * This theme (code named "Matrix") is based on the UI designed by Ross Reyman.
 *
 * @package MatrixTheme
 */
class MatrixTheme extends GalleryTheme {

    function MatrixTheme() {
	$this->GalleryTheme(dirname(__FILE__));
    }

    /**
     * @see GalleryTheme::style()
     */
    function style($params) {
	global $gallery;

	$platform = $gallery->getPlatform();
	$format = '<style type="text/css" media="all">@import url(%s);</style>';

	$urlGenerator =& $gallery->getUrlGenerator();
	$style[] = sprintf($format, $urlGenerator->generateUrl(array('href' => 'themes/matrix/styles/theme.css')));

	list ($view, $controller) = GalleryUtilities::getRequestVariables('view', 'controller');
	if (!empty($controller) || (!empty($view) && $view != 'core:ShowItem')) {
	    $style[] = sprintf($format, $urlGenerator->generateUrl(array('href' => 'themes/matrix/styles/layout.css.php')));
	}

	return join("\n", $style);
    }
}
?>
