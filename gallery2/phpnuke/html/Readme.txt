/************************************************************************/
/* PHP-NUKE: Advanced Content Management System                         */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* Gallery2 Integration module for PhpNuke 7.5 and 7.6  								*/
/*                                                                      */
/* This is a beta of Gallery2 Integration module for PhpNuke 7.5 & 7.6  */
/* If you don't have the Gallery2 installed into your system,           */
/* then DO NOT use these files.                                         */
/* This is a beta realised , so if you try it, BACKUP first.            */
/*                                                                      */
/* Version: 0.2 (28th March 2005)                                       */
/*                                                                      */
/************************************************************************/

Description
-----------

This package is a beta release, DO NOT USE IT IN PRODUCTION CONTEXT !

This module allow you to embed Gallery2 into PHPNUKE 7.5 or 7.6, it is still in heavy development and only proposed minor features:

 - A basic Administration panels which allow you to configure this module.
 - A Gallery2 entry in the main phpnuke menu to browse your gallery2 into phpnuke.
 - A block containing the G2 Sidebar.
 - The ability to mapped all your phpnuke users to G2.


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
		echo '<style type="text/css" media="all">@import url(http://localhost/gallery2/templates/layout.css);</style>';
		echo '<style type="text/css" media="all">@import url(http://localhost/gallery2/themes/matrix/theme.css);</style>';


if you're using PhpNuke7.6:
---------------------------

Open or create includes/custom_files/custom_head.php
And add this:
		
		<style type="text/css" media="all">@import url(http://localhost/gallery2/templates/layout.css);</style>
		<style type="text/css" media="all">@import url(http://localhost/gallery2/themes/matrix/theme.css);</style>



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

Gallery2 Embeding Settings:
---------------------------

Filled correctly the 5 fields required to embed Gallery2:
Setting good parameters is not so easy: You will certainly needs more than one test to success!
At the moment only the first field is tested against wrong path, more will come soon.

Click on Update embed settings:
It will save your parameters to the modules/gallery2/gallery2.cfg file
You don't need to edit this file by hand as the admin page will do it for you.

-5-

The Gallery2 Main Settings:
---------------------------

For now, it only allow you to control the sidebar:
The show sidebar checkbox allows you to remove the leftsidebar from the main Gallery2 display.
Instead you can display it inside a standard phpnuke block. (install the G2_Sidebar block to get it)

-6-

Export Users to Gallery2:
-------------------------

It will export all your phpnuke users and create them a G2 account if they have not.
The first phpnuke admin account will also be mapped to the G2 admin account.
Since if you're logged as an admin in phpnuke, you will also been logged as the admin in G2 regardless of your phpnuke normal user login.
No group mapping is done for now... It will come up for a next release.
And yes, for now there is no hook allowing to add a new user dynamically...
So you will need to perform this action each time a new user register your phpnuke website.

-7-
As usual, activate this Module in the Modules Administration.


You need to update your config, at least one time to activate this module.

-------------------
Block Configuration
-------------------

This is a standard block, so you won't have many problems installing it, but you need to activate the G2 module first.
Please, be aware the Block is still not working very well, so don't install it on a visited website !
 

----------------------------------------------------
http://gallery.sourceforge.net
http://www.phpnuke-web.com drumicube@phpnuke-web.com
----------------------------------------------------
