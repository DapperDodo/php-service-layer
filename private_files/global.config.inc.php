<?php

	require_once('local.config.inc.php');

		// new policy: by default, don't cache!
		// this overwrites any old settings from local configs
	$default_expire_http 	= 0;
	$default_expire_service = 0;
		
		// in the cache map, we can use these default vars for convenience
	$short	= 60*60;
	$long	= 60*60*16;
		
	$cache_map = array
	(
		'module_1' => array
		(
		),
		'module_2' => array
		(
		),
		'module_3' => array
		(
			'function_1' => array
			(
				'http'		=> $long,
				'service'	=> $long,
				'purge' 	=> array('cache_set_1', array())
			),
			'function_2' => array
			(
				'http' 		=> $long,
				'service' 	=> $long,
				'purge' 	=> array('cache_set_1', array())
			),
			'function_3' => array
			(
				'http' 		=> $long,
				'service' 	=> $long,
				'purge' 	=> array('different_cache_set', array())
			),
			'function_4' => array
			(
				'http' 		=> $short,
				'service' 	=> $short,
			),
		),		
	);
        
        
      

	/**
	 * Nicely print arrays
	 *
	 * @param $array	array to be printed
	 */
	
	function _debug_array ($array, $print = true) 
	{
		$output = '<pre>' . htmlentities(print_r($array, true)) . '</pre>';
		
		if($print)
		{
			print($output);
		}
		else
		{
			return($output);
		}
	}	

?>
