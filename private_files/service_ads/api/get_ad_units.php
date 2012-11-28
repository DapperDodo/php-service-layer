<?php

	echo call_buffered($expire, $servicename, $function, array($_GET['site_id'], $_GET['ad_pagetype_id'], $_GET['ad_group_id']));
	
?>