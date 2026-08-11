<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\migrations;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class release_1_0_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('pmread_version', '1.0.0')),

			// Same pattern as Marketplace / Carousel
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_PMREAD',
			)),
			array('module.add', array(
				'acp',
				'ACP_PMREAD',
				array(
					'module_basename'	=> '\phpbbworld\pmread\acp\main_module',
					'modes'				=> array('messages', 'settings'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('pmread_version')),
			array('module.remove', array(
				'acp',
				'ACP_PMREAD',
				array(
					'module_basename'	=> '\phpbbworld\pmread\acp\main_module',
					'modes'				=> array('messages', 'settings'),
				),
			)),
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_PMREAD',
			)),
		);
	}
}
