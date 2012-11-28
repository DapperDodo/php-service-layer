<?php

class db
{
	private $dir;	// the directory where components are present
	
	public function __construct($dir)
	{
		$this->dir = $dir;
	}
	
			// dispatch to a db component
	public function __call($action, $arguments) 
	{
		INFO('DB:');
		
			// determine the component to delegate this call to
		$component_name = 'db_'.$action;
		$component_file = '../private_files/'.$this->dir.'/db/class.'.$component_name.'.php';
		if(!is_file($component_file))
		{
			INFO('no component');
			INFO('API function no implemented yet');
			return '0003';
		}
		else
		{
				// instanciate the component
			INFO('delegating to component '.$component_name);
			require_once($component_file);
			$component = new $component_name($this->dir);
			
				// dispatch and return the result
			return call_user_func_array(array($component, '__invoke'), $arguments);
		}
	}
}

?>