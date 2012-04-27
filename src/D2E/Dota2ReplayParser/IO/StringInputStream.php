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
 * StringInputStream use to retrieve data from a string.
 *
 * @package    Sc2ReplayParser
 * @subpackage IO
 * @author     joel.wurtz@gmail.com
 * @version    1.0.0
 */
class StringInputStream implements InputStream
{
  private $size;
  private $data;
  private $read = 0;
  private $log = array();

  public function __construct($data)
  {
    $this->size = strlen($data);
    $this->data = $data;
  }

  public function available()
  {
    return ($this->size - $this->read);
  }

  public function read($read = 1)
  {
    $return = substr($this->data, $this->read, $read);
    $this->read += $read;

    if (!empty($this->log))
    {
      foreach($this->log as $key => $logbyte)
      {
        $nbByte = strlen($return);
        $bytesArray = unpack('C'.$nbByte, $return);
        foreach($bytesArray as $byte)
        {
          $this->log[$key][] = sprintf("%02X", $byte);
        }
      }
    }

    return $return;
  }

  public function skip($skip)
  {
    $this->read($skip);
  }

  public function mark($key = "")
  {
    $this->markArray[$key] = $this->read;
  }

  public function reset($key = "")
  {
    $this->read = $this->markArray[$key];
  }

  public function offset($offset)
  {
    $this->read = $offset;
  }

  public function beginLog($key = "")
  {
    $this->log[$key] = array();
  }

  public function endLog($key = "")
  {
    $tmp = $this->log[$key];
    unset($this->log[$key]);
    return $tmp;
  }
}