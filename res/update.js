function update()
{
	$.get('ajax.php', function(resp)
		{
			$('body').html('');
			for ( var i = 0; i < resp.length; i++ )
			{
				var classes = typeof(resp[i].classes) == 'object' ? ' ' + resp[i].classes.join(' ') : '';
				$('body').append('<div class="block' + classes + '"><div class="innerblock"><h2>' + resp[i].title + '</h2>' + resp[i].content + '</div></div>');
			}
		}, 'json');
}

setInterval('update();', 10000);
update();
