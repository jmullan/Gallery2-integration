<?php

	require_once( '../tiki_setup_inc.php' );

	require_once( 'gallery2/embed.php' );
	$activeUserId = $gTikiUser->isRegistered() ? $gTikiUser->mUserId : NULL;
	$status = GalleryEmbed::init( array( 'embedUri' => 'index.php', 'relativeG2Path' => 'gallery2', 'loginRedirect' => '/users/login.php', 'activeUserId' => $activeUserId ));
	$gallerySessionId = GalleryEmbed::getSessionId();

	if ($status->isError()) {
		if( $status->getErrorCode() & ERROR_MISSING_OBJECT ) {
			if( $g2User = GalleryEmbed::createUser( $gTikiUser->mUserId, array( 
	'username' => $gTikiUser->mInfo['login'],
	'email' => $gTikiUser->mInfo['email'],
	'fullname' => $gTikiUser->mInfo['real_name'],
	'creationtimestamp' => $gTikiUser->mInfo['registration_date']
			) ) ) {
				if( $gTikiUser->isAdmin() ) {
					list ($ret, $adminGroupId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.adminGroup');
					if ($ret->isError()) {
						return array($ret->wrap(__FILE__, __LINE__), false);
					}
					GalleryEmbed::addUserToGroup( $activeUserId, 2 );
				}
	//'language' => $gTikiUser->mInfo['language'],
	//'password' => string,
	//'hashedpassword' => string,
	//'hashmethod' => string,
			} else {
				fatalError( $status->getAsHtml() );
				exit;
			}
		}
	}
 
	GalleryCapabilities::set('showSidebar', FALSE);
	$g2data = GalleryEmbed::handleRequest();

	$smarty->assign_by_ref( 'menuLinks', $g2data['layoutData']['itemLinks'] );

	if ($g2data['isDone']) {
		exit; // G2 has already sent output (redirect or binary data)
	}
$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

	$smarty->assign_by_ref( 'g2data', $g2data );
	$gTikiSystem->display( 'tikipackage:gallery2/tikipro_embed.tpl' );  

?>
