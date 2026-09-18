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
* Search, print view and filter-aware export. No schema or config change:
* the version bump is what marks the release as applied.
*/
class release_1_5_0 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbworld\pmread\migrations\release_1_4_0');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('pmread_version', '1.5.0')),
		);
	}
}
