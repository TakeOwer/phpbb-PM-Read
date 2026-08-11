<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\cron\task;

/**
* Cron task: prune private messages older than configured days.
*/
class prune_pms extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbbworld\pmread\service\pm_manager */
	protected $pm_manager;

	/** @var \phpbb\log\log_interface */
	protected $log;

	/** @var int Default interval between runs (1 hour) */
	protected $cron_frequency = 3600;

	/** @var int Max messages deleted in a single cron pass */
	protected $max_per_run = 500;

	/**
	* @param \phpbb\config\config $config
	* @param \phpbbworld\pmread\service\pm_manager $pm_manager
	* @param \phpbb\log\log_interface $log
	*/
	public function __construct(\phpbb\config\config $config, \phpbbworld\pmread\service\pm_manager $pm_manager, \phpbb\log\log_interface $log)
	{
		$this->config = $config;
		$this->pm_manager = $pm_manager;
		$this->log = $log;
	}

	/**
	* {@inheritdoc}
	*/
	public function run()
	{
		$days = isset($this->config['pmread_auto_delete_days']) ? (int) $this->config['pmread_auto_delete_days'] : 0;
		$deleted = $this->pm_manager->delete_pms_older_than($days, $this->max_per_run);

		$this->config->set('pmread_auto_delete_last_gc', time(), false);

		if ($deleted > 0)
		{
			$this->config->set('pmread_prune_notice_time', time(), false);
			$this->config->set('pmread_prune_notice_days', $days, false);
			$this->config->set('pmread_prune_notice_count', $deleted, false);

			$user_id = defined('ANONYMOUS') ? ANONYMOUS : 1;
			$this->log->add('admin', $user_id, '127.0.0.1', 'LOG_PMREAD_AUTO_DELETED', false, array($deleted, $days));
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function is_runnable()
	{
		return !empty($this->config['pmread_auto_delete'])
			&& (int) $this->config['pmread_auto_delete_days'] > 0;
	}

	/**
	* {@inheritdoc}
	*/
	public function should_run()
	{
		$last = isset($this->config['pmread_auto_delete_last_gc']) ? (int) $this->config['pmread_auto_delete_last_gc'] : 0;
		return $last < time() - $this->cron_frequency;
	}
}
