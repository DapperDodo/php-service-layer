<?php

class process
{
	private $dir;	// the directory where components are present
	
	public function __construct($dir)
	{
		$this->dir = $dir;
	}
	
		// dispatch to a data processing component
	public function __call($action, $arguments) 
	{
		INFO('PROCESSING:');
		
			// determine the component to delegate this call to
		$component_name = 'process_'.$action;
		$component_file = '../private_files/'.$this->dir.'/process/class.'.$component_name.'.php';
		if(!is_file($component_file))
		{
			INFO('no component');
			$component_name = 'process_passthrough';
			$component_file = '../private_files/common/process/class.'.$component_name.'.php';
			$arguments = array($action, $arguments);
		}
		
			// instanciate the component
		INFO('delegating to component '.$component_name);
		require_once($component_file);
		$component = new $component_name($this->dir);
		
			// dispatch and return the result
		return call_user_func_array(array($component, '__invoke'), $arguments);
	}
}

?>