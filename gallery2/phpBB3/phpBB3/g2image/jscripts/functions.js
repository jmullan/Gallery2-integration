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
 * Gallery 2 integration for phpBB3.
 * @version $Revision$ $Date$
 * @author Scott Gregory <jettyrat@jettyfishing.com>
 */

function insertAtCursor(myField, myValue) {
	//IE support
	if (document.selection && !window.opera) {
		myField.focus();
		sel = window.opener.document.selection.createRange();
		sel.text = myValue;
	}
	//MOZILLA/NETSCAPE/OPERA support
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		myField.value = myField.value.substring(0, startPos)
		+ myValue
		+ myField.value.substring(endPos, myField.value.length);
	} else {
		myField.value += myValue;
	}
}

function insertDefaults(){
	imgs = document.getElementsByTagName('img');
	for (var i = 0; i < imgs.length; i++) {
		imgs[i].onclick = function(){insertImage(this.parentNode.getElementsByTagName("form")[0])}
	}
}

function showFileNames(){
	divs = document.getElementsByTagName('div');
	for (var i = 0; i < divs.length; i++) {
		if (divs[i].className == 'bordered_imageblock'){
			forms = divs[i].getElementsByTagName('form');
			for (var j = 0; j < forms.length; j++) {
				if (forms[j].className == 'displayed_form_thumbnail')
					forms[j].className = 'hidden_form';
				else if (forms[j].className == 'displayed_form')
					forms[j].className = 'hidden_form';
			}
		}
		else if (divs[i].className == 'transparent_imageblock')
			divs[i].className = 'bordered_imageblock';
		else if (divs[i].className == 'hidden_title')
			divs[i].className = 'displayed_title';
		else if (divs[i].className == 'inactive_placeholder')
			divs[i].className = 'active_placeholder';
	}
}

function showThumbnails(){
	divs = document.getElementsByTagName('div');
	for (var i = 0; i < divs.length; i++) {
		if (divs[i].className == 'bordered_imageblock'){
			divs[i].className = 'transparent_imageblock';
			forms = divs[i].getElementsByTagName('form');
			for (var j = 0; j < forms.length; j++) {
				if (forms[j].className == 'displayed_form')
					forms[j].className = 'hidden_form';
				else if (forms[j].className == 'displayed_form_thumbnail')
					forms[j].className = 'hidden_form';
			}
		}
		else if (divs[i].className == 'displayed_title')
			divs[i].className = 'hidden_title';
		else if (divs[i].className == 'active_placeholder')
			divs[i].className = 'inactive_placeholder';
	}
}

function insertImage(obj) {
	imagehtml=makeHtmlForInsertion(obj);
	g2ic_form=obj.g2ic_form.value;
	g2ic_field=obj.g2ic_field.value;
	insertAtCursor(window.opener.document.forms[g2ic_form].elements[g2ic_field],imagehtml);
	window.close();
}

function makeHtmlForInsertion(obj){
	htmlCode = '[img]http://' + obj.g2ic_host.value + obj.image_url.value + '&g2_view=core.DownloadItem[/img]';
	return htmlCode;
}
