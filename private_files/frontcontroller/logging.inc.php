<?php

	function INFO($msg)
	{
		global $infolog;
		
		if($infolog)
		{
			$_REQUEST['infolog'][] = $msg;
		}
	}
	
	function PLOG($msg)
	{
		global $purgelog;
		
		if($purgelog)
		{
			$_REQUEST['purgelog'][] = $msg;
		}
	}
	
	function LOCK($msg)
	{
		global $locklog;
		
		if($locklog)
		{
			$_REQUEST['locklog'][] = $msg;
		}
	}

	function DB($msg)
	{
		global $dblog;
		
		if($dblog)
		{
			$_REQUEST['dblog'][] = $msg;
		}
	}
	
	
	function CLOG($msg, $sub = false)
	{
		global $calllog;
		
		if($calllog)
		{
			if($sub)
			{
				$_REQUEST['calllog_sub'][] = $msg;
			}
			else
			{
				$_REQUEST['calllog'][] = $msg;
			}
		}
	}
	
	function show_INFO()
	{
		global $infolog;
		
		if($infolog)
		{
			$fp = fopen('infolog.html', 'a');
			fwrite($fp, 'REQUEST: '.$_SERVER['REQUEST_URI']);
			fwrite($fp, _debug_array($_REQUEST['infolog'], false));
			fwrite($fp, '<hr /><br /><br />');
			fclose($fp);
		}
	}
	
	function show_PLOG()
	{
		global $purgelog;
		
		if($purgelog)
		{
			$fp = fopen('purgelog.html', 'a');
			fwrite($fp, 'REQUEST: '.$_SERVER['REQUEST_URI']);
			fwrite($fp, _debug_array($_REQUEST['purgelog'], false));
			fwrite($fp, '<hr /><br /><br />');
			fclose($fp);
		}
	}
	
	function show_LOCK()
	{
		global $locklog;
		
		if($locklog)
		{
			$fp = fopen('locklog.html', 'a');
			fwrite($fp, 'REQUEST: '.$_SERVER['REQUEST_URI']);
			fwrite($fp, _debug_array($_REQUEST['locklog'], false));
			fwrite($fp, '<hr /><br /><br />');
			fclose($fp);
		}
	}
	
	function show_DBLG()
	{
		global $dblog;
		
		if($dblog && isset($_REQUEST['dblog']))
		{
			$fp = fopen('dblog.html', 'a');
			foreach($_REQUEST['dblog'] as $query)
			{
				fwrite($fp, $query);
			}
			fclose($fp);
		}
	}
	
	function show_CLOG()
	{
		global $calllog, $time;
		
		$g = '00';
		$b = '00';
		if($time >= 1)
		{
			$r = 'FF';
		}
		else
		{
			$r = substr('0'.dechex(floor($time*255)), 0, 2);
		}
		if($calllog)
		{
			$fp = fopen('calllog.html', 'a');
			fwrite($fp, '<div style="color:#'.$r.$g.$b.';">'.implode(' > ', $_REQUEST['calllog']).'</div>');
			if($time >= 1 && isset($_REQUEST['calllog_sub']))
			{
				foreach($_REQUEST['calllog_sub'] as $subcall)
				{
					fwrite($fp, '<div style="color:#'.$r.$g.$b.';">&nbsp;&nbsp;&nbsp;&nbsp;'.$subcall.'</div>');
				}
			}
			fclose($fp);
		}
	}

?>