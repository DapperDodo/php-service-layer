<?php

	/**
	 * vanilla call
	 */
	function call_service($servicename, $function, $arguments)
	{
			// dispatch the call to the service and return the result.
        $service = service::singleton($servicename);
		$result = call_user_func_array(array($service, $function), $arguments);

		return $result;
	}
	
	/**
	 * call with encoding/formatting
	 */
	function call($servicename, $function, $arguments)
	{
		$result = call_service($servicename, $function, $arguments);

		if(is_array($result))
		{
			return encode($result);
		}
		else
		{
			return $result;
		}
	}
	
	/**
	 * call with encoding/formatting and buffering
	 * 
	 * if the call result is not in memcache, dispatch the call to the service 
	 * else return the memcached result
	 * this function also json encodes the result
	 */
	function call_buffered($expire, $servicename, $function, $arguments)
	{
		global $memcache, $clogkey, $purgeinfo, $capsify, $nest, $site_id;
		
		if($memcache === false || $expire === 0)
		{
			CLOG('OFF');
			$result = encode(call($servicename, $function, $arguments));
		}
		else
		{
			$key = '[http:'.$servicename.':'.$function.':'.$expire.'] - '.json_encode($arguments).' - site '.$site_id;
			$keylock = 'lock - '.$key;
			if($nest) 		$key .= ' - nest';
			if($capsify) 	$key .= ' - capsify';
			INFO('http cache key : ['.$key.']');

			$BufferOut = new BufferOut();

				// anti stampede lock : check if another request has locked this
			if($BufferOut->get($keylock) !== false)
			{
				LOCK(microtime().' http lock wait : '.$keylock);
					// wait until lock is removed
				$i = 0;
				while ($i < 3) 
				{
					LOCK(microtime().' http lock wait, sleeping (loop '.$i.') : '.$keylock);
					usleep(0.5 * 1000000);
					if ($BufferOut->get($keylock) === false) 
					{
						LOCK(microtime().' http lock wait success (loop '.$i.') : '.$keylock);
						break;
					}
					$i++;
				}
				LOCK(microtime().' http lock wait ended : '.$keylock);
			}

			$result = $BufferOut->get($key);
			if($result === false)
			{
				CLOG('MISS');
				INFO('http cache miss');
				
					// anti stampede lock : lock this so other requests will wait until we cache the result
				$BufferOut->set($keylock, array(), 10);
				LOCK(microtime().' http lock set : '.$keylock);
					
					// get the output
				$result = encode(call($servicename, $function, $arguments));
				LOCK(microtime().' http lock output ready : '.$keylock);

					// do not cache errors
				if(!is_numeric($result))
				{
						// write the output to cache
					$BufferOut->set($key, $result, $expire);
						// remove anti stampede lock
					$BufferOut->purge($keylock);
					LOCK(microtime().' http lock remove : '.$keylock);
						// register this cache to a purge set
					purge_register($servicename, $function, $arguments, $key);
				}
				else
				{
					CLOG('DETECTED ERROR');
					INFO('http cache detected error');
						// remove anti stampede lock
					$BufferOut->purge($keylock);
					LOCK(microtime().' http lock remove : '.$keylock);
				}
			}
			else
			{
				CLOG('HIT');
				INFO('http cache hit');
			}
		}
		
		return $result;
	}
	
?>