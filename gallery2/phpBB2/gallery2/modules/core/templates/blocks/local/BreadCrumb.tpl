{*
 * Custom phpBB2 breadcrumb template.
 * Author: Dariush Molavi (dari@nukedgallery.net)
 *
 * $Revision$
 * $Id$
 *
 *}
{*
 * Go through each breadcrumb and display it as a link.
 *
 * G2 uses the highlight id to figure out which page to draw when you follow the
 * breadcrumbs back up the album tree.  Don't make the last item a link.
 *}
<div class="{$class}">
  <a href="/" class="BreadCrumb-1">Forum Index</a>
  {section name=parent loop=$theme.parents}
  {if !$smarty.section.parent.last}
  <a href="{g->url arg1="view=core.ShowItem" arg2="itemId=`$theme.parents[parent].id`"
		   arg3="highlightId=`$theme.parents[parent.index_next].id`"}">
    {$theme.parents[parent].title|default:$theme.parents[parent].pathComponent|markup:strip}</a>
  {else}
  <a href="{g->url arg1="view=core.ShowItem" arg2="itemId=`$theme.parents[parent].id`"
		   arg3="highlightId=`$theme.item.id`"}">
    {$theme.parents[parent].title|default:$theme.parents[parent].pathComponent|markup:strip}</a>
  {/if}
  {if isset($separator)} {$separator} {/if}
  {/section}

  {if ($theme.pageType == 'admin' || $theme.pageType == 'module')}
  <a href="{g->url arg1="view=core.ShowItem"
		   arg2="itemId=`$theme.item.id`"}">
     {$theme.item.title|default:$theme.item.pathComponent|markup:strip}</a>
  {else}
  <span>
     {$theme.item.title|default:$theme.item.pathComponent|markup:strip}</span>
  {/if}
</div>