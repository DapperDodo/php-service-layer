<?php

require_once 'buffer.php';

/**
 * Generic class for collecting db inserts in memcache
 *
 * It's using a mix of memcache and two memory type mysql tables.
 * Memcache for holding the data, and db tables to hold key lists.
 *
 * bufferIn.php
 */
class BufferIn extends Buffer{

    /**
     * Text to prepend to In Buffer variables
     *
     * @var string
     */
    var $sKeyPrefix = 'buffer_in_';

    /**
     * Table prefix for keylist tables
     *
     * @var string
     */
    var $sTablePrefix = 'buffer_queue_';

    /**
     * Flag we use to switch the double buffer.
     * Possible values are A and B
     *
     * @var string
     */
    var $sCurrentBuffer;

    /**
     * Default flag
     *
     * @var string
     */
    var $sDefaultBuffer = 'a';

    /**
     * PDO object
     *
     * @var PDO
     */
    var $oDatabase;

    /**
     * Creates a pdo instance
     *
     * @global string $cfg_db_host
     * @global string $cfg_db_name
     * @global string $cfg_db_user
     * @global string $cfg_db_pass
     */
    protected function init() {
        global $cfg_db_host, $cfg_db_name, $cfg_db_user, $cfg_db_pass;

        $this->oDatabase = dbpool::instance("mysql:host={$cfg_db_host};dbname={$cfg_db_name}", $cfg_db_user, $cfg_db_pass);

    }

    /**
     * Saves a data array in memcache and adds key to the key list
     *
     * @param array     $data_arr   Array consisting of two elements.
     *                              First element is always the table name.
     *                              Second element is an array which is a
     *                              field => value hash.
     *                              Example: array('users', array('id'=>5, 'foo'=>'bar'))
     *
     * @return mixed
     */
    public function add($data_arr) {

		INFO('buffer Add:');
        // Check data array format
        $this->check_data_format($data_arr);

        if (!$this->sError) {

            // Create random key name
            $key = $this->sKeyPrefix . md5(microtime(true)/rand(1,1000));
			INFO('key: '.$key);
			
            // Try saving and adding to key list
            if ($this->save_to_memcache($key, $data_arr)) {
			
				INFO('step 1: saved to memcache');
                if (!$this->add_to_key_list($key)) {

                    // Also remove from memcache, if add to keylist did not work
					INFO('step 2: key NOT saved to db, reverting memcache save');
                    $this->remove_from_memcache($key, $value);
                    $this->sError = 'Could not add key to keylist';

                }
				else
				{
					INFO('step 2: key saved to db');
				}

            } else {

                $this->sError = 'Could not save the data in memcache';

            }

        }

        // Return any errors or success messages
		if(!$this->sError) {

			// INFO('bufferIn result OK');
			return true;

		} else {

			INFO('bufferIn Add error: '.$this->sError);
			return '9998'; // buffer error

		}

    }

    /**
     * Checks if array is in the proper format
     *
     * @param array $data_arr
     */
    function check_data_format($data_arr) {
        
        $count = count($data_arr);
        if ($count >= 2) {

            if (is_string($data_arr[0])) {

                for ($i=1;$i<$count;$i++) {

                    if (!is_array($data_arr[$i])) {

                        $this->sError = 'Data array element is in wrong format';

                    }

                }

            } else {

                $this->sError = 'Data array is in wrong format';

            }

        } else {

            $this->sError = 'Data array has wrong number of elements';

        }

    }

    /**
     * Gives current buffer name from either memcache,
     * or sets it to the default one if it's not already in memcache.
     *
     * @return string
     */
    function current_buffer() {
	
        $this->sCurrentBuffer = $this->oMemcachePool->get($this->sKeyPrefix.'current_buffer');
        if (!$this->sCurrentBuffer) {
            $this->oMemcachePool->set($this->sKeyPrefix.'current_buffer', $this->sDefaultBuffer, 0, 0);
            $this->sCurrentBuffer = $this->current_buffer();
        }
        return $this->sCurrentBuffer;
    }

    /**
     * Gives the other (not current) buffer
     *
     * @return string
     */
    function other_buffer() {
        
        return $this->current_buffer() == 'a' ? 'b' : 'a';

    }

    /**
     * Switches current keylist table,
     * so no more adds occur in the other keylist during a process
     *
     */
    function switch_current_buffer() {

        $current_buffer = $this->current_buffer();
        if ($current_buffer == 'a') {
            $new_buffer = 'b';
        } else {
            $new_buffer = 'a';
        }
        $this->oMemcachePool->set($this->sKeyPrefix.'current_buffer', $new_buffer, 0, 0);

    }

    /**
     *
     * Adds key to the key list table
     *
     * @param string $key
     * @return boolean
     */
    function add_to_key_list($key) {
        $current_buffer = $this->current_buffer();
		INFO('current_buffer: '.$current_buffer);
        $sql = 'INSERT INTO '.$this->sTablePrefix.$current_buffer.' (key_name) VALUES (?);';
        $stmt = $this->oDatabase->prepare($sql);
        $result = $stmt->execute(array($key));
        return $result;
    }

    /**
     * Fetches all the keys from given keylist table
     *
     * @param string $buffer_name
     * @return array
     */
    function get_key_list($buffer_name) {
        $sql = 'SELECT key_name FROM '.$this->sTablePrefix.$buffer_name.';';
        $stmt = $this->oDatabase->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Empties given keylist table
     *
     * @param string $buffer_name
     * @return boolean
     */
    function empty_key_list($buffer_name) {
        $sql = 'TRUNCATE TABLE '.$this->sTablePrefix.$buffer_name.';';
        $stmt = $this->oDatabase->exec($sql);
        return $stmt;
    }

    /**
     *
     * Saves the key into memcache
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    function save_to_memcache($key, $value) {
        return $this->oMemcachePool->set($key, $value);
    }

    /**
     * Removes the key from memcache
     *
     * @param string $key
     * @return boolean
     */
    function remove_from_memcache($key) {
        return $this->oMemcachePool->delete($key);
    }

    /**
     * Adds process lock, in order to prevent other processes
     * form starting
     *
     * @return boolean
     */
    function process_lock() {

        if ($this->oMemcachePool->get($this->sKeyPrefix.'process_lock')) {

            return false;

        } else {

            return $this->oMemcachePool->set($this->sKeyPrefix.'process_lock', 1, 0, 60);

        }

    }

    /**
     * Frees the process lock
     *
     * @return boolean
     */
    function remove_process_lock() {
        return $this->oMemcachePool->delete($this->sKeyPrefix.'process_lock');
    }

    /**
     * Starts a process round if no other round 
     * is working.
     *
     * @return string
     */
    function process() {

        // Try to obtain a lock
        if($this->process_lock()) {

            // NEWINFO('STARTED WRITE ROUND');

            // Switch current buffer and wait a few seconds for ongoing add's
            $this->switch_current_buffer();
            sleep(3);

            // Find out buffer to work with
            $other_buffer = $this->other_buffer();

            // Fetch list of keys
            $key_list = $this->get_key_list($other_buffer);

            // Make sure buffer is not empty
            if(count($key_list)) {

                // Get all data from memcache
                foreach ($key_list as $key) {

                    $data[$key['key_name']] = $this->oMemcachePool->get($key['key_name']);

                    // Remove data missing in memcache
                    if(!$data[$key['key_name']]) {
                        unset($data[$key['key_name']]);
                    }

                }

                // Execute all queries
                $this->write_to_db($data);

                $this->oDatabase = null;
                $this->init();

                // Empty buffer
                $this->empty_key_list($other_buffer);

            }

            // Free the lock
            $remove = $this->remove_process_lock();
            $this->oDatabase = null;

        } else {
            // NEWINFO('PROCESS LOCK FOUND');
            $this->sError = 'Process lock found. Skipping this round.';
        }

        // Return any error or success messages
        return $this->sError;

    }

    /**
     * Executes the sqls
     *
     * @param array $data_cached Array of sql statements
     */
    function write_to_db($data_cached) {

        // pass 1 : group data by table and field combination because those can be combined into single insert statements
		$data = array();

		foreach ($data_cached as $data_arr)
		{
			$sql_fields = array();
			$sql_values = array();
			$table_name = $data_arr[0];
			$count      = count($data_arr);
			foreach ($data_arr[1] as $field => $value) {
				$sql_fields[] = $field;
				$sql_values[] = $value;
			}

			$data[$table_name][implode(', ',$sql_fields)][] = $sql_values;
		}

		// pass 2 : compile a sql statement per group
		$sqls = array();
		foreach($data as $table => $fields_rows)
		{
			foreach($fields_rows as $fields => $rows)
			{
				$placeholders = implode(', ', array_fill(0, count($rows[0]), '?'));
				if($placeholders != '')
				{
					$sqls[] = array("INSERT DELAYED IGNORE INTO {$table} ({$fields}) VALUES ({$placeholders})", $rows);
				}
			}
		}

		// pass 3 : run the sql statements
		foreach($sqls as $idx => $sql)
		{
			try
			{
                // Prepere the dbal
                $this->init();

				// prepare and execute the query
				$stmt = $this->oDatabase->prepare($sql[0]);
				foreach($sql[1] as $values)
				{
					$result = $stmt->execute($values);

                    // clean up
					$this->oDatabase = null;
					if($result === false) {
						$this->sError = 'db write had one or more failures';
					}
				}
			}
			catch (PDOException $e)
			{
                $this->sError = 'PDO exception writing to db: '.$e->getMessage();
			}
		}

    }

    /**
     * Removes the pdo
     */
    function  __destruct() {
        $this->oDatabase = null;
    }

}
?>
