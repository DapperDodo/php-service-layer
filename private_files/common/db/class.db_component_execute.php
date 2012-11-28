<?php

require_once('class.db_component.php');

		// compile and run an execute type (INSERT, UPDATE, DELETE) database query.
		// params must be in the same order as slots (?) appear in the query
class db_component_execute extends db_component
{
	function return_data(&$statement)
	{
		INFO('query execution OK');
		INFO('rows affected: '.$statement->rowCount());
		return true;
	}
}

?>