<?php


// don't load the xarGallery2Helper class, too much code to parse (performance)

/**
 * The standard gallery2 page
 * 
 * @param  there are no params, POST/GET/FILE variables are handled by G2
 * @return array of template variables
 */
function gallery2_user_main()
{     
    // Security Check
    if (!xarSecurityCheck('ReadGallery2')) return;
    $data = array();

    if (!xarModGetVar('gallery2','configured')) {
      $data['g2modhtml'] = xarML('The module has not yet been configured.');
      return $data;
    }
    require_once(xarModGetVar('gallery2','g2.includepath') . 'embed.php');
    
    // if anonymous user, set g2 activeUser to null
    // if language code = default, set it to null for g2
    // the language can only be different from default, if the user 
    // uses a different language for this session only
    $xarLangCode = xarMLSGetCurrentLocale();
    $uid = xarUserGetVar('uid'); $g2LangCode = null;
    if ($uid == _XAR_ID_UNREGISTERED) {
      $xarDefaultLangCode = xarConfigGetVar('Site.MLS.DefaultLocale'); 
      if ($xarDefaultLangCode == $xarLangCode) {
	$xarLangCode = null;
      }
      $uid = '';
    } else { // non anonymous, registered user
      $xarDefaultLangCode = xarUserGetVar('locale');
      if ($xarDefaultLangCode == $xarLangCode) {
	$xarLangCode == null;
      }
    }
    // translate language code to G2 langCode format 
    if (isset($xarLangCode) && !empty($xarLangCode)) {
      $g2LangCode = preg_replace('|(\..*)?$|', '', $xarLangCode);
    } 
		     
    // initiate G2 
    $ret = GalleryEmbed::init(array('embedUri' => xarModGetVar('gallery2','g2.basefile'),
				    'relativeG2Path' => xarModGetVar('gallery2','g2.relativeurl'),
				    'loginRedirect' => xarModGetVar('gallery2','g2.loginredirect'),
				    'activeUserId' => $uid, 'activeLanguage' => $g2LangCode));
    if (!$ret->isSuccess()) {
      $data['g2modhtml'] = $ret->getAsHtml();
      return $data;
    }

    // user interface: disable sidebar in G2 and get it as separate HTML to put it into a xaraya block
    if (xarModGetVar('gallery2', 'g2.sidebarInside') == 0) {
      GalleryCapabilities::set('showSidebar', false);
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
	xarTplAddJavaScript('head', 'code', $script);
      }
    }

    /*
     * TODO: find xarAPI function to include HTML <head> data
     *       perhaps  xartpl_modifyheadercontent()
     */
    // xarTplAddStyleLink('gallery2', $styleName, $themeFolder='');
    // dirty hack:
    if (!empty($css)) {
      foreach ($css as $style) {
	$GLOBALS['xarTpl_additionalStyles'] =  $style .'
	'. $GLOBALS['xarTpl_additionalStyles'];
      }
    }

    // set the g2 sideBar (menu) html global, so that we can retrieve it,
    // when xaraya calls all the blocks for their html
    if (isset($g2moddata['sidebarHtml']) && !empty($g2moddata['sidebarHtml'])) {
      global $g2sidebarHtml;
      $g2sidebarHtml = $g2moddata['sidebarHtml'];
      // edit css
      $GLOBALS['xarTpl_additionalStyles'] .= <<<EOCSS
<style type="text/css" media="all">
#gsSidebar {
  float:none !important;
  width:100% !important;
} 

#gsAlbumContents, #gsAdminContents, #gsOtherContents {
 float:left !important;
 width:100% !important;
} 
</style>
EOCSS;

    }
    
    return $data;
} 


?>
