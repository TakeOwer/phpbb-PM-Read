<?php
/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\migrations;

class release_1_4_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_3_2');
	}

	public function update_data()
	{
		return array(
			// Notice is on by default: transparency is the safer default.
			// config.add is a no-op when the key already exists.
			array('config.add', array('pmread_pm_notice', 1)),
			array('config.update', array('pmread_version', '1.4.0')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('pmread_pm_notice')),
		);
	}
}
