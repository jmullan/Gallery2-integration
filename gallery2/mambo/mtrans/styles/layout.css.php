<?php
/*
 * Gallery - a web based photo album viewer and editor 
 * Copyright (C) 2000-2005 Bharat Mediratta
 *
 * This program is free software;you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation;either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY;without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program;if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * $Id$
 */

/* We use the base layout CSS and override anything that we want to change */

$cssFile = dirname(__FILE__) . '/../../../templates/layout.css.php';
$gmDate = gmdate("D, d M Y H:i:s", (filemtime(__FILE__) > filemtime($cssFile)) ? filemtime(__FILE__) : filemtime($cssFile)) . " GMT";
header("Content-type: text/css");
header("Last-Modified: " . $gmDate);
header("Date: " . $gmDate);
include_once($cssFile);

?>

.gbMenu ul ul {
	margin: 0 0 0.5em 1.0em;
}
