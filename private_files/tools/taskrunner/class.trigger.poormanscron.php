<?php

require_once('class.taskrunner.php');

class poormanscron
{
	static function process()
	{
		global $taskrunner_frequency;
		 
		INFO('poormanscron process');
		INFO('frequency: '.$taskrunner_frequency);
		INFO('last trigger: '.$_ENV['poormanscron_last_trigger']);
		$now = time();
		INFO('now: '.$now);
		
			// if last trigger was > x seconds ago, run taskrunner::process()
		if(!isset($_ENV['poormanscron_last_trigger']) || ($_ENV['poormanscron_last_trigger'] + $taskrunner_frequency) < $now)
		{
			INFO('triggering!');
			$_ENV['poormanscron_last_trigger'] = $now;
			taskrunner::process();
		}
	}
}

?>