<?php

    /**
     * Decodes arguments
     *
     * @param array $param  Parameters from POST or GET
     */
    function services_decode_param() 
	{
		foreach ($_GET as $key => &$val) 
		{
				// Replace back special characters and decode
			$val = base64_decode(strtr(urldecode($val), '-_,', '+/='));
		}
		foreach ($_POST as $key => &$val) 
		{
				// Replace back special characters and decode
			$val = base64_decode(strtr(urldecode($val), '-_,', '+/='));
		}
    }
	
						
    /**
     * apply formatting to output
     */
	function encode($data)
	{
		global $capsify, $nest;
		
		if(is_array($data))
		{
			if($nest)
			{
				$data = nest($data);
			}
			if($capsify)
			{
				$data = capsify($data);
			}
			return json_encode($data);
		}
		else
		{
			return $data;
		}
	}
	
	function capsify($indata)
	{
			// recursive function, turns assoc array keys into {CAPS} format. This format is used by the generator templating system.
		$data = array();
		foreach($indata as $key => $row)
		{
			if(!is_numeric($key)) $key = '{'.strtoupper($key).'}';
			if( is_array  ($row)) $row = capsify($row);
			$data[$key] = $row;
		}
		
		return $data;
	}

	function nest($indata)
	{
		if(!is_array($indata)) return $indata;
		
			// recursive function, turns flat record sets into nested (tree) data. This format is used by the ajax system.
		$data = array();
		$parents = array();

		foreach($indata as $key => $row)
		{
			if(!is_numeric($key)) 
			{
					//consolidate empty values except 0
				if($row == '' && $row !== 0) $row = '';
			
					// Turn dash seperated keys to camelcase
				if (strstr($key, '-')) {
                    $key = str_replace('- ', '', lcfirst(ucwords(str_replace('-', '- ', $key))));
                }

				$keys = explode('_', $key);
				
				if(count($keys) > 1)
				{
					$parent = $keys[0];
					$parents[$parent] = true;
					unset($keys[0]);
					$key = implode('_', $keys);
					if( is_array  ($row)) $row = nest($row);
					$data[$parent][$key] = $row;
				}
				else
				{
					if( is_array  ($row)) $row = nest($row);
					$data[$key] = $row;
				}
			}
			else
			{
				if( is_array  ($row)) $row = nest($row);
				$data[$key] = $row;
			}
		}
		
		foreach($parents as $parent => $true)
		{
			$data[$parent] = nest($data[$parent]);
		}
		
		return $data;
	}	
	
	/**
	 * Unnests nested array
	 * 
	 * @param array $indata
	 * @return array 
	 */
	function unnest($indata, $parentkey = '') {
		
		if(!is_array($indata)) return $indata;
		
		foreach ($indata as $key => $val) {
			
			if (!is_array($val)) {
				
				if (!$parentkey) {
					$outdata[$key] = $val;
				} else {
					$outdata[$parentkey.'_'.$key] = $val;
				}
				
			} else {
				
				$combined_key = $parentkey ? $parentkey.'_'.$key : $key;
				
				if (is_array($outdata)) {
					
					$outdata = array_merge($outdata, unnest($val, $combined_key));
					
				} else {
					
					$outdata = unnest($val, $combined_key);
					
				}
				
				
			}
			
		}
		
		return $outdata;
		
	}
	

?>