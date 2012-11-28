<?php

require_once('../private_files/tools/buffer/bufferIn.php');

class process_buffer {
    
	static function needs_running() {
		
		$frequency = 15; // run this once per 15 seconds
        
		$now = time();
		
		// if last run was > x seconds ago, return true
		if(!isset($_ENV['process_buffer_last_run']) || ($_ENV['process_buffer_last_run'] + $frequency) < $now) {

			$_ENV['process_buffer_last_run'] = $now;
			return true;

		} else {

			return false;

		}

	}
	
	static function run() {

        INFO('Processing the buffer');

        $BufferIn = new BufferIn();
        $process = $BufferIn->process();

        // log if any error messages
        INFO($process);

	}

}

?>