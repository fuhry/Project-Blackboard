<?php

$hooks = array();

if ( ($d = opendir(dirname(__FILE__) . '/blocks')) )
{
	while ( $de = readdir($d) )
	{
		if ( preg_match('/\.php$/', $de) )
			include(dirname(__FILE__) . '/blocks/' . $de);
	}
	closedir($d);
}


