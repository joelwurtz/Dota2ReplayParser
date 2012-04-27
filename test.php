<?php

use D2E\Dota2ReplayParser\Replay;

require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();
require_once 'autoload.php';

$replay = new Replay('8150165.dem');
$replay->parse();