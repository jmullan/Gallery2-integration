<?php
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
require_once($mainframe->getPath('toolbar_default'));

switch ($act) {

	case 'user':
		switch ($task){
			case 'user_edit':
			mosMenuBar::startTable();
			mosMenuBar::save();
			mosMenuBar::back();
			mosMenuBar::spacer();
			mosMenuBar::endTable();
			break;
			case 'save':
			mosMenuBar::startTable();
			mosMenuBar::back();
			mosMenuBar::spacer();
			mosMenuBar::endTable();
			break;
			default:
			mosMenuBar::startTable();
			mosMenuBar::custom('sync_users', 'reload.png', 'reload_f2.png', 'Sync users', false);
			mosMenuBar::back();
			mosMenuBar::spacer();
			mosMenuBar::endTable();
			break;
		}
		break;
	case 'tools':
		mosMenuBar::startTable();
		mosMenuBar::back();
		mosMenuBar::spacer();
		mosMenuBar::endTable();
		break;
	case 'album':
		switch($task){
			case 'album_spec':
				mosMenuBar::startTable();
				mosMenuBar::save();
				mosMenuBar::back();
				mosMenuBar::spacer();
				mosMenuBar::endTable();
			break;
			default:
				mosMenuBar::startTable();
				mosMenuBar::back();
				mosMenuBar::spacer();
				mosMenuBar::endTable();
			break;
		}
		break;
	case 'help':
		
		break;
	default:
		mosMenuBar::startTable();
		mosMenuBar::save();
		mosMenuBar::back();
		mosMenuBar::spacer();
		mosMenuBar::endTable();
		break;
}
?>