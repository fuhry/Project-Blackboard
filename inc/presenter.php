<?php

require_once(dirname(__FILE__) . '/db.php');
define('BLOCK_DIR', dirname(__FILE__) . '/blocks/');

/**
 * WRITING BLOCKS
 * It's stupid easy. Make a PHP file. Create an array $arr. Set $arr['title'] and $arr['content'] as strings. Return $arr (yes, return from the file). Or return an array of arrays like $arr.
 * Single block example:
	<code>
	$block = array(
		'title' => 'Clock',
		'content' => date('r')
		);
	return $block;
	</code>
 * Multiple block example:
	<code>
	$blocks = array();
	foreach ( array('slashdot.org', 'reddit.com') as $domain )
	{
		$blocks[] = array(
			'title' => "RSS feed of $domain",
			// discover_and_render_rss() would just return a string of HTML.
			'content' => discover_and_render_rss($domain)
			);
	}
	return $blocks;
	</code>
 * You can also include additional CSS classes by setting the 'classes' member of your array to an array of strings.
 */

/**
 * Loop through every script in blocks/ directory and return its output.
 * @return array
 */

function get_blocks()
{
	$blocks = array();
	if ( $dh = opendir(BLOCK_DIR) )
	{
		while ( $dp = readdir($dh) )
		{
			if ( preg_match('/\.php$/', $dp) )
			{
				$ret = include(BLOCK_DIR . $dp);
				$plugin = preg_replace('/\.php$/', '', $dp);
				if ( is_array($ret) )
				{
					if ( isset($ret['title']) )
					{
						$ret['plugin'] = $plugin;
						$blocks[] = $ret;
					}
					else
					{
						foreach ( $ret as $b )
						{
							$b['plugin'] = $plugin;
							$blocks[] = $b;
						}
					}
				}
			}
		}
		closedir($dh);
	}
	return $blocks;
}


