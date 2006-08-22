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
 * @author Scott Gregory <jettyrat@jettyfishing.com>
 */
 -->

<h1>{L_SYNC_TITLE}</h1>

<p>{L_SYNC_EXPLAIN1}</p>
<p>{L_SYNC_EXPLAIN2}</p>
<!-- BEGIN switch_explain -->
<p>{switch_explain.L_SYNC_EXPLAIN3}</p>
<p>{switch_explain.L_SYNC_EXPLAIN4}</p>
<p>{switch_explain.L_SYNC_EXPLAIN5}</p>
<p>{switch_explain.L_SYNC_EXPLAIN6}</p>
<p>{switch_explain.L_SYNC_EXPLAIN7}</p>
<!-- END switch_explain -->

<center>
<form action="{S_G2_ACTION}" method="post">
	<table class="forumline" cols="7">
		<tr>
			<th colspan="4" width="60%">{L_SYNC_ACTION}</th>
			<th width="13%">{L_SYNC_USER}</th>
			<th width="13%">{L_SYNC_USERID}</th>
			<th width="13%">{L_SYNC_GROUPS}</th>
		</tr>
		<!-- BEGIN users_existing -->
		<tr>
			<td style="text-align: center;" class="row3" width="15%"><input type="radio" name="user[{users_existing.USER_ID}]" value="1" checked="checked" /><br />{L_SYNC_IMPORT}</td>
			<td style="text-align: center;" class="row3" width="15%"><input type="radio" name="user[{users_existing.USER_ID}]" value="2" /><br />{L_SYNC_DELETEALL}</td>
			<td style="text-align: center;" class="row3" width="15%"><input type="radio" name="user[{users_existing.USER_ID}]" value="3" /><br />{L_SYNC_DELETE}</td>
			<td style="text-align: center;" class="row3" width="15%"><input type="radio" name="user[{users_existing.USER_ID}]" value="4" /><br />{L_SYNC_LEAVE}</td>
			<td style="text-align: center;" class="row3" width="13%">{users_existing.USER_NAME}</td>
			<td style="text-align: center;" class="row3" width="13%">{users_existing.USER_ID}</td>
			<td style="text-align: center;" class="row3" width="13%">{users_existing.USER_GROUPS}</td>
		</tr>
		<!-- END users_existing -->
	</table>
	<p>&nbsp;</p>
	<p><input type="radio" name="export" value="now" />{L_SYNC_NOW}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="export" value="later" checked="checked" />{L_SYNC_LATER}</p>
	<p>&nbsp;</p>
	<input type="submit" value="{L_SYNC}" />
</form>
</center>