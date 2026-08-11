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
* ACP module info (pattern aligned with salvocortesiano extensions).
*/
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\phpbbworld\pmread\acp\main_module',
			'title'		=> 'ACP_PMREAD',
			'modes'		=> array(
				'messages'	=> array(
					'title'	=> 'ACP_PMREAD_MESSAGES',
					'auth'	=> 'ext_phpbbworld/pmread && acl_a_board',
					'cat'	=> array('ACP_PMREAD'),
				),
				'settings'	=> array(
					'title'	=> 'ACP_PMREAD_SETTINGS',
					'auth'	=> 'ext_phpbbworld/pmread && acl_a_board',
					'cat'	=> array('ACP_PMREAD'),
				),
			),
		);
	}
}
