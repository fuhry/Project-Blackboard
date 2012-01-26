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


/* This script and many more are available free online at
The JavaScript Source :: http://javascript.internet.com
Created by: James Nisbet (morBandit) :: http://www.bandit.co.nz/ */

$(function() {
  document.onselectstart = function() {return false;} // ie
  document.onmousedown = function() {return false;} // mozilla
});

