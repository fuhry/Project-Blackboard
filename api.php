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

define('DB_WRITE_RESTRICT', "users.$user.*,meta.status");
define('DB_SILENT', 1);
require('inc/db.php');

if ( !($key = db_get("users.$user.api_key")) )
	womp("You don't have the API enabled.");

if ( $api_key !== $key )
	womp("Incorrect API key");

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
