<?php

class taskrunner
{
	static function process()
	{
		global $taskrunner_tasks;
		
		INFO('taskrunner process');
		INFO($taskrunner_tasks);
		
			// for each task, if needs_running, run the task
			// tasks are configured in global $taskrunner_tasks
		foreach($taskrunner_tasks as $task)
		{
			INFO($task);
			require_once('tasks/'.$task.'.php');
			if($task::needs_running())
			{
				$task::run();
			}
		}
	}
}

?>