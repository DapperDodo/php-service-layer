<?php

require_once('../private_files/common/process/field_processors/class.field_processor.php');

class fp_bad_words extends field_processor
{
	function filter_bad_words($content)
	{
		// for example filter bad words here
		
		return $content;
	}	
}


?>
