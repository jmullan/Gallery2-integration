<?php
/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2004 Bharat Mediratta
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
 *
 * Gallery Component for Mambo Open Source CMS v4.5 or newer
 * Original author: Beckett Madden-Woods <beckett@beckettmw.com>
 *
 * $Id$
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

$database->setQuery("SELECT * FROM #__gallery2");
$param = $database->loadRowList();

/* extract params from the DB query */
$MOS_GALLERY2_PARAMS = array();
foreach ($param as $curr) {
    $MOS_GALLERY2_PARAMS[$curr[0]] = $curr[1];
}

/*if (!realpath($MOS_GALLERY2_PARAMS['path'])) {
    echo "Security Violation";
    exit;
} else { */
    if (! defined("MOS_GALLERY2_PARAMS_PATH")) {
        define ("MOS_GALLERY2_PARAMS_PATH",$MOS_GALLERY2_PARAMS['path']);
    }
    if (! defined("MOS_GALLERY2_PARAMS_RELATIVEG2PATH")) {
        define ("MOS_GALLERY2_PARAMS_RELATIVEG2PATH",$MOS_GALLERY2_PARAMS['relativeG2Path']);
    }
    if (! defined("MOS_GALLERY2_PARAMS_EMBEDURI")) {
        define ("MOS_GALLERY2_PARAMS_EMBEDURI",$MOS_GALLERY2_PARAMS['embedUri']);
    }
    if (! defined("MOS_GALLERY2_PARAMS_EMBEDPATH")) {
        define ("MOS_GALLERY2_PARAMS_EMBEDPATH",$MOS_GALLERY2_PARAMS['embedPath']);
    }
/*
}
*/

print '<table width="100%" cellpadding="4" cellspacing="0" border="0" align="center" class="contentpane">' . "\n<tr><td>\n";

    require_once(MOS_GALLERY2_PARAMS_PATH . 'embed.php');
           $ret = GalleryEmbed::init(array(
           'embedUri' => MOS_GALLERY2_PARAMS_EMBEDURI,
           'embedPath' => MOS_GALLERY2_PARAMS_EMBEDPATH,
           'relativeG2Path' => MOS_GALLERY2_PARAMS_RELATIVEG2PATH,
           'loginRedirect' => 'index.php',
           'activeUserId' => '0'));

    GalleryCapabilities::set('showSidebar', false);

    // handle the G2 request
    $g2moddata = GalleryEmbed::handleRequest();

    // show error message if isDone is not defined
    if (!isset($g2moddata['isDone']))
    {
      echo 'isDone is not defined, something very bad must have happened.';
      exit;
    }

    // die if it was a binary data (image) request
    if ($g2moddata['isDone'])
    {
      exit; /* uploads module does this too */
    }

     if ($ret->isError())
     {
       echo $ret->getAsHtml();
     }

     echo $g2moddata['sidebarHtml'];




print "</td></tr>\n</table>\n";

?>
