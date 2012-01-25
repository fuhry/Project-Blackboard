<?php
require('inc/presenter.php');
header('Content-type: text/javascript');

echo json_encode(get_blocks());

