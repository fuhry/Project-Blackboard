<?php

// actually JSON
header('Content-type: text/javascript');

function womp($str)
{
	echo json_encode(array(
		'result' => 'error',
		'error' => $str
		));
	exit;
}

$query = explode('/', $_GET['q']);
if ( count($query) < 4 )
	womp("Not enough parameters");

$user = array_shift($query);
$api_key = array_shift($query);
$call = array_shift($query);

if ( $user != 'root' )
	define('DB_WRITE_RESTRICT', "users.$user.*,meta.status");
define('DB_SILENT', 1);
require('inc/db.php');
require('inc/hooks.php');

if ( !($key = db_get("users.$user.api_key")) )
	womp("You don't have the API enabled.");

if ( $api_key !== $key )
{
	// incorrect permanent API key, is there a valid temp key?
	$temp_keys = db_get("users.$user.api_keys_temp", array());
	if ( isset($temp_keys[ $_SERVER['REMOTE_ADDR'] ]) && $temp_keys[ $_SERVER['REMOTE_ADDR'] ]['expires'] >= time() && $api_key === $temp_keys[ $_SERVER['REMOTE_ADDR'] ]['key'] )
	{
		// let the request continue
	}
	else
	{
		womp("Incorrect API key");
	}
}


switch($call)
{
	case 'get':
		$get = array_shift($query);
		echo json_encode(array(
				'result' => 'success',
				$get => db_get($get)
			));
		break;
	case 'set':
		$input = file_get_contents('php://input');
		if ( empty($input) )
		{
			womp("No input given. Send application/json on POST.");
		}
		$get = array_shift($query);
		if ( $input_dec = @json_decode($input, true) )
		{
			if ( db_set($get, $input_dec) )
			{
				if ( isset($hooks[$get]) && is_callable($hooks[$get]) )
				{
					call_user_func($hooks[$get], $input_dec, $get);
				}
				echo json_encode(array(
						'result' => 'success'
					));
			}
			else
			{
				womp("Failed to update the database.");
			}
		}
		break;
	case 'unset':
		$get = array_shift($query);
		if ( db_unset($get) )
		{
			echo json_encode(array(
					'result' => 'success'
				));
		}
		else
		{
			womp("Failed to delete the specified entry.");
		}
		break;
	case 'enum':
		$get = array_shift($query);
		echo json_encode(array(
				'result' => 'success',
				$get => db_enum($get)
			));
		break;
	case 'gettype':
		$get = array_shift($query);
		echo json_encode(array(
				'result' => 'success',
				$get => db_gettype($get)
			));
		break;
	default:
		womp('Unknown call: ' . $call);
		break;
}

