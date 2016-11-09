<?php
class LinearAlgebraUtil
{
  public static function transformPoint($point, $transformationMatrix)
  {
    return self::toPoint(self::multiply($transformationMatrix, self::to3x1Matrix($point)));
  }
  
  public static function toPoint($_3x1matrix)
  {
    return new Point($_3x1matrix[0][0], $_3x1matrix[1][0]);
  }

  public static function to3x1Matrix(Point $point)
  {
    $m = array();
    $m[0][0] = $point->x;
    $m[1][0] = $point->y;
    $m[2][0] = 1;
    return $m;
  }
  
  public static function inverse($matrix)
  {
    $M = new Matrix($matrix);
    return $M->inverse()->getArray();
  }  

  public static function multiply($m1, $m2)
  {
    $M1 = new Matrix($m1);
    $M2 = new Matrix($m2);
    return $M1->multiply($M2)->getArray();
  }  
  
  public static function distance(Point $p0, Point $p1)
  {
    return sqrt(($p1->x - $p0->x) * ($p1->x - $p0->x) + ($p1->y - $p0->y) * ($p1->y - $p0->y));
  }  
  
  public static function getAngle(Point $v0, Point $v1)
  {
    $a0 = self::getAngleHelper($v0);
    $a1 = self::getAngleHelper($v1) + 2.0 * M_PI;

    $diff = $a1 - $a0;

    if ($diff > M_PI) $diff -= 2.0 * M_PI;
    return $diff;
  }
  
  private static function getAngleHelper(Point $v)
  {
    $normalizedV = self::normalize($v);
    $dp = self::dotProduct($normalizedV, new Point(1.0, 0.0));
    if ($dp > 1.0) $dp = 1.0;
    else if ($dp < -1.0) $dp = -1.0;
    if ($v->y < 0.0)
      $angle = 2.0 * M_PI - acos($dp);
    else
      $angle = acos($dp);
    if ($angle > M_PI) $angle -= 2.0 * M_PI;
    return $angle;
  }  
  
  public static function dotProduct(Point $v0, Point $v1)
  {
    return $v0->x * $v1->x + $v0->y * $v1->y;
  }  
  
  public static function normalize(Point $vector)
  {
    if ($vector->x == 0.0 && $vector->y == 0.0) return new Point(0.0, 0.0);
    $length = sqrt($vector->x * $vector->x + $vector->y * $vector->y);
    return new Point($vector->x / $length, $vector->y / $length);
  }  

  public static function rotate(Point $point, Point $rotationCenter, $angle)
  {
    $m1 = array(array(1, 0, $rotationCenter->x), array(0, 1, $rotationCenter->y), array(0, 0, 1));
    $m2 = array(array(cos($angle), sin($angle), 0), array(-sin($angle), cos($angle), 0), array(0, 0, 1));
    $m3 = array(array(1, 0, -$rotationCenter->x), array(0, 1, -$rotationCenter->y), array(0, 0, 1));
    $p = array(array($point->x), array($point->y), array(1));
    
    $rotated = self::multiply(self::multiply(self::multiply($m1, $m2), $m3), $p);
    return new Point($rotated[0][0], $rotated[1][0]);
  }
}    
?>
