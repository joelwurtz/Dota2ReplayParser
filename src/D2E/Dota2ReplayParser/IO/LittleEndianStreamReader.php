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
 * LittleEndianStreamReader use to read data from an input stream by using little endian order.
 *
 * @package    Dota2ReplayParser
 * @subpackage custom
 * @author     joel.wurtz@gmail.com
 * @version    1.0.0
 */
class LittleEndianStreamReader extends StreamReader
{
  public function readUInt16()
  {
    $bytes = $this->stream->read(2);
    $tmp = unpack("v", $bytes);

    return $tmp[1];
  }

  public function readUInt32()
  {
    $bytes = $this->stream->read(4);
    $tmp = unpack("V", $bytes);

    return $tmp[1];
  }

  public function readInt32()
  {
    $bytes = $this->stream->read(4);
    $tmp = unpack("l", $bytes);

    return $tmp[1];
  }

  public function readInt32D2()
  {
    $result = 0;
    $count = 0;

    while(true) {
      if ($count > 4) {
	throw new \Exception("Corrupted vint32");
      }

      $b = $this->readByte();
      $result = $result | ($b & 0x7F) << (7 * $count);
      $count++;

      if (!($b & 0x80)) {
	return $result;
      }
    }
  }
}
