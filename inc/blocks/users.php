<?php

return call_user_func(function() {
$blocks = array();

foreach ( db_enum('users') as $u )
{
	$sn = db_get("users.$u.screen_name");
	if ( empty($sn) )
		continue;
	$block = array('title' => $sn, 'content' => '');
	if ( $st = db_get("users.$u.status") )
	{
		$block['content'] .= "Current status: <strong>$st</strong><br />";
	}
	if ( $pr = db_get("users.$u.project") )
	{
		$block['content'] .= "Project: <strong>$pr</strong><br />";
	}
	if ( $loc = db_get("users.$u.location") )
	{
		$block['content'] .= "Location: <strong>$loc</strong><br />";
	}
	if ( $loc = db_get("users.$u.class") )
	{
		$block['content'] .= "Class: <strong>$loc</strong><br />";
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
		$max = ceil($max / $interval) * $interval;
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
						$rowspan = $skip_rows[$day] = round((($end_block - $start_block)+1) / $interval);
						$cell = '<td class="filled cell' . ($j % 10) . '" valign="top" rowspan="'. $rowspan . '">'
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
