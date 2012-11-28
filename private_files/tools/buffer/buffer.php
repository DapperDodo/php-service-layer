<?php

require_once 'mcpool.php';

/**
 * Generic class for pooled memcache buffers
 */
abstract class Buffer {

    /**
     * Enable/disable logs
     *
     * @var boolean
     */
    var $bEnableLogs = false;
    /**
     * Memcache pool instance
     *
     * @var object
     */
    var $oMemcachePool;
    /**
     * Error message
     * 
     * @var string
     */
    var $sError = '';
    /**
     * Constructor
     *
     * @param array 	$servers	List of memcache server addresses (ip's)
     *
     */
    public function __construct() {
		INFO('BUFFER:');
        if (class_exists('Mcpool')) {
            
            $this->oMemcachePool = Mcpool::singleton();
            
            if (is_object($this->oMemcachePool)) {

				$this->init();

            } else {

                $this->sError = 'Memcache object not available';

            }

        } else {

            $this->sError = 'Memcache pool class is not available';

        }

    }
	
    /**
     * Override this to initialize the buffer after successful connecting to a memcache pool
     *
     */
	protected function init()
	{
	}

	/**
	 * flush memcache
	 */
	public function flush()
	{
		return $this->oMemcachePool->flush();	
	}
	
	/**
	 * show memcache statistics
	 */
	public function stats()
	{
		return $this->oMemcachePool->stats();
	}
}

?>
