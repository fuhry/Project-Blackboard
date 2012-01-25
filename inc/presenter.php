<?php

require_once(dirname(__FILE__) . '/db.php');
define('BLOCK_DIR', dirname(__FILE__) . '/blocks/');

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
				if ( is_array($ret) )
				{
					if ( isset($ret['title']) )
					{
						$blocks[] = $ret;
					}
					else
					{
						foreach ( $ret as $b )
						{
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


