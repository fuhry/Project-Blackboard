var _update_lock = false;
function update()
{
	if ( _update_lock )
		return false;
	_update_lock = true;
	$.ajax({
			type: 'GET',
			url: 'ajax.php',
			success: function(resp)
			{
				$('body div.block:not(.persist)').remove();
				for ( var i = 0; i < resp.length; i++ )
				{
					var classes = typeof(resp[i].classes) == 'object' ? ' ' + resp[i].classes.join(' ') : '';
					classes += ' ' + resp[i].plugin;
					if ( $('body div.block.persist.' + resp[i].plugin).length == 0 )
						$('body').append('<div class="block' + classes + '"><div class="innerblock"><h2>' + resp[i].title + '</h2>' + resp[i].content + '</div></div>');
				}
				_update_lock = false;
			},
			dataType: 'json',
			error: function(xhr, ajaxOptions, exception)
			{
				_update_lock = false;
			}
		});
}

var update_interval = setInterval('update();', 10000);
update();


/* This script and many more are available free online at
The JavaScript Source :: http://javascript.internet.com
Created by: James Nisbet (morBandit) :: http://www.bandit.co.nz/ */

$(function() {
  document.onselectstart = function() {return false;} // ie
  document.onmousedown = function() {return false;} // mozilla
});

/**
 * Core AJAX library
 */

function ajaxMakeXHR()
{
	var ajax;
	if (window.XMLHttpRequest) {
		ajax = new XMLHttpRequest();
	} else {
		if (window.ActiveXObject) {           
			ajax = new ActiveXObject("Microsoft.XMLHTTP");
		} else {
			alert('No AJAX support, unable to continue');
			return;
		}
	}
	return ajax;
}

function ajaxGet(uri, f)
{
	var ajax = ajaxMakeXHR();
	if ( !ajax )
	{
		return false;
	}
	ajax.onreadystatechange = function()
	{
		f(ajax);
	};
	ajax.open('GET', uri, true);
	ajax.setRequestHeader( "If-Modified-Since", "Sat, 1 Jan 2000 00:00:00 GMT" );
	ajax.send(null);
}

function ajaxGetPoll(uri, callback, not_first)
{
	var rtlen = 0;
	var orsc_seq = 0;
	void(callback);
	void(uri);
	var uri_ = uri;
	if ( typeof(not_first) != 'boolean' )
	{
		uri_ += (uri.indexOf('?') >= 0 ? '&' : '?') + 'first=yup';
	}
	ajaxGet(uri_, function(ajax)
			{
				if ( typeof(ajax.aborted) != 'boolean' )
					ajax.aborted = false;
				if ( ajax.readyState == 3 || ajax.readyState == 4 )
				{
					if ( ajax.aborted )
						return;
					orsc_seq++;
					var rt = String(ajax.responseText);
					rt = rt.substr(rtlen);
					var timeout = 200;
					var handle = true;
					if ( ajax.readyState == 4 && rtlen == 0 )
					{
						// do nothing, we already have our response text at the right length
					}
					else if ( ajax.readyState == 3 && rt.length > 0 )
					{
						rtlen += rt.length;
					}
					else if ( ajax.readyState == 4 && rtlen > 0 )
					{
						// completion of request - do not call response handler, but repeat
						timeout = 50;
						handle = false;
					}
					else
					{
						handle = false;
					}
					
					var do_continue = true;
					if ( handle )
					{
						// control mechanism for server
						if ( rt == 'STOP' )
						{
							ajax.aborted = true;
							ajax.abort();
							return;
						}
						
						var ret = callback(rt, ajax, orsc_seq);
						if ( typeof(ret) == 'boolean' && !ret )
							do_continue = false;
						
					}
					
					void(callback);
					void(uri);
					
					if ( do_continue && ajax.readyState == 4 )
							setTimeout(function()
								{
									ajaxGetPoll(uri, callback, true);
								}, timeout);
				}
			});
	uri_ = uri;
}

var fprint_cookie = 0;
var api_temp_key = false;

function watch_fprint()
{
	ajaxGetPoll('fprint-comet.php', function(rt, ajax, orsc_seq)
		{
			if ( rt == '' )
				return;
			eval('var response = ' + rt + ';');
			if ( typeof(response.error) == 'string' )
			{
				if ( response.error == 'Timed_out' )
					return;
				$('div.block.fprint').html('<div class="innerblock"><strong class="uhoh">' + response.error.replace(/_/g, ' ') + '</strong></div>').show();
			}
			else
			{
				var finger = response.finger.replace(/-/g, ' ');
				// finger = (finger.charAt(0).toUpperCase()) + finger.substr(1);
				$('div.block.fprint').html('<div class="innerblock"><strong>' + response.user + '</strong> swiped <strong>' + finger + '</strong></div>').show();
				update();
				if ( response.you_are_the_chosen_one )
				{
					api_temp_key = { user: response.user, key: response.api_key };
					menu_display();
				}
			}
			fprint_clear(response.ts);
		});
}

function fprint_clear(cookie)
{
	fprint_cookie = cookie;
	void(cookie);
	setTimeout(function()
		{
			if ( cookie == fprint_cookie )
				$('div.block.fprint').hide().html('');
		}, 3000);
}

watch_fprint();
