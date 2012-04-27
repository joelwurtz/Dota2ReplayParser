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
interface InputStream
{
  public function available();

  public function read($read = 1);

  public function skip($skip);

  public function mark($key = "");

  public function reset($key = "");

  public function offset($offset);

  public function beginLog($key = "");

  public function endLog($key = "");
}