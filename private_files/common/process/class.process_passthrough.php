<?php

require_once('../private_files/common/process/class.process_component.php');

/*
	this default component passes the request through to the db layer without any processing
*/
class process_passthrough extends process_component
{
	public function __invoke($action, $arguments)
	{
		return call_user_func_array(array($this->db, $action), $arguments);
	}
}

?>