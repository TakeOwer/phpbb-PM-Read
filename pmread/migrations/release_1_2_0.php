<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\migrations;

class release_1_2_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_1_0');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('pmread_auto_delete', 0)),
			array('config.add', array('pmread_auto_delete_days', 90)),
			array('config.add', array('pmread_auto_delete_last_gc', 0, true)),
			array('config.update', array('pmread_version', '1.2.0')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('pmread_auto_delete')),
			array('config.remove', array('pmread_auto_delete_days')),
			array('config.remove', array('pmread_auto_delete_last_gc')),
		);
	}
}
