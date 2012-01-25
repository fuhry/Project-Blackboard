<?php

define('DB_DIR', dirname(__FILE__) . '/../db/');

define('ST_DONE', 1);
define('ST_TODO', 2);
define('ST_WIP', 3);

function db_get($key, $default = false)
{
	$elements = explode('.', $key);
	$entry = array_pop($elements);
	$dir = implode('/', $elements);
	if ( file_exists(DB_DIR . "$dir/$entry") && ($contents = file_get_contents(DB_DIR . "$dir/$entry")) )
	{
		return json_decode($contents, true);
	}
	return $default;
}

function db_set($key, $value)
{
	if ( defined('DB_WRITE_RESTRICT') && !verify_restrict($key) )
		return false;
	
	$value = json_encode($value);
	if ( !defined('DB_SILENT') ) "db_set $key => $value<br />\n";
	
	$elements = explode('.', $key);
	$entry = array_pop($elements);
	$dir = implode('/', $elements);
	if ( !is_dir(DB_DIR . $dir) )
	{
		if ( !mkdir(DB_DIR . $dir, 0777, true) )
			return false;
	}
	if ( file_put_contents(DB_DIR . "$dir/$entry", $value) )
		return true;
	return false;
}

function verify_restrict($key)
{
	foreach ( explode(',', DB_WRITE_RESTRICT) as $rule )
	{
		$rule_expr = '/^' . str_replace(array('.', '*'), array('\\.', '.*?'), $rule) . '$/';
		if ( preg_match($rule_expr, $key) )
		{
			if ( !defined('DB_SILENT') ) "write to $key GRANTED by rule $rule<br />\n";
			return true;
		}
	}
	if ( !defined('DB_SILENT') ) echo "write to $key DENIED<br />\n";
	return false;
}

function db_unset($key)
{
	if ( defined('DB_WRITE_RESTRICT') && !verify_restrict($key) )
		return false;
	
	$elements = explode('.', $key);
	$dir = implode('/', $elements);
	
	$type = db_gettype($key);
	if ( $type == 'directory' )
	{
		foreach ( db_enum($key) as $k )
		{
			db_unset("$key.$k");
		}
		rmdir(DB_DIR . $dir);
	}
	else if ( $type == 'value' )
	{
		unlink(DB_DIR . $dir);
	}
	
	return true;
}

function db_enum($key)
{
	$elements = explode('.', $key);
	$dir = implode('/', $elements);
	$result = array();
	if ( is_dir(DB_DIR . $dir) && ($dp = opendir(DB_DIR . $dir)) )
	{
		while ( $de = readdir($dp) )
		{
			if ( is_dir(DB_DIR . "$dir/$de") && !in_array($de, array('.', '..')) )
				$result[] = $de;
			else if ( is_file(DB_DIR . "$dir/$de") && file_exists(DB_DIR . "$dir/$de") && is_readable(DB_DIR . "$dir/$de") )
				$result[] = $de;
		}
		closedir($dp);
	}
	return $result;
}

function db_gettype($key)
{
	$elements = explode('.', $key);
	$dir = implode('/', $elements);
	if ( is_file(DB_DIR . $dir) && file_exists(DB_DIR . $dir) )
		return 'value';
	else if ( is_dir(DB_DIR . $dir) )
		return 'directory';
	else
		return 'dne';
}

function db_update($arr, $stack = array())
{
	foreach ( $arr as $k => $v )
	{
		$stack[] = $k;
		if ( is_array($v) )
		{
			db_update($v, $stack);
		}
		else
		{
			db_set(implode('.', $stack), $v);
		}
		array_pop($stack);
	}
}
