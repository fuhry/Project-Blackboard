<?php

// $state = db_get('plugin.fprint.state');

$block = array(
	'title' => 'Fingerprint',
	'content' => '', // $state['ts'] + 10 > time() ? sprintf("Latest successful swipe by <strong>%s</strong> (<strong>%s</strong>) on %s", $state['user'], ucfirst(str_replace('-', ' ', $state['finger'])), date('r', $state['ts'])) : '',
	'classes' => array('persist', 'hide')
	);

$hooks['plugin.fprint.state'] = function($state)
	{
		$state['srcip'] = $_SERVER['REMOTE_ADDR'];
		db_set('plugin.fprint.state', $state);
		
		if ( !isset($state['user']) )
			return;
		
		$temp_keys = db_get("users.{$state['user']}.api_keys_temp", array());
		$temp_keys[ $_SERVER['REMOTE_ADDR'] ] = array(
					'key' => sha1(microtime() . mt_rand()),
					'expires' => time() + 120
				);
		db_set("users.{$state['user']}.api_keys_temp", $temp_keys);
	};

return $block;

