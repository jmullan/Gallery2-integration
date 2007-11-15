<?php

define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require($phpbb_root_path . 'common.' . $phpEx);
require($phpbb_root_path . 'includes/functions_display.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

if ($number = intval($_POST['user_number'])) {
	$sql = 'SELECT group_id FROM ' . GROUPS_TABLE . " WHERE group_name = 'REGISTERED' LIMIT 1";
	if (!$row = $db->sql_fetchrow($db->sql_query($sql))) {
		die('fetch registered group id failed');
	}

	$db->sql_transaction();

	for ($i = 0; $i < $number; $i++) {
		$sql = 'INSERT INTO ' . USERS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
			'user_type'	=> USER_NORMAL,
			'group_id' => $row['group_id'],
			'user_permissions' => '',
			'username' => "tester$i",
			'username_clean' => utf8_clean_string("tester$i"),
			'user_password'	=> md5('passWord'),
			'user_email' => 'joeblow@somewhere.net',
			'user_email_hash' => (int) crc32(strtolower("tester$i")) . strlen("tester$i"),
			'user_lastvisit' => time(),
			'user_lastmark' => time(),
			'user_lang'	=> 'en',
			'user_style' => $config['default_style'],
			'user_allow_pm' => 1,
			'user_sig' => '',
			'user_occ' => '',
			'user_interests' => '')
		);

		if (!$db->sql_query($sql)) {
			$db->sql_transaction('rollback');
			die('insert user failed');
		}

		$user_id = $db->sql_nextid();

		$sql = 'INSERT INTO ' . USER_GROUP_TABLE . ' ' . $db->sql_build_array('INSERT', array(
			'user_id' => $user_id,
			'group_id' => $row['group_id'],
			'user_pending' => 0)
		);
		if (!$db->sql_query($sql)) {
			$db->sql_transaction('rollback');
			die('insert user/group failed');
		}
	}

	$db->sql_transaction('commit');

	echo '<p>Done!</p>';
}
elseif ($_POST['remove']) {
	$sql = 'SELECT COUNT(user_id) AS records FROM ' . USERS_TABLE . " WHERE username LIKE 'tester%'";
	$result = $db->sql_query($sql);
	$records_count = (int) $db->sql_fetchfield('records');

	if ($records_count > 0) {
		$sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username LIKE 'tester%'";
		if (!$result = $db->sql_query($sql)) {
			die('fetch tester users failed');
		}

		$user_id = array();

		while($row = $db->sql_fetchrow($result)) {
			$user_id[] = $row['user_id'];
		}

		$user_id = implode(', ', $user_id);

		$sql = 'DELETE FROM ' . USERS_TABLE . " WHERE user_id IN ($user_id)";
		if (!$db->sql_query($sql)) {
			die('delete tester users failed');
		}

		$sql = 'DELETE FROM ' . USER_GROUP_TABLE . " WHERE user_id IN ($user_id)";
		if (!$db->sql_query($sql)) {
			die('delete tester users/groups failed');
		}
	}
	else {
		echo '<p>Nothing to remove!</p>';
	}

	echo '<p>Done!</p>';
}

?>

<form method="post" action="<?php echo $_SERVER[PHP_SELF]; ?>">
<center>
<p>Number of users to create: <input type="text" name="user_number" size="46" /></p>
<input type="submit" name="submit" value="Submit" />&nbsp;&nbsp;<input type="reset" />
</center>
</form>

<p>&nbsp;</p>

<form method="post" action="<?php echo $_SERVER[PHP_SELF]; ?>">
<center>
<p>Remove test users?</p>
<input type="submit" name="remove" value="Remove" />
</center>
</form>

