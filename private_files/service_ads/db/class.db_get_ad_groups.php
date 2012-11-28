<?php

require_once('../private_files/common/db/class.db_component_select.php');

class db_get_ad_groups extends db_component_select
{
	function __invoke()
	{
		return parent::__invoke
		(
			"
				SELECT	ad_group_id as `ad-group_id`,
						ad_group_name as `ad-group_name`
				FROM 	ad_group
				WHERE	ad_group_name != ''
				ORDER	BY ad_group_name asc 
			",
			array
			(				
			)
		);
	}
}

?>
