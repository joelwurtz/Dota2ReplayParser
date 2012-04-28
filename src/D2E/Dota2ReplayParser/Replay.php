<?php
namespace D2E\Dota2ReplayParser;

use D2E\Dota2ReplayParser\IO\StringInputStream;
use D2E\Dota2ReplayParser\IO\FileInputStream;
use D2E\Dota2ReplayParser\IO\LittleEndianStreamReader;

/**
 * Replay class
 *
 * Use this class to parse a dota2 Replay
 *
 * @author Joel Wurtz <brouznouf@gmail.com>
 */
class Replay
{
    private static $protoBuildInit = array();

    private $streamReader;
    private $codec;
    private $eventlist = array();
    private $skipFullPacket = false;
    private $skipPacket = false;
    private $mappingGlobal = array();
    private $mappingMessages = array();
    private $mappingPacket = array();

    /**
     * Constructor
     *
     * Check if replay exists and process init process
     *
     * @param string  $filename         Replay file path
     * @param boolean $skipFullPacket   Skip parsing of full packet (optimisation trick when not interessed in what is inside)
     * @param boolean $skipPacket       Skip parsing of packet (optimisation trick when not interessed in what is inside)
     * @param string  $build            Build name to handle multiple version of proto files leave it by default to have last version
     *
     * @throws \RuntimeException
     */
    public function __construct($filename, $skipFullPacket = false, $skipPacket = false, $build = "build1")
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException(sprintf("File %s does not exist", $filename));
        }

        self::loadProtoLib($build);

        $this->streamReader = new LittleEndianStreamReader(new FileInputStream($filename));
        $this->codec = new \DrSlump\Protobuf\Codec\Binary();
        $this->skipFullPacket = $skipFullPacket;
        $this->skipPacket = $skipPacket;

        $header = $this->streamReader->readString(8);

        if ($header != "PBUFDEM\0") {
            throw new \RuntimeException(sprintf("File %s is not a valid dem file", $filename));
        }

        //Skip offset
        $this->streamReader->readInt32();

        //Build mapping
        $this->buildMapping();
    }

    /**
     * Include proto lib class
     *
     * @param unknown_type $build
     */
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

    /**
     * Beginning parsing of replay file
     *
     * @throws \RuntimeException
     */
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

        	if (!isset($this->mappingGlobal[$cmd])) {
        		throw new \RuntimeException(sprintf("Invalid message type %s", $cmd));
        	}

        	$type = $this->mappingGlobal[$cmd];

        	$size = $this->streamReader->readInt32D2();
        	$bytes = $this->streamReader->readString($size);

        	if ($compressed) {
        		$bytes = snappy_uncompress($bytes);
        	}

        	if ($type == "CDemoSignonPacket") {
        		$type = "CDemoPacket";
        	}
            //echo "[$type] size: $size cmd : $cmd \n";

        	$object = $this->codec->decode(new $type, $bytes);
        	$object = $this->parseObject($object, $tick);
        }
    }

    /**
     * Add a class to track
     *
     * You can use this function to execute specific code when parser encountering a special class, closure function must respect the following schema :
     *
     * function($object, $tick)
     *
     * Where $object is the $object for which we found the corresponding tracked class and $tick is the current time information
     *
     * @param string   $class    Class to track
     * @param \Closure $closure  Callback function to execute
     */
    public function track($class, $closure)
    {
        $this->trackedClass[$class] = $closure;
    }

    public function getGameEventDescriptor($eventId)
    {
        if (isset($this->eventlist[$eventId])) {
            return $this->eventlist[$eventId];
        }

        return null;
    }

    /**
     * Parse an object and decide to continue deeper given configuration and object class
     *
     * @param \DrSlump\Protobuf\Message $object Object parsed from protobuf
     * @param int $tick Tick counter (30 tick per second)
     */
    private function parseObject($object, $tick)
    {
        $class = get_class($object);

        if (isset($this->trackedClass[$class])) {
            $closure = $this->trackedClass[$class];
            $closure($object, $tick);
        }

        switch ($class) {
            case 'CDemoPacket':
                if (!$this->skipPacket) {
                    return $this->parseDemoPacket($object, $tick);
                }
                break;
            case 'CDemoFullPacket':
                if (!$this->skipFullPacket) {
                    return $this->parseDemoPacket($object, $tick, true);
                }
                break;
            case 'CSVCMsg_UserMessage':
                return $this->parseUserMessage($object, $tick);
                break;
            case 'CSVCMsg_GameEventList':
                return $this->parseGameEventList($object);
                break;
            default:
                return $object;
                break;
        }
    }

    /**
     * Parsing a demo packet which contains data
     *
     * @param \DrSlump\Protobuf\Message $object     Object to parse
     * @param int                       $tick       Current tick time
     * @param boolean                   $full       Is it a FullPacket or a Packet
     * @throws \RuntimeException
     */
    private function parseDemoPacket($object, $tick, $full = false)
    {
        if ($full) {
            if ($object->getPacket() == null) {
                return null;
            }

            if ($object->getStringTable() != null) {
                $this->parseObject($object->getStringTable(), true);
            }

            $data = $object->getPacket()->getData();
        } else {
            $data = $object->getData();
        }

        $streamReader = new LittleEndianStreamReader(new StringInputStream($data));

        while ($streamReader->available()) {
            $cmd = $streamReader->readInt32D2();

            if (!isset($this->mappingPacket[$cmd])) {
        		throw new \RuntimeException(sprintf("Invalid message type %s", $cmd));
        	}

        	$type = $this->mappingPacket[$cmd];

            $size = $streamReader->readInt32D2();
            $bytes = $streamReader->readString($size);

            $object = $this->codec->decode(new $type, $bytes);
        	$object = $this->parseObject($object, $tick);
        }
    }

    /**
     * Parse user message object
     *
     * @param CSVCMsg_UserMessage $object
     * @param integer             $tick
     * @throws \RuntimeException
     */
    private function parseUserMessage($object, $tick)
    {
        $cmd = $object->getMsgType();

        if (!isset($this->mappingMessages[$cmd])) {
        	throw new \RuntimeException(sprintf("Invalid message type %s", $cmd));
        }

        $type = $this->mappingMessages[$cmd];

        $object = $this->codec->decode(new $type, $object->getMsgData());
        $object = $this->parseObject($object, $tick);
    }

    /**
     * Parse game event list
     *
     * @param CSVCMsg_GameEventList $object
     */
    private function parseGameEventList($object)
    {
        foreach ($object->getDescriptorsList() as $descriptor) {
            $this->eventlist[$descriptor->getEventId()] = $descriptor;
        }
    }

    /**
     * Build mapping for optimization process to not recalculate it at each packet
     *
     * @throws \RuntimeException
     */
    private function buildMapping()
    {
        $this->mappingGlobal = array();

        $refls = new \ReflectionClass('EDemoCommands');
        $messages = $refls->getConstants();

        foreach ($messages as $type => $id) {
            $this->mappingGlobal[$id] = preg_replace("/DEM_(.*)/", "CDemo$1", $type);
        }

        $this->mappingMessages = array();

        $refls = new \ReflectionClass('EBaseUserMessages');
        $messagesUser = $refls->getConstants();

        $refls = new \ReflectionClass('EDotaUserMessages');
        $messagesDotaUser = $refls->getConstants();

        foreach ($messagesUser as $type => $id) {
            $this->mappingMessages[$id] = preg_replace("/UM_(.*)/", "CUserMsg_$1", $type);
        }

        foreach ($messagesDotaUser as $type => $id) {
            $this->mappingMessages[$id] = preg_replace("/DOTA_UM_(.*)/", "CDOTAUserMsg_$1", $type);
        }

        $this->mappingPacket = array();

        $refls = new \ReflectionClass('NET_Messages');
        $messagesNet = $refls->getConstants();

        $refls = new \ReflectionClass('SVC_Messages');
        $messagesSvc = $refls->getConstants();

        foreach ($messagesNet as $type => $id) {
            $this->mappingPacket[$id] = preg_replace("/net_(.*)/", "CNETMsg_$1", $type);
        }

        foreach ($messagesSvc as $type => $id) {
            $this->mappingPacket[$id] = preg_replace("/svc_(.*)/", "CSVCMsg_$1", $type);
        }
    }
}