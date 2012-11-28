<?php

require_once('../private_files/common/class.db.php');

abstract class process_component
{
	var $db; 		// a handle to the layer below, from where raw data is retrieved from the database
	private $dir;	// the directory where components are present
	
	public function __construct($dir)
	{
		$this->db = new db($dir);
		$this->dir = $dir;
	}
	
	protected function process(&$data, $field_processors)
	{
		if(is_array($data) && count($data) > 0)
		{
				// load the field processors
			$processors = array();
			foreach($field_processors as $field_processor)
			{
				$field_processor = 'fp_'.$field_processor;
				$file = '../private_files/'.$this->dir.'/process/field_processors/class.'.$field_processor.'.php';
				if(is_file($file))
				{
					require_once($file);
					$processors[] = new $field_processor;
					INFO('field_processor: '.$field_processor);
				}
				else
				{
					INFO('field_processor not found: '.$field_processor);
				}
			}
			
				// for each record, dispatch processing to the field processors
			foreach($data as $key => &$val)
			{
				foreach($processors as $processor)
				{
					$processor($val);
				}
			}
			unset($val);
		}
	}
}

?>