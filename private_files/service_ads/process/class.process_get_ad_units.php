<?php

require_once('../private_files/common/process/class.process_component.php');

class process_get_ad_units extends process_component
{
	function __invoke($site_id, $ad_pagetype_id, $ad_group_id)
	{
		$data = $this->db->get_ad_units($site_id, $ad_pagetype_id, $ad_group_id);

		switch($ad_pagetype_id)
		{
			case 3:	// index/frontpage
				$this->process
				(
					$data, 
					array
					(
						'ad_units_index'
					)
				);
				break;
				
			// add field processors for other pagetype 1x1 handling here
			
			default:
				break;
		}
		
		return $data;
	}
}

?>