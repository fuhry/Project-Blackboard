<?php

set_time_limit(0);
require('inc/db.php');

@ob_end_flush();

$st = time();
$lt = time() - 10;

$limit = isset($_GET['first']) ? 2 : 120;

while ( true )
{
	clearstatcache();
	$state = db_get('plugin.fprint.state');
	if ( $state['ts'] > $lt )
	{
		$lt = $state['ts'];
		$state['ts_str'] = date('r', $state['ts']);
		$state['you_are_the_chosen_one'] = $state['srcip'] === $_SERVER['REMOTE_ADDR'] && isset($state['user']);
		if ( $state['you_are_the_chosen_one'] )
		{
			// bundle an API key
			$temp_keys = db_get("users.{$state['user']}.api_keys_temp", array());
			if ( isset($temp_keys[$_SERVER['REMOTE_ADDR']]) && $temp_keys[$_SERVER['REMOTE_ADDR']]['expires'] >= time() )
			{
				$state['api_key'] = $temp_keys[ $_SERVER['REMOTE_ADDR'] ]['key'];
			}
		}
		unset($state['srcip']);
		echo json_encode($state);
		flush();
	}
	
	sleep(1);
	
	if ( $st + $limit < time() )
		break;
}
