<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\acp;

/**
* ACP module (pattern aligned with salvocortesiano extensions).
*/
class main_module
{
	/** @var string */
	public $u_action;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $page_title;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \Symfony\Component\DependencyInjection\ContainerInterface */
	protected $phpbb_container;

	/** @var \phpbbworld\pmread\service\pm_manager */
	protected $pm_manager;

	/** @var \phpbb\log\log */
	protected $log;

	/**
	* @param int $id
	* @param string $mode
	*/
	public function main($id, $mode)
	{
		global $request, $config, $phpbb_container, $db, $template, $user, $phpbb_log;

		$this->request = $request;
		$this->config = $config;
		$this->db = $db;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_container = $phpbb_container;
		$this->pm_manager = $phpbb_container->get('phpbbworld.pmread.pm_manager');
		$this->log = $phpbb_log;

		$this->user->add_lang('acp/common');
		$this->user->add_lang_ext('phpbbworld/pmread', 'pmread');

		switch ($mode)
		{
			case 'settings':
				$this->mode_settings();
			break;

			case 'messages':
			case 'pmread_config': // legacy mode name
			default:
				$this->mode_messages();
			break;
		}
	}

	/**
	* ACP settings: auto-delete enable + days.
	*/
	protected function mode_settings()
	{
		$this->tpl_name = 'acp_pmread_settings';
		$this->page_title = 'ACP_PMREAD_SETTINGS';

		$form_key = 'acp_pmread_settings';
		add_form_key($form_key);

		$days = isset($this->config['pmread_auto_delete_days']) ? (int) $this->config['pmread_auto_delete_days'] : 90;
		if ($days < 1)
		{
			$days = 90;
		}

		// Manual prune: delete ALL private messages (ignores day threshold)
		$run_prune = $this->request->is_set_post('run_prune') || $this->request->variable('run_prune', false);
		if ($run_prune)
		{
			if (confirm_box(true))
			{
				$deleted = $this->pm_manager->delete_all_pms();
				$this->config->set('pmread_auto_delete_last_gc', time(), false);

				if ($deleted > 0)
				{
					$this->set_user_prune_notice($deleted, 0);
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_PMREAD_DELETED_ALL', false, array($deleted));
					trigger_error($this->user->lang('PMREAD_PRUNE_DONE', $deleted) . adm_back_link($this->u_action));
				}

				trigger_error($this->user->lang('PMREAD_PRUNE_NONE') . adm_back_link($this->u_action));
			}
			else
			{
				$total = $this->pm_manager->count_deletable_pms();

				confirm_box(false, $this->user->lang('PMREAD_PRUNE_CONFIRM', $total), build_hidden_fields(array(
					'run_prune'	=> 1,
				)));
			}
		}

		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($this->user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$auto_delete = $this->request->variable('pmread_auto_delete', 0);
			$days_input = $this->request->variable('pmread_auto_delete_days', 90);
			$exclude_groups = $this->request->variable('pmread_exclude_groups', array(0));
			$exclude_groups = array_values(array_unique(array_filter(array_map('intval', $exclude_groups))));

			if ($days_input < 1)
			{
				trigger_error($this->user->lang('PMREAD_DAYS_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$this->config->set('pmread_auto_delete', $auto_delete ? 1 : 0);
			$this->config->set('pmread_auto_delete_days', (int) $days_input);
			$this->config->set('pmread_exclude_groups', implode(',', $exclude_groups));

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_PMREAD_SETTINGS_UPDATED');
			trigger_error($this->user->lang('PMREAD_SETTINGS_SAVED') . adm_back_link($this->u_action));
		}

		$this->assign_exclude_groups($this->pm_manager->get_excluded_group_ids());

		$this->template->assign_vars(array(
			'U_ACTION'					=> $this->u_action,
			'PMREAD_VERSION'			=> isset($this->config['pmread_version']) ? $this->config['pmread_version'] : '',
			'S_PMREAD_AUTO_DELETE'		=> !empty($this->config['pmread_auto_delete']),
			'PMREAD_AUTO_DELETE_DAYS'	=> $days,
			'PMREAD_PENDING_COUNT'		=> $this->pm_manager->count_pms_older_than($days),
			'PMREAD_LAST_GC'			=> !empty($this->config['pmread_auto_delete_last_gc'])
				? $this->user->format_date((int) $this->config['pmread_auto_delete_last_gc'])
				: $this->user->lang('PMREAD_NEVER'),
		));
	}

	/**
	* Populate ACP group multi-select.
	*
	* @param array $selected_groups
	*/
	protected function assign_exclude_groups(array $selected_groups)
	{
		$this->user->add_lang('acp/groups');

		$sql = 'SELECT group_id, group_name, group_type
			FROM ' . GROUPS_TABLE . '
			ORDER BY group_type DESC, group_name ASC';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_name = $row['group_name'];
			if ((int) $row['group_type'] === GROUP_SPECIAL)
			{
				$lang_key = 'G_' . $group_name;
				if ($this->user->lang($lang_key) !== $lang_key)
				{
					$group_name = $this->user->lang($lang_key);
				}
			}

			$this->template->assign_block_vars('exclude_groups', array(
				'GROUP_ID'		=> (int) $row['group_id'],
				'GROUP_NAME'	=> $group_name,
				'S_SELECTED'	=> in_array((int) $row['group_id'], $selected_groups, true),
			));
		}
		$this->db->sql_freeresult($result);
	}

	/**
	* ACP message list + manual delete actions.
	*/
	protected function mode_messages()
	{
		$this->tpl_name = 'acp_pmread';
		$this->page_title = 'ACP_PMREAD_MESSAGES';

		$deletemark = $this->request->variable('delmarked', false, false, \phpbb\request\request_interface::POST);
		$deleteall = $this->request->variable('delall', false, false, \phpbb\request\request_interface::POST);
		$marked = array_map('intval', $this->request->variable('mark', array(0)));
		$marked = array_values(array_unique(array_filter($marked)));
		$start = $this->request->variable('start', 0);

		if ($deleteall || ($deletemark && count($marked)))
		{
			if (confirm_box(true))
			{
				$deleted_count = 0;

				if ($deleteall)
				{
					$deleted_count = $this->pm_manager->delete_all_pms();
					$log_message = 'LOG_PMREAD_DELETED_ALL';
					$success_lang = 'MESSAGES_DELETED_ALL';
				}
				else
				{
					$deleted_count = $this->pm_manager->delete_pms_hard($marked);
					$log_message = 'LOG_PMREAD_DELETED';
					$success_lang = 'MESSAGES_DELETED';
				}

				if ($deleted_count > 0)
				{
					if ($deleteall)
					{
						$this->set_user_prune_notice($deleted_count, 0);
					}
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, false, array($deleted_count));
				}

				trigger_error($this->user->lang($success_lang, $deleted_count) . adm_back_link($this->u_action));
			}
			else
			{
				$confirm_lang = $deleteall ? 'CONFIRM_DELETE_ALL' : 'CONFIRM_DELETE_MARKED';

				confirm_box(false, $this->user->lang($confirm_lang), build_hidden_fields(array(
					'start'		=> $start,
					'delmarked'	=> $deletemark,
					'delall'	=> $deleteall,
					'mark'		=> $marked,
				)));
			}
		}

		if ($deletemark && !count($marked))
		{
			trigger_error($this->user->lang('NO_MESSAGES_SELECTED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$total_count = 0;
		$per_page = 15;

		$sql = 'SELECT COUNT(msg_id) as total
			FROM ' . PRIVMSGS_TABLE;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_count = (int) $row['total'];
		$this->db->sql_freeresult($result);

		$pagination_url = $this->u_action;
		$pagination = $this->phpbb_container->get('pagination');
		if ($total_count)
		{
			$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_count, $per_page, $start);
		}

		$sql = 'SELECT msg_id, message_subject, message_text, message_time, author_id, to_address, bbcode_uid, bbcode_bitfield, enable_bbcode, enable_magic_url, enable_smilies
			FROM ' . PRIVMSGS_TABLE . '
			ORDER BY msg_id DESC';
		$result = $this->db->sql_query_limit($sql, $per_page, $start);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$bbcode_options = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
			$message_text = generate_text_for_display($row['message_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

			$name = $this->resolve_usernames_from_to_address($row['to_address']);
			$name2 = $this->resolve_username_by_id((int) $row['author_id']);

			$this->template->assign_block_vars('message', array(
				'MSG_ID'	=> $row['msg_id'],
				'SBJ'		=> $row['message_subject'],
				'MSGT'		=> $message_text,
				'DATE'		=> $this->user->format_date($row['message_time']),
				'FROM'		=> $name2,
				'TO'		=> $name,
			));
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'U_ACTION'			=> $this->u_action,
			'PMREAD_VERSION'	=> isset($this->config['pmread_version']) ? $this->config['pmread_version'] : '',
			'TOTAL_ITEMS'		=> $this->user->lang('TOTAL_ITEMS', (int) $total_count),
			'PAGE_NUMBER'		=> $pagination->on_page($total_count, $per_page, $start),
		));
	}

	/**
	* Flag a board-wide phpbb.alert for registered users after PM wipe/prune.
	*
	* @param int $deleted Number of deleted messages
	* @param int $days 0 = all messages wiped; >0 = automatic age-based prune
	*/
	protected function set_user_prune_notice($deleted, $days = 0)
	{
		$this->config->set('pmread_prune_notice_time', time(), false);
		$this->config->set('pmread_prune_notice_days', (int) $days, false);
		$this->config->set('pmread_prune_notice_count', (int) $deleted, false);
	}

	/**
	* @param string $to_address
	* @return string
	*/
	protected function resolve_usernames_from_to_address($to_address)
	{
		$names = array();
		$to_array = explode(',', (string) $to_address);

		foreach ($to_array as $token)
		{
			$token = trim($token);
			if ($token === '')
			{
				continue;
			}

			if (strpos($token, 'u_') === 0)
			{
				$names[] = $this->resolve_username_by_id((int) substr($token, 2));
			}
		}

		$names = array_filter($names);
		return count($names) ? implode(', ', $names) : '';
	}

	/**
	* @param int $user_id
	* @return string
	*/
	protected function resolve_username_by_id($user_id)
	{
		$user_id = (int) $user_id;
		if (!$user_id)
		{
			return '';
		}

		$sql = 'SELECT username
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql, 600);
		$username = $this->db->sql_fetchfield('username');
		$this->db->sql_freeresult($result);

		return $username ? $username : '';
	}
}
