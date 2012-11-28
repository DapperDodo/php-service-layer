<?php

		/* register a memcache key to a purge set */
	function purge_register($servicename, $function, $arguments, $key)
	{
		global $memcache, $cache_map;
		
		if($memcache === true)
		{
				// is purging defined for this service call?
			if(isset($cache_map[$servicename][$function]['purge']))
			{
				PLOG('PURGE REGISTER');
				
					// get the purge configuration for this function
				$purgeinfo = $cache_map[$servicename][$function]['purge'];
				$purge_id = $purgeinfo[0];
				$purge_argument_map = $purgeinfo[1];
				PLOG($purgeinfo);
				
					// determine the purge set
				$parguments = array();
				foreach($purge_argument_map as $parg)
				{
					$parguments[] = $arguments[$parg];
				}
				$pkey = '[purge:'.$purge_id.'] - '.json_encode($parguments);
				PLOG('pkey : '.$pkey);
				
					// get the purge set from memcache
				$BufferOut = new BufferOut();
				$serializedkeys = $BufferOut->get($pkey);
				if($serializedkeys === false)
				{
					$keys = array();
					PLOG('(no old purge keys)');
				}
				else
				{
					$keys = json_decode($serializedkeys, true);
					PLOG('old purge keys : ');
					PLOG($keys);
				}
				
				if(!in_array($key, $keys))
				{
						// add the key to the purge set
					$keys[] = $key;
					PLOG('new purge keys : ');
					PLOG($keys);
					
						// write the purge set back to memcache
					$BufferOut->set($pkey, json_encode($keys), 0);
				}
				else
				{
					PLOG('key already present');
				}
			}
		}
	}
	
		/* purge a purge set */
	function purge($purge_id, $arguments = array())
	{
		global $memcache;
		
		if($memcache === true)
		{
			PLOG('PURGE');
			
				// determine the purge set
			$pkey = '[purge:'.$purge_id.'] - '.json_encode($arguments);
			PLOG('pkey : '.$pkey);
			
				// get the purge set from memcache
			$BufferOut = new BufferOut();
			$serializedkeys = $BufferOut->get($pkey);
			if($serializedkeys === false)
			{
				PLOG('(no keys to purge)');
			}
			else
			{
					// purge all keys in the purge set
				$keys = json_decode($serializedkeys, true);
				PLOG('registered keys : ');
				PLOG($keys);
				foreach($keys as $key)
				{
					$BufferOut->purge($key);
				}
				
					// finally purge the set index itself
				$BufferOut->purge($pkey);
			}
		}
	}
?>