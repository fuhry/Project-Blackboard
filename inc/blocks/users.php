<?php

return call_user_func(function() {
$blocks = array();

foreach ( db_enum('users') as $u )
{
	$block = array('title' => db_get("users.$u.screen_name"), 'content' => '');
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
	$blocks[] = $block;
}

return $blocks;
});
