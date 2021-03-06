<?php

define('DB_DIR', dirname(__FILE__) . '/../db/');

define('ST_DONE', 1);
define('ST_TODO', 2);
define('ST_WIP', 3);

/**
 * Make sure a key name is syntactically correct.
 * @param string
 * @return bool
 */

function db_validate_key($key)
{
	return preg_match('/^([a-z0-9_-]+\.)*[a-z0-9_-]+$/', $key) ? true : false;
}

/**
 * Retrieve a value from the database
 * @param string Key name
 * @param mixed Default value. Defaults to false.
 * @return mixed
 */ 

function db_get($key, $default = false)
{
	if ( !db_validate_key($key) )
		return $default;
	$elements = explode('.', $key);
	$entry = array_pop($elements);
	$dir = implode('/', $elements);
	if ( file_exists(DB_DIR . "$dir/$entry") && ($contents = file_get_contents(DB_DIR . "$dir/$entry")) )
	{
		return json_decode($contents, true);
	}
	return $default;
}

/**
 * Store a value in the database
 * @param string Key name
 * @param mixed Value, can be pretty much anything besides closures and objects
 */

function db_set($key, $value)
{
	if ( !db_validate_key($key) )
		return false;
	
	// Permissions check. Scripts like edit and api can define this DB_WRITE_RESTRICT
	// constant to limit writes to the database to certain patterns, protecting the
	// database from modifications of keys a user shouldn't modify.
	// syntax: key1,key2,...,keyn - can contain asterisks
	//
	// Remember kids, it's a constant. You can only define it once and you can't undefine it.
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

/**
 * Checks a key against the DB_WRITE_RESTRICT whitelist.
 * @param string
 * @return bool true = allowed to write
 */

function verify_restrict($key)
{
	foreach ( explode(',', DB_WRITE_RESTRICT) as $rule )
	{
		// turn the restriction rule into 
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

/**
 * Delete a database key, recursively if necessary.
 * @param string
 * @return bool
 */

function db_unset($key)
{
	if ( !db_validate_key($key) )
		return false;
	
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

/**
 * List subkeys underneath a key
 * @param string
 * @return array
 */

function db_enum($key)
{
	if ( !db_validate_key($key) )
		return false;
	
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

/**
 * Is a key a value, or a directory with more keys in it?
 * @param string Key name
 * @return string 'value', 'directory', or 'dne' (does not exist)
 */

function db_gettype($key)
{
	if ( !db_validate_key($key) )
		return false;
	
	$elements = explode('.', $key);
	$dir = implode('/', $elements);
	if ( is_file(DB_DIR . $dir) && file_exists(DB_DIR . $dir) )
		return 'value';
	else if ( is_dir(DB_DIR . $dir) )
		return 'directory';
	else
		return 'dne';
}

/**
 * Take a nested array and convert it to key-value format. Used in edit.php to directly pass in $_POST. Recursive.
 * @param array
 * @param array Internal use only
 */

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
