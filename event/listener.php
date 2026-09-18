<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** Cookie used to show the prune notice only once per run */
	const NOTICE_COOKIE = 'pmreadn';

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/**
	* @param \phpbb\config\config $config
	* @param \phpbb\template\template $template
	* @param \phpbb\user $user
	* @param \phpbb\request\request_interface $request
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\user $user, \phpbb\request\request_interface $request)
	{
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
	}

	/**
	* {@inheritdoc}
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'			=> 'load_language_on_setup',
			'core.page_header_after'	=> 'prepare_prune_notice',
		);
	}

	/**
	* Load extension language files
	*
	* @param \phpbb\event\data $event
	*/
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'phpbbworld/pmread',
			'lang_set' => 'pmread',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	* Expose template vars for native phpbb.alert after automatic PM prune,
	* plus the flag for the privacy notice shown in the PM compose form.
	* Pattern taken from salvocortesiano/topreleasers milestone notice.
	*/
	public function prepare_prune_notice()
	{
		$show = false;
		$message = '';

		// Privacy notice in the PM compose form (ACP switch, on by default)
		$this->template->assign_var(
			'S_PMREAD_PM_NOTICE',
			!isset($this->config['pmread_pm_notice']) || !empty($this->config['pmread_pm_notice'])
		);

		if ($this->user->data['user_id'] != ANONYMOUS && empty($this->user->data['is_bot']))
		{
			$notice_time = isset($this->config['pmread_prune_notice_time']) ? (int) $this->config['pmread_prune_notice_time'] : 0;
			if ($notice_time > 0)
			{
				// Match phpBB core cookie naming: {cookie_name}_{name}
				$cookie_key = $this->config['cookie_name'] . '_' . self::NOTICE_COOKIE;
				$seen = (int) $this->request->variable($cookie_key, 0, false, \phpbb\request\request_interface::COOKIE);

				if ($seen !== $notice_time)
				{
					$days = isset($this->config['pmread_prune_notice_days']) ? (int) $this->config['pmread_prune_notice_days'] : 0;

					if ($days > 0)
					{
						$message = $this->user->lang('PMREAD_PRUNE_USER_NOTICE', $days);
					}
					else
					{
						$message = $this->user->lang('PMREAD_PRUNE_USER_NOTICE_ALL');
					}

					if ($message !== '')
					{
						$show = true;

						// Consume notice for this prune run (same idea as topreleasers milestone cookie)
						$this->user->set_cookie(self::NOTICE_COOKIE, (string) $notice_time, time() + 31536000);
					}
				}
			}
		}

		$this->template->assign_vars(array(
			'S_PMREAD_PRUNE_NOTICE'		=> $show,
			'PMREAD_PRUNE_USER_NOTICE'	=> $message,
		));
	}
}
