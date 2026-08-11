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
}
