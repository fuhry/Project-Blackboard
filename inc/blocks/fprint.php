<?php

// $state = db_get('plugin.fprint.state');

$block = array(
	'title' => 'Fingerprint',
	'content' => '', // $state['ts'] + 10 > time() ? sprintf("Latest successful swipe by <strong>%s</strong> (<strong>%s</strong>) on %s", $state['user'], ucfirst(str_replace('-', ' ', $state['finger'])), date('r', $state['ts'])) : '',
	'classes' => array('persist', 'hide')
	);

$hooks['plugin.fprint.state'] = function($state)
	{
		if ( !empty($state['user']) )
		{
			if ( db_get("users.{$state['user']}.last_swipe") == 'in' )
			{
				// Swiping out
				$schedule = db_get("users.{$state['user']}.schedule", array());
				$days = 'MTWRFSU';
				$status = 'Out';
				$location = $class = '';
				foreach ( $schedule as $entry )
				{
					$today = $days{ intval(date('w')) - 1 };
					$now = (intval(date('G')) * 60) + intval(ltrim(date('i'), '0'));
					$start = (intval($entry['start_hour']) * 60) + intval($entry['start_minute']);
					$end = (intval($entry['end_hour']) * 60) + intval($entry['end_minute']);
					$d = implode('', $entry['days']);
					// echo "today is $today, event on $d starts at $start, ends at $end, now is $now\n";
					if ( in_array($today, $entry['days']) && ($now + 25) > $start && $now < $end )
					{
						// this event is happening now, or about to happen
						if ( $entry['type'] == 'class' )
						{
							$status = 'In class';
							$location = $entry['location'];
							$class = $entry['name'];
						}
						else
						{
							$location = $entry['location'];
							$status = $entry['name'];
						}
					}
				}
				db_set("users.{$state['user']}.status", $status);
				db_set("users.{$state['user']}.location", $location);
				db_set("users.{$state['user']}.class", $class);
				db_set("users.{$state['user']}.last_swipe", 'out');
			}
			else
			{
				db_set("users.{$state['user']}.last_swipe", 'in');
				db_set("users.{$state['user']}.status", "Available");
				db_set("users.{$state['user']}.location", "On floor");
				db_set("users.{$state['user']}.class", "");
			}
		}
	};

return $block;

