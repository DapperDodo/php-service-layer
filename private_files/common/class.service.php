<?php

require_once("../private_files/common/class.process.php");

abstract class service
{

    /**
     * Array that holds instances
     */
    private static $aSingletons;

    /**
     * Statistics
     */
    public static $aInstanceCounts;
    public static $aUsageCounts;

    private function  __construct() {
        
    }

    /**
     * Singleton method
     *
     * @return $sService
     */
    public static function singleton($sService) {

        if (!isset(self::$aSingletons[$sService])) {

            self::$aInstanceCounts[$sService]++;
            self::$aSingletons[$sService] = new $sService;

        }

        self::$aUsageCounts[$sService]++;
        return self::$aSingletons[$sService];

    }

    /**
     * We lock the clone method
     */
    public function __clone() {

        INFO('Service object clone is not allowed');
        trigger_error('Clone is not allowed.', E_USER_ERROR);

    }

	public function __call($action, $arguments)
	{
		global $memcache, $cache_map, $default_expire_service, $purgeinfo, $site_id;
		
		INFO('SERVICE:');
		CLOG($action.'&'.implode('&', $arguments), true);
		if
		(
			(
				!isset($cache_map[get_class($this)][$action]['service']) 
				&& 
				(
					!isset($default_expire_service) 
					|| 
					$default_expire_service === 0
				)
			) 
			|| 
			$memcache === false 
			|| 
			$cache_map[get_class($this)][$action]['service'] === 0
		)
		{
			INFO('no service cache');
			$result = $this->call_component($action, $arguments);
		}
		else
		{
			if(isset($cache_map[get_class($this)][$action]['service']))
			{
				$expire = $cache_map[get_class($this)][$action]['service'];
			}
			else
			{
				$expire = $default_expire_service;
			}
			INFO('service cache expire: '.$expire);
			$key = '[service:'.get_class($this).':'.$action.':'.$expire.'] - '.json_encode($arguments).' - site '.$site_id;
			$keylock = 'lock - '.$key;
			INFO('service cache key : ['.$key.']');

			$BufferOut = new BufferOut();
			
				// anti stampede lock : check if another request has locked this
			if($BufferOut->get($keylock) !== false)
			{
				LOCK(microtime().' service lock wait : '.$keylock);
					// wait until lock is removed
				$i = 0;
				while ($i < 3) 
				{
					LOCK(microtime().' service lock wait, sleeping (loop '.$i.') : '.$keylock);
					usleep(0.5 * 1000000);
					if ($BufferOut->get($keylock) === false) 
					{
						LOCK(microtime().' service lock wait success (loop '.$i.') : '.$keylock);
						break;
					}
					$i++;
				}
				LOCK(microtime().' service lock wait ended : '.$keylock);
			}
			
			$result = $BufferOut->get($key);
			if($result === false)
			{
				INFO('service cache miss');
				
					// anti stampede lock : lock this so other requests will wait until we cache the result
				$BufferOut->set($keylock, array(), 10);
				LOCK(microtime().' service lock set : '.$keylock);
									
				$result = $this->call_component($action, $arguments);
				LOCK(microtime().' service lock output ready : '.$keylock);
				
					// do not cache errors
				if(!is_numeric($result))
				{
						// write the output to cache
					$BufferOut->set($key, $result, $expire);
						// remove anti stampede lock
					$BufferOut->purge($keylock);
					LOCK(microtime().' service lock remove : '.$keylock);
						// register this cache to a purge set
					purge_register(get_class($this), $action, $arguments, $key);
				}
				else
				{
					INFO('service cache detected error');
						// remove anti stampede lock
					$BufferOut->purge($keylock);
					LOCK(microtime().' service lock remove : '.$keylock);
				}
			}
			else
			{
				INFO('service cache hit');
			}
		}
		
		return $result;	
	}
	
	function call_component($action, $arguments)
	{
			// determine the component to delegate this call to
		$component_name = 'service_'.$action;
		$component_file = '../private_files/service_'.get_class($this).'/service/class.'.$component_name.'.php';
		if(!is_file($component_file))
		{
			INFO('no component');
			$component_name = 'service_passthrough';
			$component_file = '../private_files/common/service/class.'.$component_name.'.php';
			$arguments = array($action, $arguments);
		}
			// instanciate the component
		INFO('delegating to component '.$component_name);
		require_once($component_file);
		$component = new $component_name('service_'.get_class($this));
		
			// delegate the call and return the result
		return call_user_func_array(array($component, '__invoke'), $arguments);
	}	
}

?>