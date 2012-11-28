<?php

abstract class db_component
{
		// by default, operations need to be done on a master db server
	function force_master()
	{
		return true;
	}
	
		// compile and run a database query, and return the results
		// params must be in the same order as slots (?) appear in the query
	function __invoke($query, $params = array())
	{
		INFO('query:');
		INFO($query);
		INFO('params:');
		if(count($params) == 0)
		{
			INFO('no params');
		}
		else
		{
			INFO($params);
		}
		
			try 
			{
					// configure PDO, the db abstraction layer
				$dbh = dbpool::instance($this->force_master());
				if($dbh === null)
				{
					throw new PDOException('connection could not be made');
				}
				else
				{
						// prepare and execute the query
					$stmt = $dbh->prepare($query);
					$time_start_query = microtime(true);
					$result = $stmt->execute($params);
					$time_end_query = microtime(true);

					if($result === true)
					{
						INFO('query execution succeeded');
						$timetaken = $time_end_query - $time_start_query;
						DB(microtime().' - '.$timetaken.' - '.get_class($this).' - '.$query.'<br />');
						return $this->return_data($stmt);
					}
					else
					{
						INFO('query execution failed');
						INFO($stmt->errorInfo());
						return '9998';
					}
				}
			}
			catch (PDOException $e) 
			{
				INFO('query execution failed: '.$e->getMessage());
				INFO($e->getMessage());
			}	
		return '9999';	//technical error
	}
	
	abstract function return_data(&$statement);
}

?>