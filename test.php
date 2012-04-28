<?php

use D2E\Dota2ReplayParser\Replay;

require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();
require_once 'autoload.php';

$replay = new Replay('12930689.dem', true);

$replay->track('CDOTAUserMsg_ChatEvent', function (CDOTAUserMsg_ChatEvent $chatEvent, $tick) {
    $seconds = ($tick - ($tick % 30)) / 30;
    $type = $chatEvent->getType();
    echo "[$seconds] Chat message type $type\n";
});

$replay->parse();