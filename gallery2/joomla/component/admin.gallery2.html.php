<?php
/**
 * Backend html file.
 * 
 * @package g2bridge
 * @version $Revision$
 * @copyright Copyright (C) 2005 - 2007 4 The Web. All rights reserved.
 * @license GNU General Public License either version 2 of the License, or (at
 * your option) any later version.
 */

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * Class containing back-end html
 * 
 * @package g2bridge
 * @subpackage core
 */
class HTML_content {
	/**
	 * About page with Link towards pages: project,forum,4 the web
	 * Show Logo and 4 the web logo
	 *
	 * @param object g2bridgeVersion
	 * @todo update
	 */
	function about($version){
		HTML_content::header('About &amp; Help');
		?>
		<table width="90%" border="0" cellpadding="2" cellspacing="2" class="adminlist">
			<tr class="row0">
				<td><img src="components/com_gallery2/images/g2bridge_logo.png" width="200px"></td>
				<td align="left">
					<p>
					<a href="http://trac.4theweb.nl/g2bridge">Project Home:</a> Documentation, bug tracking, roadmap, etc...<br />
					<a href="http://forum.4theweb.nl/forumdisplay.php?f=5">Support Forum:</a> If you got any questions, remarks, wishes or suggestions feel free to post.<br />
					There is documentation accessable through the Help buttons in your Joomla toolbar displayed on every admin page, but more up-to-date versions can be found at the project page.
					</p>
				</td>
			</tr>
			<tr class="row1">
				<td><img src="components/com_gallery2/images/logo-228x67.png" width="200px"></td>
				<td>
					<p>
					<a href="http://gallery.menalto.com">Gallery Menalto</a><br />
					<a href="http://codex.gallery2.org">Documentation</a>
					</p>
				</td>
			</tr>
			<!--
			<tr>
				<td>
				
				</td>
				<td><a href="http://www.4theweb.nl"><img src="components/com_gallery2/images/4theweb.png"></a></td>
			</tr>
			-->
			<tr class="row0">
				<td colspan="2">
				<p>
				<b>Support</b> this project:<br />
				This project is freeware but we spent a lot of time developing and supporting this 
				project. If you enjoy the product, please take a moment and 
				<a href="http://www.4theweb.nl/index.php?option=com_donation&ref=about&Itemid=57">
				make a donation</a> to help 
				support further development and webserver costs for this project! You can also help
				by reviewing/voting on the Joomla! 
				<a href="http://extensions.joomla.org/component/option,com_mtree/task,viewlink/link_id,137/Itemid,35/">
				extension</a> page.<br />
				</p>
				</td>
			</tr>
		</table>
		<?php
		HTML_content::footer();
	}
	
	function configuration($option, $act, $task, $g2bJLC){
	?>
		<script language="javascript" type="text/javascript">
	    function submitbutton(pressbutton) 
	    {
	    	var form = document.adminForm;
	      	if (pressbutton == 'cancel') 
	      	{
	        	submitform( pressbutton );
	        	return;
	      	} 
	      	else 
	      	{
	        	submitform( pressbutton );
	      	}
	    }
	    </script>
	    <?php
	    HTML_content::header('Settings');
	    
	    $g2bJLC->showAdminForm();
	    
	    HTML_content::footer();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $option
	 * @param unknown_type $act
	 * @param unknown_type $lists
	 * @param unknown_type $pageNav
	 * @param unknown_type $search
	 * @param unknown_type $rows
	 * @param unknown_type $errors
	 */
	function userList($option, $act, $lists, $pageNav, $search, $rows, $errors){
		?>
		<form action="index2.php" method="get" name="adminForm">

		<table class="adminheading">
		<tr>
			<th class="g2bridge"> User Manager</th>
			<td> Filter: </td>
			<td> <input type="text" name="search" value="<?php print $search;?>" class="inputbox" onChange="document.adminForm.submit();" /> </td>
			<td width="right"> <?php print $lists['type'];?> </td>
			<td width="right"> <?php print $lists['logged'];?> </td>
		</tr>
		</table>
		
		<table class="adminlist">
		<tr>
			<th width="2%" class="title">#</th>
			<th width="3%" class="title"><input type="checkbox" name="toggle" value="" onClick="checkAll(<?php print count($rows); ?>);" /></th>
			<th class="title">Fullname</th>
			<th width="5%" class="title" nowrap="nowrap">Logged In</th>
			<th width="5%" class="title">Enabled</th>
			<th width="15%" class="title" >Username</th>
			<th width="15%" class="title">Group</th>
			<th width="5%" class="title" nowrap="nowrap">Gallery 2</th>
			<th width="25%" class="title">Message</th>
		</tr>
		<?php
		$k = 0; 
		for ($i=0, $n=sizeof( $rows ); $i < $n; $i++) {
			$row 	=& $rows[$i];
			/* blocked or Enabled */
			$img 	= $row->block ? 'publish_x.png' : 'tick.png';
			$alt 	= $row->block ? 'Enabled' : 'Blocked';
			/* details link */
			$link 	= 'index2.php?option=com_gallery2&amp;act=user&amp;task=edit&amp;uid='. $row->id. '&amp;hidemainmenu=1';
			
			?>
			<tr class="<?php print "row$k"; ?>">
				<td>
					<?php print $i+1+$pageNav->limitstart;?>
				</td>
				<td>
					<?php print mosHTML::idBox( $i, $row->id ); ?>
				</td>
				<td>
					<a href="<?php print $link; ?>"><?php print (!empty($row->name)) ? $row->name : $row->username; ?></a>
				</td>
				<td align="center">
					<?php print $row->loggedin ? '<img src="images/tick.png" width="12" height="12" border="0" alt="" />': ''; ?>
				</td>
				<td>
					<img src="images/<?php print $img;?>" width="12" height="12" border="0" alt="<?php print $alt; ?>" />
				</td>
				<td align="left"><?php print $row->username; ?></td>
				<td><?php print $row->groupname; ?></td>
				<td align="center">
				<?php isset($errors[$row->id]) ? print '<img src="images/disabled.png" width="12" height="12" border="0" alt="failed" />'
											   : print '<img src="images/tick.png" width="12" height="12" border="0" alt="succes" />';?>
				</td>
				<td align="left">
				<?php isset($errors[$row->id]) ? print $errors[$row->id] : print '';?>
				</td>
			</tr>
			<?php
			/* next row */
			$k = 1 - $k;
		}
		?>
		</table>

		<?php print $pageNav->getListFooter(); ?>
		
		<input type="hidden" name="option" value="<?php print $option;?>" />
		<input type="hidden" name="act" value="<?php print $act;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="0" />
		</form>
		<?php
		HTML_content::footer();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $option
	 * @param unknown_type $act
	 * @param unknown_type $userObject
	 * @param unknown_type $list
	 */
	function userEdit($option, $act, $user, $juid, $list){
		
		HTML_content::header('User Details for '.$user->getuserName());
		
		?>
		<form action="index2.php" method="post" name="adminForm">
		<table width="100%" border="0" cellpadding="4" class="adminform">
            <tr>
				<th width="200px" align="left">groupname:</th>
				<th align="left">member:</th>
			</tr>
		<?php
		foreach($list as $name => $html){
			print  '<tr><td>'.$name.'</td><td>'.$html.'</td></tr>'."\n";
		}
		?>
		</table>
		<input type="hidden" name="option" value="<?php print $option;?>" />
		<input type="hidden" name="act" value="<?php print $act;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="uid" value="<?php print $juid;?>" />
		</form>
		<?php
		HTML_content::footer();
	}
	
	/**
	 * Nice error handeling
	 *
	 * @param string html error message
	 * @param string $option
	 * @param string $act
	 * @todo add nice header and link back to several places.
	 */
	function error($msg, $option, $act){
		HTML_content::header('Error');
		
		print "An error occurred, here is the Gallery 2 Error: <br>";
		print $msg;
		
		HTML_content::footer();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $option
	 * @param unknown_type $act
	 * @param unknown_type $task
	 * @param unknown_type $urlToGallery
	 */
	function wizardStepOne($option, $act, $task, $urlToGallery = ''){
		HTML_content::header('Wizard Step 1');
		
		?>
		<form action="index2.php" method="post" name="adminForm">
		<table width="100%" border="0" cellpadding="4" class="adminform">
            <tr>
				<th align="left">URL To Gallery 2</th>
			</tr>
			<tr>
				<td align="left">
					<input type="text" name="urlToGallery" value="<?php print $urlToGallery;?>" class="inputbox" size="50" />
				</td>
			</tr>
			<tr>
				<td>Please provide the URL to your gallery 2 install, something similiar as "http://www.example.com/gallery2/main.php".</td>
			</tr>
		</table>
		<input type="hidden" name="option" value="<?php print $option;?>" />
		<input type="hidden" name="act" value="<?php print $act;?>" />
		<input type="hidden" name="task" value="wizardStepTwo" />
		<input type="hidden" name="hidemainmenu" value="1" />
		</form>
		<?php
		
		HTML_content::footer();	
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $option
	 * @param unknown_type $act
	 * @param unknown_type $task
	 * @param unknown_type $urlToGallery
	 * @param unknown_type $pathToGallery
	 * @param unknown_type $urlScheme
	 */
	function wizardStepTwo($option, $act, $task, $urlToGallery, $pathToGallery){
		HTML_content::header('Wizard Step 2');
		
		?>
		<form action="index2.php" method="post" name="adminForm">
		<table width="100%" border="0" cellpadding="4" class="adminform">
            <tr>
            	<td>
            	Wizard has fetched the following result from your input. <br />
            	Save to continue or back to correct these settings. 
            	</td>
            </tr>
            <tr>
				<td align="left">URL To Gallery 2:</td>
				<td align="left"><?php print $urlToGallery;?></td>
			</tr>
			<tr>
				<td align="left">Server Path to Gallery 2:</td>
				<td align="left"><?php print $pathToGallery;?></td>
			</tr>
		</table>
		<input type="hidden" name="option" value="<?php print $option;?>" />
		<input type="hidden" name="act" value="<?php print $act;?>" />
		<input type="hidden" name="task" value="" />
		</form>
		<?php
		
		HTML_content::footer();	
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $title
	 */
	function header($title){
		?>
		<link rel="stylesheet" href="components/com_gallery2/admin.gallery2.css" type="text/css"/>
		<table class="adminheading">
			<tr>
				<th class="g2bridge">
				<?php print $title; ?>
				</th>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function footer(){
		require_once(_G2BPATH.'/version.php');
		$version = new g2BridgeVersion();
		?>
		<div align="center" class="footer">
			<span class="smallgrey"><?php print $version->getLongVersion(); ?></span>
		</div>
		<?php
	}
	
	/**
	 * Enter description here...
	 *
	 */
	function progressbar(){
		?>
		<!-- template -->
		<link rel="stylesheet" href="components/com_gallery2/admin.gallery2.css" type="text/css"/>
		<div id="ProgressBar" class="gbBlock">
			<h3 id="progressTitle">
				&nbsp;
			</h3>
			
			<p id="progressDescription">
				&nbsp;
			</p>
			
			<table width="100%" cellspacing="0" cellpadding="0">
				<tr>
			      <td id="progressDone">&nbsp;</td>
			      <td id="progressToGo">&nbsp;</td>
			    </tr>
			</table>
	
			<p id="progressTimeRemaining">
			    &nbsp;
			</p>
			
			<p id="progressMemoryInfo" style="position: absolute; top: 0px; right: 15px">
			    &nbsp;
			</p>
			
			<p id="progressErrorInfo" style="display: none">
			
			</p>
			
			<a id="progressContinueLink" style="display: none">
			   Continue...
			</a>
		</div>
		<!-- script -->
		<script type="text/javascript">
			// <![CDATA[
		    var saveToGoDisplay = document.getElementById('progressToGo').style.display;
		    function updateProgressBar(title, description, percentComplete, timeRemaining, memoryInfo) {
			    document.getElementById('progressTitle').innerHTML = title;
			    document.getElementById('progressDescription').innerHTML = description;
			
			    var progressMade = Math.round(percentComplete * 100);
			    var progressToGo = document.getElementById('progressToGo');
			
			    if (progressMade == 100) {
			      progressToGo.style.display = 'none';
			    } else {
			      progressToGo.style.display = saveToGoDisplay;
			      progressToGo.style.width = (100 - progressMade) + "%";
			    }
			
			    document.getElementById('progressDone').style.width = progressMade + "%";
			    document.getElementById('progressTimeRemaining').innerHTML = timeRemaining;
			    document.getElementById('progressMemoryInfo').innerHTML = memoryInfo;
		   }
		
		   function completeProgressBar(url) {
			   var link = document.getElementById('progressContinueLink');
			   link.href = url;
			   link.style.display = 'inline';
		   }
		
		  function errorProgressBar(html) {
			   var errorInfo = document.getElementById('progressErrorInfo');
			   errorInfo.innerHTML = html;
			   errorInfo.style.display = 'block';
		  }
		  // ]]>
		</script>
		<?php
		/* flush buffer */
		ob_flush();
		flush();
	}
}
?>