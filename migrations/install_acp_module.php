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
* Reinstall ACP modules using the same declarative pattern as
* Marketplace / Carousel / SearchLogger (no container access).
*/
class install_acp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_2_1');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('pmread_version', '1.3.0')),

			// Safe even if missing: module.remove returns early when not found.
			// Remove children before categories. parent=false = any parent.
			array('module.remove', array('acp', false, 'PMR_SETTINGS')),
			array('module.remove', array('acp', false, 'PMR_CONFIG')),
			array('module.remove', array('acp', false, 'PMR')),
			array('module.remove', array('acp', false, 'ACP_PMREAD_SETTINGS')),
			array('module.remove', array('acp', false, 'ACP_PMREAD_MESSAGES')),
			array('module.remove', array('acp', false, 'ACP_PMREAD')),

			// Install exactly like working salvocortesiano extensions
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
			array('module.remove', array('acp', false, 'ACP_PMREAD_SETTINGS')),
			array('module.remove', array('acp', false, 'ACP_PMREAD_MESSAGES')),
			array('module.remove', array('acp', false, 'ACP_PMREAD')),
		);
	}
}
