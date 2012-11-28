<?php

/**
 * Manages the $_ENV global variable from a MySQL Database. 
 *
 * Preequisites :
 * DB should have a table named envvar(content - varchar(65000) , change_time - int(10)).
 * envvar has at least one row of information on it.
 * 
 * Notes :
 * Because this class is intended for performance, do not store gentle data.
 * Requests may overwrite other requests' data sometimes.
 * Data is fetched from DB at the very start of the request, and do not check for changes in DB afterwards.
 * 
 */
class EnvManager{
	
	/**
	 * class variable that holds the SQL query result
	 * @var array
	 */
	private $aEnvVars;
	
	/**
	 * Constructs EnvManager. Takes path and file from local.config
	 */
	public function __construct(){
		$this->init();		
	}
	
	public function __destruct(){
		
	}
	
	/**
	 * Fills _ENV content
	 */
	public function init(){
				
		if(!isset($this->aEnvVars)){
			$this->aEnvVars = $this->fetchEnvVars();
		}
		
		foreach ($this->aEnvVars as $key => $value) {
			$_ENV[$key] = $value;
		}
	}
	
	/**
	 * Fetch _ENV content from DB
	 * @return array <string, mixed>
	 */
	protected function fetchEnvVars(){

		// configure PDO, the db abstraction layer
		$dbh = dbpool::instance(true);	//force master

		$query = "SELECT * FROM envvar WHERE id=1";

		$resultSerialized = "";
		
	 	foreach ($dbh->query($query) as $row){
        	$resultSerialized = $row[1];
        }
		$dbh = null;

		// There's only one row in db for user defined contents in $_ENV
		// It is stored as serialized		
		$aEnvVars = array();
		
		// Array of user defined contents
		$content = json_decode($resultSerialized, true);
		
		// If content is null
		if(!$content) 
			$content = array();
			
		return $content;
	}
	
	/**
	 * If there's a change in $_ENV, write it to DB
	 */
	public function writeChanges(){
		
		/*
		 * Fetch
		 */ 
		$aEnvVars = $this->aEnvVars;
		
		/*
		 * Compare
		 */ 
		//$aChanged = $this->compareEnvVars($_ENV, $aEnvVars);
		$aChanged = $this->hasArrayChanged($_ENV, $aEnvVars);
		
		/*
		 * Write
		 */
		if($aChanged){
			$this->saveEnv();
		}
	}
	
	/**
	 * Write _ENV to DB
	 */
	protected function saveEnv(){
		// configure PDO, the db abstraction layer
		$dbh = dbpool::instance(true);	//force master
		
		$content = json_encode($_ENV);

		if(strlen($content) > 16000){
			trigger_error("Variable size exceeds maximum limit");
		}
		
		$query = "INSERT INTO envvar VALUES(1,'". $content ."','". time() ."') ON DUPLICATE KEY UPDATE content = '". $content ."', change_time = '". time() ."'";
		$dbh->exec($query);
		
		$dbh = null;
	}	
	
	/**
	 * Compares two array and returns true if a difference is found
	 * @param array $aNew new state
	 * @param array $aOld old state
	 * @return boolean true if changed
	 */
	protected function hasArrayChanged($aNew, $aOld){
		
		if(count($aNew) != count($aOld)){ 
			// has changes
			return true;
		} else if(count($aNew) == 0 && count($aOld) == 0){
			// no elements
			return false;
		}
		
		$aAdded = array_diff_key($aNew, $aOld);		
		if(count($aAdded) > 0) return true;
		
		$aDeleted = array_diff_key($aOld, $aNew);
		if(count($aDeleted) > 0) return true;
				
		// Get intersected keys and old values
		$intersect = array_intersect_key($aNew, $aOld);
		
		$aUpdated = array();
		foreach ($intersect as $key => $value){
			if($aOld[$key] != $aNew[$key] ){
				// value changed - add new value to updated list
				$aUpdated[$key] = $aNew[$key];
				return true;
			}
		}
		if(count($aUpdated) > 0) return true;
		
		return false;
	}
	
	/**
	 * Compares two key-value array and returns the added, deleted and inserted elements
	 * @param array $aNew new state
	 * @param array $aOld old state
	 * @return array of changes:
	 */
	protected function compareArrays($aNew, $aOld){
		$aAdded = array_diff_key($aNew, $aOld);
		$aDeleted = array_diff_key($aOld, $aNew);
		
		// Get intersected keys and old values
		$intersect = array_intersect_key($aNew, $aOld);
		
		$aUpdated = array();
		foreach ($intersect as $key => $value){
			if($aOld[$key] != $aNew[$key] ){
				// value changed - add new value to updated list
				$aUpdated[$key] = $aNew[$key];
			}
		}

		INFO(" Newly added servers to offline list :" + count($aAdded));
		INFO(" Servers which made a time update on offline list :" + count($aUpdated));
		INFO(" Servers that goes online and deleted from offline list :" + count($aDeleted));
		
		$aChanged['added_updated'] = array_merge($aAdded, $aUpdated);		
		$aChanged['deleted'] = $aDeleted;
		
		return $aChanged;
	}
	
}
