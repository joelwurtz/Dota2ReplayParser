<?php

use D2E\Dota2ReplayParser\Replay;

require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();
require_once 'autoload.php';

$replay = new Replay('12930689.dem', true);

$replay->track('CSVCMsg_GameEvent', function  (CSVCMsg_GameEvent $gameEvent, $tick) use($replay) {
    $descriptor = $replay->getGameEventDescriptor($event->getEventId());
    if ($descriptor != null) {
        echo "Event : ".$descriptor->getName()."\n";
    }
});

$replay->parse();