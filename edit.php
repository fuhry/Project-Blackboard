<?php

$user = $_SERVER['REMOTE_USER'];
define('DB_WRITE_RESTRICT', "users.$user.*");
require('inc/db.php');

if ( isset($_POST['schedule']) )
{
	switch($_POST['schedule']['action'])
	{
		case 'add':
			$schedule = db_get("users.$user.schedule", array());
			foreach ( array('start_hour', 'start_minute', 'end_hour', 'end_minute') as $key )
			{
				$_POST['schedule_add'][$key] = intval($_POST['schedule_add'][$key]);
				if ( $_POST['schedule_add'][$key] < 0 || (preg_match('/hour$/', $key) && $_POST['schedule_add'][$key] > 23) || (preg_match('/minute$/', $key) && $_POST['schedule_add'][$key] > 59) )
				{
					echo "<p><strong class=\"uhoh\">You entered an invalid time.</strong></p>";
					break 2;
				}
			}
			$schedule[] = $_POST['schedule_add'];
			db_set("users.$user.schedule", $schedule);
			break;
		case 'delete':
			$schedule = db_get("users.$user.schedule", array());
			if ( isset($_POST['schedule_del']) && is_array($_POST['schedule_del']) )
			{
				foreach ( $_POST['schedule_del'] as $id => $_ )
				{
					$id = intval($id);
					echo "unset(\$schedule[$id]) ({$schedule[$id]['name']})<br />\n";
					unset($schedule[$id]);
				}
				$schedule = array_values($schedule);
				db_set("users.$user.schedule", $schedule);
			}
			break;
	}
	unset($_POST['schedule']);
	unset($_POST['schedule_add']);
	unset($_POST['schedule_del']);
}

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
		
		<h2>Your schedule</h2>
		<p>Swipe out when you're leaving to update your status.</p>
		
		<form method="post">
		<table border="0" class="normal table">
			<thead>
				<tr>
					<th>Type</th>
					<th>Name</th>
					<th>Location</th>
					<th>Days</th>
					<th>Start time</th>
					<th>End time</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( db_get("users.$user.schedule", array()) as $i => $entry ): ?>
					<tr>
						<td><?php echo htmlspecialchars($entry['type']); ?></td>
						<td><?php echo htmlspecialchars($entry['name']); ?></td>
						<td><?php echo htmlspecialchars($entry['location']); ?></td>
						<td><?php echo htmlspecialchars(implode('', $entry['days'])); ?></td>
						<td><?php printf("%d:%02d", $entry['start_hour'], $entry['start_minute']); ?></td>
						<td><?php printf("%d:%02d", $entry['end_hour'], $entry['end_minute']); ?></td>
						<td><input type="checkbox" name="schedule_del[<?php echo $i; ?>]" /></td>
					</tr>
				<?php endforeach; ?>
				<tr class="add">
					<td><select name="schedule_add[type]"><option value="class">Class</option><option value="other">Other</option></select></td>
					<td>Status/class name: <input type="text" name="schedule_add[name]" /></td>
					<td>Location: <input type="text" name="schedule_add[location]" /></td>
					<td>
						Days:
						<table border="0">
							<tr>
								<?php foreach ( array('M', 'T', 'W', 'R', 'F', 'S', 'U') as $day ): ?>
								<td style="text-align: center;"><input type="checkbox" name="schedule_add[days][]" value="<?php echo $day; ?>" />
								<?php endforeach; ?>
							</tr>
							<tr>
								<?php foreach ( array('M', 'T', 'W', 'R', 'F', 'S', 'S') as $day ): ?>
								<td style="text-align: center;"><?php echo $day; ?></td>
								<?php endforeach; ?>
							</tr>
						</table>
					</td>
					<td>Start time: <input size="2" type="text" name="schedule_add[start_hour]" />:<input size="2" type="text" name="schedule_add[start_minute]" /></td>
					<td>End time: <input size="2" type="text" name="schedule_add[end_hour]" />:<input size="2" type="text" name="schedule_add[end_minute]" /></td>
					<td><button name="schedule[action]" value="add">Add entry</button></td>
				</tr>
			</tbody>
		</table>
		<button name="schedule[action]" value="delete">Delete selected</button>
		</form>
		
		<?php
		} // else
		?>
	</body>
</html>