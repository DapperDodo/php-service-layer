<?php

require_once('class.db_component_execute.php');

/**
	compile and run a simple INSERT database query.
	data format example:
	array
	(
		'user_played', 
		array
		(
			'game_id' => $game_id, 
			'user_id' => $user_id, 
			'site_id' => $site_id, 
			'logged_time' => $datetime
		)
	)
*/

class db_component_buffered_insert extends db_component_execute
{
	function __invoke($data)
	{
		global $memcache;
		INFO($data);
		
		if($memcache === false)
		{
			INFO('memcache OFF');
			$sqlfields = '';
			$sqlvalues = '';
			foreach($data[1] as $field => $value)
			{
				if($sqlfields != '') $sqlfields .= ', ';
				$sqlfields .= $field;
				
				if($sqlvalues != '') $sqlvalues .= ', ';
				$sqlvalues .= "?";
			}
			
			$query = "INSERT DELAYED IGNORE INTO {$data[0]} ({$sqlfields}) VALUES ({$sqlvalues})";

			return parent::__invoke($query, array_values($data[1]));
		}
		else
		{
			INFO('memcache ON');
			require_once('../private_files/tools/buffer/bufferIn.php');
			$BufferIn = new BufferIn();
			return $result = $BufferIn->add($data);
		}
	}
}

?>