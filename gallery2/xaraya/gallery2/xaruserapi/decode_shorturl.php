<?php

/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * @author Andy Staudacher
 * @param $params array containing the different elements of the virtual path
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function gallery2_userapi_decode_shorturl($params)
{
    // Initialise the argument list we will return
    $args = array();
	
    return array('main',$args);
}

?>
