<?php

$throttle = array
(
	//'get_shared_data' => 1,
	//'get_translations_by_site_id' => 1,
	//'get_user_comments_paged' => 1,
	//'get_games_random' => 1,
);


if
(
	(!isset($_GET['bypass_killswitch']) || $_GET['bypass_killswitch'] == 'false')
	&&
	in_array
	(
		$function, 
		array
		(
			//'get_shared_data',					//footer
			//'get_translations_by_site_id',		//translations
			//'get_game_comments_paged',			//game comments
			//'get_user_comments_paged',			//profile page comments tab
			//'get_user_games_played_paged',		//profile page games tab
			//'log_game_play_count',				//gameplay logging
			//'search_games',						//search page
			//'search_terms',						//autocomplete
			//'get_random_related_games',			//game page 'meer games' roulette box
			//'get_related_games_paged',			//old 'meer' games tab
			//'',
			//'',
			//'',
			//'',
			//'',
			//'',
			//'',
			//'',
			//'',
			//'',
			//'',
		)
	)
)
{
	die("0000"); // killswitch on: service manually offline
}

if(isset($_GET['bypass_killswitch']))
{
	unset($_GET['bypass_killswitch']);
}

	//////////////////////////////////////////////
	// throttling
	//////////////////////////////////////////////

if
(
	isset($throttle[$function])
)
{
	$random = rand(0, 100);
	if($random > $throttle[$function])
	{
		die("0000"); // throttle on: this request didn't make the cut
	}
}
?>