<?php

$user = $_SERVER['REMOTE_USER'];
define('DB_WRITE_RESTRICT', "users.$user.*");
require('inc/db.php');

db_update($_POST);

if ( db_gettype("users.$user") == 'directory' && !($key = db_get("users.$user.api_key")) )
{
	$key = sha1(microtime() . mt_rand());
	db_set("users.$user.api_key", $key);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
	<head>
		<title>door - management</title>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Refresh" content="600" />
		<link rel="stylesheet" type="text/css" href="res/styles.css" />
	</head>
	<body>
		<h2>Door - user management</h2>
		<p>Logged in as: <?php echo $_SERVER['REMOTE_USER']; ?></p>
		<?php
		$user = $_SERVER['REMOTE_USER'];
		if ( db_gettype("users.$user") != 'directory' )
		{
			?>
			<form method="post" enctype="multipart/form-data">
				<p>You haven't been set up yet. Enter your desired screen name:</p>
				<p><input type="text" name="users[<?php echo $user; ?>][screen_name]" value="<?php echo ucwords($user); ?>" /></p>
				<p><input type="submit" value="Set me up!" /></p>
			</form>
			<?php
		}
		else {
		?>
		<h2>API access</h2>
		<p>Your API key is: <tt><?php echo $key; ?></tt></p>
		<p>You can use this API to query and update the database. You can modify any entries under your user (that is, <tt>user.<?php echo $user; ?>.*</tt>). Requests are RESTful, responses
			are in JSON.</p>
		<p>Current keys that exist under your user:</p>
		<ul>
		<?php
		function walk($key)
		{
			foreach ( db_enum($key) as $k )
			{
				$type = db_gettype("$key.$k");
				if ( $type == 'value' )
				{
					$v = htmlspecialchars(json_encode(db_get("$key.$k")));
					echo "<li>$key.$k => $type($v)</li>";
				}
				else if ( $type == 'directory' )
				{
					echo "<li>$k => $type<ul>";
					walk("$key.$k");
					echo "</ul></li>";
				}
			}
		}
		walk("users.$user");
		echo '</ul>';
		?>
		<p>Example API URL:</p>
		<p><tt>http://<?php echo $_SERVER['HTTP_HOST']; ?>/api/<?php echo "$user/$key/get/users.$user.screen_name"; ?></tt></p>
		<p>Update the database with the <tt>set</tt> call; to specify the data you will want to post JSON. You can accomplish this with cURL:</p>
		<p><tt>curl --data '"<?php echo db_get("users.$user.screen_name"); ?>"' http://<?php echo $_SERVER['HTTP_HOST']; ?>/api/<?php echo "$user/$key/set/users.$user.screen_name"; ?> ; echo</tt></p>
		
		<h2>Your status</h2>
		<p>Anything left blank will not be displayed.</p>
		<form method="post">
		<ul>
			<?php
			foreach ( array('status', 'location', 'project', 'class') as $k )
			{
				echo '<li>';
				printf("%s: ", ucwords($k));
				$v = db_get("users.$user.$k", '');
				$k_form = "users.$user.$k";
				$k_form = explode('.', $k_form);
				$k_form = array_shift($k_form) . '[' . implode('][', $k_form) . ']';
				printf("<input type=\"text\" name=\"%s\" value=\"%s\" />", $k_form, htmlspecialchars($v));
				echo '</li>';
			}
			?>
		</ul>
		<input type="submit" value="Update" />
		</form>
		
		<?php
		} // else
		?>
	</body>
</html>