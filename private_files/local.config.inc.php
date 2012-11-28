<?php

	//error_reporting(E_STRICT | E_ALL);
	error_reporting(E_ALL ^ E_NOTICE);
	//error_reporting(0);
	
		// infolog config
		// true: return infolog content
	$infolog	= false;
	$calllog	= false;
	$locklog	= false;
	$dblog		= false;
	$purgelog	= false;
	
		// master servers for all operations
	$cfg_db_masters = array
	(
		array
		(
			'host' => 'IP_of_master_1',
			'user' => 'dbusr',
			'pass' => '',
			'name' => 'mydb',
			'type' => 'master'
		),
		array
		(
			'host' => 'IP_of_master_2',
			'user' => 'dbusr',
			'pass' => '',
			'name' => 'mydb',
			'type' => 'master'
		),
	);
	
		// master+slave servers for read only operations
	$cfg_db_slaves = array
	(
		array
		(
			'host' => 'IP_of_slave_1',
			'user' => 'dbusr',
			'pass' => 'test',
			'name' => 'mydb',
			'type' => 'slave'
		),
		array
		(
			'host' => 'IP_of_slave_2',
			'user' => 'dbusr',
			'pass' => 'test',
			'name' => 'mydb',
			'type' => 'slave'
		),
	);
	
		// memcache config
	$memcache		= false;
	$memcache_port  = '11211';
	$memcache_hosts = array
	(
		'127.0.0.1',
		'1.2.3.4'
	);

		// how to run automated tasks like db cleanups
	$taskrunner = false;
	$taskrunner_trigger = 'poormanscron';
	$taskrunner_frequency = 10; // trigger spacing in seconds
	$taskrunner_tasks = array
	(
		'user_played_rollup', 'process_buffer'
	);
?>