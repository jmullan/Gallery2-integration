<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
global $iso_client_lang, $ret, $g2_Config, $mosConfig_locale;
//loading config files and functions
require_once("components/com_gallery2/userfuncs.php" );
$g2_Config = G2helperclass::g2_Config();
$lang = G2helperclass::switchlang($iso_client_lang, $mosConfig_locale);
# Get the right language if it exists
if (file_exists($mosConfig_absolute_path.'/administrator/components/com_gallery2/language/'.$mosConfig_lang.'.php')) {
  include($mosConfig_absolute_path.'/administrator/components/com_gallery2/language/'.$mosConfig_lang.'.php');
} else {
  include($mosConfig_absolute_path.'/administrator/components/com_gallery2/language/english.php');
}
//content
     print '<table width="100%" cellpadding="4" cellspacing="0" border="0" align="center" class="contentpane">' . "\n<tr><td>\n";
	//userid
	if ($g2_Config['mirrorUsers'] == 1)
    {
        $user_id = $my->id;
    }
    else
    {
        $user_id = '';
    }
	//load embed.php
	G2helperclass::embed();
	//init G2
	$ret = G2helperclass::init_G2($user_id, 'false');
	//pathway
	if(!empty($_GET['g2_itemId'])){
		G2helperclass::pathway($_GET['g2_itemId']);
	}


    if (($g2_Config['mirrorUsers'] == 1) && $ret->isError() && !empty($my->username))
    {
        if ($ret->getErrorCode() & ERROR_MISSING_OBJECT)
        {
            // check if there's no G2 user mapped to the activeUserId
            $ret = GalleryEmbed::isExternalIdMapped($my->id, 'GalleryUser');

            if ($ret->getErrorCode() & ERROR_MISSING_OBJECT)
            {
                // user not mapped, create G2 user now
                $ret = G2helperclass::new_user($my->id, array('username' => $my->username, 
                                                'email' => $row->email, 'fullname' => $row->name, 
                                                'hashedpassword' => $row->password, 'hashmethod' => 'md5'));
                if ($ret->isError())
                {
                    if ($ret->getErrorCode() & ERROR_COLLISION)
                    {
                        list ($ret, $g2user) = GalleryCoreApi::fetchUserByUserName($my->username);
                        if (!$ret->isError())
                        {
                            GalleryEmbed::addExternalIdMapEntry($my->id, $g2user->getId(), 'GalleryUser');
                        }
                    }
                    else
                    {
                        print $ret->getAsHtml();
                        return 0;
                    }
                }

                GalleryEmbed::checkActiveUser($my->id);
            }
            else
            {
                // a real problem, handle it, i.e. raise error or print error message
                print $ret->getAsHtml();
                return 0;
            }
        }
        else
        {
            print $ret->getAsHtml();
        }
    }        

    if ($g2_Config['displaysidebar'] == 0) {
        GalleryCapabilities::set('showSidebarBlocks', false);
    }
    else {
        GalleryCapabilities::set('showSidebarBlocks', true);
    }
    if ($g2_Config['displaylogin'] == 0) {
        GalleryCapabilities::set('login' , false);
    }
    else {
        GalleryCapabilities::set('login' , true);
    }

    if ($g2_Config['mirrorUsers'] == 1 AND $g2_Config['enableAlbumCreation'] == 1){
		$return = G2helperclass::g2_album_check();
		print $return['print'];
	}

    // handle the G2 request
    $g2moddata = GalleryEmbed::handleRequest($my->username);

    // show error message if isDone is not defined
    if (!isset($g2moddata['isDone']))
    {
      print 'isDone is not defined, something very bad must have happened.';
      exit;
    }

    // die if it was a binary data (image) request
    if ($g2moddata['isDone'])
    {
      exit; /* uploads module does this too */
    }

     if ($ret->isError())
     {
       print $ret->getAsHtml();
     }
	
     print $g2moddata['headHtml'];
	 print '<style type="text/css"> <!--  #gsHeader { display : none; } #gsFooter { display: none; } --> </style>';
     print $g2moddata['bodyHtml'];

print "</td></tr>\n</table>\n";
include_once("components/com_gallery2/footer.php");
	/*
	$text = str_replace("<", "&lt;", $g2moddata['sidebarBlocksHtml']);
	$text = str_replace(">", "&gt;", $text);
	foreach($text as $key => $val){
	print 'key:'.$key.' Valua:'.substr($val, 0, 100).'<br />';
	}
	*/
?>