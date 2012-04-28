<?php

use D2E\Dota2ReplayParser\Replay;

require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();
require_once 'autoload.php';

$replay = new Replay('12930689.dem', true);

$fileInfo = $replay->getFileInfo();

$replay->track('CSVCMsg_GameEvent', function  (CSVCMsg_GameEvent $gameEvent, $tick) use($replay) {
    print_r($replay->getGameEvent($gameEvent, "dota_combatlog"));
});

$replay->parse();