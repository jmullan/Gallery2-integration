This module creates a filter that lets you embed items from your embedded Gallery 2 install. 

Requirements:

* Gallery 2 - http://gallery.menalto.com/
* gallery.module - http://drupal.org/project/gallery

Syntax:

  [G2:item_id class=name size=number frame=name]


* item_id (required) *
This is the item ID from G2. If you look at the URL of the item, this is the last number.

* class *
The block that G2 returns is wrapped in a DIV so additional styling can be done. The classes for this DIV are located in g2_filter.css.  Included with the module are "left", "right", and "nowrap". These position the image block to the left or right or on a line all its own with the text not wrapping. You can also add your own class(es) to the CSS file and they will automatically be available.

* size *
The length of the longest side for the thumbnail. The other side is determined automatically to keep the same aspect ratio.

* frame *
G2 comes with several item/album frames and allows you to add more. You can use any of these frames for the embedded thumbnail just by specifying a name. Frames included with the default install are: Bamboo, Book, Branded Wood, Dot Apple, Dots, Flicking, Gold, Gold 2, Polaroid, Polaroids, Shadow, Shells, Slide, Solid, Spiral Notebook, Wood.




Send comments to mcox@charter.net
