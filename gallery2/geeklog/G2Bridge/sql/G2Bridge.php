<?php
// +---------------------------------------------------------------------------+
// | G2Bridge Plugin formerly GL_Gallery2                                      |
// +---------------------------------------------------------------------------+
// | G2Bridge.php                                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2005 Andy Maloney [asmaloney@users.sf.net]                  |
// | Adapted for Gallery 2.1 by Wayne Patterson [suprsidr@gmail.com]           |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

$_SQL[] = "
CREATE TABLE IF NOT EXISTS {$_TABLES['G2B_vars']} (
  name varchar(14) NOT NULL default '',
  `value` varchar(32) default '',
  PRIMARY KEY  (name)
)";

// visibility of control block
$_DATA[] = "INSERT IGNORE INTO {$_TABLES['G2B_vars']} VALUES ( 'control_block', 'gallery2' )";

?>