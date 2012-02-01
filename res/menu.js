var NUM_1 = 49;
var NUM_2 = 50;
var NUM_3 = 51;
var NUM_4 = 52;
var NUM_5 = 53;

document.onkeyup = function(e)
	{
		if ( e.keyCode == NUM_5 )
		{
			$('div.menu').remove();
			return false;
		}
		
		if ( !(e.keyCode >= NUM_1 && e.keyCode <= NUM_4) )
			return false;
		
		var num = e.keyCode - NUM_1;
		var li = $('div.menu ul li').get(num);
		if ( typeof(li) == 'object' )
		{
			$(li).click();
		}
	};
	
var menu = {
	Available: {
			"On floor": {
					"Just here": { status: "Available", location: "On floor" },
					"Working on a project": { status: "PROJECTS!", location: "On floor" },
					"Around": { status: "Available", location: "On floor, out of my room" }
				},
			
		},
	Unavailable: {
			Sleeping: { status: "Sleeping", location: "On floor" },
			Studying: { status: "Homework", location: "On floor" },
			Sick: { status: "Sick", location: "On floor" },
			// Sex: { status: "Sexing", location: "On floor" }
		},
	"Not here": {
			"In class": { status: "__inclass__" },
			"Out for food": {
					Commons: { status: "Out for food", location: "Commons" },
					"Sol's": { status: "Out for food", location: "Sol's" },
					"The Whore": { status: "Out for food", location: "The Whore" },
					Other: {
							Ritz: { status: "Out for food", location: "RITZ Sports Zone" },
							"Crossroads/Salsarita's": { status: "Out for food", location: "Crossroads/Salsarita's" },
							"Brick City": { status: "Out for food", location: "Brick City Café" },
							"Off-campus": { status: "Out for food", location: "Off-campus" },
						}
				},
			"Extended away": {
					"Out for the weekend": { status: "Away through the weekend", location: '' }
				}
		}
	};

function menu_display(obj)
{
	$('div.menu').remove();
	if ( typeof(obj) != 'object' )
		obj = menu;
	var div = $('<div />').addClass('menu').append('<ul />');
	for ( var i in obj )
	{
		$('ul', div).append('<li />');
		$('ul li:last', div).text(i).data('menu', obj[i]).click(menu_handle_click);
	}
	$(div).appendTo('body');
}

function menu_handle_click()
{
	var data = $(this).data('menu');
	var iama = 'submenu';
	for ( var i in data )
	{
		if ( typeof(data[i]) == 'string' )
			iama = 'final';
	}
	if ( iama == 'submenu' )
	{
		menu_display(data);
	}
	else if ( iama == 'final' )
	{
		set_status(data);
	}
}

var _finish_flag = 0;
function set_status(data)
{
	_finish_flag = 0;
	for ( var i in data )
	{
		_finish_flag++;
		$.post('/api/' + api_temp_key.user + '/' + api_temp_key.key + '/set/users.' + api_temp_key.user + '.' + i, '"' + data[i] + '"', function()
			{
				_finish_flag--;
			}, 'json');
	}
	waitForFinish(function()
		{
			$('div.menu').remove();
			update();
		});
}

function waitForFinish(callback)
{
	void(callback);
	if ( _finish_flag <= 0 )
		callback();
	else
		setTimeout(function()
			{
				waitForFinish(callback);
			}, 200);
}
