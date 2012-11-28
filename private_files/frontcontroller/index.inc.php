<?php
	/**
	 * responsibilities of the frontcontroller:
	 *
	 * - bootstrapping	:	configure, start $_ENV manager
	 *
	 * - security		:	check the service token
	 * 						only map expected $_GET/$_POST params to the service API call
	 * - codec			: 	encode to json (only for outbound requests (get))
	 *						decode from json (only for inbound requests (post, set, log, add))
	 *						handle {CAPS} formatting if requested
	 *						handle nested formatting if requested
	 * - buffering		: 	store results in memcache to relieve service load (only for outbound requests (get))
	 *						set buffer expire time for each individual API function
	 * - dispatching	: 	delegate calls to the appropriate service
	 *
	 **/

		/////////////////
		// bootstrapping
		/////////////////

    // Make $site_id a global (to be moved to appropriate place soon)
	// TODO: site_id is cast to int here. This messes up cache keys when the original site_id was a string. We need to figure out where that happens and make it consistent.
    $site_id = 0;
    if (isset($_GET['site_id'])) {

        if (isset($_GET['encoded'])) {
            $decoded_site_id = base64_decode(strtr(urldecode($_GET['site_id']), '-_,', '+/='));
            if (is_numeric($decoded_site_id)) {
                $site_id = (int) $decoded_site_id;
            }
        } else {
            if (is_numeric($_GET['site_id'])) {
                $site_id = (int) $_GET['site_id'];
            }
        }
    }

	require_once('../private_files/global.config.inc.php');

	require_once('../private_files/frontcontroller/codecs.inc.php');
	require_once('../private_files/frontcontroller/logging.inc.php');
	require_once('../private_files/frontcontroller/purging.inc.php');
	require_once('../private_files/frontcontroller/calling.inc.php');
		
    require_once('../private_files/common/class.dbpool.php');
		// check if a master db is online
	if(dbpool::instance(true) === null) // force master
	{
		die('9997');	// could not connect to a master database, which is needed for every call (due to EnvManager), so bail before errors are cached.
	}
	
	require_once('../private_files/tools/EnvManager.php');
	$oEnvManager = new EnvManager();
	
		// load services
	require_once('../private_files/service_metaservice/class.metaservice.php');
	require_once('../private_files/service_user_generated_content/class.user_generated_content.php');
	require_once('../private_files/service_metaservice/class.metaservice.php');
	require_once('../private_files/service_games/class.games.php');
	require_once('../private_files/service_users/class.users.php');
	require_once('../private_files/service_users_facebook/class.users_facebook.php');
	require_once('../private_files/service_sites/class.sites.php');
	require_once('../private_files/service_translations/class.translations.php');
    require_once('../private_files/service_kgnconnect/class.kgnconnect.php');
    require_once('../private_files/service_kgn/class.kgn.php');
	require_once('../private_files/service_ads/class.ads.php');
	require_once('../private_files/common/functions.inc.php');
	require_once('../private_files/service_email/class.email.php');
	
		// load buffer
	if($memcache) require_once('../private_files/tools/buffer/bufferOut.php');
	$purgeinfo = array();

	$time_start = microtime(true);
	INFO('time start: '.$time_start);	

	INFO('FRONTCONTROLLER:');
	$metaservice = service::singleton('metaservice');
	$check = $metaservice->check_token($_GET['service_token']);
	unset($_GET['service_token']);
	if($check === true)
	{
		INFO('service token OK');
		
		$servicename = $_GET['service'];
		INFO('service: '.$servicename);
		unset($_GET['service']);
		
		CLOG(implode('&', $_GET));

		$service_path = "../private_files/service_{$servicename}/class.{$servicename}.php";
		if(@file_exists($service_path))
		{
			require_once($service_path);

				// determine the API function
			$function = $_GET['function'];
			INFO('function: '.$function);
			unset($_GET['function']);
			
				// killswitches
			@include_once("kill.inc.php");

				// determine the http cache configuration for this API function
			if(isset($cache_map[$servicename][$function]['http']))
			{
				$expire = $cache_map[$servicename][$function]['http'];
				INFO('http cache expire: '.$expire);
			}
			elseif(isset($default_expire_http))
			{
				$expire = $default_expire_http;
				INFO('http cache expire: '.$default_expire_http);
			}
			else
			{
				$expire = 0;
				INFO('no http cache');
			}

				// determine if {CAPS} formatting is requested
			if(isset($_GET['capsify']) && $_GET['capsify'] != 0)
			{
				$capsify = true;
				INFO('capsify is ON ');
				unset($_GET['capsify']);
			}
			else
			{
				$capsify = false;		
			}

				// determine if nested formatting is requested
			if(isset($_GET['nest']) && $_GET['nest'])
			{
				$nest = true;
				INFO('nest is ON ');
				unset($_GET['nest']);
			}
			else
			{
				$nest = false;		
			}

			if(isset($_GET['encoded']))
			{
				unset($_GET['encoded']);
				services_decode_param();
			}
			
			if((isset($_GET['user_id']) && $_GET['user_id'] == 1) || (isset($_POST['user_id']) && $_POST['user_id'] == 1))
			{
				die('0123'); //anonymous user (may not be requested)
			}
			
			INFO('$_GET:');
			INFO($_GET);
			
			if(empty($_POST) && $_GET['postify'] == 1)
			{
					// convert get to post for debugging purposes
				$_POST = $_GET;
			}

			INFO('$_POST:');
			INFO($_POST);
			
			$component_file = "../private_files/service_{$servicename}/api/{$function}.php";
			INFO('http api component: '.$component_file);
			if(is_file($component_file))
			{
				require_once($component_file);
			}
			else
			{
//_debug_array("http api component {$component_file} not found");			
				INFO("http api component {$component_file} not found");			

				switch($function)
				{
					//////////////////////////////////////////
					// GET (with http cache)
					//////////////////////////////////////////

					default:
						INFO('API function unknown: ['.$servicename.'->'.$function.']');
						echo '0002';
				}
			}
		}
		else
		{
			INFO('service API unknown: ['.$servicename.']');
			echo '0001';
		}
	}
	else
	{
		INFO('service token INCORRECT: ['.$_GET['service_token'].']');
		echo($check);
	}	
	
	if(isset($taskrunner) && $taskrunner === true && $taskrunner_trigger === 'poormanscron')
	{
		require_once('../private_files/tools/taskrunner/class.trigger.poormanscron.php');
		poormanscron::process();
	}

    // check to see how well st is doing 
    $aInstanceCounts = service::$aInstanceCounts;
    $aUsageCounts = service::$aUsageCounts;
    
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	INFO('time end: '.$time_end);	
	INFO('total request time: '.$time);	
	CLOG($time);	
	
	show_INFO();
	show_CLOG();
	show_PLOG();
	show_LOCK();
	show_DBLG();

	/*
	 * $_ENV CUSTOM GLOBAL END 
	 */
	$oEnvManager->writeChanges();

    // PDO statistics
    $iPDOInstanceCount = dbpool::$instanceCount;
    $iPDOUsageCount = dbpool::$usageCount;

?>
