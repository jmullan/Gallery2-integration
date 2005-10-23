<?php

// Load the xarGallery2Helper class
include_once(dirname(__FILE__) .'/../xargallery2helper.php');

/**
 * The standard gallery2 page
 * 
 * @param  there are no params, POST/GET/FILE variables are handled by G2
 * @return array of template variables
 */
function gallery2_user_main()
{     
    // Security Check
    if (!xarSecurityCheck('ReadGallery2', 0)) return;
    $data = array();

    // first check if the module has been configured
    if(!xarGallery2Helper::isConfigured()) {
	$data['g2modhtml'] = xarML('The module has not yet been configured.');
	$data['configured'] = 0;
	return $data;
    } else {
	$data['configured'] = 1;
    }
  
    // init G2
    if (!xarGallery2Helper::init(false, true, false)) {   
	$data['g2modhtml'] = 'G2 returned an error on the init call.';
	return $data;
    }

    // user interface: disable sidebar in G2 and get it as separate HTML to put it into a xaraya block
    if (xarModGetVar('gallery2', 'g2.sidebarInside') == 0) {
	GalleryCapabilities::set('showSidebarBlocks', false);
    }

    // Deactivate xaraya's output buffers to get the embedded progress bars and immediate views to work 
    $outputBuffers = array();
    while (($ob = ob_get_contents()) !== false) {
	@ob_end_clean();
	$outputBuffers[] = $ob;
    }
    
    // handle the G2 request
    $g2moddata = GalleryEmbed::handleRequest();
  
    // show error message if isDone is not defined
    if (!isset($g2moddata['isDone'])) {
      $data['g2modhtml'] = 'isDone is not defined, something very bad must have happened.';
      return $data;
    }
    // die if it was a binary data (image) request
    if ($g2moddata['isDone']) {
      exit; /* uploads module does this too */
    }

    /* Get xaraya's output buffers back in place (no immediate output) */
    foreach ($outputBuffers as $ob) {
	ob_start();
	echo $ob;
    }
    
    // put the body html from G2 into the xaraya template 
    $data['g2modhtml'] = isset($g2moddata['bodyHtml']) ? $g2moddata['bodyHtml'] : '';

    // get the page title, javascript and css links from the <head> html from G2
    $title = ''; $javascript = array();	$css = array();
 
    if (isset($g2moddata['headHtml'])) {
      list($title, $css, $javascript) = GalleryEmbed::parseHead($g2moddata['headHtml']);
    }
    
    /* set title */
    if(!empty($title)) {
      xarTplSetPageTitle(xarVarPrepForDisplay($title));
    } 
    
    /* Add G2 javascript to template */
    if (!empty($javascript)) {
      foreach ($javascript as $script) {
	  if(preg_match("|<script(?:\s[^>]*)?\ssrc=[\"\'](.+)[\"\'](?:\s[^>]*)?>.*</script>|Usi",
			 $script , $regs)) {
	      xarTplAddJavaScript('head', 'src', $regs[1]);
	  } else {
	      preg_match("|<script(?:\s[^>]*)?>(.+)</script>|Usi",
			 $script, $regs);
	      xarTplAddJavaScript('head', 'code', $regs[1]);
	  }
      }
    }

    /*
     * TODO: find xarAPI function to include HTML <head> data
     *       perhaps  xartpl_modifyheadercontent()
     */
    // xarTplAddStyleLink('gallery2', $styleName, $themeFolder='');
    // dirty hack:
    $cssfiles = array();
    if (!empty($css)) {
	/* Get the relative path from modules/gallery2/xarstyles/ to relativeG2Path/ css file */
	global $gallery;
	$urlGenerator =& $gallery->getUrlGenerator();
	$g2Url = $urlGenerator->getCurrentUrlDir(true);
	$relativeG2Path = xarModGetVar('gallery2','g2.relativeurl');
	
      foreach (array_reverse($css) as $style) {
	  /* TODO: Can only handle <link tag (not <style) due to xaraya */
	  if (preg_match('/<link.* href\s*=\s*[\'"](.+)[\'"].*/Usi', $style, $regs)) {
	      $url = $regs[1];
	      /* Replace absolute URL by relative url
	       * e.g. http://example.com/gallery2/themes/matrix/theme.css to
	       *      ../../../$relativeG2Path/themes/matrix/theme.css
	       * Use G2 UrlGenerator to replace strings since it was also used to generate
	       * the css url.
	       */
	      $path = str_replace($g2Url, '../../../' . $relativeG2Path, $url);
	      /* TODO: Can only add css links that end on .css due to xaraya */
	      if (preg_match('/\.css$/', $path)) {
		  $cssfiles[] = str_replace('.css', '', $path);
	      }
	  }
      }
    }
    $data['cssfiles'] = $cssfiles;
    
    // set the g2 sideBar (menu) html global, so that we can retrieve it,
    // when xaraya calls all the blocks for their html
    if (isset($g2moddata['sidebarBlocksHtml']) && !empty($g2moddata['sidebarBlocksHtml'])) {
      global $g2sidebarHtml;
      $g2sidebarHtml = $g2moddata['sidebarBlocksHtml'];
    }
    
    return $data;
} 


?>
