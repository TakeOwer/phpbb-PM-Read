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
* Soft enable legacy module rows if present. No container usage.
*/
class release_1_2_1 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_2_0');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('pmread_version', '1.2.1')),
			array('custom', array(array($this, 'enable_pmread_modules'))),
		);
	}

	/**
	* Force PM Read ACP modules enabled and visible.
	*/
	public function enable_pmread_modules()
	{
		$sql = 'UPDATE ' . $this->table_prefix . "modules
			SET module_enabled = 1,
				module_display = 1
			WHERE module_class = 'acp'
				AND " . $this->db->sql_in_set('module_langname', array(
					'PMR',
					'PMR_CONFIG',
					'PMR_SETTINGS',
					'ACP_PMREAD',
					'ACP_PMREAD_MESSAGES',
					'ACP_PMREAD_SETTINGS',
				));
		$this->db->sql_query($sql);
	}
}
