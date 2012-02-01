<?php

set_time_limit(0);
require('inc/db.php');

@ob_end_flush();

$st = time();
$lt = time() - 10;

while ( true )
{
	clearstatcache();
	$state = db_get('plugin.fprint.state');
	if ( $state['ts'] > $lt )
	{
		$lt = $state['ts'];
		$state['ts_str'] = date('r', $state['ts']);
		echo json_encode($state);
		flush();
	}
	
	sleep(1);
	
	if ( $st + 120 < time() )
		break;
}
