{if $gTikiSystem->isPackageActive( 'gallery2' ) }
	{tikimodule title="Images" name="last_blog_posts"}
	
{php}
global $smarty, $gQueryUserId, $module_rows, $module_params, $tikilib;

	for( $i=0; $i < $module_rows; $i++ ) {
		print '<div style="text-align:center;">';
		readfile( 'http://'.$_SERVER["HTTP_HOST"].GALLERY2_PKG_URL.'gallery2/main.php?g2_view=imageblock:External&g2_blocks=randomImage&g2_show=title|owner|date' );
		print '</div>';
	}
{/php}

	{/tikimodule}
{/if}
