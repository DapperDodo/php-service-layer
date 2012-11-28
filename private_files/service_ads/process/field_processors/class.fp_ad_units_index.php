<?php

require_once('../private_files/common/process/field_processors/class.field_processor.php');

class fp_ad_units_index extends field_processor
{
	function __invoke(&$data)
	{
		if($data['ad_type'] === '1x1')
		{
				// don't return the 1x1 ad units when its config setting is OFF
			global $site_id;
			
			$sites = service::singleton('sites');
				//TODO: the cast to string is needed because the global $site_id was cast to (int) at the start of the request. 
			$config = $sites->get_site_config((string)$site_id);

			if($config['config_index_1x1'] == 0)
			{
				$data['ad_unit-id'] = 0;
				$data['ad_openx_unit-id'] = 0;
				$data['ad_openx_group-id'] = 0;
			}
		}
	}
}

?>