<?php

require_once('../private_files/common/class.process.php');

abstract class service_component
{
	var $processing; 	// a handle to the layer below
	private $dir;		// the directory where components are present
	
	public function __construct($dir)
	{
		$this->processing = new process($dir);
		$this->dir = $dir;
	}

	/************************************************
	*** private functions
	*************************************************/

	protected function get_ids($data, $idfield)
	{
		$ids = array();
		foreach($data as $row)
		{
			$ids[] = $row[$idfield];
		}
		return $ids;
	}
	
	protected function merge($array1, $array2, $keyname)
    {
		if(is_array($array1) && count($array1) > 0 && is_array($array2) && count($array2) > 0)
		{
			$data = array();
			foreach($array1 as $row)
			{
				$data[$row[$keyname]] = $row;
			}
			foreach($array2 as $row)
			{
				foreach($row as $col => $val)
				{
					$data[$row[$keyname]][$col] = $val;
				}
			}
		}
		else $data = array();
		
        return $data;
    }

	/**
	 * Merges two arrays into one key-value array. Key is extracted from array1
	 * e.g :
	 * 	array1(
	 * 		array(
	 * 				'group_id' => 1,
	 * 				'group_name' => '1 on 1 soccer',
	 * 			)
	 *  )
	 *  
	 *   array2(
	 *   	array(
	 *   			'group_game_count' => 4 
	 *   ) 
	 *   
	 *   $keyname = 'group_id'
	 *   
	 *   $result(
	 *   	1 => array(
	 *   			'group_id' => 1,
	 *   			'group_name' => '1 on 1 soccer',
	 *   			'group_game_count' => 4
	 *   	)
	 *   )
	 *   
	 *   
	 * 	
	 * @param array $array1
	 * @param array $array2
	 * @param string $keyname
	 * @return array
	 */
	protected function mergeinto($array1, $array2, $keyname)
    {
    	if(is_array($array1) && count($array1) > 0 && is_array($array2) && count($array2) > 0)
    	{
    		$data = array();
    		foreach($array1 as $row)
    		{
    			$data[$row[$keyname]] = $row;
    			foreach($array2 as $row2)
    			{
    				foreach($row2 as $col => $val)
    				{
    					$data[$row[$keyname]][$col] = $val;
    				}
    			}
    		}
    	}
    	else $data = array();

    	return $data;
    }
	
	/*
		take a page (slice) from a bigger recordset.
		returns assoc array:
		page => data slice
		pages => count of pages
	*/
	protected function page($data, $limit, $page)
	{
		if($limit == 0)
		{
			return $data;
		}
		else
		{
				// NOTE: the following statement does ABSOLUTELY NOTHING
				// except it prevents an error occurring in the pages calculation
				// on some installations. SO DO NOT REMOVE!
			$cdata = (float)count($data);
			
			$pages = ceil(count($data) / $limit);
			
			$from = $limit * $page;
			$to = $from + $limit;
			$slice = array();
			for($i = $from; $i < $to; $i++)
			{
				if(isset($data[$i]))
				{
					$slice[] = $data[$i];
				}
			}
				// NOTE: the cast to string is to prevent random cases where a JSON number is unreadable for javascript.
				// this is a workaround. Remove it when the browser bug is solved.
			return array('page' => $slice, 'pages' => (string)$pages);	// pages start with 0, so if pages=8, the last page=7 !
		}
	}
}

?>