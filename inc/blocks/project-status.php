<?php

$block = array('title' => 'Project status', 'content' => '');

foreach ( db_get('meta.status') as $k => $v )
{
	$block['content'] .= "$k: ";
	switch($v)
	{
		case ST_DONE:
			$block['content'] .= '<strong>DONE</strong>';
			break;
		case ST_TODO:
			$block['content'] .= '<strong class="todo">TODO</strong>';
			break;
		case ST_WIP:
			$block['content'] .= '<strong class="wip">WIP</strong>';
			break;
	}
	$block['content'] .= '<br />';
}

return $block;

