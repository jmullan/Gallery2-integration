<?php
global $gTikiSystem;

$gTikiSystem->registerPackage( 'gallery2', dirname( __FILE__ ).'/' );
if( $gTikiSystem->isPackageActive( 'gallery2' ) ) {
	$gTikiSystem->registerAppMenu( 'gallery2', GALLERY2_PKG_DIR, GALLERY2_PKG_URL.'index.php', 'tikipackage:gallery2/menu_gallery2.tpl', 'gallery2' );
}
?>
