<!--
/*
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2006 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
/**
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */
 -->

<h1>{L_SYNC_TITLE}</h1>

<P>{L_SYNC_EXPLAIN}</P>

<center>
<form action="{S_G2_ACTION}" method="post">
	{L_SYNC_USER_LIST}
	<p>&nbsp;</p>
	<p><input type="radio" name="export" value="now" />Export Users Now&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="export" value="later" checked="checked" />Export User At Gallery 2 Access</p>
	<p>&nbsp;</p>
	<input type="submit" value="{L_SYNC}" />
</form>
</center>