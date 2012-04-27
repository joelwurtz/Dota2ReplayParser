<?php
/*
 * This file is part of the Dota2ReplayParser.
 * (c) 2011 joel.wurtz@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace D2E\Dota2ReplayParser\IO;

/**
 * StreamReader use to read stream from a input stream.
 *
 * @package    Dota2ReplayParser
 * @subpackage IO
 * @author     joel.wurtz@gmail.com
 * @version    1.0.0
 */
abstract class StreamReader implements InputStream
{
  protected $stream;

  public function __construct(InputStream $inputStream)
  {
    $this->stream = $inputStream;
  }

  public function readByte()
  {
    $byte = $this->stream->read();
    $tmp = unpack('C', $byte);

    return $tmp[1];
  }

  public function readBytes($bytes)
  {
    $bytesRead = $this->stream->read($bytes);

    return unpack('C'.$bytes, $bytesRead);
  }

  public function readString($bytes)
  {
    return $this->stream->read($bytes);
  }

  public function readLine()
  {
    $char = null;
    $line = "";

    while ($this->available() > 0 && $char != "\n")
    {
      $char = chr($this->readByte());
      $line .= $char;
    }

    $line = substr($line, 0, strlen($line) - 1);

    return trim($line);
  }

  abstract public function readUInt16();

  abstract public function readUInt32();

  public function available()
  {
    return $this->stream->available();
  }

  public function read($read = 1)
  {
    return $this->stream->read($read);
  }

  public function skip($skip)
  {
    return $this->stream->skip($skip);
  }

  public function mark($key = "")
  {
    return $this->stream->mark($key);
  }

  public function reset($key = "")
  {
    return $this->stream->reset($key);
  }

  public function offset($offset)
  {
    return $this->stream->offset($offset);
  }

  public function beginLog($key = "")
  {
    $this->stream->beginLog($key);
  }

  public function endLog($key = "")
  {
    return $this->stream->endLog($key);
  }

  public function findSequence($sequence, $fromStart = false)
  {
    //$this->mark("sequenceSearchStart");
    if ($fromStart)
    {
      $this->offset(0);
    }

    if (!is_array($sequence))
    {
      $sequence = array($sequence);
    }

    $firstByte = array_shift($sequence);
    $byteToRead = count($sequence);

    while ($this->available() > 0)
    {
      $byte = $this->readByte();
      if ($firstByte == $byte && $this->available() >= $byteToRead)
      {
        $this->mark("beginSearch");
        $bytes = $this->readBytes($byteToRead);
        if ($bytes === $sequence)
        {
          return true;
        }
        else
        {
          $this->reset("beginSearch");
        }
      }
    }

    return false;
  }
}