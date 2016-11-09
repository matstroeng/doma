<?php
class StringUtil
{
  public static function getExtension($fileName)
  {
    $pathInfo = pathinfo($fileName);
    return $pathInfo["extension"];
  }

  // read to next and shorten
  public static function rtnas(&$str, $dlr, $caseSensitive = false)
  {
    if($caseSensitive)
      $pos = strpos($str, $dlr);
    else
      $pos = self::stripos4($str, $dlr);

    if($pos === false)
    {
      $ret = $str;
      $str = "";
    }
    else
    {
      $ret = substr($str, 0, $pos);
      $str = substr($str, $pos + strlen($dlr));
    }
    return $ret;
  }

  // read to next and shorten extended
  public static function rtnasEx(&$str, $dlr, $count, $caseSensitive = false)
  {
    for($i=0; $i<$count-1; $i++)
      self::rtnas($str, $dlr, $caseSensitive);
    return self::rtnas($str, $dlr, $caseSensitive);
  }

  // reverse read to next and shorten
  public static function rrtnas(&$str, $dlr, $caseSensitive = false)
  {
    $revstr = strrev($str);
    $revdlr = strrev($dlr);
    $lenstr = strlen($str);
    $lendlr = strlen($dlr);

    if($caseSensitive)
      $pos = strpos($revstr, $revdlr);
    else
      $pos = self::stripos4($revstr, $revdlr);

    if($pos === false)
    {
      $ret = $str;
      $str = "";
    }
    else
    {
      $ret = substr($str, 0, $lenstr - $lendlr - $pos);
      $str = substr($str, $lenstr - $pos);
    }

    return $ret;
  }

  private static function stripos4($haystack, $needle, $offset=0)
  {
    return strpos(strtolower($haystack), strtolower($needle), $offset);
  }
}
  
?>
