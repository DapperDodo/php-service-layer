<?php

require_once('../private_files/common/service/class.service_component.php');

class service_get_ad_units extends service_component
{
	function __invoke($site_id, $ad_pagetype_id, $ad_group_id)
	{
		$data = $this->processing->get_ad_units($site_id, $ad_pagetype_id, $ad_group_id);
		
		$output = array();
		foreach($data as $ad)
		{
			$output[$ad['ad_type']] = $ad;
		}
		
        return $output;
	}
}

?>
