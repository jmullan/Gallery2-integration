<?php

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'about' => 'About G2Image',
	'current_album' => 'Current Album:',
	'description' => 'Description:',
	'display_legend' => 'Display Options',
	'empty_album' => 'There are no photos in this album.<br /><br />Please pick another album from the navigation options above.',
	'filename' => 'Filename: ',
	'filenames' => 'Filenames',
	'g2_directory_error' => 'Cannot open Gallery2 data directory.',
	'g2_id_by_path_error' => 'Cannot get the Gallery2 ID for the requested path.<br />This is most likely due to a file or directory residing in your Gallery2 data directory structure, but not being registered in Gallery2 as part of your album structure.  All the rest of the functionality on this page will work.<br /><br />Here\'s the path that is invalid: ',
	'g2_id_not_found_error' => 'getImageBlock failed for %s',
	'g2_image_title' => 'Gallery2 Title:',
	'go' => 'Go',
	'image_title' => 'Title: ',
	'last_modification_new' => 'Last Modification (newest first)',
	'last_modification_old' => 'Last Modification (oldest first)',
	'name_a_to_z' => 'Filename (A-z)',
	'name_z_to_a' => 'Filename (z-A)',
	'page' => 'Page:',
	'page_navigation' => 'Page Navigation:',
	'photo_albums' => 'Photo Albums',
	'redraw' => 'Redraw',
	'sort_error' => 'Error sorting by',
	'sorted_by' => 'Sorted by:',
	'subalbums' => 'Subalbums:',
	'thumbnails' => 'Thumbnails',
	'thumbnails_per_page' => 'Per Page:',
	'title' => 'Gallery 2 Image Chooser',
	'title_a_to_z' => 'Gallery2 Title (A-z)',
	'title_z_to_a' => 'Gallery2 Title (z-A)',
));

?>