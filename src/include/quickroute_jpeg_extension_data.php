<?php
  define( "NOT_A_NUMBER", acos(1.01) );
  define( "POSITIVE_INFINITY", -log(0) );
  define( "NEGATIVE_INFINITY", log(0) );

  define("LAP_TYPE_START", 0);
  define("LAP_TYPE_LAP", 1);
  define("LAP_TYPE_STOP", 2);

  define("EPSILON", 0.001);

  class QuickRouteJpegExtensionData
  {
    public $IsValid;
    public $Version;
    public $MapCornerPositions;
    public $ImageCornerPositions;
    public $MapLocationAndSizeInPixels;
    public $Sessions;
    public $ExecutionTime;

    private static function GetTags()
    {
      return array(
        "Version" => 1,
        "MapCornerPositions" => 2,
        "ImageCornerPositions" => 3,
        "MapLocationAndSizeInPixels" => 4,
        "Sessions" => 5,
        "Session" => 6,
        "Route" => 7,
        "Handles" => 8,
        "ProjectionOrigin" => 9,
        "Laps" => 10,
        "SessionInfo" => 11,
        "MapReadingInfo" => 12
      );
    }

    private static function GetWaypointAttributes()
    {
      return array(
        "Position" => 1,
        "Time" => 2,
        "HeartRate" => 4,
        "Altitude" => 8
      );
    }

    public function __construct($fileName, $calculate = true)
    {
      $startTime = microtime(true);
      $fp = fopen($fileName, "r");
      $data = "";

      // find APP0 QuickRoute marker
      $soi = fread($fp, 2);
      if($soi == "\xff\xd8")
      {
        // try to find QuickRoute Jpeg Extension Data block
        while(!feof($fp))
        {
          if (fread($fp, 1) != "\xff") break; // we have reached image data
          if (fread($fp, 1) == "\xe0") // APP0
          {
            $quickrouteSegment = false;
            $length = 256 * ord(fread($fp, 1)) + ord(fread($fp, 1));
            if($length >= 12)
            {
              $stamp = fread($fp, 10);
              if($stamp == "QuickRoute")
              {
                $data .= fread($fp, $length - 12);
                $quickrouteSegment = true;
              }
              else
              {
                fseek($fp, $length - 12, SEEK_CUR);
              }
            }
            else
            {
              fseek($fp, $length - 2, SEEK_CUR);
            }
            if(!$quickrouteSegment && $data) break;
          }
          else
          {
            break;
          }
        }
      }
      fclose($fp);

      $this->IsValid = ($data != "");
      if($this->IsValid)
      {
        // QR data was found in image
        $this->Create($data);
        if($calculate) $this->Calculate();
      }
      $endTime = microtime(true);
      $this->ExecutionTime = $endTime - $startTime;
    }

    public function Calculate()
    {
      foreach($this->Sessions as $s)
      {
        $s->Calculate();
      }
    }

    private function Create($data)
    {
      if(!$data) return null;
      $dataLength = strlen($data);

      $tags = self::GetTags();
      $pos = 0;
      while($pos < $dataLength)
      {
        $tag = self::ReadByte(substr($data, $pos, 1));
        $pos++;
        $tagDataLength = self::ReadUInt32(substr($data, $pos, 4));
        $pos += 4;
        $tagData = substr($data, $pos, $tagDataLength);
        $pos += $tagDataLength;
        switch($tag)
        {
          case $tags["Version"]:
            $this->Version = self::ReadByte(substr($tagData, 0, 1)) .".".
                             self::ReadByte(substr($tagData, 1, 1)) .".".
                             self::ReadByte(substr($tagData, 2, 1)) .".".
                             self::ReadByte(substr($tagData, 3, 1));
            break;

          case $tags["MapCornerPositions"]:
            $this->MapCornerPositions["SW"] = self::ReadLongLat(substr($tagData, 0, 8));
            $this->MapCornerPositions["NW"] = self::ReadLongLat(substr($tagData, 8, 8));
            $this->MapCornerPositions["NE"] = self::ReadLongLat(substr($tagData, 16, 8));
            $this->MapCornerPositions["SE"] = self::ReadLongLat(substr($tagData, 24, 8));
            break;

          case $tags["ImageCornerPositions"]:
            $this->ImageCornerPositions["SW"] = self::ReadLongLat(substr($tagData, 0, 8));
            $this->ImageCornerPositions["NW"] = self::ReadLongLat(substr($tagData, 8, 8));
            $this->ImageCornerPositions["NE"] = self::ReadLongLat(substr($tagData, 16, 8));
            $this->ImageCornerPositions["SE"] = self::ReadLongLat(substr($tagData, 24, 8));
            break;

          case $tags["MapLocationAndSizeInPixels"]:
            $this->MapLocationAndSizeInPixels = new QRRectangle();
            $this->MapLocationAndSizeInPixels->X = self::ReadUInt16(substr($tagData, 0, 2));
            $this->MapLocationAndSizeInPixels->Y = self::ReadUInt16(substr($tagData, 2, 2));
            $this->MapLocationAndSizeInPixels->Width = self::ReadUInt16(substr($tagData, 4, 2));
            $this->MapLocationAndSizeInPixels->Height = self::ReadUInt16(substr($tagData, 6, 2));
            break;

          case $tags["Sessions"]:
            $this->Sessions = self::ReadSessions($tagData);
            break;
        }
      }
    }

    private static function ReadSessions($data)
    {
      $tags = self::GetTags();
      $sessions = array();
      $sessionCount = self::ReadUInt32(substr($data, 0, 4));
      $pos = 4;
      $dataLength = strlen($data);
      for($i=0; $i<$sessionCount; $i++)
      {
        $tag = self::ReadByte(substr($data, $pos, 1));
        $pos++;
        $tagDataLength = self::ReadUInt32(substr($data, $pos, 4));
        $pos += 4;
        $tagData = substr($data, $pos, $tagDataLength);
        $pos += $tagDataLength;
        switch($tag)
        {
          case $tags["Session"]:
            $sessions[] = self::ReadSession($tagData);
            $sessionCount++;
            break;
        }
      }
      return $sessions;
    }

    private static function ReadSession($data)
    {
      $tags = self::GetTags();
      $waypointAttributes = self::GetWaypointAttributes();
      $session = new QRSession();

      $pos = 0;
      $dataLength = strlen($data);
      while($pos < $dataLength)
      {
        $tag = self::ReadByte(substr($data, $pos, 1));
        $pos++;
        $tagDataLength = self::ReadUInt32(substr($data, $pos, 4));
        $pos += 4;
        $tagData = substr($data, $pos, $tagDataLength);
        $pos += $tagDataLength;
        switch($tag)
        {
          case $tags["Route"]:
            $session->Route = new QRRoute();
            $subPos = 0;
            $attributes = self::ReadUInt16(substr($tagData, $subPos, 2));
            $subPos += 2;
            $extraWaypointAttributesLength = self::ReadUInt16(substr($tagData, $subPos, 2));
            $subPos += 2;
            $segmentCount = self::ReadUInt32(substr($tagData, $subPos, 4));
            $subPos += 4;
            for($i=0; $i<$segmentCount; $i++)
            {
              $segment = new QRRouteSegment();
              $waypointCount = self::ReadUInt32(substr($tagData, $subPos, 4));
              $subPos += 4;
              for($j=0; $j<$waypointCount; $j++)
              {
                $waypoint = new QRWaypoint();
                // position
                if($attributes & $waypointAttributes["Position"])
                {
                  $waypoint->Position = self::ReadLongLat(substr($tagData, $subPos, 8));
                  $subPos += 8;
                }
                // time
                if($attributes & $waypointAttributes["Time"])
                {
                  $timeType = self::ReadByte(substr($tagData, $subPos, 1));
                  $subPos += 1;
                  if($timeType == 0)
                  {
                    $time = self::ReadDateTime(substr($tagData, $subPos, 8));
                    $subPos += 8;
                  }
                  else
                  {
                    $time = $lastTime + self::ReadUInt16(substr($tagData, $subPos, 2)) / 1000;
                    $subPos += 2;
                  }
                  $waypoint->Time = $time;
                }
                // heart rate
                if($attributes & $waypointAttributes["HeartRate"])
                {
                  $waypoint->HeartRate = self::ReadByte(substr($tagData, $subPos, 1));
                  $subPos += 1;
                }
                // altitude
                if($attributes & $waypointAttributes["Altitude"])
                {
                  $waypoint->Altitude = self::ReadInt16(substr($tagData, $subPos, 2));
                  $subPos += 2;
                }
                $subPos += $extraWaypointAttributesLength;
                $segment->Waypoints[] = $waypoint;
                $lastTime = $time;
              }
              $session->Route->Segments[] = $segment;
            }
            break;

          case $tags["Handles"]:
            $handleCount = self::ReadUInt32(substr($tagData, 0, 4));
            $subPos = 4;
            for($i=0; $i<$handleCount; $i++)
            {
              $handle = new QRHandle();
              // transformation matrix
              $handle->TransformationMatrix = new QRMatrix(3, 3);
              for($j=0; $j<3; $j++)
              {
                for($k=0; $k<3; $k++)
                {
                  $value = self::ReadDouble(substr($tagData, $subPos, 8));
                  $handle->TransformationMatrix->SetElement($j, $k, $value);
                  $subPos += 8;
                }
              }
              // parameterized location
              $handle->ParameterizedLocation = new QRParameterizedLocation();
              $handle->ParameterizedLocation->SegmentIndex = self::ReadUInt32(substr($tagData, $subPos, 4));
              $subPos += 4;
              $handle->ParameterizedLocation->Value = self::ReadDouble(substr($tagData, $subPos, 8));
              $subPos += 8;
              // pixel location
              $handle->PixelLocation = new QRPoint();
              $handle->PixelLocation->X = self::ReadDouble(substr($tagData, $subPos, 8));
              $subPos += 8;
              $handle->PixelLocation->Y = self::ReadDouble(substr($tagData, $subPos, 8));
              $subPos += 8;
              // type
              $handle->Type = self::ReadInt16(substr($tagData, $subPos, 2));
              $subPos += 2;
              $session->Handles[] = $handle;
            }
            break;

          case $tags["ProjectionOrigin"]:
             $session->ProjectionOrigin = self::ReadLongLat(substr($tagData, 0, 8));
            break;

          case $tags["Laps"]:
            $lapCount = self::ReadUInt32(substr($tagData, 0, 4));
            $subPos = 4;
            for($i=0; $i<$lapCount; $i++)
            {
              $lap = new QRLap();
              $lap->Time = self::ReadDateTime(substr($tagData, $subPos, 8));
              $subPos += 8;
              $lap->Type = self::ReadByte(substr($tagData, $subPos, 1));
              $subPos += 1;
              $session->Laps[] = $lap;
            }
            break;

          case $tags["SessionInfo"]:
            $session->SessionInfo = new QRSessionInfo();
            $session->SessionInfo->Person = new QRPerson();
            // person name
            $length = self::ReadUInt16(substr($tagData, 0, 2));
            $subPos = 2;
            $session->SessionInfo->Person->Name = self::ReadUtf8String(substr($tagData, $subPos, $length));
            $subPos += $length;
            // person club
            $length = self::ReadUInt16(substr($tagData, $subPos, 2));
            $subPos += 2;
            $session->SessionInfo->Person->Club = self::ReadUtf8String(substr($tagData, $subPos, $length));
            $subPos += $length;
            // person id
            $session->SessionInfo->Person->Id = self::ReadUInt32(substr($tagData, $subPos, 4));
            $subPos += 4;
            // session description
            $length = self::ReadUInt16(substr($tagData, $subPos, 2));
            $subPos += 2;
            $session->SessionInfo->Description = self::ReadUtf8String(substr($tagData, $subPos, $length));
            $subPos += $length;
            // if there are more fields added in the future, make sure that we are not
            break;

          case $tags["MapReadingInfo"]:
            $session->MapReadingInfo = array();
            $subPos = 0;
            $count = 0;
            while($subPos < $tagDataLength)
            {
              $timeType = self::ReadByte(substr($tagData, $subPos, 1));
              $subPos += 1;
              if($timeType == 0)
              {
                $time = self::ReadDateTime(substr($tagData, $subPos, 8));
                $subPos += 8;
              }
              else
              {
                $time = $lastTime + self::ReadUInt16(substr($tagData, $subPos, 2)) / 1000;
                $subPos += 2;
              }
              $count++;
              if($count % 2 == 0) 
              {
                $mapReading = new QRMapReading();
                $mapReading->StartTime = $lastTime;
                $mapReading->EndTime = $time;
                $session->MapReadingInfo[] = $mapReading;
              }
              $lastTime = $time;
            }
            break;
        }
      }
      return $session;
    }

    private static function ReadIntegerValue($data, $byteCount, $signed)
    {
      $bitCount = $byteCount * 8;
      $value = 0;
      $multiplier = 1;
      for($i=0; $i<$byteCount; $i++)
      {
        if(isset($data[$i])) $value += $multiplier * ord($data[$i]);
        $multiplier *= 1 << 8;
      }
      if($signed && isset($data[$byteCount-1]) && (ord($data[$byteCount-1]) & (1 << 7)))
      {
        $value = -pow(2, $bitCount) + $value;
      }
      return $value;
    }

    private static function ReadByte($data)
    {
      return self::ReadIntegerValue($data, 1, false);
    }

    private static function ReadSByte($data)
    {
      return self::ReadIntegerValue($data, 1, true);
    }

    private static function ReadUInt16($data)
    {
      return self::ReadIntegerValue($data, 2, false);
    }

    private static function ReadInt16($data)
    {
      return self::ReadIntegerValue($data, 2, true);
    }

    private static function ReadUInt32($data)
    {
      return self::ReadIntegerValue($data, 4, false);
    }

    private static function ReadInt32($data)
    {
      return self::ReadIntegerValue($data, 4, true);
    }

    private static function ReadUInt64($data)
    {
      return self::ReadIntegerValue($data, 8, false);
    }

    private static function ReadInt64($data)
    {
      return self::ReadIntegerValue($data, 8, true);
    }

    private static function ReadDouble($data)
    {
      if($data == "\x00\x00\x00\x00\x00\x00\x00\x00") return 0;
      if($data == "\x80\x00\x00\x00\x00\x00\x00\x00") return -0;

      $sign = (ord($data[7]) >> 7 == 0 ? 1 : -1);
      $exponent=-1023;
      $exponent += (ord($data[7]) % 128) << 4;
      $exponent += ord($data[6]) >> 4;

      $base=1.0;
      for($i=4; $i<8; $i++) $base += ((ord($data[6]) >> (7-$i)) % 2) * pow(0.5, $i-3);
      for($i=5; $i>=0; $i--)
        for($j=0; $j<8; $j++) $base += ((ord($data[$i]) >> (7-$j)) % 2) * pow(0.5, (5-$i)*8+$j+5);

      $double = (float)$sign*pow(2,$exponent)*$base;
      return $double;
    }

    private static function ReadDateTime($data)
    {
      // converts a .NET datetime (1 unit = 100 nanoseconds, starts at 0000-01-01 00:00:00 UTC) to a PHP time (1 unit = 1 second, starts at 1970-01-01 00:00:00 UTC)
      // The DateTime.Kind bits (two most significant bits) are not taken into consideration, UTC is assumed
      $val = self::ReadUInt64($data);
      if($val >= 9223372036854775808) $val -= 9223372036854775808; // (2^63)
      if($val >= 4611686018427387904) $val -= 4611686018427387904; // (2^62)
      return ($val - 621355968000000000) / 10000000;
    }

    private static function ReadLongLat($data)
    {
      $longLat = new QRLongLat();
      $longLat->Longitude = self::ReadInt32(substr($data, 0, 4)) / 3600000;
      $longLat->Latitude = self::ReadInt32(substr($data, 4, 4)) / 3600000;
      return $longLat;
    }

    private static function ReadUtf8String($data)
    {
      return utf8_decode($data);
    }

    private static function WriteIntegerValue($fp, $value, $byteCount, $signed)
    {
      $bitCount = $byteCount  * 8;
      if($signed)
      {
        if($value < -pow(2, $bitCount-1)) $value = -pow(2, $bitCount-1);
        if($value > pow(2, $bitCount-1)-1) $value = pow(2, $bitCount-1)-1;
      }
      else
      {
        if($value < 0) $value = 0;
        if($value > pow(2, $bitCount)-1) $value = pow(2, $bitCount-1);
      }

      if($signed && $value < 0) $value = pow(2, $bitCount) + $value;
      for($i=0; $i<$byteCount; $i++)
      {
        fwrite($fp, chr($value % 256));
        $value = ($value - $value % 256) / 256;
      }
    }

    private static function WriteByte($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 1, false);
    }

    private static function WriteSByte($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 1, true);
    }

    private static function WriteUInt16($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 2, false);
    }

    private static function WriteInt16($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 2, true);
    }

    private static function WriteUInt32($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 4, false);
    }

    private static function WriteInt32($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 4, true);
    }

    private static function WriteUInt64($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 8, false);
    }

    private static function WriteInt64($fp, $data)
    {
      return self::WriteIntegerValue($fp, $data, 8, true);
    }

    // TODO: php doesn't handle doubles internally, what shall I do?
    private static function WriteDouble($fp, $data)
    {
      $precisionBits = 52;
      $exponentBits = 11;
      $bigEndian = false;

      $bias = pow( 2, $exponentBits - 1 ) - 1;
      $minExp = -$bias + 1;
      $maxExp = $bias;
      $minUnnormExp = $minExp - $precisionBits;
      $status = is_nan( $n = (float)$data ) || $n == NEGATIVE_INFINITY || $n == POSITIVE_INFINITY ? $n : 0;
      $exp = 0;
      $len = 2 * $bias + 1 + $precisionBits + 3;
      $bin = array_pad( array(), $len, 0 );
      $signal = ( $n = $status !== 0 ? 0 : $n ) < 0;
      $n = abs( $n );
      $intPart = floor( $n );
      $floatPart = $n - $intPart;
      for( $i = $bias + 2; $intPart && $i; $bin[--$i] = abs( $intPart % 2 ), $intPart = floor( $intPart / 2 ) );
      for( $i = $bias + 1; $floatPart > 0 && $i; )
          if( $bin[++$i] = ( ( $floatPart *= 2 ) >= 1 ) - 0 )
              --$floatPart;
      for( $i = -1; ++$i < $len && !$bin[$i]; );
      $i = ( $exp = $bias + 1 - $i ) >= $minExp && $exp <= $maxExp ? $i + 1 : $bias + 1 - ( $exp = $minExp - 1 );
      if( $bin[( $lastBit = $precisionBits - 1 + $i ) + 1] ){
          if( !( $rounded = $bin[$lastBit] ) )
              for( $j = $lastBit + 2; !$rounded && $j < $len; $rounded = $bin[$j++] );
          for( $j = $lastBit + 1; $rounded && --$j >= 0; )
              if( $bin[$j] = !$bin[$j] - 0 )
                  $rounded = 0;
      }
      for( $i = $i - 2 < 0 ? -1 : $i - 3; ++$i < $len && !$bin[$i]; );
      if( ( $exp = $bias + 1 - $i ) >= $minExp && $exp <= $maxExp )
          ++$i;
      else if( $exp < $minExp ){
          if( $exp != $bias + 1 - $len && $exp < $minUnnormExp )
              throw new Exception( __METHOD__ . ": underflow" );
              $i = $bias + 1 - ( $exp = $minExp - 1 );
      }
      if( $intPart || $status !== 0 ){
          throw new Exception( __METHOD__ . ": " . ( $intPart ? "overflow" : $status ) );
          $exp = $maxExp + 1;
          $i = $bias + 2;
          if( $status == NEGATIVE_INFINITY )
              $signal = 1;
          else if( is_nan( $status ) )
              $bin[$i] = 1;
      }
      for( $n = abs( $exp + $bias ), $j = $exponentBits + 1, $result = ""; --$j; $result = ( $n % 2 ) . $result, $n = $n >>= 1 );
      $result = ( $signal ? "1" : "0" ) . $result . implode( "", array_slice( $bin, $i, $precisionBits ) );
      for( $n = 0, $j = 0, $i = strlen( $result ), $r = array(); $i; $j = ( $j + 1 ) % 8 ){
          $n += ( 1 << $j ) * $result[--$i];
          if( $j == 7 ){
              $r[] = chr( $n );
              $n = 0;
          }
      }
      $r[] = $n ? chr( $n ) : "";
      $bin = implode( "", ( $bigEndian ? array_reverse( $r ) : $r ) );
      fwrite($fp, $bin);
    }

    // TODO: php doesn't handle 64 bit integers, what shall I do?
    private static function WriteDateTime($fp, $data)
    {
      // converts a PHP time (1 unit = 1 second, starts at 1970-01-01 00:00:00 UTC) to a .NET datetime (1 unit = 100 nanoseconds, starts at 0000-01-01 00:00:00 UTC)
      // two most significant bits in .NET time not used
      $val = (int)($data * 10000000 + 621355968000000000);
      if($val >= 9223372036854775808) $val -= 9223372036854775808; // (2^63)
      if($val >= 4611686018427387904) $val -= 4611686018427387904; // (2^62)
      self::WriteUInt64($fp, $val);
    }

    private static function WriteLongLat($fp, $longLat)
    {
      self::WriteInt32($fp, round($longLat->Longitude * 3600000));
      self::WriteInt32($fp, round($longLat->Latitude * 3600000));
    }

  }

  class QRSession
  {
    public $Route;
    public $Handles;
    public $ProjectionOrigin;
    public $Laps;
    public $SessionInfo;
    public $MapReadingInfo;
    // derived properties
    public $StraightLineDistance = null;

    public function GetStartTime()
    {
      return $this->Route->Segments[0]->Waypoints[0]->Time;
    }

    public function GetEndTime()
    {
      $segment = $this->Route->Segments[count($this->Route->Segments)-1];
      return $segment->Waypoints[count($segment->Waypoints)-1]->Time;
    }

    public function Calculate()
    {
      $this->Route->CalculateParameters();
      $this->CalculateLaps();
    }

    private function CalculateLaps()
    {
      $this->StraightLineDistance = 0;
      for($i=0; $i<count($this->Laps); $i++)
      {
        $lap = $this->Laps[$i];
        $pl = $this->Route->GetParameterizedLocationFromTime($lap->Time);
        $lap->Position = $this->Route->GetPositionFromParameterizedLocation($pl);
        $distance = $this->Route->GetDistanceFromParameterizedLocation($pl);
        if(in_array($lap->Type, array(LAP_TYPE_LAP, LAP_TYPE_STOP)))
        {
          // distances is only calculated for lap and stop lap types
          $lap->Distance = $distance - $lastDistance;
		  if ((is_object($lastLap))&&(is_object($lap->Position)))
          {
          $lap->StraightLineDistance = $lap->Position->DistanceTo($lastLap->Position);
		  }
		  else
		  {
		    $lap->StraightLineDistance = 0;
		  }
          $this->StraightLineDistance += $lap->StraightLineDistance;
        }
        $lastDistance = $distance;
        $lastLap = $lap;
      }
    }
  }

  class QRSessionInfo
  {
    public $Person;
    public $Description;
  }

  class QRPerson
  {
    public $Name;
    public $Club;
    public $Id;
  }

  class QRRoute
  {
    public $Segments;
    // derived properties
    public $Distance = null;
    public $ElapsedTime = null;

    public function CalculateParameters()
    {
      $this->Distance = 0;
      $this->ElapsedTime = 0;
      foreach($this->Segments as $s)
      {
        $count = count($s->Waypoints);
        // calculate distance at each waypoint using optimized algorithm
        $longLats = array();
        foreach($s->Waypoints as $w)
        {
          $longLats[] = $w->Position;
        }
        $distances = QRLongLat::PolyDistances($longLats);
        $distance = 0;
        // store these distances and also the elapsed times
        for($i=0; $i<$count; $i++)
        {
          $distance += $distances[$i];
          $s->Waypoints[$i]->Distance = $this->Distance + $distance;
          $s->Waypoints[$i]->ElapsedTime = $this->ElapsedTime + $s->Waypoints[$i]->Time - $s->Waypoints[0]->Time;
        }
        // update distance sum
        $this->Distance += $distance;
        // update elapsed time sum
        $this->ElapsedTime += $s->Waypoints[$count-1]->Time - $s->Waypoints[0]->Time;
      }
    }

    public function GetParameterizedLocationFromTime($time)
    {
      // which segment?
      $segmentIndex = -1;
      for($i=0; $i<count($this->Segments); $i++)
      {
        $startTime = $this->Segments[$i]->Waypoints[0]->Time;
        $endTime = $this->Segments[$i]->Waypoints[count($this->Segments[$i]->Waypoints)-1]->Time;
        if($time + EPSILON >= $startTime && $time - EPSILON <= $endTime)
        {
          $segmentIndex = $i;
          break;
        }
      }
      if($segmentIndex == -1) return null; // outside the session

      // perform binary search in this segment index
      $min = 0;
      $max = count($this->Segments[$segmentIndex]->Waypoints)-1;
      while($min<=$max)
      {
        $middle = (int)(($min+$max)/2);
        $middleTime = $this->Segments[$segmentIndex]->Waypoints[$middle]->Time;
        if(abs($time - $middleTime) < EPSILON) return new QRParameterizedLocation($segmentIndex, $middle);
        if($time < $middleTime)
        {
          $max = $middle-1;
        }
        else
        {
          $min = $middle+1;
        }
      }
      $t0 = $this->Segments[$segmentIndex]->Waypoints[$max]->Time;
      $t1 = $this->Segments[$segmentIndex]->Waypoints[$min]->Time;
      if($t1 == $t0) return ParameterizedLocation($segmentIndex, $max);
      return new QRParameterizedLocation($segmentIndex, $max + ($time-$t0) / ($t1-$t0)); // $max is now min index
    }

    public function GetDistanceFromParameterizedLocation($parameterizedLocation)
    {
      if($parameterizedLocation == null) return null;
      list($w0, $w1, $t) = $this->GetWaypointsAndParameterFromParameterizedLocation($parameterizedLocation);
      return $w0->Distance + $t * ($w1->Distance - $w0->Distance);
    }

    public function GetPositionFromParameterizedLocation($parameterizedLocation)
    {
      if($parameterizedLocation == null) return null;
      list($w0, $w1, $t) = $this->GetWaypointsAndParameterFromParameterizedLocation($parameterizedLocation);
      return new QRLongLat(
        $w0->Position->Longitude + $t * ($w1->Position->Longitude - $w0->Position->Longitude),
        $w0->Position->Latitude + $t * ($w1->Position->Latitude - $w0->Position->Latitude)
      );
    }

    public function GetWaypointsAndParameterFromParameterizedLocation($parameterizedLocation)
    {
      if($parameterizedLocation == null) return null;
      $segment = $this->Segments[$parameterizedLocation->SegmentIndex];
      if(!$segment) return null;
      $waypoints = $segment->Waypoints;

      $i = (int)$parameterizedLocation->Value;
      if($i >= count($waypoints) - 1) $i = count($waypoints) - 2;
      if(count($waypoints) < 2) return array($waypoints[0], $waypoints[0], 0);
      $t = $parameterizedLocation->Value - $i;

      return array($waypoints[$i], $waypoints[$i + 1], $t);
    }

    public function GetDistanceFromTime($time)
    {
      $pl = $this->GetParameterizedLocationFromTime($time);
      return $this->GetDistanceFromParameterizedLocation($pl);
    }

    public function GetWaypointPositionsAsArray($samplingInterval, $positionDecimalPlaces = -1)
    {
      $segments = array();
      foreach($this->Segments as $s)
      {
        $segment = array();
        for($i=0; $i<count($s->Waypoints); $i++)
        {
          $w = $s->Waypoints[$i];
          if($i == 0 ||
             $i == count($s->Waypoints) -1 ||
             $w->Time >= $lastWaypoint->Time + $samplingInterval)
          {
            $longLat = ($positionDecimalPlaces == -1 ?
              array($w->Position->Longitude, $w->Position->Latitude) :
              array(round($w->Position->Longitude, $positionDecimalPlaces), round($w->Position->Latitude, $positionDecimalPlaces)));
            $segment[] = $longLat;
            $lastWaypoint = $w;
          }
        }
        $segments[] = $segment;
      }
      return $segments;
    }

  }

  class QRRouteSegment
  {
    public $Waypoints;

  }

  class QRWaypoint
  {
    public $Position;
    public $Time;
    public $HeartRate;
    public $Altitude;
    // derived properties
    public $Speed;
    public $Distance;
    public $ElapsedTime;
  }

  class QRLongLat
  {
    const rho = 6378200; // earth radius in metres
    public $Longitude;
    public $Latitude;

    public function __construct($longitude = 0, $latitude = 0)
    {
      $this->Longitude = $longitude;
      $this->Latitude = $latitude;
    }

    public function Project($projectionOrigin)
    {
      $lambda0 = $projectionOrigin->Longitude * M_PI / 180;
      $phi0 = $projectionOrigin->Latitude * M_PI / 180;

      $lambda = $this->Longitude * M_PI / 180;
      $phi = $this->Latitude * M_PI / 180;
      return new QRPoint(self::rho * cos($phi) * sin($lambda - $lambda0),
                       self::rho * (cos($phi0) * sin($phi) - sin($phi0) * cos($phi) * cos($lambda - $lambda0)));
    }

    public function DistanceTo($other)
    {
      // use spherical coordinates: self::rho, phi, theta
      $sinPhi0 = sin(0.5 * M_PI + $this->Latitude / 180 * M_PI);
      $cosPhi0 = cos(0.5 * M_PI + $this->Latitude / 180 * M_PI);
      $sinTheta0 = sin($this->Longitude / 180 * M_PI);
      $cosTheta0 = cos($this->Longitude / 180 * M_PI);

      $sinPhi1 = sin(0.5 * M_PI + $other->Latitude / 180 * M_PI);
      $cosPhi1 = cos(0.5 * M_PI + $other->Latitude / 180 * M_PI);
      $sinTheta1 = sin($other->Longitude / 180 * M_PI);
      $cosTheta1 = cos($other->Longitude / 180 * M_PI);

      $p0 = new QRMatrix(3, 1);
      $p0->SetElement(0, 0, self::rho * $sinPhi0 * $cosTheta0);
      $p0->SetElement(1, 0, self::rho * $sinPhi0 * $sinTheta0);
      $p0->SetElement(2, 0, self::rho * $cosPhi0);

      $p1 = new QRMatrix(3, 1);
      $p1->SetElement(0, 0, self::rho * $sinPhi1 * $cosTheta1);
      $p1->SetElement(1, 0, self::rho * $sinPhi1 * $sinTheta1);
      $p1->SetElement(2, 0, self::rho * $cosPhi1);

      $distance = self::DistancePointToPoint($p0, $p1);
      return $distance;
    }

    public static function PolyDistances($longLats)
    {
      if(count($longLats) < 2) return 0;
      $distances = array(0);

      $sinPhi1 = sin(0.5 * M_PI + $longLats[0]->Latitude / 180 * M_PI);
      $cosPhi1 = cos(0.5 * M_PI + $longLats[0]->Latitude / 180 * M_PI);
      $sinTheta1 = sin($longLats[0]->Longitude / 180 * M_PI);
      $cosTheta1 = cos($longLats[0]->Longitude / 180 * M_PI);
      $p1 = new QRMatrix(3, 1);
      $p1->SetElement(0, 0, self::rho * $sinPhi1 * $cosTheta1);
      $p1->SetElement(1, 0, self::rho * $sinPhi1 * $sinTheta1);
      $p1->SetElement(2, 0, self::rho * $cosPhi1);

      for($i=1; $i<count($longLats); $i++)
      {
        $sinPhi0 = $sinPhi1;
        $cosPhi0 = $cosPhi1;
        $sinTheta0 = $sinTheta1;
        $cosTheta0 = $cosTheta1;
        $p0 = $p1;

        $sinPhi1 = sin(0.5 * M_PI + $longLats[$i]->Latitude / 180 * M_PI);
        $cosPhi1 = cos(0.5 * M_PI + $longLats[$i]->Latitude / 180 * M_PI);
        $sinTheta1 = sin($longLats[$i]->Longitude / 180 * M_PI);
        $cosTheta1 = cos($longLats[$i]->Longitude / 180 * M_PI);
        $p1 = new QRMatrix(3, 1);
        $p1->SetElement(0, 0, self::rho * $sinPhi1 * $cosTheta1);
        $p1->SetElement(1, 0, self::rho * $sinPhi1 * $sinTheta1);
        $p1->SetElement(2, 0, self::rho * $cosPhi1);

        $distances[] = self::DistancePointToPoint($p0, $p1);
      }
      return $distances;
    }

    private static function DistancePointToPoint($p0, $p1)
    {
      $sum = 0;
      for ($i = 0; $i < $p0->GetRows(); $i++)
        $sum += ($p1->GetElement($i, 0) - $p0->GetElement($i, 0)) * ($p1->GetElement($i, 0) - $p0->GetElement($i, 0));
      return sqrt($sum);
    }


  }

  class QRPoint
  {
    public $X;
    public $Y;

    public function __construct($x = 0, $y = 0)
    {
      $this->X = $x;
      $this->Y = $y;
    }
  }

  class QRRectangle
  {
    public $X;
    public $Y;
    public $Width;
    public $Height;
  }

  class QRHandle
  {
    public $TransformationMatrix;
    public $ParameterizedLocation;
    public $PixelLocation;
    public $Type;
  }

  class QRLap
  {
    public $Time;
    public $Type;
    // derived properties
    public $Position;
    public $Distance;
    public $StraightLineDistance;
  }

  class QRParameterizedLocation
  {
    public $SegmentIndex;
    public $Value;

    public function __construct($segmentIndex = 0, $value = 0)
    {
      $this->SegmentIndex = $segmentIndex;
      $this->Value = $value;
    }
  }

  class QRMatrix
  {
    public $Elements;
    private $Rows;
    private $Columns;

    public function __construct($rows, $columns)
    {
      $this->Rows = $rows;
      $this->Columns = $columns;
    }

    public function GetRows()
    {
      return $this->Rows;
    }

    public function GetColumns()
    {
      return $this->Columns;
    }

    public function SetElement($i, $j, $value)
    {
      if($i < 0 || $i > $this->Rows-1 || $j < 0 || $j > $this->Columns-1) return;
      $this->Elements[$this->Columns*$i + $j] = $value;
    }

    public function GetElement($i, $j)
    {
      if($i < 0 || $i > $this->Rows-1 || $j < 0 || $j > $this->Columns-1) return null;
      return $this->Elements[$this->Columns*$i + $j];
    }

    // $this * $other
    public function Multiply($other)
    {
      if($this->Columns != $other->GetRows()) return null;
      $r = $this->Rows;
      $c = $other->GetColumns();

      $result = new QRMatrix($r, $c);
      for ($i=0; $i<$r; $i++)
      {
        for ($j=0; $j<$c; $j++)
        {
          $sum = 0;
          for ($k=0; $k<$this->Columns; $k++)
          {
            $sum += $this->GetElement($i, $k) * $other->GetElement($k, $j);
          }
          $result->SetElement($i, $j, $sum);
        }
      }
      return $result;
    }
  }

  class QRMapReading
  {
    public $StartTime;
    public $EndTime;
  }

?>
