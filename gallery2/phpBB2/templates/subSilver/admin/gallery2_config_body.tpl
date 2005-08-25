<!--
/*
 * $RCSfile$
 *
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2005 Bharat Mediratta
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
/**
 * Gallery 2 integration for phpBB2.
 * @version $Revision$ $Date$
 * @author Dariush Molavi <dari@nukedgallery.net>
 */
 -->

<h1>{L_CONFIG_TITLE}</h1>

<P><b><font color="red">{L_CONFIG_EXPLAIN}</font></b></p>

<form method="post" action="{S_G2_ACTION}"><table cellspacing="1" cellpadding="4" border="0" align="center" class="forumline">
	<tr>
		<td align="center" class="row1">{L_FULLPATH}</td>
		<td align="left" class="row2"><input type="text" name="fullpath" size="60" maxlength="255" value="{S_FULLPATH}"  /></td>
	</tr>
	<tr>
		<td align="center" class="row1">{L_EMBEDURI}</td>
		<td align="left" class="row2"><input type="text" name="embeduri" value="{S_EMBEDURI}"  /></td>
	</tr>
	<tr>
		<td align="center" class="row1">{L_EMBEDPATH}</td>
		<td align="left" class="row2"><input type="text" name="embedpath" value="{S_EMBEDPATH}"  /></td>
	</tr>
	<tr>
		<td align="center" class="row1">{L_RELATIVEPATH}</td>
		<td align="left" class="row2"><input type="text" name="relativepath" value="{S_RELATIVEPATH}"  /></td>
	</tr>
	<tr>
		<td align="center" class="row1">{L_LOGINPATH}</td>
		<td align="left" class="row2"><input type="text" name="loginpath" value="{S_LOGINPATH}"  /></td>
	</tr>
	<tr>
		<td align="center" class="row1">{L_COOKIEPATH}</td>
		<td align="left" class="row2"><input type="text" name="cookiepath" value="{S_COOKIEPATH}"  /></td>
	</tr>
	<tr>
		<td align="center" class="row1">{L_ACTIVEUSERID}</td>
		<td align="left" class="row2"><input type="text" name="activeuserid" value="{S_ACTIVEUSERID}"  /></td>
	</tr>
	<tr>
		<td align="center" class="catbottom" colspan="2"><input type="submit" name="save" value="{L_SUBMIT}"  /></td>
	</tr>
</table></form>