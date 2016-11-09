<?php
/**
* Basic class for matrix operations
*
*/
class Matrix        {
        
        /**
        * the matrix, a two dimensional array
        * @var array
        */
        private $matrix;

        /**
        * total of rows of the matrix
        * @var int
        */
        private $rowCount = 0;

        /**
        * total of columns of the matrix
        * @var int
        */
        private $columnCount = 0;

        /**
        * Constructs a new Matrix based on the two-dimensional that is passed in
        * @param array $matrix a two-dimensional array. $matrix is passed by reference
                                        and should be indexed without gaps, starting from 0!
                                        the first dimension contains arrays which are the rows
        */
        function __construct(&$matrix)        {
                $this->matrix                =& $matrix;
                $this->rowCount                = count($matrix);
                $this->columnCount        = count(current($matrix));
                //parent::__construct($array);
        }

        /**
        * returns the row of the matrix at position $index
        * if $index is an invalid key of the row, an exception will be thrown
        * @param int $index key of the row that has to be retrieved. possible values ranges from 0 to numberOfRows - 1
        * @return array
        */
        function getRow($index)        {
                if (!array_key_exists($index, $this->matrix))        {
                        throw new Exception('invalid row index provided');
                }
                return $this->matrix[$index];
        }

        /**
        * returns the column of the matrix at position $index
        * if $index is an invalid key of the row, an exception will be thrown
        * @param int $index key of the row that has to be retrieved. possible values ranges from 0 to numberOfRows - 1
        * @return array
        */
        function getColumn($index)        {
                $res = array();

                if ($index > $this->columnCount - 1 || $index < 0)        {
                        throw new Exception('invalid column index provided');
                }
                foreach ($this->matrix as $row)        {
                        $res[] = $row[$index];
                }
                return $res;
        }

        /**
        * multiply $rhs with this Matrix
        * @param mixed $rhs
                        if $rhs is numeric, each value of this matrix is multiplicated by $rhs (so called scalar multiplication)
                        if $rhs is a Matrix, a ordinary matrix product wil be returned
                        see <a href="http://en.wikipedia.org/wiki/Matrix_multiplication">http://en.wikipedia.org/wiki/Matrix_multiplication</a> for a formal explanation
        * @return Matrix new Matrix-object as the result of the operation
        */
        function multiply($rhs)        {
                if (is_numeric($rhs))        {
                        return $this->multiplyByNumber($rhs);
                } elseif ($rhs instanceof Matrix)        {
                        return $this->multiplyByMatrix($rhs);
                } else        {
                        throw new Exception('invalid operand');
                }
        }

        /**
        * sums this and $rhs matrix.
        * The dimensions of the both matrices have to be te same.<br>see <a href="http://en.wikipedia.org/wiki/Matrix_%28mathematics%29#Sum">http://en.wikipedia.org/wiki/Matrix_%28mathematics%29#Sum</a> for a formal explanation
        * @param Matrix $rhs
        * @return Matrix new Matrix-object as the result of the operation
        */
        function sum(Matrix $rhs)        {
                if ($rhs->rowCount !== $this->rowCount && $rhs->columnCount !== $this->columnCount)        {
                        throw new Exception('matrices cannot be added, because they have different dimensions');
                }

                for ($i = 0; $i < $this->rowCount; $i++)        {
                        $res[$i] = array();
                        for ($j = 0; $j < $this->columnCount; $j++)        {
                                $res[$i][$j] = $this->matrix[$i][$j] + $rhs->matrix[$i][$j];
                        }
                }
                return new Matrix($res);
        }

        /**
        * subtracts $rhs from this matrix
        * The dimensions of the both matrices have to be te same. Works like sum(), but this time the operation is subtract<br>
        * @param Matrix $rhs
        * @return Matrix new Matrix-object as the result of the operation
        */
        function minus(Matrix $rhs)        {
                if ($rhs->rowCount !== $this->rowCount && $rhs->columnCount !== $this->columnCount)        {
                        throw new Exception('matrices cannot be subtracted, because they have different dimensions');
                }

                for ($i = 0; $i < $this->rowCount; $i++)        {
                        $res[$i] = array();
                        for ($j = 0; $j < $this->columnCount; $j++)        {
                                $res[$i][$j] = $this->matrix[$i][$j] - $rhs->matrix[$i][$j];
                        }
                }
                return new Matrix($res);
        }

        function numberOfRows()        {
                return $this->rowCount;
        }

        function numberOfColumns()        {
                return $this->columnCount;
        }

        /**
        * HTML representation of the Matrix
        * only for debugging purposes
        * @return string table
        *
        */
        function __toString()        {
                $s = '<table border="1">';
                for ($i = 0; $i < $this->rowCount; $i++)        {
                        $s .= '<tr>';
                        for ($j = 0; $j < $this->columnCount; $j++)        {
                                $s .= '<td>'.$this->matrix[$i][$j].'</td>';
                        }
                        $s .= '</tr>';
                }
                return $s.'</table>';
        }

        private function multiplyByNumber($number)        {
                for ($i = 0; $i < $this->rowCount; $i++)        {
                        $res[$i] = array();
                        for ($j = 0; $j < $this->columnCount; $j++)        {
                                $res[$i][$j] = $this->matrix[$i][$j] * $number;
                        }
                }
                return new Matrix($res);
        }

        private function multiplyByMatrix(Matrix $rhs)        {
                if ($this->columnCount !== $rhs->rowCount)        {
                        throw new Exception('The matrices cannot be multiplied');
                }
                $res = array();

                for ($i = 0; $i < $this->rowCount; $i++)        {
                        $res[$i] = array();
                        for ($j = 0; $j < $rhs->columnCount ; $j++)        {
                                $res[$i][$j] = 0;
                                for ($k = 0; $k < $this->columnCount; $k++)        {
                                        $res[$i][$j] += $this->matrix[$i][$k] * $rhs->matrix[$k][$j];
                                }
                        }
                }
                return new Matrix($res);
        }
        
  public static function identity($order)
  {
    $identity = array();
    for($i=0; $i<$order; $i++)
      for($j=0; $j<$order; $j++)
        $identity[$i][$j] = ($i == $j ? 1 : 0);
    return new Matrix($identity);
  }
  
  private function cloneArray()
  {
    $m = array();
    for($i=0; $i<$this->rowCount; $i++)
      for($j=0; $j<$this->columnCount; $j++)
        $m[$i][$j] = $this->matrix[$i][$j];
    return $m;
  }

  public function getArray()
  {
    return $this->matrix;
  }
  
  // based on (buggy) http://aspire.cosmic-ray.org/javalabs/java12/seasons/matrix/Matrix.java
  public function inverse()
  {
    $b = $this->cloneArray();
    $rows = $this->rowCount;
    $cols = $this->columnCount;
    if($rows != $cols) die ("Non-square matrix.");
    $n = $rows;
    
    if($n == 1) 
    {
      $a = array();
      $a[0][0] = 1 / $b[0][0];
      return new Matrix($a);
    }
    
    $m = self::identity($n);
    $m = $m->getArray();

    for ($i = 0; $i < $n; $i++)
    {
      // find pivot
      $mag = 0;
      $pivot = -1;

      for ($j = $i; $j < $n; $j++)
      {
        $mag2 = abs($b[$j][$i]);
        if ($mag2 > $mag)
        {
          $mag = $mag2;
          $pivot = $j;
        }
      }

      // no pivot (error)
      if ($pivot == -1 || $mag == 0)
      {
         return null;
      }

      // move pivot row into position
      if ($pivot != $i)
      {
        for ($j = $i; $j < $n; $j++)
        {
          $temp = $b[$i][$j];
          $b[$i][$j] = $b[$pivot][$j];
          $b[$pivot][$j]= $temp;
        }

        for ($j = 0; $j < $n; $j++)
        {
          $temp = $m[$i][$j];
          $m[$i][$j] = $m[$pivot][$j];
          $m[$pivot][$j] = $temp;
        }
      }

      // normalize pivot row
      $mag = $b[$i][$i];
      for ($j = $i; $j < $n; $j++) $b[$i][$j] = $b[$i][$j] / $mag;
      for ($j = 0; $j < $n; $j++) $m[$i][$j] = $m[$i][$j] / $mag;

      // eliminate pivot row component from other rows
      for ($k = 0; $k < $n; $k++)
      {
        if ($k == $i) continue;
        $mag2 = $b[$k][$i];

        for ($j = $i; $j < $n; $j++) $b[$k][$j] = $b[$k][$j] - $mag2 * $b[$i][$j];
        for ($j = 0; $j < $n; $j++) $m[$k][$j] = $m[$k][$j] - $mag2 * $m[$i][$j];
      }
    }
    return new Matrix($m);
  }
  
  function getElement($i, $j)
  {
    return $this->matrix[$i][$j];  
  }

}
?>