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
/* Version: 0.2 (13th february 2005)                                     */
/*                                                                      */
/************************************************************************/

Description
-----------

This package is a beta release, DO NOT USE IT IN PRODUCTION CONTEXT !

This module allow you to embed Gallery2 into PHPNUKE 7.5, it is still in heavy development and only proposed minor features:

 - A basic Administration panels which allow you to configure this module.
 - A Gallery2 entry in the main phpnuke menu to browse your gallery2 into phpnuke.
 - A Simple Block which display the gallery2 left menu 



Installation
------------

The zip archive contains 3 folders: 

		modules/  it contains the gallery2 module itself 
		blocks/   it contains the G2 menu block
		images/   it only contains an icon which will be displayed by main Admin panel.
		
		
Just upload each folder to those matching pour PhpNuke CMS installation.
In most case (depending of your installation), just copy the html folder to the root of your phpnuke installation.


Configuration
-------------

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
It will also activate the module by setting the activate flag in the cfg file to true.

-6-
The show sidebar checkbox allows you to remove the leftsidebar from the main Gallery2 display.
Instead you can display it inside a standard phpnuke block. (install the G2_Sidebar block to get it)

-6-
The Export Phpnuke users to Gallery 2 button, will export all your phpnuke to G2.
No group mapping is done for now... It will come up for a next release.
And yes, for now there is no hook allowing to add a new user dynamically...
So you will need to perform this action each time a new user register your phpnuke website.

-6-
As usual, activate this Module in the Modules Administration.

-7-
You can also active the G2_sidebar block, this is also a standard installation.




----------------------------------------------------
http://gallery.sourceforge.net
http://www.phpnuke-web.com drumicube@phpnuke-web.com
----------------------------------------------------
