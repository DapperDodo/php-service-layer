<?php

require_once('../private_files/common/db/class.db_component_select.php');

class db_get_ad_units extends db_component_select
{
	function __invoke($site_id, $ad_pagetype_id, $ad_group_id)
	{
		if($ad_group_id != '' && $ad_group_id > 0)
		{
			return parent::__invoke
			(
				"
					SELECT 
							ad_unit_id AS `ad_unit-id`,
							ad_openx_unit_id AS `ad_openx_unit-id`,
							ad_openx_group_id AS `ad_openx_group-id`,
							ad_type_name AS ad_type,
							ad_group_name AS ad_group
					FROM 
							ad_unit
								INNER JOIN ad_type USING (ad_type_id)
								INNER JOIN ad_group USING (ad_group_id)
					WHERE 
							site_id = ?
					 AND 	ad_pagetype_id = ?
					 AND	ad_unit.ad_group_id = ?
				",
				array
				(				
					$site_id,
					$ad_pagetype_id,
					$ad_group_id
				)
			);
		}
		else
		{
			return parent::__invoke
			(
				"
					SELECT 
							ad_unit_id AS `ad_unit-id`,
							ad_openx_unit_id AS `ad_openx_unit-id`,
							ad_openx_group_id AS `ad_openx_group-id`,
							ad_type_name AS ad_type
					FROM 
							ad_unit
								INNER JOIN ad_type USING (ad_type_id)
					WHERE 
							site_id = ?
					 AND 	ad_pagetype_id = ?
				",
				array
				(				
					$site_id,
					$ad_pagetype_id
				)
			);
		}
	}
}

?>
