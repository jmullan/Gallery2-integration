<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
class HTML_content {
	
    function showSettings($option, $params, $act, $g2_Config, $task) {
	require_once("../components/com_gallery2/userfuncs.php" );
	require_once("../components/com_gallery2/version.php" );	
	?>
<table cellpadding="4" cellspacing="0" border="0" width="100%">
        <tr>
            <td width="228"><a target="_blank" href="http://gallery.sourceforge.net/"><img src="components/com_gallery2/images/logo-228x67.png" border="0" width="228" height="67" align="middle" /></a></td><td align="left" class="sectionname" style="margin-left: 10px;">Gallery Component Settings</td>
        </tr>
</table>
<script language="javascript" src="js/dhtml.js"></script>
<script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            submitform(pressbutton);
        }
</script>
<div align="left" class="sectionname"><p>
</p></div>
<form action="index2.php" method="post" name="adminForm">
    
  <table cellpadding="2" cellspacing="4" border="0" width="100%" class="adminform">
  <tr><th>Config settings:</th><th></th><th></th></tr>
    <tr>
      <td width="140" valign="top">Full Path to Gallery G2:</td>
        <td valign="top"><input class="inputbox" type="text" name="g2_path" size="50" value="<?php echo $g2_Config['path']; ?>"></td>
        <td class="error" valign="top">Full server path to Gallery G2</td>
      </tr>
      <tr>
        
      <td width="140" valign="top">Relative path to Gallery G2:</td>
        <td valign="top"><input class="inputbox" type="text" name="g2_relativeG2Path" size="50" value="<?php echo $g2_Config['relativeG2Path']; ?>"></td>
        <td class="error" valign="top">Relative path to Gallery G2. (ex: ../gallery2 or ./gallery2 )
        </td>
      </tr>
	  <tr>
        
      <td width="140" valign="top">Path to Mambo:</td>
        <td valign="top"><input class="inputbox" type="text" name="g2_embedPath" size="50" value="<?php echo $g2_Config['embedPath']; ?>"></td>
        <td class="error" valign="top">Path to your Mambo installation (ex: / or /mambo )</td>
      </tr>
	  <tr>
        
      <td width="140" valign="top">embedUri:</td>
        <td valign="top"><input class="inputbox" type="text" name="g2_embedUri" readonly="true" size="50" value="<?php echo $g2_Config['embedUri']; ?>"></td>
        <td class="error" valign="top">This is generated, can be adjusted. If it is wrong adjust the path to mambo and Relative pat to gallery2.</td>
      </tr>
      <tr>
        
      <td width="140" valign="top">Login page redirect:</td>
        <td valign="top"><input class="inputbox" type="text" name="g2_loginredirect" size="50" value="<?php echo $g2_Config['loginredirect']; ?>"></td>
        <td class="error" valign="top">Where to redirect to if the user has no access. (ex: / or /index.php)</td>
      </tr>
      <tr>
        
      <td width="140" valign="top">Display G2 Sidebar:</td>
        <td valign="top"><?php echo $params['displaysidebar']; ?></td>
        <td class="error" valign="top">Display G2's sidebar in the main content area? Hiding right-hand modules gives Gallery more room.</td>
      </tr>
      <tr>
        
      <td width="140" valign="top">Display G2 login:</td>
        <td valign="top"><?php echo $params['displaylogin']; ?></td>
        <td class="error" valign="top">Display G2's login?</td>
      </tr>
      <tr>
        
      <td width="140" valign="top">Use mirrored user logins:</td>
        <td valign="top"><?php echo $params['mirrorUsers']; ?></td>
        <td class="error" valign="top">Mirrors Mambo user list in Gallery 2 as required. Turning off the "Display G2's Login" and "Display G2's Sidebar" options is recommended if this feature is used.</td>
      </tr>
      <tr>
        
      <td width="140" valign="top">Run user setup script:</td>
        <td valign="top"><?php echo $params['userSetup']; ?></td>
        <td class="error" valign="top">You should run the user setup if you want to use the above option. The user you are currently logged in as will be set as the G2 admin, so make sure you're logged in as the right person!</td>
      </tr>
      <tr>
        <td width="80" valign="top">Enable auto-album creation:</td>
        <td valign="top"><?php echo $params['enableAlbumCreation']; ?></td>
        <td class="error" valign="top">Enables automatic album creation for registered users. Has no effect if the "Mirror user logins" option is not enabled.</td>
      </tr>
	  <tr>
        <td width="80" valign="top">Where to make the user albums:</td>
        <td valign="top"><input class="inputbox" type="text" name="g2_rootuseralbum" size="4" value="<?php echo $g2_Config['rootuseralbum']; ?>"></td>
        <td class="error" valign="top">Album id where the user albums will be made, when using above option.</td>	  	
	  </tr>
    </table>
	<?php 
	$report = G2helperclass::g2_pathcheck("path", $g2_Config['path']);
	if(array_key_exists('path', $g2_Config) AND array_key_exists('version', $g2_Config) AND $report[1]){
	G2helperclass::embed($g2_Config);
	$ret =  G2helperclass::init_G2($my->id, 'true');
	?>
	<table class="adminform">
		<tr>
			<th width="25%">Info:</th><th></th>
		</tr>
		<tr>
			<td>Gallery Core:</td>
			<td><?php print G2helperclass::g2_version(); ?></td>
		</tr>
		<tr>
			<td>Component version:</td>
			<td><?php print $g2_Config['version']; ?></td>
		</tr>
		<tr>
			<td>Warnings:</td>
			<td>
			<?php
			global $mosConfig_sef, $mosConfig_locale, $mosConfig_lang;
				if($mosConfig_sef == 1){
					print 'SEF is enabled in mambo, this is not yet supported in this component!<br />';
				}
				if(version_compare(G2helperclass::g2_version(), $version['g2_min']) < 0) {
					print '<tr><td>'.$version['g2_min'].'</td><td><font color="#FF0000">Please update your G2!</font></td></tr>';
				}
			?>	
			</td>
		</tr>
	</table>
	<table class="adminform">
		<tr>
			<th width="25%">Language settings:</th>
			<th width="25%">Mambo:</th>
			<th>G2:</th>
		</tr>
		<tr>
			<td>default character set</td>
			<td><?php print $mosConfig_locale; ?></td>
			<td><?php print $g2_Config['default.language']; ?></td>
		</tr>
		<tr>
			<td>Multi language support:</td>
			<td>??</td>
			<td><?php print $g2_Config['languages']; ?></td>
		</tr>
	</table>
	<?php } ?>
  <input type="hidden" name="option" value="<?php echo $option; ?>">
  <input type="hidden" name="act" value="<?php echo $act; ?>">
  <input type="hidden" name="task" value="">
</form>
<?php
    }
		//userlist
	function showUsers( &$rows, $pageNav, $search, $option, $lists, $param ) {
		global $mosConfig_offset;
		//start G2 embed
		require_once("../components/com_gallery2/userfuncs.php" );
		$g2_Config = G2helperclass::g2_Config();
		G2helperclass::embed($g2_Config);
		$ret =  G2helperclass::init_G2($my->id, 'true');

		?>
		<form action="index2.php" method="post" name="adminForm">

		<table class="adminheading">
		<tr>
			<th class="user">
			User Manager Gallery 2
			</th>
			<td>
			Filter:
			</td>
			<td>
			<input type="text" name="search" value="<?php echo $search;?>" class="inputbox" onChange="document.adminForm.submit();" />
			</td>
			<!--
			<td width="right">
			<?php echo $lists['type'];?>
			</td>
			<td width="right">
			<?php echo $lists['logged'];?>
			</td>-->
		</tr>
		</table>

		<table class="adminlist">
		<tr>
			<th width="2%" class="title">
			#
			</th>
			<!--
			<th width="3%" class="title">
			<input type="checkbox" name="toggle" value="" onClick="checkAll(<?php echo count($rows); ?>);" />
			</th>
			-->
			<th class="title">
			Name
			</th>
			<!--
			<th width="5%" class="title" nowrap="nowrap">
			Logged In
			</th>-->
			<th width="5%" class="title">
			Enabled
			</th>
			<th width="15%" class="title" >
			UserID
			</th>
			<th width="15%" class="title">
			Group
			</th>
			<th width="5%" class="title">
			G2 user check
			</th>
			<th width="25%" class="title">
			Error Message
			</th>
		</tr>
		<?php
		$k = 0;
		for ($i=0, $n=count( $rows ); $i < $n; $i++) {
			$row 	=& $rows[$i];
			unset($error_msg);
			$img 	= $row->block ? 'publish_x.png' : 'tick.png';
			$task 	= $row->block ? 'unblock' : 'block';
			$alt 	= $row->block ? 'Enabled' : 'Blocked';
			$link 	= 'index2.php?option=com_gallery2&amp;act=user&amp;task=user_edit&amp;id='. $row->id. '&amp;hidemainmenu=1';
			?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
				<?php echo $i+1+$pageNav->limitstart;?>
				</td>
				<!--<td>
				<?php echo mosHTML::idBox( $i, $row->id ); ?>
				</td>-->
				<td>
				<a href="<?php echo $link; ?>">
				<?php echo $row->name; ?>
				</a>
				</td>
				<!--
				<td align="center">
				<?php echo $row->loggedin ? '<img src="images/tick.png" width="12" height="12" border="0" alt="" />': ''; ?>
				</td>-->
				<td>
				<img src="images/<?php echo $img;?>" width="12" height="12" border="0" alt="<?php echo $alt; ?>" />
				</td>
				<td>
				<?php echo $row->username; ?>
				</td>
				<td>
				<?php echo $row->groupname; ?>
				</td>
				<td align="center">
				<?php
					$ret = GalleryEmbed::isExternalIdMapped($row->id, 'GalleryUser');
					if (!$ret->_errorCode) {
						//if external id found
						//check if all is in sync? todo
						unset($flag);
						list( $ret , $gUser) = GalleryCoreApi::loadEntityByExternalId($row->id, 'GalleryUser');
						if($gUser->getuserName() != $row->username ){ $flag = true; $error_msg[]='Username';}
						if($gUser->getfullName() != $row->name){ $flag = true; $error_msg[]='Fullname';}
						if($gUser->getemail() != $row->email){ $flag = true; $error_msg[]='email';}
						if($gUser->gethashedPassword() != $row->password){
						 	//check if user is blocked
							if($row->block == 1){
								print '<img src="/administrator/images/disabled.png"> / ';
								$error_msg[]='User is blocked!';
								$flag = false;
							} else {
								$flag = true;
								$error_msg[]='password';
							}
						 }
						if(!$flag){
							echo '<img src="/administrator/images/tick.png">';
						} else {
							echo '<img src="/administrator/images/publish_x.png">';
						}
						
					}else{
						$gUser = GalleryCoreApi::fetchUserByUsername ($row_username->user_login);
						if (!$gUser[0]->_errorCode) {
							//Collision i think
							echo '<img src="/administrator/images/disable.png">';
						}else{
							//if no match and no external id
							$error_msg[]='User doesn\'t exist in G2';
							echo '<img src="/administrator/images/publish_x.png">';
						}
					}
				?>
				</td>
				<td align="left">
				<?php 
				if(count($error_msg) > 0){
					print implode(", " ,$error_msg);
				}
				 ?>
				</td>
				</tr>
			<?php
			$k = 1 - $k;
		}
		?>
		</table>
		<?php echo $pageNav->getListFooter(); ?>

		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="act" value="user" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="0" />
		</form>
		<?php
	}
	function showTools($option, $act, $task){
	//heading
	?>
	<table class="adminheading">
		<tr>
			<th class="user">
			Tools Manager Gallery 2
			</th>
		</tr>
	</table>
	
<table width="100%" border="0" cellpadding="4">
  <tr> 
    <td><a href="index2.php?option=com_gallery2&act=tools&task=sync_users">Sync 
      Users</a></td>
    <td>This will at new user to the G2 and update existing users(blocked users 
      are not done!), Do this first or Sync group will fail!</td>
  </tr>
  <tr> 
    <td><a href="index2.php?option=com_gallery2&act=tools&task=sync_group">Sync 
      Groups</a></td>
    <td>This will copy all Mambo Groups to Gallery2 and place the users into the 
      group. </td>
  </tr>
  <tr> 
    <td><a href="index2.php?option=com_gallery2&act=tools&task=sync_group_remove">Remove 
      Groups</a></td>
    <td>This will remove all Mambo Groups from Gallery2</td>
  </tr>
</table>
	<?php
	}
	
//clean this function up and check html<<check<<	
function showUserDetails( $option, $act, $task, $userId, $groupids, $count_total, $count_album, $album_ids, $groupswitch,  $g2_id ){
	?>
	<table class="adminheading">
		<tr>
			<th class="user">
			User Details Gallery 2
			</th>
		</tr>
	</table>
<form action="index2.php" method="post" name="adminForm">
<table align="left">
  <tr> 
    <td>
	 
        <table width="100%" border="0" cellpadding="4" class="adminform">
		  <tr><th>Summary:</th><th></th></tr>
          <tr> 
            <td width="180"><strong>Number of albums:</strong></td>
            <td><?php print $count_album ?></td>
          </tr>
          <tr> 
            <td width="180"><strong>Number of Photo's:</strong></td>
            <td><?php print ($count_total - $count_album) ?></td>
          </tr>
        </table>
      </td>
  </tr>
  <tr> 
    <td>
          <table width="100%" border="0" cellpadding="4" class="adminform">
            <tr>
				<th width="200px" align="left">groupname:</th>
					<th align="left">member:</th>
			</tr>
            <?php
  foreach ($groupids as $id => $name) {
 		print  '<tr><td>'.$name.'</td><td>'.$groupswitch[$name].'</td></tr>';
	}
?>
            <!--<tr>
<td><input name="" type="button" value="remove from group"></td>
</tr>-->
          </table>
        </div>
   		</td>
  </tr>
  <?php if($count_album > 0){  ?>
  <tr> 
    <td>
        <table width="100%" border="0" cellpadding="4" class="adminform">
		<tr><th>User albums:</th><th></th></tr>
          <?php
	 	
	 list ($ret, $items) = GalleryCoreApi::loadEntitiesById($album_ids);
	 foreach ($items as $item){
		$title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
		$titles[$item->getId()] = preg_replace('/\r\n/', ' ', $title);
		$itemId = $item->getId();
		$discription = $item->getdescription();
	list ($ret, $viewed) = GalleryCoreApi::fetchItemViewCount($itemId);

		if(empty($discription)){ $discription = 'empty!';}
		?>
          <tr> 
            <td width="150" nowrap><strong>Name:</strong><?php print $titles[$itemId]; ?></td>
			<td><a href="index2.php?option=com_gallery2&act=album&task=album_spec&amp;albumId=<?php print $itemId; ?>">View Album Details</a></td>
          </tr>
          <!--<tr>
		  <td><strong>ParentId:</strong><?php print $item->getparentId(); ?></td>
            <td nowrap><strong>Created:</strong><?php print date("j-m-Y", $item->getcreationTimestamp()) ; ?></td>
            <td colspan="2" nowrap><strong>Last Modified:</strong><?php print date("j-m-Y", $item->getmodificationTimestamp()) ; ?></td>
          </tr>
          <tr> 
            <td nowrap><strong>View count:<?php print $viewed; ?></strong></td>
            <td colspan="2" nowrap><strong>Since:</strong><?php print date("j-m-Y", $item->getviewedSinceTimestamp( )) ; ?></td>
          </tr>
          <tr> 
            <td colspan="3" nowrap><strong>Key words:</strong><?php print $item->getkeywords(); ?></td>
          </tr>
          <tr> 
            <td colspan="3"><strong>Summary:</strong><?php print $item->getsummary(); ?></td>
          </tr>
          <tr> 
            <td colspan="3"><strong>Description:</strong><?php print $discription; ?></td>
          </tr>|-->
          <tr> 
            <td colspan="3"><hr></td>
          </tr>
          <?php	 
		 }
	?>
        </table>
		
     </td>
  </tr>
  <?php } ?>
</table>
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="act" value="user" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="g2_id" value="<?php echo $g2_id;?>" />
		<input type="hidden" name="user_id" value="<?php echo $userId;?>" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="1" />
		
		</form>
<?php
	}
//album details
function showAlbum( $option, $act, $task, $albumId, $details){
global $mosConfig_live_site;
$user_id = $details['mamboid'];
$link 	= 'index2.php?option=com_gallery2&amp;act=user&amp;task=user_edit&amp;id='. $user_id. '&amp;hidemainmenu=1';
?>
<form action="index2.php" method="post" name="adminForm">
	<table class="adminheading">
		<tr>
    		<th class="categories"><div align="left">Album Details for "<?php print $details['title']; ?>"</div></th>
		</tr>
	</table>
	<table width="100%">
			<tr>
				<td width="100%" valign="top">
					<table class="adminform" align="left">
						<tr> 
						  <th colspan="2"> General Info </th>
						  <th></th>
						  <th></th>
						</tr>
						<tr> 
						  <td width="15%" align="left"> <strong>Title:</strong> </td>
						  <td width="45%" align="left"><input name="title" type="text" size="45" class="inputbox" maxlength="125" value="<?php print $details['title']; ?>"></td>
						  <td width"10%" align="left"><strong>Owner:</strong></td>
						  <td width"30%" align="left"><a href="<?php echo $link; ?>"><?php echo $details['mamboname']; ?></a></td>
						</tr>
						<tr> 
						  <td align="left"> <strong>Summary:</strong> </td>
						  <td align="left"><input name="summary" type="text" size="45" class="inputbox" maxlength="250" value="<?php print $details['summary']; ?>"></td>
						</tr>
						<tr> 
						  <td valign="top" align="left"> <strong>Description:</strong></td>
						  <td><textarea name="description" cols="43" class="inputbox" rows="4"><?php print $details['description']; ?></textarea></td>
						</tr>
						<tr> 
						  <td valign="top" align="left"> <strong>Keywords:</strong></td>
						  <td><input name="keywords" type="text" size="45" class="inputbox" maxlength="250" value="<?php print $details['keywords']; ?>"></td>
						  <td valign="top" align="left"><strong>Viewed:</strong></td>
						  <td><?php print $details['views']; ?> </td>
						</tr>
						<tr> 
						  <td valign="top" align="left"> <strong>Creation date:</strong></td>
						  <td> <?php print $details['creationDate']; ?></td>
						  <td valign="top" align="left"><strong>Since:</strong></td>
						  <td><?php print $details['viewedsince']; ?> </td>
						</tr>
						<tr> 
						  <td valign="top" align="left"> <strong>Last Modified:</strong></td>
						  <td> <?php print $details['lastmodified']; ?></td>
						  <td valign="top" align="left"><strong>Parent Album:</strong></td>
						  <td><?php
						  if($details['parentid'] == 0){ ?>
							No Parent.
						  <?php } else { ?>
							 <a href="index2.php?option=com_gallery2&act=album&task=album_spec&albumId=<?php print $details['parentid']; ?>"><?php print $details['parentname']; ?></a>
						  <?php } ?>
						  </td>
						</tr>
					</table>
				</td>
			<td width="160" valign="top">
				<table class="adminform">
				<tr>
					<th colspan="1">
					Album Highlight
					</th>
				</tr>
				<tr>
					
            <td align="center"> 
              <?php if($details['parentid'] == 0){ ?><a target="_blank" href="http://gallery.sourceforge.net/"> 
              <img src="components/com_gallery2/images/logo-228x67.png" border="0" width="150" height="67" align="middle" /></a>
              <?php } else { ?>
              <img src="<?php print $mosConfig_live_site.'/gallery2/main.php?g2_view=core.DownloadItem&'.$details['thumbid'] ;?>"/> 
              <?php } ?>
            </td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td width="75%">
				<table class="adminform">
					<tr>
						<th colspan="1">Child Details</th>
						<th></th><th></th><th></th>
					</tr>
					<?php if(count($details['childalbumids'])>0){ ?>
					<tr>
					<td width="15%">Child Albums:</td>
					<td><?php 
					foreach($details['childalbumids'] as $album){
						$array[] = '<a href="index2.php?option=com_gallery2&act=album&task=album_spec&albumId='.$album.'">'.$details['childname'][$album].'</a>';
					}
					print implode(", ", $array);
					?>
					</td>
					</tr>
					<?php }
					if((count($details['childids'])-count($details['childalbumids']))>0){ ?>
					<tr>
						<td width="15%">Contains none album childs:</td>
						<td><?php print count($details['childids']) - count($details['childalbumids']); ?></td>
					</tr>
					<?php } ?>
				</table>	
			</td>
		</tr>
		</table>
		<input type="hidden" name="return" value="<?php echo $albumId;?>" />
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="act" value="album" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="hidemainmenu" value="1" />
		</form>
<?php
}//end show album
function showAlbumTree( $option, $act, $task, $tree, $titles, $keywords, $summary, $description, $childs, $last_modified){
	?>
	<table class="adminheading">
		<tr>
    		<th class="categories"><div align="left">Album Tree</div></th>
		</tr>
	</table>
	<table class="adminform">
		<tr> 
			<th colspan="1" width="5%">Id:</th>
			<th colspan="1">Album Title:</th>
			<th colspan="1">Album notes:</th>
			<th colspan="1">Last Modified:</th>
		</tr>
	<?php
		//first a function
		function getthekeys($parent, $depth){
			foreach($parent as $key => $val){
				$keys[$key]=$depth;
				if(count($val) > 0){
					$depth++;
					$back = getthekeys($val, $depth);
					foreach($back as $key => $val){
						$keys[$key]=$val;
					}
				}
			}
			
			return $keys;
		}//end function
	
		foreach($tree as $key => $val){
			unset($keys);
			$keys[$key]=0;//parent
			//check variables
			//let's check the childeren
			if(count($val) > 0){
				$childeren = getthekeys($val, 1);
				foreach($childeren as $key => $val){
					$keys[$key]=$val;//parent
				}
			}
			//foreach key in the right order, gogogo
			foreach($keys as $key => $val){
				unset($error);
				unset($print);
				
				//error checking
				if(!$keywords[$key]){ $error[] = 'keywords missing'; }
				if(!$summary[$key]){ $error[] = 'Summary missing'; }
				if(!$description[$key]){ $error[] = 'Description missing'; }
				if(count($childs[$key]) < 1){ $error[] = 'Empty Album'; }
				//output
				print '<tr>';
				print '<td>'.$key.'</td>';
				print '<td>'.str_repeat("&nbsp;", $val * 3).'<a href="index2.php?option=com_gallery2&act=album&task=album_spec&albumId='.$key.'">'.$titles[$key].'</a></td>';
				if(count($error)>0){
					print '<td>'.implode(", ", $error).'</td><td>'.$last_modified[$key].'</td>';
				} else {
					print '<td></td><td>'.$last_modified[$key].'</td>';
				}
				print '</tr>';
			}
		}
	?>
	</table>
<?php	
}//end showAlbumTree

function showHelp( $option, $act, $task, $anchor ){
?>
<table width="100%%" border="0" cellpadding="4">
  <td colspan="2" align="left" valign="bottom"> <span class="smallgrey"><strong>Credits:</strong></span><br /> 
    <span class="smallgrey">To all people of the mambo+G2 community!<br />
    Special thank's for testing &amp; good suggestions to:<br />
    Aravot, Simon major, Doctorjc and Darren Martz</span><br /> &nbsp;<br /> <span class="smallgrey"><strong>Contact:</strong></span><br /> 
    <a href="http://gallery.menalto.com/index.php?name=PNphpBB2&file=viewforum&f=23" class="smallgrey">G2 
    integration<span class="smallgrey"></span></a><br /> &nbsp;<br /> <span class="smallgrey"><strong>Version:</strong></span><br /> 
    <span class="smallgrey">2.0.6</span><br /> </td>
  <tr> 
    <td><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        Support this component <br />
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHNwYJKoZIhvcNAQcEoIIHKDCCByQCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYC5WzoTbpf1xghYMPf4PQO7ACN48QhIctObNaBSLzsLQDSQecKKBDwsol6fki9YO7a00FSpcUc2bY77Y0gUrUVLBkF3SBAmQtCGh5Sp46sKdAS4kQDAxsZpSH4mYqt9ahSxI/6v8+2zregbSdLAgthuWzSrr5cZq665eZuBlW02EjELMAkGBSsOAwIaBQAwgbQGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIP05SIlNWYkGAgZCyQLDFaO/5zm4nnvniu0xkk9x+0YIGK9LLsJzBGXJ37K8JwXlHGRlwpH3M+DJBD6tsk1AxPBb4SHI/nez+q6CF8Yp1fTlSYU0VWQXD9vSfWih4xfNWl6E6InJ78+N1h+8Cz34fRUEOtY0R4zUw4Zb0ceZfZTogLsrTkrSS/p2KVoTJPRPf9slUTRfeKh8NYCGgggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0wNTA3MTgwNzM2MjZaMCMGCSqGSIb3DQEJBDEWBBSbKg4jM7Ws0Ln+iSc0H6D2pFPW4DANBgkqhkiG9w0BAQEFAASBgCKuw7BXiPxPnX6a6XZ/NuJqOzEDqf7eiQ/nF5TEGzbcdBDKizm9czkc7EtPTFDUXu8f84w7i1RKcbkRkClqkxGW+m0qM1de/VbGXZf8XI1Eesxs7rF0rE0i+3e5D2O1LI5+RrRrKRdd096kq6zdFMQkbZBmU2cue35Qx95+Wxys-----END PKCS7-----
">
      </form></td>
  </tr>
</table>
<?php
}//end show help
}//end class
?>