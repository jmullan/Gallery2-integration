/************************************************************************/
/* PHP-NUKE: Advanced Content Management System                         */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* Gallery2 Integration module for PhpNuke 7.5 													*/
/*                                                                      */
/* This is a beta of Gallery2 Integration module for PhpNuke 7.5        */
/* If you don't have the Gallery2 installed into your system,           */
/* then DO NOT use these files.                                         */
/* This is a beta realised , so if you try it, BACKUP first.            */
/*                                                                      */
/* Version: 0.1 (20th January 2005)                                     */
/*                                                                      */
/************************************************************************/

Description
-----------

This package is a beta release, DO NOT USE IT IN PRODUCTION CONTEXT !

This module allow you to embed Gallery2 into PHPNUKE 7.5, it is still in heavy development and only proposed minor features:

 - A basic Administration panels which allow you to configure this module.
 - A Gallery2 entry in the main phpnuje menu to browse your gallery2 into phpnuke.


------------
Installation
------------

The zip archive contains 2 folders: 

		modules/  it contains the gallery2 module itself 
		images/   it only contains an icon which will be displayed by main Admin panel.
		blocks/		it contains the G2 menu block
		
Just upload each folder to those matching pour PhpNuke CMS installation.
In most case (depending of your installation), just copy the html folder to the root of your phpnuke installation.


if you're not using PhpNuke7.6:
-------------------------------

Open an edit the header.php file:

Just before le line: echo "\n\n\n</head>\n\n"; 
Add this:
		// ------ gallery 2 header addition ------
		echo '<style type="text/css" media="all">@import url(http://localhost/gallery2/layouts/matrix/layout.css.php);</style>';
		echo '<style type="text/css" media="all">@import url(http://localhost/gallery2/themes/matrix/styles/theme.css);</style>';


if you're using PhpNuke7.6:
---------------------------

Open or create includes/custom_files/custom_head.php
And add this:

		<style type="text/css" media="all">@import url(http://localhost/gallery2/layouts/matrix/layout.css.php);</style>
		<style type="text/css" media="all">@import url(http://localhost/gallery2/themes/matrix/styles/theme.css);</style>



The layout and css addition is static for now, and do not allow customisation.
This is just for testing purpose, it should evolve soon !


--------------------
Module Configuration
--------------------

-1-
In any case, this module will not install Gallery2 for you.
So, you must first have a fully installed Gallery2 copy on your website.

-2- 
Make sure the modules/gallery2/gallery2.cfg on your server is writable.
If this is not the case: just chmod it to 666 .

-3-
In your PhpNuke portal: 
Logged yourseft as an Administrator, and go to the Administration panel.
Choose Gallery2 in the Modules Administration

-4-
Filled correctly the 5 fields required to embed Gallery2:

Setting good parameters is not so easy: You will certainly needs more than one test to success!
At the moment only the first field is tested against wrong path, more will come soon.

-5-
Click on Update embed settings
It will save your parameters to the modules/gallery2/gallery2.cfg file
You don't need to edit this file by hand as the admin page will do it for you.

-6-


-7-
As usual, activate this Module in the Modules Administration.


You need to save your config, at least one time to activate this module.


----------------------------------------------------
http://gallery.sourceforge.net
http://www.phpnuke-web.com drumicube@phpnuke-web.com
----------------------------------------------------
