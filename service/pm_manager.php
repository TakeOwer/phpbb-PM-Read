<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\service;

/**
* Shared private message hard-delete helpers.
*/
class pm_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \Symfony\Component\DependencyInjection\ContainerInterface */
	protected $phpbb_container;

	/** @var array|null Cached excluded user IDs */
	protected $excluded_user_ids = null;

	/** @var array Cached user_id => username */
	protected $username_cache = array();

	/** @var array Cached group_id => group name */
	protected $groupname_cache = array();

	/**
	* @param \phpbb\db\driver\driver_interface $db
	* @param \phpbb\config\config $config
	* @param \Symfony\Component\DependencyInjection\ContainerInterface $phpbb_container
	*/
	public function __construct($db, $config, $phpbb_container)
	{
		$this->db = $db;
		$this->config = $config;
		$this->phpbb_container = $phpbb_container;
	}

	/**
	* Group IDs excluded from PM deletion (ACP setting).
	*
	* @return array
	*/
	public function get_excluded_group_ids()
	{
		$raw = isset($this->config['pmread_exclude_groups']) ? (string) $this->config['pmread_exclude_groups'] : '';
		return array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));
	}

	/**
	* User IDs belonging to excluded groups.
	*
	* @return array
	*/
	public function get_excluded_user_ids()
	{
		if ($this->excluded_user_ids !== null)
		{
			return $this->excluded_user_ids;
		}

		$group_ids = $this->get_excluded_group_ids();
		if (!count($group_ids))
		{
			$this->excluded_user_ids = array();
			return $this->excluded_user_ids;
		}

		$user_ids = array();
		$sql = 'SELECT DISTINCT user_id
			FROM ' . USER_GROUP_TABLE . '
			WHERE user_pending = 0
				AND ' . $this->db->sql_in_set('group_id', $group_ids);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_ids[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$this->excluded_user_ids = array_values(array_unique($user_ids));
		return $this->excluded_user_ids;
	}

	/**
	* Remove msg IDs that involve excluded-group users (author or recipient).
	*
	* @param array $msg_ids
	* @return array
	*/
	public function filter_deletable_msg_ids(array $msg_ids)
	{
		$msg_ids = array_values(array_unique(array_filter(array_map('intval', $msg_ids))));
		if (!count($msg_ids))
		{
			return array();
		}

		$excluded_users = $this->get_excluded_user_ids();
		if (!count($excluded_users))
		{
			return $msg_ids;
		}

		$protected = array();

		$sql = 'SELECT msg_id
			FROM ' . PRIVMSGS_TABLE . '
			WHERE ' . $this->db->sql_in_set('msg_id', $msg_ids) . '
				AND ' . $this->db->sql_in_set('author_id', $excluded_users);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$protected[(int) $row['msg_id']] = true;
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT DISTINCT msg_id
			FROM ' . PRIVMSGS_TO_TABLE . '
			WHERE ' . $this->db->sql_in_set('msg_id', $msg_ids) . '
				AND ' . $this->db->sql_in_set('user_id', $excluded_users);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$protected[(int) $row['msg_id']] = true;
		}
		$this->db->sql_freeresult($result);

		if (!count($protected))
		{
			return $msg_ids;
		}

		$deletable = array();
		foreach ($msg_ids as $msg_id)
		{
			if (!isset($protected[$msg_id]))
			{
				$deletable[] = $msg_id;
			}
		}

		return $deletable;
	}

	/**
	* Hard-delete private messages for all users by msg_id.
	*
	* @param array $msg_ids Message IDs
	* @return int Number of messages deleted from PRIVMSGS_TABLE
	*/
	public function delete_pms_hard(array $msg_ids)
	{
		$msg_ids = $this->filter_deletable_msg_ids($msg_ids);

		if (!count($msg_ids))
		{
			return 0;
		}

		$this->db->sql_transaction('begin');

		$user_unread = array();
		$user_new = array();
		$folder_counts = array();

		$sql = 'SELECT user_id, folder_id, pm_unread, pm_new
			FROM ' . PRIVMSGS_TO_TABLE . '
			WHERE ' . $this->db->sql_in_set('msg_id', $msg_ids);
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_id = (int) $row['user_id'];
			$folder_id = (int) $row['folder_id'];

			if (!isset($user_unread[$user_id]))
			{
				$user_unread[$user_id] = 0;
				$user_new[$user_id] = 0;
			}

			$user_unread[$user_id] += (int) $row['pm_unread'];
			$user_new[$user_id] += (int) $row['pm_new'];

			if (!in_array($folder_id, array(PRIVMSGS_INBOX, PRIVMSGS_OUTBOX, PRIVMSGS_SENTBOX, PRIVMSGS_NO_BOX), true))
			{
				if (!isset($folder_counts[$folder_id]))
				{
					$folder_counts[$folder_id] = 0;
				}
				$folder_counts[$folder_id]++;
			}
		}
		$this->db->sql_freeresult($result);

		/* @var $phpbb_notifications \phpbb\notification\manager */
		$phpbb_notifications = $this->phpbb_container->get('notification_manager');
		$phpbb_notifications->delete_notifications('notification.type.pm', $msg_ids);

		/** @var \phpbb\attachment\manager $attachment_manager */
		$attachment_manager = $this->phpbb_container->get('attachment.manager');
		$attachment_manager->delete('message', $msg_ids, false);
		unset($attachment_manager);

		$sql = 'DELETE FROM ' . PRIVMSGS_TO_TABLE . '
			WHERE ' . $this->db->sql_in_set('msg_id', $msg_ids);
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . PRIVMSGS_TABLE . '
			WHERE ' . $this->db->sql_in_set('msg_id', $msg_ids);
		$this->db->sql_query($sql);
		$deleted_count = (int) $this->db->sql_affectedrows();

		foreach ($user_unread as $user_id => $num_unread)
		{
			$num_new = isset($user_new[$user_id]) ? (int) $user_new[$user_id] : 0;
			$num_unread = (int) $num_unread;

			if (!$num_unread && !$num_new)
			{
				continue;
			}

			$set_sql = array();
			if ($num_unread)
			{
				$set_sql[] = 'user_unread_privmsg = CASE WHEN user_unread_privmsg >= ' . $num_unread . ' THEN user_unread_privmsg - ' . $num_unread . ' ELSE 0 END';
			}
			if ($num_new)
			{
				$set_sql[] = 'user_new_privmsg = CASE WHEN user_new_privmsg >= ' . $num_new . ' THEN user_new_privmsg - ' . $num_new . ' ELSE 0 END';
			}

			$sql = 'UPDATE ' . USERS_TABLE . '
				SET ' . implode(', ', $set_sql) . '
				WHERE user_id = ' . (int) $user_id;
			$this->db->sql_query($sql);
		}

		foreach ($folder_counts as $folder_id => $count)
		{
			$sql = 'UPDATE ' . PRIVMSGS_FOLDER_TABLE . '
				SET pm_count = CASE WHEN pm_count >= ' . (int) $count . ' THEN pm_count - ' . (int) $count . ' ELSE 0 END
				WHERE folder_id = ' . (int) $folder_id;
			$this->db->sql_query($sql);
		}

		$this->db->sql_transaction('commit');

		return $deleted_count;
	}

	/**
	* Delete all private messages in batches (respects excluded groups).
	*
	* @return int Total number of messages deleted
	*/
	public function delete_all_pms()
	{
		$batch_size = 250;
		$total_deleted = 0;
		$last_id = 0;

		do
		{
			$msg_ids = array();

			$sql = 'SELECT msg_id
				FROM ' . PRIVMSGS_TABLE . '
				WHERE msg_id > ' . (int) $last_id . '
				ORDER BY msg_id ASC';
			$result = $this->db->sql_query_limit($sql, $batch_size);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$msg_ids[] = (int) $row['msg_id'];
			}
			$this->db->sql_freeresult($result);

			if (!count($msg_ids))
			{
				break;
			}

			$last_id = max($msg_ids);
			$deletable = $this->filter_deletable_msg_ids($msg_ids);

			if (count($deletable))
			{
				$total_deleted += $this->delete_pms_hard($deletable);
			}
		}
		while (count($msg_ids) === $batch_size);

		$sql = 'SELECT COUNT(msg_id) AS total
			FROM ' . PRIVMSGS_TABLE;
		$result = $this->db->sql_query($sql);
		$remaining = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		// Only wipe global counters when nothing remains
		if ($remaining < 1)
		{
			$sql = 'UPDATE ' . USERS_TABLE . '
				SET user_new_privmsg = 0,
					user_unread_privmsg = 0';
			$this->db->sql_query($sql);

			$sql = 'UPDATE ' . PRIVMSGS_FOLDER_TABLE . '
				SET pm_count = 0';
			$this->db->sql_query($sql);
		}

		return $total_deleted;
	}

	/**
	* Delete private messages older than the given number of days.
	*
	* @param int $days Age threshold in days
	* @param int $max_per_run Safety cap per cron execution (0 = unlimited)
	* @return int Number of messages deleted
	*/
	public function delete_pms_older_than($days, $max_per_run = 500)
	{
		$days = (int) $days;
		if ($days < 1)
		{
			return 0;
		}

		$cutoff = time() - ($days * 86400);
		$batch_size = 250;
		$max_per_run = (int) $max_per_run;
		$total_deleted = 0;
		$last_id = 0;

		do
		{
			$limit = $batch_size;
			if ($max_per_run > 0)
			{
				$remaining_cap = $max_per_run - $total_deleted;
				if ($remaining_cap <= 0)
				{
					break;
				}
				$limit = min($batch_size, $remaining_cap);
			}

			$msg_ids = array();

			$sql = 'SELECT msg_id
				FROM ' . PRIVMSGS_TABLE . '
				WHERE message_time < ' . (int) $cutoff . '
					AND msg_id > ' . (int) $last_id . '
				ORDER BY msg_id ASC';
			$result = $this->db->sql_query_limit($sql, $limit);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$msg_ids[] = (int) $row['msg_id'];
			}
			$this->db->sql_freeresult($result);

			if (!count($msg_ids))
			{
				break;
			}

			$last_id = max($msg_ids);
			$deletable = $this->filter_deletable_msg_ids($msg_ids);

			if (count($deletable))
			{
				$total_deleted += $this->delete_pms_hard($deletable);
			}
		}
		while (count($msg_ids) === $limit);

		return $total_deleted;
	}

	/**
	* Count PMs older than the given number of days (excluding protected groups).
	*
	* @param int $days
	* @return int
	*/
	public function count_pms_older_than($days)
	{
		$days = (int) $days;
		if ($days < 1)
		{
			return 0;
		}

		$cutoff = time() - ($days * 86400);
		$excluded_users = $this->get_excluded_user_ids();

		if (!count($excluded_users))
		{
			$sql = 'SELECT COUNT(msg_id) AS total
				FROM ' . PRIVMSGS_TABLE . '
				WHERE message_time < ' . (int) $cutoff;
			$result = $this->db->sql_query($sql);
			$total = (int) $this->db->sql_fetchfield('total');
			$this->db->sql_freeresult($result);
			return $total;
		}

		// Count only messages with no excluded author/recipient
		$sql = 'SELECT COUNT(p.msg_id) AS total
			FROM ' . PRIVMSGS_TABLE . ' p
			WHERE p.message_time < ' . (int) $cutoff . '
				AND ' . $this->db->sql_in_set('p.author_id', $excluded_users, true) . '
				AND NOT EXISTS (
					SELECT 1
					FROM ' . PRIVMSGS_TO_TABLE . ' t
					WHERE t.msg_id = p.msg_id
						AND ' . $this->db->sql_in_set('t.user_id', $excluded_users) . '
				)';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	* Count all deletable PMs (excluding protected groups).
	*
	* @return int
	*/
	public function count_deletable_pms()
	{
		$excluded_users = $this->get_excluded_user_ids();

		if (!count($excluded_users))
		{
			$sql = 'SELECT COUNT(msg_id) AS total
				FROM ' . PRIVMSGS_TABLE;
			$result = $this->db->sql_query($sql);
			$total = (int) $this->db->sql_fetchfield('total');
			$this->db->sql_freeresult($result);
			return $total;
		}

		$sql = 'SELECT COUNT(p.msg_id) AS total
			FROM ' . PRIVMSGS_TABLE . ' p
			WHERE ' . $this->db->sql_in_set('p.author_id', $excluded_users, true) . '
				AND NOT EXISTS (
					SELECT 1
					FROM ' . PRIVMSGS_TO_TABLE . ' t
					WHERE t.msg_id = p.msg_id
						AND ' . $this->db->sql_in_set('t.user_id', $excluded_users) . '
				)';
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	* Split a phpBB address list into user and group IDs.
	*
	* phpBB stores recipients as "u_2:g_5:u_9" - the separator is a COLON.
	* A comma is accepted as well so that malformed/legacy data still parses.
	*
	* @param string $address
	* @return array array('u' => array(), 'g' => array())
	*/
	public function split_address($address)
	{
		$out = array('u' => array(), 'g' => array());

		$tokens = preg_split('/[:,]/', (string) $address, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($tokens as $token)
		{
			$token = trim($token);

			if (strpos($token, 'u_') === 0)
			{
				$id = (int) substr($token, 2);
				if ($id)
				{
					$out['u'][] = $id;
				}
			}
			else if (strpos($token, 'g_') === 0)
			{
				$id = (int) substr($token, 2);
				if ($id)
				{
					$out['g'][] = $id;
				}
			}
		}

		return $out;
	}

	/**
	* Pre-load every username/group name referenced by a rowset in two queries.
	* Avoids the N+1 pattern when rendering or exporting a page of messages.
	*
	* @param array $rows Rows containing author_id / to_address / bcc_address
	*/
	public function prime_names(array $rows)
	{
		$user_ids = $group_ids = array();

		foreach ($rows as $row)
		{
			if (!empty($row['author_id']))
			{
				$user_ids[] = (int) $row['author_id'];
			}

			foreach (array('to_address', 'bcc_address') as $field)
			{
				if (empty($row[$field]))
				{
					continue;
				}

				$parts = $this->split_address($row[$field]);
				$user_ids = array_merge($user_ids, $parts['u']);
				$group_ids = array_merge($group_ids, $parts['g']);
			}
		}

		$user_ids = array_diff(array_unique($user_ids), array_keys($this->username_cache));
		$group_ids = array_diff(array_unique($group_ids), array_keys($this->groupname_cache));

		if (count($user_ids))
		{
			$sql = 'SELECT user_id, username
				FROM ' . USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('user_id', $user_ids);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->username_cache[(int) $row['user_id']] = $row['username'];
			}
			$this->db->sql_freeresult($result);
		}

		if (count($group_ids))
		{
			$sql = 'SELECT group_id, group_name, group_type
				FROM ' . GROUPS_TABLE . '
				WHERE ' . $this->db->sql_in_set('group_id', $group_ids);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->groupname_cache[(int) $row['group_id']] = $this->translate_group_name($row['group_name'], (int) $row['group_type']);
			}
			$this->db->sql_freeresult($result);
		}
	}

	/**
	* Translate special group names (ADMINISTRATORS -> Administrators).
	*
	* @param string $group_name
	* @param int $group_type
	* @return string
	*/
	protected function translate_group_name($group_name, $group_type)
	{
		if ((int) $group_type !== GROUP_SPECIAL)
		{
			return $group_name;
		}

		/** @var \phpbb\user $user */
		$user = $this->phpbb_container->get('user');
		$lang_key = 'G_' . $group_name;

		return ($user->lang($lang_key) !== $lang_key) ? $user->lang($lang_key) : $group_name;
	}

	/**
	* @param int $user_id
	* @return string Empty string when the user no longer exists
	*/
	public function get_username($user_id)
	{
		$user_id = (int) $user_id;
		if (!$user_id)
		{
			return '';
		}

		if (!isset($this->username_cache[$user_id]))
		{
			$sql = 'SELECT username
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . $user_id;
			$result = $this->db->sql_query($sql, 600);
			$username = $this->db->sql_fetchfield('username');
			$this->db->sql_freeresult($result);

			$this->username_cache[$user_id] = $username ? $username : '';
		}

		return $this->username_cache[$user_id];
	}

	/**
	* Turn "u_2:g_5" into "Alice, Moderators".
	* Call prime_names() on the rowset first to keep this query-free.
	*
	* @param string $address
	* @return string
	*/
	public function format_address($address)
	{
		if (empty($address))
		{
			return '';
		}

		$parts = $this->split_address($address);
		$names = array();

		foreach ($parts['u'] as $user_id)
		{
			$name = $this->get_username($user_id);
			if ($name !== '')
			{
				$names[] = $name;
			}
		}

		foreach ($parts['g'] as $group_id)
		{
			if (isset($this->groupname_cache[$group_id]))
			{
				$names[] = $this->groupname_cache[$group_id];
			}
		}

		return implode(', ', $names);
	}

	/**
	* Fetch one batch of messages for CSV export, using keyset pagination so
	* that a full-board export never loads the whole table into memory.
	*
	* @param array $msg_ids Restrict to these IDs; empty array = every message
	* @param int $last_id Highest msg_id returned by the previous batch
	* @param int $limit Batch size
	* @param string $sql_where Active search filter, empty for none
	* @return array
	*/
	public function fetch_export_batch(array $msg_ids, $last_id = 0, $limit = 200, $sql_where = '')
	{
		$where = array('p.msg_id > ' . (int) $last_id);

		if (count($msg_ids))
		{
			$msg_ids = array_values(array_unique(array_filter(array_map('intval', $msg_ids))));

			if (!count($msg_ids))
			{
				return array();
			}

			$where[] = $this->db->sql_in_set('p.msg_id', $msg_ids);
		}

		if ($sql_where !== '')
		{
			$where[] = $sql_where;
		}

		$sql = 'SELECT p.msg_id, p.author_id, p.to_address, p.bcc_address, p.message_subject,
				p.message_text, p.message_time, p.bbcode_uid
			FROM ' . PRIVMSGS_TABLE . ' p
			WHERE ' . implode(' AND ', $where) . '
			ORDER BY p.msg_id ASC';
		$result = $this->db->sql_query_limit($sql, (int) $limit);

		$rows = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	* Resolve a username and/or email pattern to user IDs.
	* '*' works as a wildcard; without one the term is matched as "contains",
	* which is what an admin typing a partial name expects.
	*
	* @param string $username
	* @param string $email
	* @param int $limit Safety cap - a very loose pattern must not build a huge OR
	* @return array
	*/
	public function resolve_user_ids($username = '', $email = '', $limit = 250)
	{
		$where = array();

		$username = trim((string) $username);
		$email = trim((string) $email);

		if ($username !== '')
		{
			$clean = utf8_clean_string($username);

			if (strpos($clean, '*') === false)
			{
				$clean = '*' . $clean . '*';
			}

			$clean = str_replace('*', $this->db->get_any_char(), $clean);
			$where[] = 'username_clean ' . $this->db->sql_like_expression($clean);
		}

		if ($email !== '')
		{
			$mail = utf8_strtolower($email);

			if (strpos($mail, '*') === false)
			{
				$mail = '*' . $mail . '*';
			}

			$mail = str_replace('*', $this->db->get_any_char(), $mail);
			$where[] = 'user_email ' . $this->db->sql_like_expression($mail);
		}

		if (!count($where))
		{
			return array();
		}

		$user_ids = array();

		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . '
			WHERE ' . implode(' AND ', $where) . '
			ORDER BY user_id ASC';
		$result = $this->db->sql_query_limit($sql, (int) $limit);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_ids[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		return $user_ids;
	}

	/**
	* SQL fragment matching a single recipient inside an address list column.
	*
	* The column holds "u_2:g_5:u_9". A bare LIKE '%u_5%' would also match u_50,
	* so the column is wrapped in colons and the token searched as ':u_5:'.
	* sql_concatenate() keeps this portable across MySQL / PostgreSQL / SQLite.
	*
	* @param string $column
	* @param int $user_id
	* @return string
	*/
	protected function address_match_sql($column, $user_id)
	{
		$expr = $this->db->sql_concatenate($this->db->sql_concatenate("':'", $column), "':'");
		$pattern = $this->db->get_any_char() . ':u_' . (int) $user_id . ':' . $this->db->get_any_char();

		return $expr . ' ' . $this->db->sql_like_expression($pattern);
	}

	/**
	* Turn a filter array into a WHERE fragment (without the WHERE keyword).
	* Every message query uses the alias "p" for the privmsgs table.
	*
	* Expected keys: from, to (timestamps), keyword, scope (any|from|to), user_ids
	*
	* @param array $filter
	* @return string Empty string when no filter is active
	*/
	public function build_filter_sql(array $filter)
	{
		$filter = array_merge(array(
			'from'		=> 0,
			'to'		=> 0,
			'keyword'	=> '',
			'scope'		=> 'any',
			'user_ids'	=> array(),
		), $filter);

		$where = array();

		if (!empty($filter['from']))
		{
			$where[] = 'p.message_time >= ' . (int) $filter['from'];
		}

		if (!empty($filter['to']))
		{
			$where[] = 'p.message_time <= ' . (int) $filter['to'];
		}

		if ($filter['keyword'] !== '')
		{
			$pattern = $this->db->get_any_char() . $filter['keyword'] . $this->db->get_any_char();
			$like = $this->db->sql_like_expression($pattern);
			$where[] = '(p.message_subject ' . $like . ' OR p.message_text ' . $like . ')';
		}

		if (count($filter['user_ids']))
		{
			$parts = array();

			if ($filter['scope'] !== 'to')
			{
				$parts[] = $this->db->sql_in_set('p.author_id', $filter['user_ids']);
			}

			if ($filter['scope'] !== 'from')
			{
				foreach ($filter['user_ids'] as $user_id)
				{
					$parts[] = $this->address_match_sql('p.to_address', $user_id);
					$parts[] = $this->address_match_sql('p.bcc_address', $user_id);
				}
			}

			if (count($parts))
			{
				$where[] = '(' . implode(' OR ', $parts) . ')';
			}
		}

		return count($where) ? implode(' AND ', $where) : '';
	}

	/**
	* @param string $sql_where Fragment from build_filter_sql()
	* @return int
	*/
	public function count_messages($sql_where = '')
	{
		$sql = 'SELECT COUNT(p.msg_id) AS total
			FROM ' . PRIVMSGS_TABLE . ' p'
			. ($sql_where !== '' ? ' WHERE ' . $sql_where : '');
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		return $total;
	}

	/**
	* One page of messages for the ACP list, newest first.
	*
	* @param string $sql_where
	* @param int $start
	* @param int $limit
	* @return array
	*/
	public function fetch_messages($sql_where, $start, $limit)
	{
		$sql = 'SELECT p.msg_id, p.message_subject, p.message_text, p.message_time, p.author_id,
				p.to_address, p.bcc_address, p.bbcode_uid, p.bbcode_bitfield,
				p.enable_bbcode, p.enable_magic_url, p.enable_smilies
			FROM ' . PRIVMSGS_TABLE . ' p'
			. ($sql_where !== '' ? ' WHERE ' . $sql_where : '') . '
			ORDER BY p.msg_id DESC';
		$result = $this->db->sql_query_limit($sql, (int) $limit, (int) $start);

		$rows = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	* Full rows for the print view, oldest first so a printout reads
	* chronologically.
	*
	* @param array $msg_ids
	* @param int $limit Hard cap - printing thousands of messages helps nobody
	* @return array
	*/
	public function fetch_messages_by_id(array $msg_ids, $limit = 500)
	{
		$msg_ids = array_values(array_unique(array_filter(array_map('intval', $msg_ids))));

		if (!count($msg_ids))
		{
			return array();
		}

		$sql = 'SELECT p.msg_id, p.message_subject, p.message_text, p.message_time, p.author_id,
				p.to_address, p.bcc_address, p.bbcode_uid, p.bbcode_bitfield,
				p.enable_bbcode, p.enable_magic_url, p.enable_smilies
			FROM ' . PRIVMSGS_TABLE . ' p
			WHERE ' . $this->db->sql_in_set('p.msg_id', $msg_ids) . '
			ORDER BY p.msg_id ASC';
		$result = $this->db->sql_query_limit($sql, (int) $limit);

		$rows = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $rows;
	}
}
