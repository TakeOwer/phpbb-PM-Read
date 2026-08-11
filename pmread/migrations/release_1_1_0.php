<?php

/**
*
* @package PMRead
* @copyright (c) 2014 DeaDRoMeO ; phpbbworld.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbworld\pmread\migrations;

class release_1_1_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_0_0');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('pmread_version', '1.1.0')),
		);
	}
}
