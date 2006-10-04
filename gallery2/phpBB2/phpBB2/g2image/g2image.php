<?php
//  Gallery 2 Image Selector
//  Version 2.0
//  By Kirk Steffensen - http://g2image.steffensenfamily.com/
//  Released under the GPL version 2.
//  Modified and adapted to phpbb2 by Scott Gregory - jettyrat@jettyfishing.com

$g2ic_version_text = '2.0';

$g2ic_image_ext_regex = '@(jpg|jpeg|png|gif|bmp|svg)$@i';

$g2ic_images_per_page = 10;

define('IN_PHPBB', true);
$phpbb_root_path = './../';
require($phpbb_root_path . 'extension.inc');
require($phpbb_root_path . 'common.' . $phpEx);
$userdata = session_pagestart($user_ip, PAGE_POSTING);
init_userprefs($userdata);

require($phpbb_root_path . 'g2helper.inc');
$g2h = new g2helper($db);
$g2h->init($userdata);

$lang_file_path = $phpbb_root_path . 'language/lang_' . $board_config['default_lang'] . '/lang_g2image.' . $phpEx;
if (file_exists($lang_file_path)) {
	include($lang_file_path);
}
else {
	include($phpbb_root_path . 'language/lang_english/lang_g2image.' . $phpEx);
}

session_start();

if (isset($_REQUEST['g2ic_form'])) {
	$g2ic_form = $_REQUEST['g2ic_form'];
	$_SESSION['g2ic_form'] = $_REQUEST['g2ic_form'];
}
elseif (isset($_SESSION['g2ic_form'])) {
	$g2ic_form = $_SESSION['g2ic_form'];
}

if (isset($_REQUEST['g2ic_field'])){
	$g2ic_field = $_REQUEST['g2ic_field'];
	$_SESSION['g2ic_field'] = $_REQUEST['g2ic_field'];
}
elseif (isset($_SESSION['g2ic_field'])) {
	$g2ic_field = $_SESSION['g2ic_field'];
}

$g2ic_last_album = (isset($_SESSION['g2ic_last_album_visited'])) ? $_SESSION['g2ic_last_album_visited'] : '/';

// ====( Main Code )======================================
$g2ic_current_page = 1;

$g2ic_rel_path = '/';

$g2ic_sortby = 'title_asc';

$g2ic_dirs = null;

$g2ic_root_album_path = $gallery->getConfig('data.gallery.albums');

g2ic_magic_quotes_remove($_REQUEST);

echo g2ic_make_html_header();

$g2ic_rel_path = g2ic_get_rel_path();

$g2ic_current_page = (!empty($_REQUEST['g2ic_page'])) ? floor(intval($_REQUEST['g2ic_page'])) : 1;

$g2ic_sortby = (isset($_REQUEST['sortby'])) ? $_REQUEST['sortby'] : $g2ic_sortby;

$g2ic_display_filenames = (isset($_REQUEST['display'])) ? (($_REQUEST['display'] == 'filenames') ? true : false) : ((!empty($g2ic_display_filenames)) ? $g2ic_display_filenames : false);

$g2ic_images_per_page = (isset($_REQUEST['images_per_page'])) ? $_REQUEST['images_per_page'] : $g2ic_images_per_page;

list($g2ic_dirs_titles, $g2ic_image_files) = g2ic_get_gallery_dirs_and_files();

$g2ic_page_navigation = g2ic_make_html_page_navigation();

echo g2ic_make_html_dir_menu();

$g2ic_album_url = g2ic_get_album_info($g2ic_rel_path);

if (isset($g2ic_page_navigation['empty'])) {
	print_r($g2ic_page_navigation['empty']);
}
elseif (isset($g2ic_page_navigation['error'])) {
	print_r($g2ic_page_navigation['error']);
}
else {
	echo g2ic_make_html_display_options();
	print_r($g2ic_page_navigation['html']);
	echo g2ic_make_html_image_navigation();
	print_r($g2ic_page_navigation['html']);
}

echo g2ic_make_html_about($g2ic_version_text);

echo '</body>' . "\n\n"
. '</html>';

$_SESSION['g2ic_last_album_visited'] = $g2ic_rel_path;

$g2h->done();

// ====( Functions )=======================================

//---------------------------------------------------------------------
//	Function:	g2ic_get_album_info
//	Parameters:	string $album_path_name
//	Returns:	string $album_url
//	Purpose:	Get info about an album from Gallery2 and parse out the
//			album URL
//---------------------------------------------------------------------

function g2ic_get_album_info($album_path_name) {
	$href = '';

	// Strip out %20 and replace with space
	$album_path_name = str_replace ('%20', ' ', $album_path_name);

	$gallery_block_html = g2ic_imagebypathblock($album_path_name);

	// Parse out the results
	preg_match('/href="[^"]*"/', $gallery_block_html, $href);
	$album_url = preg_replace('/href="/', '', $href[0]);
	$album_url = preg_replace('/"/', '', $album_url);

	return $album_url;
}

//---------------------------------------------------------------------
//	Function:	g2ic_get_g2_id_title_by_path
//	Parameters:	string $path
//	Returns:	array ($g2itemid, $g2item_title)
//	Purpose:	Get an item's Gallery2 Title by path.
//			Die on errors.
//---------------------------------------------------------------------

function g2ic_get_g2_id_title_by_path($path) {
	global $lang;

	list($ret, $g2itemid) = GalleryCoreApi::fetchItemIdByPath($path);

	if (empty($ret)) {
		list($ret, $item) = GalleryCoreApi::loadEntitiesById($g2itemid);
		if (empty($ret)) {
			$g2item_title = $item->getTitle() . "\n";
		}
		else {
			echo sprintf($lang['G2_LOADENTITIESBYID_FAILED'], $g2itemid) . $lang['G2_ERROR'] . $ret->getAsHtml()
			. '</body>' . "\n\n"
			. '</html>';
			exit;
		}
	}
	else {
		$g2itemid = false;
		$g2item_title = false;
	}

	return array($g2itemid, $g2item_title);
}

//---------------------------------------------------------------------
//	Function:	g2ic_get_gallery_dirs_and_files
//	Parameters:	None
//	Returns:	$dirs, $files
//	Purpose: 	Return files with allowed extension from upload directory.
//			The filenames are matched against the allowed file extensions.
//---------------------------------------------------------------------

function g2ic_get_gallery_dirs_and_files() {
	global $g2ic_rel_path, $g2ic_root_album_path, $g2ic_image_ext_regex, $g2ic_sortby, $lang;

	$image_files = $dirs = $titles = $ids = $filenames = $file_times = $file_titles = $file_ids = $dirs_titles = array();

	$current_dir = $file = $title = $name = $file_on_disk = '';

	// open current directory
	if (!$current_dir = opendir($g2ic_root_album_path . $g2ic_rel_path)) {
		echo '<p>' . $lang['g2_directory_error'] . '</p>';
		return false;
	}

	// Get info on valid directories and files
	while ($file = readdir($current_dir)) {
		$file_on_disk = $g2ic_root_album_path . $g2ic_rel_path . $file;

		//Reads the DirNames for navigation
		if (is_dir($file_on_disk)) {
			if ($file != '.' && $file != '..') {
				list($id, $title) = g2ic_get_g2_id_title_by_path($g2ic_rel_path . $file);
				if (!empty($title)) {
					$dirs[] = $file;
					$titles[] = $title;
				}
			}
		}

		if (is_file($file_on_disk) && preg_match($g2ic_image_ext_regex , $file)) {
			$name = basename($file_on_disk);
			list($id, $title) = g2ic_get_g2_id_title_by_path($g2ic_rel_path . $name);
			if (!empty($title)) {
				$filenames[] = $name;
				$file_titles[] = $title;
				$file_ids[] = $id;
				if ($temp = @filemtime($file_on_disk)) {
					$file_times[] = $temp;
				}
			}
		}
	}

	closedir($current_dir);

	// Sort directories and files
	$count_dirs = count($dirs);
	$count_files = count($filenames);

	if ($count_dirs > 0){
		array_multisort($titles, $dirs);

		for($i = 0; $i < $count_dirs; $i++) {
			$dirs_titles[$i] = array('directory' => $dirs[$i], 'title' => $titles[$i]);
		}
	}

	if ($count_files > 0){
		switch ($g2ic_sortby) {
			case 'title_asc' :
				array_multisort($file_titles, $filenames, $file_times, $file_ids);
				break;
			case 'title_desc' :
				array_multisort($file_titles, SORT_DESC, $filenames, SORT_DESC, $file_times, $file_ids);
				break;
			case 'name_asc' :
				array_multisort($filenames, $file_titles, $file_times, $file_ids);
				break;
			case 'name_desc' :
				array_multisort($filenames, SORT_DESC, $file_titles, SORT_DESC, $file_times, $file_ids);
				break;
			case 'mtime_asc' :
				array_multisort($file_times, $file_titles, $filenames, $file_ids);
				break;
			case 'mtime_desc' :
				array_multisort($file_times, SORT_DESC, $file_titles, $filenames, $file_ids);
		}

		for($i = 0; $i < $count_files; $i++) {
			$image_files[$i] = array('filename' => $filenames[$i],'title' => $file_titles[$i],'id' => $file_ids[$i]);
		}
	}

	return array($dirs_titles, $image_files);

}

//---------------------------------------------------------------------
//	Function:	g2ic_get_img_info
//	Parameters:	string $image_path_name
//	Returns:	array ($thumbnail_src,$thumbnail_width,$thumbnail_height,
//			$thumbnail_alt_text,$image_url,$gallery_url)
//	Purpose:	Get info about an image from Gallery2 and parse out the
//			results into the infomation required to generate the HTML
//---------------------------------------------------------------------

function g2ic_get_img_info($img_path_name) {
	global $g2ic_rel_path;

	$src = '';
	$href = '';
	$width = '';
	$height = '';
	$alt = '';

	// Strip leading slash unless this is the root directory
	$img_path_name = (strlen($img_path_name) > 1) ? substr_replace($img_path_name, '', 0, 1) : $img_path_name;

	// Strip out %20 and replace with space
	$img_path_name = str_replace('%20', ' ', $img_path_name);

	$gallery_block_html = g2ic_imagebypathblock($img_path_name);

	// Parse out the results
	preg_match('/src="[^"]*"/', $gallery_block_html, $src);
	preg_match('/href="[^"]*"/', $gallery_block_html, $href);
	preg_match('/width="[^"]*"/', $gallery_block_html, $width);
	preg_match('/height="[^"]*"/', $gallery_block_html, $height);
	preg_match('/alt="[^"]*"/', $gallery_block_html, $alt);

	$thumbnail_src = preg_replace('/src="/', '', $src[0]);

	$thumbnail_src = preg_replace('/"/', '', $thumbnail_src);

	$thumbnail_width = $width[0];

	$thumbnail_height = $height[0];

	$thumbnail_alt_text = preg_replace('/alt="/', '', $alt[0]);

	$thumbnail_alt_text = preg_replace('/"/', '', $thumbnail_alt_text );

	$image_url = preg_replace('/href="/', '', $href[0]);
	$image_url = preg_replace('/"/', '', $image_url);

	return array($thumbnail_src, $thumbnail_width, $thumbnail_height, $thumbnail_alt_text, $image_url);
}

//---------------------------------------------------------------------
//	Function:	g2ic_get_rel_path
//	Parameters:	None
//	Returns:	string $path - relative path to current folder
//	Purpose:	If 'rel_path' is set by GET, return a cleaned-up string.
//---------------------------------------------------------------------

function g2ic_get_rel_path() {
	global $g2ic_last_album, $g2ic_root_album_path;

	// If GET or POST have 'rel_path' set, use it
	if (isset($_REQUEST['rel_path'])){
		$path = str_replace ('..', '', $_REQUEST['rel_path']);
	}

	// Else if the $g2ic_last_album_visited is invalid, use the root '/'
	elseif (!is_dir($g2ic_root_album_path . $g2ic_last_album)) {
		$path = '/';
	}

	// Else use $g2ic_last_album
	else {
		$path = $g2ic_last_album;
	}

	return $path;
}

//---------------------------------------------------------------------
//	Function:	g2ic_imageblock
//	Parameters:	$g2inputid, $g2itemsize=null
//	Returns:	string $img
// 	Purpose:	Called by g2ic_imagebypathblock.
//---------------------------------------------------------------------

function g2ic_imageblock($g2itemid) {
	global $lang;

	// Build the Image Block
	$blockoptions['blocks'] = 'specificItem';
	$blockoptions['show'] = 'none';
	$blockoptions['itemId'] = $g2itemid;

	list ($ret, $itemimg, $headimg) = GalleryEmbed::getImageBlock($blockoptions);

	if (empty($ret)) {
		$img = $itemimg;

		// Compact the output
		$img = preg_replace('/(\s+)?(\<.+\>)(\s+)?/', '$2', $img);

		GalleryEmbed::done();
	}
	else {
		echo sprintf($lang['g2_id_not_found_error'], $g2itemid) . $lang['G2_ERROR'] . $ret->getAsHtml()
		. '</body>' . "\n\n"
		. '</html>';
		exit; // Die if file not found.  Should not be able to get to here.
	}

	return $img;
}

//---------------------------------------------------------------------
//	Function:	g2ic_imagebypathblock
//	Parameters:	$g2inputpath
//	Returns:	$img
// 	Purpose:	Include image from gallery based on path
//---------------------------------------------------------------------

function g2ic_imagebypathblock($g2itempath) {
	global $lang;

	// Make Sure Item Path does not contain a + as it should instead be a space
	$g2itempath = str_replace ('+', ' ', $g2itempath);

	// Get the Image
	list ($ret, $g2itemid) = GalleryCoreAPI::fetchItemIdByPath($g2itempath);

	if (empty($ret)) {
		$img = g2ic_imageblock($g2itemid);
	}
	else {
		echo $lang['g2_id_by_path_error'] . $g2itempath . '<br />'
		. '</body>' . "\n\n"
		. '</html>';
		$img = $lang['invalid_image'];
	}

	return $img;
}

//---------------------------------------------------------------------
//	Function:	g2ic_magic_quotes_remove
//	Parameters:	array &$array POST or GET with magic quotes
//	Returns:	None
//	Purpose:	Remove magic Quotes
//---------------------------------------------------------------------

function g2ic_magic_quotes_remove(&$array) {
	if (!get_magic_quotes_gpc()) {
		return;
	}
	else {
		foreach($array as $key => $elem) {
			if (is_array($elem)) {
				g2ic_magic_quotes_remove($elem);
			}
			else {
				$array[$key] = stripslashes($elem);
			}
		}
	}
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_about
//	Parameters:	$version
//	Returns:	string $html
//	Purpose:	Creates the "About" alert HTML
//---------------------------------------------------------------------

function g2ic_make_html_about($version) {
	global $lang;

	$html = '<div class="about_button">' . "\n"
	. '    <input type="button" onclick="alert(\'Gallery2 Image Selector\nVersion ' . $version
	. '\nAuthor: Kirk Steffensen'
	. '\nAdapted and modified for phpBB<->Gallery2\nby Scott Gregory - jettyrat@jettyfishing.com\')" '
	. 'value="' . $lang['about'] . '"/>' . "\n"
	. '</div>' . "\n";

	return $html;
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_dir_menu
//	Parameters:	None
//	Returns:	string $html
//	Purpose:	Creates the directory navigation HTML
//---------------------------------------------------------------------

function g2ic_make_html_dir_menu(){
	global $g2ic_rel_path, $g2ic_dirs_titles, $g2ic_sortby, $g2ic_display_filenames, $g2ic_images_per_page, $lang;

	$html = '<div>' . "\n"
	. '    <fieldset>' . "\n"
	. '        <legend>' . $lang['current_album'] . '</legend>' . "\n"
	. '        <a href="?rel_path=/&amp;sortby=' . $g2ic_sortby;
	$html .= ($g2ic_display_filenames) ? '&amp;display=filenames' : '&amp;display=thumbnails';
	list($root_id, $root_title) = g2ic_get_g2_id_title_by_path('/');
	$html .= "&amp;images_per_page=$g2ic_images_per_page\">$root_title/</a>\n";

	$tok = strtok ($g2ic_rel_path, '/');
	$rel_path_tok = "/$tok/";
	while ($tok) {
		$html .= "        <a href=\"?rel_path=$rel_path_tok&amp;sortby=$g2ic_sortby";
		$html .= ($g2ic_display_filenames) ? '&amp;display=filenames' : '&amp;display=thumbnails';

		list($dir_id, $dir_title) = g2ic_get_g2_id_title_by_path($rel_path_tok);
		$html .= "&amp;images_per_page=$g2ic_images_per_page\">$dir_title/</a>\n";

		$tok = strtok ('/');
		$rel_path_tok .= "$tok/";
	}

	$html .= '    </fieldset>' . "\n";

	// Subdirectory navigation
	if (!empty($g2ic_dirs_titles)) {
		$html .= '    <fieldset>' . "\n"
		. '        <legend>' . $lang['subalbums'] . '</legend>' . "\n"
		. '        <form name="subdirectory_navigation">' . "\n"
		. '            <select name="subalbums">' . "\n";

		foreach ($g2ic_dirs_titles as $key => $row) {
			$html .= "                <option value=\"?rel_path=$g2ic_rel_path" . $row['directory'] . "/&amp;sortby=$g2ic_sortby";
			$html .= ($g2ic_display_filenames) ? '&amp;display=filenames' : '&amp;display=thumbnails';
			$html .= "&amp;images_per_page=$g2ic_images_per_page\">" . $row['title'] . '</option>' . "\n";
		}

		$html .=	'            </select>' . "\n"
		. '            <input type="button" name="Submit" value="' . $lang['go'] . '" onClick="top.location.href = this.form.subalbums.options[this.form.subalbums.selectedIndex].value; return false;">' . "\n"
		. '        </form>' . "\n"
		. '    </fieldset>' . "\n";
	}

	$html .= '</div>' . "\n\n";

	return $html;
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_display_options
//	Parameters:	None
//	Returns:	string $html
//	Purpose:	Make the HTML for the Sort Selector
//---------------------------------------------------------------------

function g2ic_make_html_display_options(){
	global $g2ic_sortby, $g2ic_rel_path, $g2ic_images_per_page, $g2ic_display_filenames, $lang;

	$images_per_page_options = array(10, 20, 30, 40, 50, 60);

	if (!in_array($g2ic_images_per_page, $images_per_page_options)){
		array_push($images_per_page_options, $g2ic_images_per_page);
		sort($images_per_page_options);
	}

	// array for output
	$sortoptions = array(
		'title_asc' => array('text' => $lang['title_a_to_z']),
		'title_desc' => array('text' => $lang['title_z_to_a']),
		'name_asc' => array('text' => $lang['name_a_to_z']),
		'name_desc' => array('text' => $lang['name_z_to_a']),
		'mtime_desc' => array('text' => $lang['last_modification_new']),
		'mtime_asc' => array('text' => $lang['last_modification_old'])
	);

	$sortoptions[$g2ic_sortby]['selected'] = TRUE;

	$html = '<div>' . "\n"
	. '    <fieldset>' . "\n"
	. '        <legend>' . $lang['display_legend'] . '</legend>' . "\n"
	. '        <form action="' . $_SERVER['PHP_SELF'] . '" method="get">' . "\n"
	. '            <input type="hidden" name="rel_path" value="' . $g2ic_rel_path . '">' . "\n"
	. '            ' . $lang['sorted_by'] . "\n"
	. g2ic_make_html_select('sortby', $sortoptions)
	. '            ' . $lang['thumbnails_per_page'] . "\n"
	. '            <select name="images_per_page">' . "\n";

	for ($i = 0;$i < count($images_per_page_options); $i++) {
		$html .= '                <option value="' . $images_per_page_options[$i] . '"';
		if ($images_per_page_options[$i] == $g2ic_images_per_page)
			$html .= ' selected="selected"';
		$html .= '>' . $images_per_page_options[$i] . '</option>' . "\n";
	}

	$html .=	'            </select>' . "\n"
	. '            <input type="submit" value="' . $lang['redraw'] . '" /><br />' . "\n"
	. '            <input type="radio" name="display" value="thumbnails"';
	$html .= (empty($g2ic_display_filenames)) ? ' checked="checked"' : '';
	$html .= ' onclick="showThumbnails()">' . $lang['thumbnails'] . '</input>' . "\n";

	$html .= '            <input type="radio" name="display" value="filenames"';
	$html .= (!empty($g2ic_display_filenames)) ? ' checked="checked"' : '';
	$html .= ' onclick="showFileNames()">' . $lang['filenames'] . '</input>' . "\n";

	$html .= '        </form>' . "\n"
	. '    </fieldset>' . "\n"
	. '</div>' . "\n\n";

	return $html;
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_header
//	Parameters:	None
//	Returns:	string $html - HTML header
//	Purpose:	Make the text for the HTML header
//---------------------------------------------------------------------

function g2ic_make_html_header() {
	global $lang;

	$html = '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n"
	. '<head>' . "\n"
	. '    <title>' . $lang['title'] . '</title>' . "\n"
	. '    <link rel="stylesheet" href="css/g2image.css" type="text/css" />' . "\n"
	. '    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8" />' . "\n"
	. '    <script language="javascript" type="text/javascript" src="jscripts/functions.js"></script>' . "\n"
	. '</head>' . "\n\n"
	. '<body>' . "\n\n";

	return $html;
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_image_navigation
//	Parameters:	None
//	Returns:	string $html
//	Purpose:	Create the HTML for image navigation/selection
//---------------------------------------------------------------------

function g2ic_make_html_image_navigation() {
	global $g2ic_image_files, $g2ic_current_page, $g2ic_images_per_page, $g2ic_rel_path, $g2ic_sortby, $g2ic_g2thumbnail_src, $g2ic_thumbnail_width, $g2ic_thumbnail_height, $g2ic_thumbnail_alt_text, $g2ic_image_url, $g2ic_album_url, $g2ic_custom_url, $g2ic_display_filenames, $g2ic_class_mode, $g2ic_form, $g2ic_field, $lang, $phpEx;

	reset($g2ic_image_files);

	$html = '';
	
	$protocol = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/')));
	$host = $_SERVER['HTTP_HOST'];
	$path = str_replace('g2image/g2image.' . $phpEx, '', $_SERVER['SCRIPT_NAME']);
	$href = $protocol . '://' . $host . $path . 'gallery2.' . $phpEx . '?g2_view=core.DownloadItem&g2_itemId=';

	foreach ($g2ic_image_files as $key => $row) {
		$image_filename = $row['filename'];
		$image_title = $row['title'];
		$image_id = $row['id'];

		if ($key >= $g2ic_current_page * $g2ic_images_per_page) {
			break; // Have gone past the range for this page
		}
		else {

			$album_name = substr_replace($g2ic_rel_path, '', 0, 1);

			$html .= (!empty($g2ic_display_filenames)) ? '<div class="bordered_imageblock">' . "\n" : '<div class="transparent_imageblock">' . "\n";

			$html .= g2ic_make_html_img($image_filename) . "\n";

			$html .= (!empty($g2ic_display_filenames)) ? '    <div class="displayed_title">' . "\n" : '    <div class="hidden_title">' . "\n";

			$html .= '        ' . $lang['image_title'] . htmlspecialchars($image_title) . '<br />' . "\n";

			$html .= '        ' . $lang['filename'] . htmlspecialchars($image_filename) . "\n";

			$html .= '    </div>' . "\n\n";

			$html .= (!empty($g2ic_display_filenames)) ? '    <div class="active_placeholder">' . "\n" : '    <div class="inactive_placeholder">' . "\n";

			$html .= '    </div>' . "\n\n";

			$imgdesc = $g2ic_thumbnail_alt_text;

			// Create the hidden input area (appears when image is clicked)
			$html .= '    <form action="' . $_SERVER['PHP_SELF'] . '?rel_path=' . $g2ic_rel_path . '" method="post" id="' . $image_filename . '" class="hidden_form">' . "\n\n"

			. '        <fieldset>' . "\n"

			// hidden fields
			. '            <input type="hidden" name="thumbnail_src" value="' . $g2ic_g2thumbnail_src . '" />' . "\n"
			. '            <input type="hidden" name="image_url" value="' . $g2ic_image_url . '" />' . "\n"
			. '            <input type="hidden" name="album_url" value="' . $g2ic_album_url . '" />' . "\n"
			. '            <input type="hidden" name="image_name" value="' . $image_filename . '" />' . "\n"
			. '            <input type="hidden" name="image_id" value="' . $image_id . '" />' . "\n"
			. '            <input type="hidden" name="album_name" value="' . $album_name . '" />' . "\n"
			. '            <input type="hidden" name="thumbw" value="' . $g2ic_thumbnail_width . '" />' . "\n"
			. '            <input type="hidden" name="thumbh" value="' . $g2ic_thumbnail_height . '" />' . "\n"
			. '            <input type="hidden" name="file" value="' . rawurlencode($image_filename) . '" />' . "\n"
			. '            <input type="hidden" name="sortby" value="' . $g2ic_sortby . '" />' . "\n"
			. '            <input type="hidden" name="g2ic_page" value="' . $g2ic_current_page . '" />' . "\n"
			. '            <input type="hidden" name="relpath" value="' . $g2ic_rel_path . '" />' . "\n"
			. '            <input type="hidden" name="class_mode" value="' . $g2ic_class_mode . '" />' . "\n"
			. '            <input type="hidden" name="g2ic_form" value="' . $g2ic_form . '" />' . "\n"
			. '            <input type="hidden" name="g2ic_field" value="' . $g2ic_field . '" />' . "\n"
			. '            <input type="hidden" name="g2ic_href" value="' . $href . $image_id  . '" />' . "\n"
			. '        </fieldset>' . "\n\n"
			. '    </form>' . "\n"
			. '</div>' . "\n\n";
		}
	}

	return $html;
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_img
//	Parameters:	$image - Filename
//	Returns:	string $html
//	Purpose:	Make <img ... /> html snippet for an image
//---------------------------------------------------------------------

function g2ic_make_html_img($image) {
	global $g2ic_rel_path, $g2ic_g2thumbnail_src, $g2ic_thumbnail_width, $g2ic_thumbnail_height, $g2ic_thumbnail_alt_text, $g2ic_image_url;

	$html = '';

	$filename = $g2ic_rel_path . rawurlencode($image);

	// Determine $g2ic_img_html and $g2ic_link_html
	list ($g2ic_g2thumbnail_src, $g2ic_thumbnail_width, $g2ic_thumbnail_height, $g2ic_thumbnail_alt_text, $g2ic_image_url) = g2ic_get_img_info($filename);

	// ---- image code
	$html .= "    <img src=\"$g2ic_g2thumbnail_src\" $g2ic_thumbnail_width $g2ic_thumbnail_height alt=\"$g2ic_thumbnail_alt_text\"\n";

	$html .= "    onclick=\"insertImage(this.parentNode.getElementsByTagName('form')[0])\"\n";

	$html .= "    />\n";

	return $html;

}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_page_navigation
//	Parameters:	None
//	Returns:	string $html - HTML for page navigation
//	Purpose:	Generate the HTML for navigating over multiple pages
//---------------------------------------------------------------------

function g2ic_make_html_page_navigation() {
	global $g2ic_image_files, $g2ic_display_filenames, $g2ic_images_per_page, $g2ic_current_page, $g2ic_rel_path, $g2ic_sortby, $lang;

	//Check if the current directory is empty - print empty message and return if empty
	if (empty($g2ic_image_files)) {
		$html = '<p><strong>' . $lang['empty_album'] . '</strong></p>' . "\n\n";
		$page_navigation['empty'] = $html;
		return $page_navigation;
	}

	// ---- navigation for pages of images
	$pages = ceil(count($g2ic_image_files) / $g2ic_images_per_page);
	$g2ic_current_page = ($g2ic_current_page > $pages) ? $pages : $g2ic_current_page;

	$pagelinks = array();
	for ($count = 1; $count <= $pages; $count++) {
		if ($g2ic_current_page == $count) {
			$pagelinks[] = "        <strong>$count</strong>";
		}
		else {
			$html = '        <a href="' . $_SERVER['PHP_SELF'] . "?g2ic_page=$count"
			. "&amp;sortby=$g2ic_sortby&amp;rel_path=$g2ic_rel_path";
			$html .= ($g2ic_display_filenames) ? '&amp;display=filenames' : '&amp;display=thumbnails';
			$html .= "&amp;images_per_page=$g2ic_images_per_page\">$count</a>";
			$pagelinks[] = $html;
		}
	}

	if (count($pagelinks) > 1) {
		$html = '<div>' . "\n"
		. '    <fieldset>' . "\n"
		. '        <legend>' . $lang['page_navigation'] . '</legend>' . "\n"
		. '        ' . $lang['page'] . ' ' . "\n"
		. implode("     - \n", $pagelinks)
		. "\n"
		. '    </fieldset>' . "\n"
		. '</div>' . "\n\n";
	}
	else {
		$html = '';
	}

	$page_navigation['html'] = $html;

	return $page_navigation;
}

//---------------------------------------------------------------------
//	Function:	g2ic_make_html_select
//	Parameters:	$name,$options
//	Returns:	string $html
//	Purpose:	Make html for select element
//	Notes:		The hash $options should contain values and Description:
//			array(
//				'value' => array(
//					text     => 'Description',
//					selected => (TRUE|FALSE),
//				),
//				...
//			)
//---------------------------------------------------------------------

function g2ic_make_html_select($name, $options) {
	$html = '            <select name="' . $name . '" id="' . $name . '" size="1">' . "\n";
	foreach ($options as $value => $option) {
		$html .= '                <option value="' . $value . '"';
		$html .= (!empty($option['selected'])) ? ' selected="selected"' : '';
		$html .= '>' . $option['text'] . '</option>' . "\n";
	}
	$html .= '            </select>' . "\n";

	return $html;
}

?>
