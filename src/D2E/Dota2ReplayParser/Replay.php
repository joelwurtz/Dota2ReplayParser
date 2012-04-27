<?php

namespace D2E\Dota2ReplayParser;

use D2E\Dota2ReplayParser\IO\FileInputStream;
use D2E\Dota2ReplayParser\IO\LittleEndianStreamReader;

class Replay
{
    private static $protoBuildInit = array();

    private $streamReader;
    private $codec;

    public function __construct($filename, $build = "build1")
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException(sprintf("File %s does not exist", $filename));
        }

        self::loadProtoLib($build);

        $this->streamReader = new LittleEndianStreamReader(new FileInputStream($filename));
        $this->codec = new \DrSlump\Protobuf\Codec\Binary();

        $header = $this->streamReader->readString(8);

        if ($header != "PBUFDEM\0") {
            throw new \RuntimeException(sprintf("File %s is not a valid dem file", $filename));
        }

        //Skip offset
        $this->streamReader->readInt32();
    }

    public static function loadProtoLib($build = "build1")
    {
        if (!isset($protoBuildInit[$build])) {
            $protoBuildInit[$build] = true;

            include_once __DIR__.'/../../proto/'.$build.'/ai_activity.php';
            include_once __DIR__.'/../../proto/'.$build.'/demo.php';
            include_once __DIR__.'/../../proto/'.$build.'/descriptor.php';
            include_once __DIR__.'/../../proto/'.$build.'/dota_commonmessages.php';
            include_once __DIR__.'/../../proto/'.$build.'/dota_modifiers.php';
            include_once __DIR__.'/../../proto/'.$build.'/dota_usermessages.php';
            include_once __DIR__.'/../../proto/'.$build.'/generated_proto.php';
            include_once __DIR__.'/../../proto/'.$build.'/netmessages.php';
            include_once __DIR__.'/../../proto/'.$build.'/usermessages.php';
        }
    }

    public function getMessageOfType($type)
    {

    }

    public function parse()
    {
        $continue = true;

        while($continue && $this->streamReader->available()) {
        	$cmd = $this->streamReader->readInt32D2();
        	$tick = $this->streamReader->readInt32D2();
        	$compressed = false;

        	if ($cmd & \EDemoCommands::DEM_IsCompressed) {
        		$compressed = true;
        		$cmd = $cmd & ~\EDemoCommands::DEM_IsCompressed;
        	}

        	$refls = new \ReflectionClass('EDemoCommands');
        	$messages = $refls->getConstants();

        	if (!in_array($cmd, $messages)) {
        		throw new \RuntimeException(sprintf("Invalid message type %s", $cmd));
        	}

        	$type = array_search($cmd, $messages);
        	$type = preg_replace("/DEM_(.*)/", "CDemo$1", $type);

        	$size = $this->streamReader->readInt32D2();
        	$bytes = $this->streamReader->readString($size);

        	if ($compressed) {
        		$bytes = snappy_uncompress($bytes);
        	}

        	if ($type == "CDemoSignonPacket") {
        		continue;
        	}

        	echo "[$type] size: $size, compressed: $compressed\n";

        	$object = $this->codec->decode(new $type, $bytes);
        }
    }
}