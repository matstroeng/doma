<?php

  abstract class DatabaseObject
  {
    protected $DBTableName;
    protected $ClassName;
    protected $Data;
    protected $PrimaryKey = "ID";
    protected $AutoIncrement = true;

    public function __set($name, $value)
    {
      if(array_key_exists($name, $this->Data))
      {
        $this->Data[$name] = $value;
      }
    }

    public function __get($name)
    {
      if(array_key_exists($name, $this->Data))
      {
        return $this->Data[$name];
      }
      else
      {
        return null;
      }
    }

    public function Save()
    {
      $values = array();
      foreach($this->Data as $key => $val)
      {
        $values[$key] = $val;
      }

      if(!$this->Data[$this->PrimaryKey])
      {
        if($this->AutoIncrement) unset($values[$this->PrimaryKey]);
      }
      $sql = $this->BuildReplaceQuery($values);
      self::Query($sql);

      if(!$this->Data[$this->PrimaryKey])
      {
        $this->Data[$this->PrimaryKey] = mysqli_insert_id($GLOBALS["dbCon"]);
      }
    }

    public function LoadFromSql($sql)
    {
      $className = $this->ClassName;
      $objects = array();
      $rs = self::Query($sql);
      while($r = mysqli_fetch_assoc($rs))
      {
        $object = new $className();
        $object->LoadFromArray($r);
        $objects[] = $object;
      }
      return $objects;
    }

    public function Load($primaryKeyValue)
    {
      $className = $this->ClassName;
      $sql = "SELECT * FROM ". $this->DBTableName ." WHERE `". $this->PrimaryKey ."`='". mysqli_real_escape_string($GLOBALS["dbCon"], $primaryKeyValue) ."'";
      if($r = mysqli_fetch_assoc(self::Query($sql)))
      {
        $this->LoadFromArray($r);
        return true;
      }
      return false;
    }

    public function LoadFromArray($array)
    {
      foreach($array as $key=>$val)
      {
        $this->$key = $val;
      }
    }

    public function Delete()
    {
      $sql = "DELETE FROM `". $this->DBTableName ."` WHERE `". $this->PrimaryKey ."`='". mysqli_real_escape_string($GLOBALS["dbCon"], $this->Data[$this->PrimaryKey]) ."'";
      self::Query($sql);
    }

    private function BuildReplaceQuery($values)
    {
      $sql1 = "REPLACE INTO `". $this->DBTableName ."`(";
      $sql2 = ") VALUES (";
      foreach($values as $key => $val)
      {
        $sql1 .= "`$key`, ";
        $sql2 .= (is_null($val) ? "NULL, " : "'" . mysqli_real_escape_string($GLOBALS["dbCon"], $val) ."', ");
      }
      if(strlen($sql1) > 1) $sql1 = substr($sql1, 0, strlen($sql1) - 2);
      if(strlen($sql2) > 1) $sql2 = substr($sql2, 0, strlen($sql2) - 2);
      return $sql1 . $sql2 .")";
    }

    private static function Query($sql)
    {
      $result = mysqli_query($GLOBALS["dbCon"], $sql);
      Helper::WriteToLog($sql);
	  $error = mysqli_error($GLOBALS["dbCon"]);
      if($error) Helper::WriteToLog("MYSQL ERROR: ". $error);
      return $result;
    }

  }
?>