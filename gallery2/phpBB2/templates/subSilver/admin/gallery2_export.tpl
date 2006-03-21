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
 * @author Scott Gregory
 */
 -->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head>
<script type="text/javascript">
var saveToGoDisplay = document.getElementById('progressToGo').style.display;
function updateProgressBar(description, percentComplete) {
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
  }
</script>

<style type="text/css">
<!--

 /* General page style. The scroll bar colours only visible in IE5.5+ */
body {
	background-color: #E5E5E5;
	scrollbar-face-color: #DEE3E7;
	scrollbar-highlight-color: #FFFFFF;
	scrollbar-shadow-color: #DEE3E7;
	scrollbar-3dlight-color: #D1D7DC;
	scrollbar-arrow-color:  #006699;
	scrollbar-track-color: #EFEFEF;
	scrollbar-darkshadow-color: #98AAB1;
}

/* General font families for common tags */
font,p { font-family: Verdana, Arial, Helvetica, sans-serif }
p, td		{ font-size : 11; color : #000000; }
a:link,a:active,a:visited { color : #006699; }
a:hover		{ text-decoration: underline; color : #DD6900; }
.gbBlock {
    padding: 0.7em;
    border-width: 0 0 1px 0;
    border-style: inherit;
    border-color: inherit;
    /* IE can't inherit these */
    border-style: expression(parentElement.currentStyle.borderStyle);
    border-color: expression(parentElement.currentStyle.borderColor);
}
h1,h2 { font-family: "Trebuchet MS", Verdana, Arial, Helvetica, sans-serif; font-size : 22px; font-weight : bold; text-decoration : none; line-height : 120%; color : #000000;}
#ProgressBar #progressDone {
    background-color: #fd6704;
    border: thin solid #ddd;
}

#ProgressBar #progressToGo {
    background-color: #eee;
    border: thin solid #ddd;
}
#gallery h2, #gallery h3, #gallery h4 {
    font-family: "Trebuchet MS", Arial, Verdana, Helvetica, sans-serif;
}
.giTitle, #gallery h2, #gallery h3, #gallery h4 {
    font-size: 1.3em;
    font-weight: bold;
}
#gallery .gbBlock h3 {
    margin-bottom: 0.5em;
}
@import url("../templates/subSilver/formIE.css");
-->
</style>
</head><body>
<h1>Exporting Users</h1><br />
<div id="ProgressBar" class="gbBlock">
  <p id="progressDescription">
    &nbsp;
  </p>

  <table width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td id="progressDone" style="display: inline-block; width:0%">&nbsp;</td>
      <td id="progressToGo" style="display: inline-block; width:100%; border-left: none">&nbsp;</td>
    </tr>
  </table>
</div>
