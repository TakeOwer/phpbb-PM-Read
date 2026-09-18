<?php
/**
*
* @package PMRead
* Legacy wrapper kept for old module rows until install_acp_module migration runs.
*
*/

namespace phpbbworld\pmread\acp;

class pmread_info extends main_info
{
	public function module()
	{
		$data = parent::module();
		$data['filename'] = '\phpbbworld\pmread\acp\pmread_module';
		return $data;
	}
}
