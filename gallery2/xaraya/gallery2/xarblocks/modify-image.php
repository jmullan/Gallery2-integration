<?php
/**
 * File: $Id$
 * 
 * G2 image block configuration
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage gallery2
 * @author Example module development team 
 */

/**
 * modify block settings
 */
function gallery2_imageblock_modify($blockinfo)
{ 
  // Get current content
  if (!is_array($blockinfo['content'])) {
    $vars = unserialize($blockinfo['content']);
  } else {
    $vars = $blockinfo['content'];
  }

  // Defaults
  if (empty($vars['ibtype'])) {
    $vars['ibtype'] = 0;
  } 
  if (empty($vars['ibshowtitle'])) {
    $vars['ibshowtitle'] = 0;
  }
  if (empty($vars['ibshowdate'])) {
    $vars['ibshowdate'] = 0;
  }
  if (empty($vars['ibshowviews'])) {
    $vars['ibshowviews'] = 0;
  }
  if (empty($vars['ibshowowner'])) {
    $vars['ibshowowner'] = 0;
  }

  if (empty($vars['ibheading'])) {
    $vars['ibheading'] = 0;
  }
  
  // Send content to template
  return array('ibtype' => $vars['ibtype'],
	       'ibshowtitle' => $vars['ibshowtitle'],
	       'ibshowdate' => $vars['ibshowdate'],
	       'ibshowviews' => $vars['ibshowviews'],
	       'ibshowowner' => $vars['ibshowowner'],
	       'ibheading' => $vars['ibheading'],
	       'blockid' => $blockinfo['bid']);
}

/**
 * update block settings
 */
function gallery2_imageblock_update($blockinfo)
{
    if (!xarVarFetch('ibtype', 'int:0:5', $vars['ibtype'], 0, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('ibshowtitle', 'checkbox', $vars['ibshowtitle'], 0, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('ibshowdate', 'checkbox', $vars['ibshowdate'], 0, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('ibshowviews', 'checkbox', $vars['ibshowviews'], 0, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('ibshowowner', 'checkbox', $vars['ibshowowner'], 0, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('ibheading', 'checkbox', $vars['ibheading'], 0, XARVAR_DONT_SET)) {return;}

    $blockinfo['content'] = $vars;

    return $blockinfo;
} 

?>
