<?php

/**
 * Memcache pooler class
 * memcachepool.php
 *
 * Class for using multiple memcache servers, allowing failover
 *
 * @todo Need to implement other methods of the real Memcache class
 * @todo Needs smart load balancing between servers (currently it's random)
 * @todo Can't use Sessions in machine-machine communication.
 *
 */
class Mcpool extends Memcache {

    /**
     * If caching is available (i.e can connect to any of servers)
     *
     * @var boolean
     */
    var $bCacheAvailable = true;
    /**
     * Default memcached host ip list
     *
     * Be free to extend the list, offline hosts will
     * automatically get flagged as not available.
	 * This list is configurable in local config ($memcache_hosts)
     *
     * @var array
     */
    var $aHosts;
    /**
     * Default memcached port (usually 11211)
	 * This parameter is configurable in local config ($memcache_port)
     *
     * @var string
     */
    var $sDefaultPort;
    /**
     * How often to check a host again
     * in seconds
     *
     * @var int
     */
    var $iCheckTimeout = 200;
    /**
     * Connection timeout
     * This effects performance violently.
     * It might miss some connections when too low,
     * and will be very slow when too high.
     * 0.3 is our default. 1 is php default.
     *
     * @var int
     */
    var $iConnTimeout = 0.5;
    /**
     * Mcpool instance
     * We need it for singleton
     */
    private static $oMe;
    /**
     * Memcache instances
     *
     * @var object
     */
    var $oTestInstance;
    var $oRealInstance;

    /**
     * Constructor
     *
     * @param array 	$servers	List of memcache server addresses (ip's)
     */
    private function __construct() {
		INFO('MEMCACHE POOL:');
		global $memcache_hosts, $memcache_port;
		
		$this->aHosts = $memcache_hosts;
		INFO($this->aHosts);
		$this->sDefaultPort = $memcache_port;

        // Get current time
        $iTime = time();

        $onlineCount = 0;
        $isConnected = true;
        $serverCount = count($this->aHosts);
        
        $this->oRealInstance = new Memcache;
        // We use a test instance to test connection, because a real connection
        // would reset the server pool.
        $this->oTestInstance = new Memcache;

       	if(isset($_ENV['offline_servers']))
		{
			INFO('Offline Servers:');
			INFO($_ENV['offline_servers']);
		}
		
        foreach ($this->aHosts as $index => $host) {

        	if(isset($_ENV['offline_servers'])){
        		$host_offline = array_key_exists($host, $_ENV['offline_servers']);
        	} else {
        		$host_offline = false;
        	}	
        	
            INFO('Check host #'.$index.': '.$host);
            if (!$host_offline
            		OR ($host_offline 
            			AND (($iTime - $_ENV['offline_servers'][$host]) > $this->iCheckTimeout))) {
                INFO('Try connecting '.$host);

                // Try to connect to this host
                if ($this->oTestInstance->connect($host, $this->sDefaultPort, $this->iConnTimeout)) {

                    INFO('Connected: '.$host);
					                    
                    if($host_offline){
	                    // Server was offline, but connected now. Delete from offline list
	                    INFO('Making server online : '.$host);
	                    unset($_ENV['offline_servers'][$host]);
	                    // $oServerManager->flagOnline($host);
                    }
                    
                    // Add host to server pool
                    $this->oRealInstance->addServer($host);
                    // Only close if connection was success
                    $this->oTestInstance->close();
                    
                    $onlineCount ++;
                    

                } else {
					
                    INFO('Could not connect: '.$host);
                    
//                    // Flag the server as offline
					INFO('Making server offline : '.$host);
                    $_ENV['offline_servers'][$host] = time();
					//$oServerManager->flagOfflineServer($host);
                                        
                    $this->oRealInstance->addServer($host, $this->sDefaultPort, true, 1, 1, -1, false);
                }

                INFO('Flag time: '.$iTime);

            } else {

                INFO('Skipping tests because '.$host.' is already marked.');

                if (!key_exists($host, $_ENV['offline_servers'])) {
                    // Add host to server pool
                    @$this->oRealInstance->addServer($host);
                } else {
                    $this->oRealInstance->addServer($host, $this->sDefaultPort, true, 1, 1, -1, false);
                }
                
            }

        }
        
        // Check if we are connected
        if ($this->set('connection_test_variable', 1) === false) {

            INFO('Connection test failed.');

            $isConnected = false;
        }

        if ($onlineCount == 0 OR $isConnected == false) {

            // Set true if some servers are not offline
            $this->bCacheAvailable = false;

            // Add a log if logger object is available
            INFO('Memcache NOT available. Total hosts: '.$serverCount.', Online: '.$onlineCount);

        } else {

            // Add a log if logger object is available
            INFO('Memcache available. Total hosts: '.$serverCount.', Online: '.$onlineCount);

        }

    }

    /**
     * Singleton method
     *
     * @return Mcpool
     */
    public static function singleton() {

        if (!isset(self::$oMe)) {

            $c = __CLASS__;
            self::$oMe = new $c;

        }

        return self::$oMe;

    }

    /**
     * We lock the clone method
     */
    public function __clone() {

        INFO('Memcache object clone is not allowed');
        trigger_error('Clone is not allowed.', E_USER_ERROR);

    }

    /**
     * Sets a variable in memcache
     *
     */
    public function set($key, $var, $compress=0, $expire=0) {
        return $this->oRealInstance->set($key, $var, $compress, $expire);
    }

    /**
     * Increment a variable by 1
     *
     */
    public function increment($key, $var) {
        return $this->oRealInstance->increment($key, $var);
    }

    /**
     * Delete a key
     *
     */
    public function delete($key) {
        return $this->oRealInstance->delete($key);
    }

    /**
     * Flush the buffer
     *
     */
    public function flush() {
        return $this->oRealInstance->flush();
    }

    /**
     * Get the buffer stats
     *
     */
    public function stats() {
        return $this->oRealInstance->getExtendedStats();
    }

    /**
     * Gets a variable from memcache
     *
     */
    public function get($key) {
        if (is_array($key)) {
            $dest = array();
            foreach ($key as $subkey) {
                $val = get($subkey);
                if (!($val === false))
                    $dest[$subkey] = $val;
            }
            return $dest;
        } else {
            return $this->oRealInstance->get($key);
        }
    }
}

?>
