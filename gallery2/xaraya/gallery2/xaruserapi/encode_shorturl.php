<?php

/**
 * return the path for a short URL to xarModURL for this module
 *
 * @author Andy Staudacher
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function gallery2_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);
	
	$module = 'gallery2';
	$path = '/' . $module . '/';

	return $path;
}

?>
