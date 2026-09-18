<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\migrations;

class release_1_3_2 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_3_1');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('pmread_exclude_groups', '')),
			array('config.update', array('pmread_version', '1.3.2')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('pmread_exclude_groups')),
		);
	}
}
