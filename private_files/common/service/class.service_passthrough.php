<?php

require_once('../private_files/common/service/class.service_component.php');

/*
	this default component passes the request through to the process layer without any special additions
*/
class service_passthrough extends service_component
{
	public function __invoke($action, $arguments)
	{
		return call_user_func_array(array($this->processing, $action), $arguments);
	}
}

?>