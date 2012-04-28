<?php

use D2E\Dota2ReplayParser\Replay;

require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();
require_once 'autoload.php';

$replay = new Replay('12930689.dem');

$replay->track('CDOTAUserMsg_ChatEvent', function (CDOTAUserMsg_ChatEvent $chatEvent) {
    echo $chatEvent->getValue()."\n";
});

$replay->parse();