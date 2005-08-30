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

<h1>{G2_TITLE}</h1>

<form method="post" action="{S_G2_ACTION}"><table cellspacing="1" cellpadding="4" border="0" align="center" class="forumline">
	<tr>
		<th colspan="2" class="thHead" align="center">{G2_ADMIN_TASK}</th>
	</tr>
	<tr>
		<td align="center" class="catBottom">{S_HIDDEN_FIELDS}<input type="submit" name="config" value="{L_CONFIG}" class="mainoption" /></td>
	</tr>
	<tr>
		<td align="center" class="catBottom">{S_HIDDEN_FIELDS}<input type="submit" name="sync_intro" value="{L_SYNC}" class="mainoption" /></td>
	</tr>
	<tr>
		<td align="center" class="catBottom">{S_HIDDEN_FIELDS}<input type="submit" name="gr_sync_intro" value="{L_GR_SYNC}" class="mainoption" /></td>
	</tr>
</table></form>
