<?php

/**
 * init func
 */
function gallery2_sidebarblock_init()
{
  return array();
}

/**
 * Block info array
 */
function gallery2_sidebarblock_info()
{
  return array('text_type' => 'sidebar',
	       'text_type_long' => 'Gallery2 Sidebar',
	       'module' => 'gallery2',
	       'allow_multiple' => false,
	       'form_content' => false,
	       'form_refresh' => false,
	       'show_preview' => false);
}

/**
 * Display func
 * @param $blockinfo array containing title, content
 */
function gallery2_sidebarblock_display($blockinfo)
{
  // security check
  if (!xarSecurityCheck('ReadGallery2', 1, 'Block', $blockinfo['title'])) {return;}

  global $g2sidebarHtml;
  if (!empty($g2sidebarHtml)) {
    $blockinfo['content'] = $g2sidebarHtml;
  } else {
    return null;
  }
  return $blockinfo;
}

?>


