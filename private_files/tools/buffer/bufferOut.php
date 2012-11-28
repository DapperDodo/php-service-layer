<?php

require_once 'buffer.php';

/**
 * Generic class for buffering service output in memcache
 *
 * bufferOut.php
 *
 */
class BufferOut extends Buffer 
{
	/**
	 * store the data in memcache
	 */
	public function set($key, $data, $expire=0)
	{
		INFO(__METHOD__);
		INFO('expire: '.$expire.' seconds');
		
			// dispatch to memcache pool and return any errors or success messages
		if($this->oMemcachePool->set($key, $data, 0, $expire))
		{
			INFO('buffer set OK');
			return true;
		}
		else
		{
			INFO('buffer set error: '.$this->sError);
			return false;
		}
	}
	
	/**
	 * get the data back from memcache
	 */
	public function get($key)
	{
		INFO(__METHOD__);
		return $this->oMemcachePool->get($key);	
	}
	
	/**
	 * purge a key from memcache
	 */
	public function purge($key)
	{
		INFO(__METHOD__);
		return $this->oMemcachePool->delete($key);	
	}
}

?>
