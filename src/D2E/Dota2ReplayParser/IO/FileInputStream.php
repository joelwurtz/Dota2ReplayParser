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
 * InputStream represent an input stream of bytes.
 *
 * @package    Dota2ReplayParser
 * @subpackage custom
 * @author     joel.wurtz@gmail.com
 * @version    1.0.0
 */
class FileInputStream implements InputStream
{
  private $resource;
  private $size;
  private $read = 0;
  private $markArray = array();

  public function __construct($file)
  {
    $this->size = filesize($file);
    $this->resource = fopen($file, 'rb');
  }

  public function available()
  {
    return ($this->size - $this->read);
  }

  public function read($read = 1)
  {
    $this->read += $read;

    return fread($this->resource, $read);
  }

  public function skip($skip)
  {
    $this->read += $skip;

    return fseek($this->resource, $skip, SEEK_CUR);
  }

  public function mark($key = "")
  {
    $this->markArray[$key] = $this->read;
  }

  public function reset($key = "")
  {
    $this->read = $this->markArray[$key];

    return fseek($this->resource, $this->markArray[$key], SEEK_SET);
  }

  public function offset($offset)
  {
    $this->read = $offset;

    return fseek($this->resource, $offset, SEEK_SET);
  }

  public function beginLog($key = "")
  {

  }

  public function endLog($key = "")
  {

  }
}