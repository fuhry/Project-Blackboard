<?php

return call_user_func(function() {
$blocks = array();

foreach ( db_enum('users') as $u )
{
	$sn = db_get("users.$u.screen_name");
	if ( empty($sn) )
		continue;
	$block = array('title' => $sn, 'content' => '');
	
	$st = db_get("users.$u.status");
	$pr = db_get("users.$u.project");
	$loc = db_get("users.$u.location");
	$cls = db_get("users.$u.class");
	
	if ( $st == '__inclass__' )
	{
		$schedule = db_get("users.$u.schedule", array());
		$days = 'MTWRFSU';
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
					$st = 'In class';
					$loc = $entry['location'];
					$cls = $entry['name'];
					break;
				}
				else
				{
					$loc = $entry['location'];
					$st = $entry['name'];
				}
			}
			else
			{
				$st = 'In class';
				$cls = 'Unknown';
			}
		}
	}
	
	if ( $st )
	{
		$block['content'] .= "Current status: <strong>$st</strong><br />";
	}
	if ( $pr )
	{
		$block['content'] .= "Project: <strong>$pr</strong><br />";
	}
	if ( $loc )
	{
		$block['content'] .= "Location: <strong>$loc</strong><br />";
	}
	if ( $cls )
	{
		$block['content'] .= "Class: <strong>$cls</strong><br />";
	}
	/*
	if ( $tw = db_get("users.$u.twitter") )
	{
		$block['content'] .= <<<EOF
		<script>
		new TWTR.Widget({
		  version: 2,
		  type: 'profile',
		  rpp: 4,
		  interval: 30000,
		  width: 250,
		  height: 300,
		  theme: {
			shell: {
			  background: '#333333',
			  color: '#ffffff'
			},
			tweets: {
			  background: '#000000',
			  color: '#ffffff',
			  links: '#4aed05'
			}
		  },
		  features: {
			scrollbar: false,
			loop: false,
			live: false,
			behavior: 'all'
		  }
		}).render().setUser('$tw').start();
		</script>
EOF;
	}
	*/
	if ( $sch = db_get("users.$u.schedule") )
	{
		$sat = $sun = 0;
		// determine earliest start time
		$min = 24*60;
		$max = 0;
		foreach ( $sch as $entry )
		{
			if ( in_array('S', $entry['days']) )
				$sat = 1;
			if ( in_array('U', $entry['days']) )
				$sun = 1;
			if ( ($entry['start_hour']*60 + $entry['start_minute']) < $min )
				$min = $entry['start_hour']*60 + $entry['start_minute'];
			if ( ($entry['end_hour']*60 + $entry['end_minute']) > $max )
				$max = $entry['end_hour']*60 + $entry['end_minute'];
		}
		// determine how many days we need to cover
		$days = 5 + $sat + $sun;
		$skip_rows = array();
		$block['content'] .= '<table class="schedule">';
		$block['content'] .= '<tr><th class="mainhead" colspan="' . ( 1 + $days ) . '">Schedule</th></tr>';
		$days = 'MTWRF' . ($sat ? 'S' : '') . ($sun ? 'U' : '');
		$block['content'] .= '<tr><th></th>';
		foreach ( str_split($days, 1) as $day )
		{
			$block['content'] .= "<th>$day</th>";
			$skip_rows[$day] = 0;
		}
		$block['content'] .= '</tr>';
		
		$interval = 30;
		$min = floor($min / $interval) * $interval;
		$max = (ceil($max / $interval)-1) * $interval;
		for ( $i = $min; $i <= $max; $i += $interval )
		{
			$block['content'] .= '<tr>';
			$block['content'] .= '<td>';
			$block['content'] .= sprintf("%d:%02d", floor($i/60), $i % 60);
			foreach ( str_split($days, 1) as $day )
			{
				if ( --$skip_rows[$day] >= 1 )
				{
					continue;
				}
				$cell = '';
				foreach ( $sch as $j => $entry )
				{
					$start_time = $entry['start_hour']*60 + $entry['start_minute'];
					$end_time = $entry['end_hour']*60 + $entry['end_minute'];
					$start_block = floor($start_time / $interval) * $interval;
					$end_block = ceil($end_time / $interval) * $interval;
					if ( $start_block == $i && in_array($day, $entry['days']) )
					{
						$now = intval(date('G') * 60) + intval(date('i'));
						$daylist = 'MTWRFSU';
						$dayidx = ($n = intval(date('w'))-1) < 0 ? $n + 7 : $n;
						$is_now = $daylist{ $dayidx } == $day && $now >= $start_time && $now <= $end_time ? ' now' : '';
						$rowspan = $skip_rows[$day] = round((($end_block - $start_block)+1) / $interval);
						$cell = '<td class="filled cell' . ($j % 10) . '' . $is_now . '" valign="top" rowspan="'. $rowspan . '">'
									. sprintf(
											"<strong>%s</strong><br />"
											. "%d:%02d &ndash; %d:%02d<br />"
											. "%s",
											htmlspecialchars($entry['name']),
											$entry['start_hour'], $entry['start_minute'], $entry['end_hour'], $entry['end_minute'],
											htmlspecialchars($entry['location'])
										)
									. '</td>';
					}
				}
				if ( empty($cell) )
				{
					$cell .= '<td class="spacer"></td>';
				}
				$block['content'] .= $cell;
			}
			$block['content'] .= '</td>';
		}
		
		$block['content'] .= '</table>';
	}
	$blocks[] = $block;
}

return $blocks;
});
