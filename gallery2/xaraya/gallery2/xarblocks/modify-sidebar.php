<?php


/**
 * Modify function to the blocks admin
 * @param $blockinfo array containing title, content
 */
function gallery2_sidebarblock_modify($blockinfo)
{
  // get current content
  if (!is_array($blockinfo['content'])) {
    $vars = @unserialize($blockinfo['content']);
  } else {
    $vars = $blockinfo['content'];
  }

  $vars['blockid'] = $blockinfo['bid'];
  return $vars;
}

/**
 * update block settings
 */
function gallery2_imageblock_update($blockinfo)
{     
  return $blockinfo;
}

?>