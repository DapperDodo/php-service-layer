<?php

/**
 * This is a wrapper for the PDO
 * with a singleton method
 *
 */
 
class dbpool extends PDO
{
    static $instance = null;
	static $servers = null;

    // Statistics
    public static $instanceCount = 0;
    public static $usageCount = 0;
    public static $signature = '';
    public static $type = '';

    // Singleton method
    public static function &instance($force_master = false) 
	{
			// if we have a slave instance running, but require a master, reset the instance and the servers
		if($force_master && self::$instance !== null && self::$type === 'slave')
		{
			self::$instance	= null;
			self::$servers	= null;
		}
	
		$max_attemps = 3; //in case of 'MySQL has gone away' situations, how many times should we retry by default?
        $attempt = 1;
        do 
		{
			try
			{
				if(self::$instance === null) 
				{
					INFO('connecting to db ...');
					
					if(self::$servers === null)
					{
						if($force_master)
						{
							DB('forced master!'.'<br />');
							
								// randomize the available master servers
							global $cfg_db_masters;
							shuffle($cfg_db_masters);
							self::$servers = $cfg_db_masters;
						}
						else
						{
								// randomize the available master+slave servers
							global $cfg_db_slaves;
							shuffle($cfg_db_slaves);
							self::$servers = $cfg_db_slaves;
						}
						if($max_attemps < count(self::$servers))
						{
							$max_attemps = count(self::$servers);
						}
					}
					
						// pick the first server
					if(count(self::$servers) > 1)
					{
							// if there are more servers in the server pool, remove the one we choose
						$server = array_shift(self::$servers);
					}
					else
					{
							// always keep the last server, because we may need to retry connecting on the same server if all else fails
						$server = self::$servers[0];
					}

					$dsn			= "mysql:host={$server['host']};dbname={$server['name']}";
					$username		= $server['user'];
					$password		= $server['pass'];
					$type			= $server['type'];
					
					self::$signature = $username.'@'.$dsn.' ('.$type.')';
					self::$type = $type;
					self::$instanceCount++;
					
					/*
					if($attempt <= ($max_attemps-1) && $type === 'slave') 
					{
						throw new PDOException('connection is foobar -simulated-');
					}
					*/
					
					self::$instance = @new self($dsn, $username, $password, array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_TIMEOUT => 1800, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
					DB('connected to '.self::$signature.'<br />');
				}
				
				self::$usageCount++;
				return self::$instance;
			}
			catch (PDOException $e) 
			{
				INFO('db connection failed (attempt '.$attempt.', '.self::$signature.') : '.$e->getMessage());
				DB('db connection failed (attempt '.$attempt.', '.self::$signature.') : '.$e->getMessage().'<br />');
			}	
		} while ($attempt++ <= $max_attemps);
		
		INFO('db connection failed after 3 attempts');
		DB('db connection failed after 3 attempts'.'<br />');
		return null;
    }
}
?>
