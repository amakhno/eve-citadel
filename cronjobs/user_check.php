<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

require_once(__DIR__ . '/../lib/db.class.php');
require_once(__DIR__ . '/../lib/other.php');
require_once(__DIR__ . '/../lib/auth.class.php');
require_once(__DIR__ . '/../lib/esi.class.php');
require_once(__DIR__ . '/../lib/ts3.class.php');
require_once(__DIR__ . '/../lib/phpbb3.class.php');
require_once(__DIR__ . '/../lib/discord.class.php');

$db_client = new citadelDB();
$auth_manager = new AuthManager($db_client);
$esi_client = new ESIClient();
$ts_client = new ts3client();
$phpbb3_client = new phpBB3client();
$discord_client = new DiscordCitadelClient();
$users = $db_client->users_get_active();

foreach(array_chunk($users, 5, true) as $users_chunk) {
	foreach ($users_chunk as $user) {
		$character_id = $user['character_id'];
		$character_esi = $esi_client->character_get_details($character_id);
		$character_cache = $db_client->character_info_get($character_id);
		$alliance_esi_id = $character_esi['alliance_id'];
		$alliance_cached_id = $character_cache['alliance_id'];
		$corp_esi_id = $character_esi['corporation_id'];
		$corporation_esi = $db_client->corporation_info_get($corp_esi_id);
		$corp_cached_id = $character_cache['corporation_id'];
		$corporation_cache = $db_client->corporation_info_get($corp_cached_id);
		$user_groups = $db_client->usergroups_getby_user($user['id']);
		$group_new_name = corp_group_name($corporation_esi['ticker']);
		$group_old_name = corp_group_name($corporation_cache['ticker']);

		$member_group = $db_client->groups_getby_name($config['auth']['role_member']);
		$blue_group = $db_client->groups_getby_name($config['auth']['role_blue']);
		$group_new = $db_client->groups_getby_name($group_new_name);
		$group_old = $db_client->groups_getby_name($group_old_name);

		if ($auth_manager->is_member($alliance_esi_id, $corp_esi_id)) {
			$auth_manager->character_check_membership($character_id, $character_esi, $character_cache);
			$auth_manager->auth_role_check($user['id'], $member_group, $blue_group, $user_groups, true);
			$auth_manager->corp_role_check($user['id'], $group_old, $group_new, $user_groups, true);
		} elseif ($auth_manager->is_blue($alliance_esi_id, $corp_esi_id)) {
			$auth_manager->character_check_membership($character_id, $character_esi, $character_cache);
			$auth_manager->auth_role_check($user['id'], $member_group, $blue_group, $user_groups, false);
			$auth_manager->corp_role_check($user['id'], $group_old, $group_new, $user_groups, false);
		} else {
			print_r("Now user {$character_esi['name']} is not member or blue. Delete all roles.");
			$ts_token = $db_client->teamspeak_get_token($user['id']);

			if ($discord_id != null) {
				$db_client->teamspeak_delete($user['id']);
				$ts_client->user_del($character_id, $ts_token);
			}

			$discord_id = $db_client->discord_get_id($user['id']);
			if ($discord_id != null) {
				$discord_client->user_del($discord_id);
			}

			$phpbb3_username = $db_client->phpbb3_get_username($user['id']);
			if ($phpbb3_username != null) {
				$fake_password = password_generate();
				$user_email = "revoke_".uniqid()."@localhost";
				$pwhash = password_hash($fake_password, PASSWORD_DEFAULT);

				$phpbb3_client->user_update($phpbb3_username, $user_email, $pwhash);
				$phpbb3_client->user_sessions_del($phpbb3_username);
				$phpbb3_client->user_autologin_del($phpbb3_username);
				$phpbb3_client->user_deactivate($phpbb3_username);
				$db_client->phpbb3_delete($user['id']);
			}

			$db_client->usergroups_deleteby_user($user['id']);
		}

		unset(
			$character_esi,
			$character_cache,
			$alliance_esi_id,
			$alliance_cached_id,
			$corp_esi_id,
			$corp_cached_id,
			$user_email,
			$pwhash
		);
		usleep(500000);
	}
	usleep(10000000);
}
unset($esi_client, $ts_client, $phpbb3_client);
?>