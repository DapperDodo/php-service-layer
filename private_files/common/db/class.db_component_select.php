<?php

require_once('class.db_component.php');

		// compile and run a database select query, and return the results
		// params must be in the same order as slots (?) appear in the query
class db_component_select extends db_component
{
		// by exception, select operations can be done on slave db instances
	function force_master()
	{
		return false;
	}

	function return_data(&$statement)
	{
			// get the data
		$data = $statement->fetchAll(PDO::FETCH_ASSOC);
		INFO('result:');
		INFO($data);
		return $data;
	}
}

?>