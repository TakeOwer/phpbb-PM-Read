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

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/**
	* @param int $id
	* @param string $mode
	*/
	public function main($id, $mode)
	{
		global $request, $config, $phpbb_container, $db, $template, $user, $phpbb_log;
		global $phpbb_root_path, $phpEx;

		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
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

			$pm_notice = $this->request->variable('pmread_pm_notice', 0);

			$this->config->set('pmread_auto_delete', $auto_delete ? 1 : 0);
			$this->config->set('pmread_auto_delete_days', (int) $days_input);
			$this->config->set('pmread_exclude_groups', implode(',', $exclude_groups));
			$this->config->set('pmread_pm_notice', $pm_notice ? 1 : 0);

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_PMREAD_SETTINGS_UPDATED');
			trigger_error($this->user->lang('PMREAD_SETTINGS_SAVED') . adm_back_link($this->u_action));
		}

		$this->assign_exclude_groups($this->pm_manager->get_excluded_group_ids());

		$this->template->assign_vars(array(
			'U_ACTION'					=> $this->u_action,
			'PMREAD_VERSION'			=> isset($this->config['pmread_version']) ? $this->config['pmread_version'] : '',
			'S_PMREAD_AUTO_DELETE'		=> !empty($this->config['pmread_auto_delete']),
			'S_PMREAD_PM_NOTICE'		=> !isset($this->config['pmread_pm_notice']) || !empty($this->config['pmread_pm_notice']),
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
	/**
	* Read the search filter from the request.
	*
	* @return array
	*/
	protected function collect_filter()
	{
		$filter = array(
			'user'		=> trim($this->request->variable('fu', '', true)),
			'email'		=> trim($this->request->variable('fe', '', true)),
			'keyword'	=> trim($this->request->variable('fk', '', true)),
			'scope'		=> $this->request->variable('fs', 'any'),
			'date_from'	=> trim($this->request->variable('fd', '')),
			'date_to'	=> trim($this->request->variable('ft', '')),
			'year'		=> $this->request->variable('fy', 0),
		);

		if (!in_array($filter['scope'], array('any', 'from', 'to'), true))
		{
			$filter['scope'] = 'any';
		}

		$filter['from'] = $this->to_timestamp($filter['date_from'], false);
		$filter['to'] = $this->to_timestamp($filter['date_to'], true);

		// A bare year is a shortcut for 1 Jan - 31 Dec of that year
		if (!$filter['from'] && !$filter['to'] && $filter['year'] >= 1970 && $filter['year'] <= 2200)
		{
			$filter['from'] = $this->to_timestamp($filter['year'] . '-01-01', false);
			$filter['to'] = $this->to_timestamp($filter['year'] . '-12-31', true);
		}
		else
		{
			$filter['year'] = 0;
		}

		$filter['user_ids'] = array();
		if ($filter['user'] !== '' || $filter['email'] !== '')
		{
			$filter['user_ids'] = $this->pm_manager->resolve_user_ids($filter['user'], $filter['email']);

			// No user matched: force an empty result set rather than ignoring the filter
			if (!count($filter['user_ids']))
			{
				$filter['user_ids'] = array(0);
			}
		}

		$filter['active'] = ($filter['user'] !== '' || $filter['email'] !== '' || $filter['keyword'] !== ''
			|| $filter['from'] || $filter['to']);

		return $filter;
	}

	/**
	* Convert YYYY-MM-DD to a timestamp in the board timezone.
	*
	* @param string $date
	* @param bool $end_of_day
	* @return int 0 when the input is empty or malformed
	*/
	protected function to_timestamp($date, $end_of_day = false)
	{
		$date = (string) $date;

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
		{
			return 0;
		}

		try
		{
			$datetime = $this->user->create_datetime($date . ($end_of_day ? ' 23:59:59' : ' 00:00:00'));
			return (int) $datetime->getTimestamp();
		}
		catch (\Exception $e)
		{
			return 0;
		}
	}

	/**
	* Rebuild the filter as a query string so pagination and redirects keep it.
	*
	* @param array $filter
	* @param bool $for_url true escapes & for template output
	* @return string
	*/
	protected function filter_query(array $filter, $for_url = true)
	{
		$params = array();

		foreach (array('fu' => 'user', 'fe' => 'email', 'fk' => 'keyword', 'fd' => 'date_from', 'ft' => 'date_to') as $key => $field)
		{
			if ($filter[$field] !== '')
			{
				$params[] = $key . '=' . urlencode($filter[$field]);
			}
		}

		if ($filter['scope'] !== 'any')
		{
			$params[] = 'fs=' . $filter['scope'];
		}

		if (!empty($filter['year']))
		{
			$params[] = 'fy=' . (int) $filter['year'];
		}

		if (!count($params))
		{
			return '';
		}

		return ($for_url ? '&amp;' : '&') . implode($for_url ? '&amp;' : '&', $params);
	}

	/**
	* ACP message list, search, delete, export and print actions.
	*/
	protected function mode_messages()
	{
		$this->tpl_name = 'acp_pmread';
		$this->page_title = 'ACP_PMREAD_MESSAGES';

		add_form_key('acp_pmread');

		$deletemark = $this->request->variable('delmarked', false, false, \phpbb\request\request_interface::POST);
		$deleteall = $this->request->variable('delall', false, false, \phpbb\request\request_interface::POST);
		$exportmark = $this->request->variable('exportmarked', false, false, \phpbb\request\request_interface::POST);
		$exportall = $this->request->variable('exportall', false, false, \phpbb\request\request_interface::POST);
		$printmark = $this->request->variable('printmarked', false, false, \phpbb\request\request_interface::POST);
		$printall = $this->request->variable('printall', false, false, \phpbb\request\request_interface::POST);
		$marked = array_map('intval', $this->request->variable('mark', array(0)));
		$marked = array_values(array_unique(array_filter($marked)));
		$start = $this->request->variable('start', 0);

		$filter = $this->collect_filter();
		$sql_where = $this->pm_manager->build_filter_sql($filter);
		$filter_url = $this->filter_query($filter);

		// Print and export stream their own document: handle them before rendering
		if ($printmark || $printall || $exportall || $exportmark)
		{
			if (!check_form_key('acp_pmread'))
			{
				trigger_error($this->user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if (($printmark || $exportmark) && !count($marked))
			{
				trigger_error($this->user->lang('PMREAD_NO_MESSAGES_SELECTED') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if ($printmark || $printall)
			{
				$limit = 500;

				if ($printall)
				{
					// Current search result, newest last so the printout reads
					// chronologically like the "selected" view does
					$rows = array_reverse($this->pm_manager->fetch_messages($sql_where, 0, $limit));
				}
				else
				{
					$rows = $this->pm_manager->fetch_messages_by_id($marked, $limit);
				}

				$this->print_view($rows, count($rows) >= $limit ? $limit : 0);
			}

			// "Export all" exports the current search result, not the whole board
			$this->export_csv($exportall ? array() : $marked, $exportall ? $sql_where : '');
			// neither returns
		}

		if ($deleteall || ($deletemark && count($marked)))
		{
			if (confirm_box(true))
			{
				$deleted_count = 0;

				if ($deleteall)
				{
					$deleted_count = $this->pm_manager->delete_all_pms();
					$log_message = 'LOG_PMREAD_DELETED_ALL';
					$success_lang = 'PMREAD_MESSAGES_DELETED_ALL';
				}
				else
				{
					$deleted_count = $this->pm_manager->delete_pms_hard($marked);
					$log_message = 'LOG_PMREAD_DELETED';
					$success_lang = 'PMREAD_MESSAGES_DELETED';
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
				$confirm_lang = $deleteall ? 'PMREAD_CONFIRM_DELETE_ALL' : 'PMREAD_CONFIRM_DELETE_MARKED';

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
			trigger_error($this->user->lang('PMREAD_NO_MESSAGES_SELECTED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$per_page = 15;
		$total_count = $this->pm_manager->count_messages($sql_where);

		if ($start > $total_count)
		{
			$start = 0;
		}

		$pagination = $this->phpbb_container->get('pagination');
		if ($total_count)
		{
			$pagination->generate_template_pagination($this->u_action . $filter_url, 'pagination', 'start', $total_count, $per_page, $start);
		}

		$rows = $this->pm_manager->fetch_messages($sql_where, $start, $per_page);

		// Two queries for every name on the page instead of one query per name
		$this->pm_manager->prime_names($rows);

		foreach ($rows as $row)
		{
			$bbcode_options = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
			$message_text = generate_text_for_display($row['message_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

			$this->template->assign_block_vars('message', array(
				'MSG_ID'	=> $row['msg_id'],
				'SBJ'		=> $row['message_subject'],
				'MSGT'		=> $message_text,
				'DATE'		=> $this->user->format_date($row['message_time']),
				'FROM'		=> $this->pm_manager->get_username((int) $row['author_id']),
				'TO'		=> $this->pm_manager->format_address($row['to_address']),
				'BCC'		=> $this->pm_manager->format_address($row['bcc_address']),
			));
		}

		$this->template->assign_vars(array(
			'U_ACTION'			=> $this->u_action . $filter_url,
			'U_RESET_FILTER'	=> $this->u_action,
			'U_FIND_USER'		=> append_sid($this->phpbb_root_path . 'memberlist.' . $this->php_ext, 'mode=searchuser&amp;form=pmread_search&amp;field=fu&amp;select_single=true'),
			'PMREAD_VERSION'	=> isset($this->config['pmread_version']) ? $this->config['pmread_version'] : '',
			'TOTAL_ITEMS'		=> $this->user->lang('PMREAD_TOTAL_ITEMS', (int) $total_count),
			'PAGE_NUMBER'		=> $pagination->on_page($total_count, $per_page, $start),

			'S_FILTER_ACTIVE'	=> !empty($filter['active']),
			'FILTER_USER'		=> $filter['user'],
			'FILTER_EMAIL'		=> $filter['email'],
			'FILTER_KEYWORD'	=> $filter['keyword'],
			'FILTER_DATE_FROM'	=> $filter['date_from'],
			'FILTER_DATE_TO'	=> $filter['date_to'],
			'FILTER_YEAR'		=> $filter['year'] ? (int) $filter['year'] : '',
			'S_SCOPE_ANY'		=> ($filter['scope'] === 'any'),
			'S_SCOPE_FROM'		=> ($filter['scope'] === 'from'),
			'S_SCOPE_TO'		=> ($filter['scope'] === 'to'),
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
	* Render the selected messages as a standalone, print-optimised page and
	* terminate. Deliberately not an ACP template: the printout must not carry
	* the admin navigation, and it opens in its own tab.
	*
	* @param array $msg_ids
	*/
	protected function print_view(array $rows, $truncated_at = 0)
	{
		$this->pm_manager->prime_names($rows);

		$levels = ob_get_level();
		while ($levels-- > 0 && @ob_end_clean())
		{
			// noop
		}

		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: private, no-cache');

		$sitename = isset($this->config['sitename']) ? $this->config['sitename'] : '';
		$printed_on = $this->user->format_date(time());
		$printed_by = $this->user->data['username'];

		echo '<!DOCTYPE html>' . "\n";
		echo '<html lang="' . htmlspecialchars($this->user->lang_name, ENT_QUOTES, 'UTF-8') . '">' . "\n";
		echo '<head>' . "\n";
		echo '<meta charset="utf-8" />' . "\n";
		echo '<title>' . htmlspecialchars($this->user->lang('PMREAD_PRINT_TITLE'), ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
		echo '<style>' . $this->print_css() . '</style>' . "\n";
		echo '</head>' . "\n<body>\n";

		echo '<div class="toolbar no-print">'
			. '<button type="button" onclick="window.print();">' . htmlspecialchars($this->user->lang('PMREAD_PRINT_NOW'), ENT_QUOTES, 'UTF-8') . '</button> '
			. '<button type="button" onclick="window.close();">' . htmlspecialchars($this->user->lang('PMREAD_PRINT_CLOSE'), ENT_QUOTES, 'UTF-8') . '</button>'
			. '</div>';

		echo '<h1>' . htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8') . ' &ndash; ' . htmlspecialchars($this->user->lang('PMREAD_PRINT_TITLE'), ENT_QUOTES, 'UTF-8') . '</h1>';
		echo '<p class="meta">' . htmlspecialchars($this->user->lang('PMREAD_PRINT_META', $printed_by, $printed_on, count($rows)), ENT_QUOTES, 'UTF-8') . '</p>';

		if ($truncated_at)
		{
			echo '<p class="warn">' . $this->esc($this->user->lang('PMREAD_PRINT_TRUNCATED', (int) $truncated_at)) . '</p>';
		}

		if (!count($rows))
		{
			echo '<p>' . htmlspecialchars($this->user->lang('PMREAD_NO_MESSAGES'), ENT_QUOTES, 'UTF-8') . '</p>';
		}
		else
		{
			echo '<table class="grid">' . "\n";
			echo '<thead><tr>'
				. '<th class="c-id">' . $this->esc($this->user->lang('PMREAD_MSG_ID')) . '</th>'
				. '<th class="c-date">' . $this->esc($this->user->lang('PMREAD_DATETIME')) . '</th>'
				. '<th>' . $this->esc($this->user->lang('PMREAD_FROM')) . '</th>'
				. '<th>' . $this->esc($this->user->lang('PMREAD_TO')) . '</th>'
				. '<th>' . $this->esc($this->user->lang('PMREAD_EXPORT_COL_BCC')) . '</th>'
				. '<th>' . $this->esc($this->user->lang('PMREAD_SUBJECT')) . '</th>'
				. '</tr></thead>' . "\n<tbody>\n";

			foreach ($rows as $row)
			{
				$bbcode_options = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				$message_text = generate_text_for_display($row['message_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $bbcode_options);

				echo '<tr class="head">'
					. '<td class="c-id">' . (int) $row['msg_id'] . '</td>'
					. '<td class="c-date">' . $this->esc($this->user->format_date((int) $row['message_time'], 'Y-m-d H:i')) . '</td>'
					. '<td>' . $this->esc($this->pm_manager->get_username((int) $row['author_id'])) . '</td>'
					. '<td>' . $this->esc($this->pm_manager->format_address($row['to_address'])) . '</td>'
					. '<td>' . $this->esc($this->pm_manager->format_address($row['bcc_address'])) . '</td>'
					. '<td>' . $this->esc($row['message_subject']) . '</td>'
					. '</tr>' . "\n";

				// generate_text_for_display() returns markup phpBB itself produced
				echo '<tr class="body"><td colspan="6"><div class="msg">' . $message_text . '</div></td></tr>' . "\n";
			}

			echo '</tbody></table>' . "\n";
		}

		echo '<script>window.onload = function () { window.print(); };</script>' . "\n";
		echo '</body></html>';

		garbage_collection();
		exit_handler();
	}

	/**
	* @param string $value
	* @return string
	*/
	protected function esc($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}

	/**
	* Stylesheet for the print view: a compact spreadsheet-like grid that
	* survives a black and white printer.
	*
	* @return string
	*/
	protected function print_css()
	{
		return '
body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #000; margin: 18px; }
h1 { font-size: 15pt; margin: 0 0 4px 0; }
p.meta { font-size: 9pt; color: #444; margin: 0 0 14px 0; }
.toolbar { margin-bottom: 14px; }
.toolbar button { font-size: 11pt; padding: 4px 12px; margin-right: 6px; }
table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
table.grid th, table.grid td { border: 1px solid #666; padding: 4px 6px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
table.grid th { background: #e4e4e4; font-size: 9pt; text-align: left; text-transform: uppercase; letter-spacing: .03em; }
tr.head td { background: #f4f4f4; font-size: 9.5pt; }
tr.body td { font-size: 10pt; }
.c-id { width: 68px; }
.c-date { width: 108px; }
.msg { white-space: pre-wrap; }
.msg img { max-width: 16px; max-height: 16px; vertical-align: middle; }
tr.head, tr.body { page-break-inside: avoid; }
@media print {
	body { margin: 0; font-size: 10pt; }
	.no-print { display: none; }
	table.grid th { background: #e4e4e4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
	thead { display: table-header-group; }
}
';
	}

	/**
	* Stream every requested message as a CSV download and terminate.
	*
	* UTF-8 BOM + semicolon separator so that Excel in a European locale opens
	* the file correctly with a double click.
	*
	* @param array $msg_ids Empty array exports everything matching $sql_where
	* @param string $sql_where Active search filter, empty for none
	*/
	protected function export_csv(array $msg_ids, $sql_where = '')
	{
		$batch_size = 200;
		$filename = 'pmread_export_' . date('Ymd_His') . '.csv';

		// A board-wide export can take a while
		@set_time_limit(0);

		// Discard anything the ACP may already have buffered.
		// Bounded loop: ob_end_clean() can legitimately fail on a locked buffer.
		$levels = ob_get_level();
		while ($levels-- > 0 && @ob_end_clean())
		{
			// noop
		}

		header('Cache-Control: private, no-cache');
		header('Pragma: no-cache');
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$out = fopen('php://output', 'w');
		fwrite($out, "\xEF\xBB\xBF");

		fputcsv($out, array(
			$this->user->lang('PMREAD_MSG_ID'),
			$this->user->lang('PMREAD_FROM'),
			$this->user->lang('PMREAD_TO'),
			$this->user->lang('PMREAD_EXPORT_COL_BCC'),
			$this->user->lang('PMREAD_DATETIME'),
			$this->user->lang('PMREAD_SUBJECT'),
			$this->user->lang('PMREAD_EXPORT_COL_TEXT'),
		), ';');

		$last_id = 0;

		do
		{
			$rows = $this->pm_manager->fetch_export_batch($msg_ids, $last_id, $batch_size, $sql_where);

			if (!count($rows))
			{
				break;
			}

			$this->pm_manager->prime_names($rows);

			foreach ($rows as $row)
			{
				$last_id = (int) $row['msg_id'];

				fputcsv($out, array(
					(int) $row['msg_id'],
					$this->csv_cell($this->pm_manager->get_username((int) $row['author_id'])),
					$this->csv_cell($this->pm_manager->format_address($row['to_address'])),
					$this->csv_cell($this->pm_manager->format_address($row['bcc_address'])),
					$this->user->format_date((int) $row['message_time'], 'Y-m-d H:i:s'),
					$this->csv_cell($this->clean_text($row['message_subject'])),
					$this->csv_cell($this->clean_text($row['message_text'], $row['bbcode_uid'])),
				), ';');
			}

			flush();
		}
		while (count($rows) === $batch_size);

		fclose($out);

		garbage_collection();
		exit_handler();
	}

	/**
	* Strip BBCode/markup and normalise line endings for a CSV cell.
	*
	* @param string $text
	* @param string $uid BBCode uid of the row, empty for subjects
	* @return string
	*/
	protected function clean_text($text, $uid = '')
	{
		$text = (string) $text;

		if ($uid !== '')
		{
			strip_bbcode($text, $uid);
		}

		$text = str_replace(array('<br />', '<br/>', '<br>'), "\n", $text);
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = str_replace(array("\r\n", "\r"), "\n", $text);

		return $text;
	}

	/**
	* Neutralise CSV formula injection: a cell opening with = + - @ is executed
	* as a formula by Excel and LibreOffice.
	*
	* @param string $value
	* @return string
	*/
	protected function csv_cell($value)
	{
		$value = (string) $value;

		if ($value !== '' && strpos("=+-@\t\r", $value[0]) !== false)
		{
			$value = "'" . $value;
		}

		return $value;
	}
}
