<?php

if ( !defined('BLOCK_DIR') )
	return;

require_once(BLOCK_DIR . '../http.php');

try
{
	if ( $cache = db_get("plugin.tfw.response_cache") )
	{
		if ( $cache['timestamp'] + 600 >= time() )
		{
			$body = $cache['response'];
		}
	}
	
	if ( empty($body) )
	{
		$req = new Request_HTTP('thefuckingweather.com', '/');
		$req->add_get('where', '14623');
		
		$head = $req->get_response_headers_array();
		if ( $req->response_code == 200 )
		{
			$body = $req->get_response_body();
			
			db_set("plugin.tfw.response_cache", array(
					'timestamp' => time(),
					'response' => $body
				));
		}
	}
	
	if ( !empty($body) )
	{
		if ( preg_match('#<span class="temperature" tempf="([0-9]+)">#', $body, $temp_match) && 
			 preg_match('#<p class="remark">(.+?)</p>#', $body, $remark_match) )
		{
			$content = "<strong>{$temp_match[1]}&deg;</strong>!?<br />
						{$remark_match[1]}";
		}
		else
		{
			$content = 'THE FUCKING SCRAPER DOESN\'T WORK';
		}
	}
	else
	{
		$content = 'THE FUCKING WEATHER IS FUCKING DOWN';
	}
}
catch ( Exception $e )
{
	$content = 'THE FUCKING WEATHER IS FUCKING DOWN';
}

/*
if ( !empty($cache) )
{
	$content .= '<br /><div style="font-size: x-small">
					(debug) Cache stats:
						<ul>
							<li>Cache time: ' . date('h:i:s', $cache['timestamp']) . '</li>
							<li>Now: ' . date('h:i:s', time()) . '</li>
							<li>Remaining time: ' . ( 600 - (time() - $cache['timestamp']) ) . ' seconds</li>
						</ul>
					</div>';
}
*/

return array(
		'title' => 'The Fucking Weather', // 'The f*cking weather',
		'content' => $content // preg_replace('/fucking/i', 'F*CKING', $content)
	);

