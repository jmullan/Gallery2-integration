/************************************************************************/
/* PHP-NUKE: Advanced Content Management System                         */
/* ============================================                         */
/*                                                                      */
/* Copyright (c) 2004 by Francisco Burzi                                */
/* http://phpnuke.org                                                   */
/*                                                                      */
/* Gallery2 Integration module for PhpNuke		  		*/
/*                                                                      */
/* This is a beta of Gallery2 Integration module for PhpNuke.		*/
/* If you don't have the Gallery2 installed into your system,           */
/* then DO NOT use these files.                                         */
/* This is a beta realised , so if you try it, BACKUP first.            */
/*                                                                      */
/* Version: 0.5 (13 July 2005)						*/
/*                                                                      */
/************************************************************************/

-----------
Description
-----------

This package is a beta release, DO NOT USE IT IN PRODUCTION CONTEXT !

This module will allow you to embed Gallery2 into PHPNuke. It is still in heavy development and only proposed minor features:

 - A basic Administration panel which allow you to configure this module.
 - A Gallery2 entry in the main PHPNuke menu.
 - A block containing the G2 Sidebar.
 - The ability to map all your PHPNuke users to G2.


------------
Installation
------------

The zip archive contains 4 folders: 

		admin/		Contains admin links for versions of PHPNuke < 7.5
		modules/	Contains the gallery2 module itself 
		images/		Contains an icon which will be displayed by main Admin panel.
		blocks/		Contains the G2 menu block
		
Just upload each folder to those matching pour PHPNuke installation.
In most case (depending of your installation), just copy the html folder to the root of your PHPNuke installation.


--------------------
Module Configuration
--------------------

-1-
This module will NOT install Gallery2 for you.
You must first have a fully installed Gallery2 copy on your website.

-2- 
Make sure the modules/gallery2/gallery2.cfg on your server is writable.
If this is not the case, chmod it to 666 .

-3-
In your PHPNuke portal: 
Log in as an Administrator, and go to the Administration panel.
Choose Gallery2 in the Modules Administration

-4-

Gallery2 Embedding Settings:
---------------------------

The following fields must be properly set for the embedding to function.
  1. "Full path to your Gallery2 directory" - This is the complete path to your Gallery2
     installation. For example: /home/myuser/public_html/nuke/modules/gallery2/

  2. "URL to your embedded Gallery" - This is the URL that would take you to your embedded
     Gallery2 installation. For example: http://www.mysite.com/nuke/modules.php?name=gallery2

  3. "Relative path to your Gallery2 directory" - This is the path to your Gallery2 directory
     relative to your root web directory.  For example: /nuke/modules/gallery2/

  4. "URL for user login" - This is the URL which users would visit to log into your PHPNuke site, 
     normally via the Your_Account module.  For example: nuke/modules.php?name=Your_Account

  5. "Active User ID" - You can safely leave this set to '0'.

At the moment only the first field is tested against wrong path, more will come soon.

Click on Update embed settings:
It will save your parameters to the modules/gallery2/gallery2.cfg file
You don't need to edit this file by hand as the admin page will do it for you.

-5-

The Gallery2 Main Settings:
---------------------------

As of this writing, it only allows you to control the sidebar:
The show sidebar checkbox allows you to remove the leftsidebar from the main Gallery2 display.
If you leave this unchecked, you can display it inside a standard PHPNuke block. (install the G2_Sidebar block to get it)

-6-

Export Users to Gallery2:
-------------------------

This will export all your PHPNuke users and create them a G2 account if they do not have one.
The first PHPNuke admin account will also be mapped to the G2 admin account.
Therefore, if you're logged as an admin in PHPNuke, you will also been logged as the admin in G2 regardless of your PHPNuke normal user login.
No group mapping is done for now....It will come in a future release.
The export user is split in multiple pages which allows a maximum export of 100 users per page, preventing errors during large database export.

The dynamic addition of users now works:
Everytime a new user registers on your PHPNuke portal, on their first G2 visit, an account will be created for him.


-7-
As usual, activate this Module in the Modules Administration.
You need to Export users in the admin at least one time (step 6), to activate this module.

-------------------
Block Configuration
-------------------

This is a standard block, so you won't have many problems installing it, but you need to activate the G2 module first.
Please, be aware the Block is still not working very well, so don't install it on a visited website !
 

----------------------------------------------------
http://gallery.sourceforge.net
http://www.phpnuke-web.com drumicube@phpnuke-web.com
http://www.nukedgallery.net dari@nukedgallery.net
----------------------------------------------------