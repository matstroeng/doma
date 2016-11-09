<?php
  class Session
  {
    public static function GetLanguageStrings()
    {
      return self::GetValue("LANGUAGE_STRINGS");
    }
    public static function SetLanguageStrings($value)
    {
      self::SetValue("LANGUAGE_STRINGS", $value);
    }
    
    public static function GetLanguageFile()
    {
      return self::GetValue("LANGUAGE_FILE");
    }
    public static function SetLanguageFile($value)
    {
      self::SetValue("LANGUAGE_FILE", $value);
    }    

    public static function GetLanguageCode()
    {
      return self::GetValue("LANGUAGE_CODE");
    }
    public static function SetLanguageCode($value)
    {
      self::SetValue("LANGUAGE_CODE", $value);
    }    
    
    public static function GetIsLoggedInAdmin()
    {
      return self::GetValue("IS_LOGGED_IN_ADMIN");
    }
    public static function SetIsLoggedInAdmin($value)
    {
      self::SetValue("IS_LOGGED_IN_ADMIN", $value);
    }
    
    public static function GetLoggedInUser()
    {
      return self::GetValue("LOGGED_IN_USER");
    }
    public static function SetLoggedInUser($value)
    {
      self::SetValue("LOGGED_IN_USER", $value);
    }
    
    public static function GetDisplayedUser()
    {
      return self::GetValue("DISPLAYED_USER");
    }
    public static function SetDisplayedUser($value)
    {
      self::SetValue("DISPLAYED_USER", $value);
    }

    public static function GetPublicCreationCodeEntered()
    {
      return self::GetValue("PUBLIC_CREATION_CODE_ENTERED");
    }
    public static function SetPublicCreationCodeEntered($value)
    {
      self::SetValue("PUBLIC_CREATION_CODE_ENTERED", $value);
    }

    public static function GetSearchCriteria($userID)
    {
      return self::GetValue("SEARCH_CRITERIA_$userID");
    }
    public static function SetSearchCriteria($userID, $value)
    {
      self::SetValue("SEARCH_CRITERIA_$userID", $value);
    }

    public static function GetDatabaseVersion()
    {
      return self::GetValue("DATABASE_VERSION");
    }
    public static function SetDatabaseVersion($value)
    {
      self::SetValue("DATABASE_VERSION", $value);
    }

    private static function GetValue($key)
    {
      return isset($_SESSION["DOMA_". DB_MAP_TABLE ."_$key"]) ? $_SESSION["DOMA_". DB_MAP_TABLE ."_$key"] : null;
    }
    private static function SetValue($key, $value)
    {
      if($value == null)
      {
        unset($_SESSION["DOMA_". DB_MAP_TABLE ."_$key"]);
      }
      else
      {
        $_SESSION["DOMA_". DB_MAP_TABLE ."_$key"] = $value;
      }
    }
  }
?>
