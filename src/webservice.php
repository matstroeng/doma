<?php
  include_once(dirname(__FILE__) ."/config.php");
  include_once(dirname(__FILE__) ."/include/definitions.php");
  include_once(dirname(__FILE__) ."/include/nusoap.php");

  define("NAMESPACE1", 'http://www.matstroeng.se/doma');

  $server = createServer();

  // Use the request to (try to) invoke the service
  $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
  $server->service($HTTP_RAW_POST_DATA);

  function createServer()
  {
    // *************************************************************************************
    // create the server instance
    // *************************************************************************************
    $server = new soap_server();
    $server->xml_encoding = "UTF-8";
    $server->decode_utf8 = false;
    $server->configureWSDL('DOMAService', NAMESPACE1);

    // *************************************************************************************
    // define complex types
    // *************************************************************************************
    $server->wsdl->addComplexType(
        'PublishMapRequest',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Username' => array('name' => 'Username', 'type' => 'xsd:string'),
            'Password' => array('name' => 'Password', 'type' => 'xsd:string'),
            'MapInfo' => array('name' => 'Map', 'type' => 'tns:MapInfo')
        )
    );

    $server->wsdl->addComplexType(
        'PublishMapResponse',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Success' => array('name' => 'Success', 'type' => 'xsd:boolean'),
            'ErrorMessage' => array('name' => 'ErrorMessage', 'type' => 'xsd:string'),
            'URL' => array('name' => 'URL', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'PublishPreUploadedMapRequest',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Username' => array('name' => 'Username', 'type' => 'xsd:string'),
            'Password' => array('name' => 'Password', 'type' => 'xsd:string'),
            'MapInfo' => array('name' => 'Map', 'type' => 'tns:MapInfo'),
            'PreUploadedMapImageFileName' => array('name' => 'PreUploadedMapImageFileName', 'type' => 'xsd:string'),
            'PreUploadedBlankMapImageFileName' => array('name' => 'PreUploadedBlankMapImageFileName', 'type' => 'xsd:string'),
            'PreUploadedThumbnailImageFileName' => array('name' => 'PreUploadedThumbnailImageFileName', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'PublishPreUploadedMapResponse',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Success' => array('name' => 'Success', 'type' => 'xsd:boolean'),
            'ErrorMessage' => array('name' => 'ErrorMessage', 'type' => 'xsd:string'),
            'URL' => array('name' => 'URL', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'UploadPartialFileRequest',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Username' => array('name' => 'Username', 'type' => 'xsd:string'),
            'Password' => array('name' => 'Password', 'type' => 'xsd:string'),
            'FileName' => array('name' => 'FileName', 'type' => 'xsd:string'),
            'Data' => array('name' => 'Data', 'type' => 'xsd:base64Binary')
        )
    );

    $server->wsdl->addComplexType(
        'UploadPartialFileResponse',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Success' => array('name' => 'Success', 'type' => 'xsd:boolean'),
            'ErrorMessage' => array('name' => 'ErrorMessage', 'type' => 'xsd:string'),
            'FileName' => array('name' => 'FileName', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'GetAllMapsRequest',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Username' => array('name' => 'Username', 'type' => 'xsd:string'),
            'Password' => array('name' => 'Password', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'GetAllMapsResponse',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Success' => array('name' => 'Success', 'type' => 'xsd:boolean'),
            'ErrorMessage' => array('name' => 'ErrorMessage', 'type' => 'xsd:string'),
            'Maps' => array('name' => 'Maps', 'type' => 'tns:MapInfoArray')
        )
    );

    $server->wsdl->addComplexType(
        'GetAllCategoriesRequest',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Username' => array('name' => 'Username', 'type' => 'xsd:string'),
            'Password' => array('name' => 'Password', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'GetAllCategoriesResponse',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Success' => array('name' => 'Success', 'type' => 'xsd:boolean'),
            'ErrorMessage' => array('name' => 'ErrorMessage', 'type' => 'xsd:string'),
            'Categories' => array('name' => 'Categories', 'type' => 'tns:CategoryArray')
        )
    );
    
    $server->wsdl->addComplexType(
        'ConnectRequest',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Username' => array('name' => 'Username', 'type' => 'xsd:string'),
            'Password' => array('name' => 'Password', 'type' => 'xsd:string')
        )
    );

    $server->wsdl->addComplexType(
        'ConnectResponse',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'Success' => array('name' => 'Success', 'type' => 'xsd:boolean'),
            'ErrorMessage' => array('name' => 'ErrorMessage', 'type' => 'xsd:string'),
            'Version' => array('name' => 'Version', 'type' => 'xsd:string')
        )
    );
    
    $server->wsdl->addComplexType(
        'MapInfo',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'ID' => array('name' => 'ID', 'type' => 'xsd:int'),
            'UserID' => array('name' => 'UserID', 'type' => 'xsd:int'),
            'CategoryID' => array('name' => 'CategoryID', 'type' => 'xsd:int'),
            'Date' => array('name' => 'Date', 'type' => 'xsd:dateTime'),
            'Name' => array('name' => 'Name', 'type' => 'xsd:string'),
            'Organiser' => array('name' => 'Organiser', 'type' => 'xsd:string'),
            'Country' => array('name' => 'Country', 'type' => 'xsd:string'),
            'Discipline' => array('name' => 'Discipline', 'type' => 'xsd:string'),
            'RelayLeg' => array('name' => 'RelayLeg', 'type' => 'xsd:string'),
            'MapName' => array('name' => 'MapName', 'type' => 'xsd:string'),
            'ResultListUrl' => array('name' => 'ResultListUrl', 'type' => 'xsd:string'),
            'Comment' => array('name' => 'Comment', 'type' => 'xsd:string'),
            'MapImageData' => array('name' => 'MapImageData', 'type' => 'xsd:base64Binary'),
            'MapImageFileExtension' => array('name' => 'MapImageFileExtension', 'type' => 'xsd:string'),
            'BlankMapImageData' => array('name' => 'BlankMapImageData', 'type' => 'xsd:base64Binary')
        )
    );


    $server->wsdl->addComplexType(
        'Category',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'ID' => array('name' => 'ID', 'type' => 'xsd:int'),
            'UserID' => array('name' => 'UserID', 'type' => 'xsd:int'),
            'Name' => array('name' => 'Name', 'type' => 'xsd:string')
        )
    );
    
    $server->wsdl->addComplexType(
        'MapInfoArray',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
            array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:MapInfo[]')
        ),
        'tns:MapInfo'
    );
    
    $server->wsdl->addComplexType(
        'CategoryArray',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
            array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Category[]')
        ),
        'tns:Category'
    );    


    // *************************************************************************************
    // register service methods
    // *************************************************************************************
    $server->register(
        'PublishMap',
        array('request' => 'tns:PublishMapRequest'),        // input parameters
        array('response' => 'tns:PublishMapResponse'),      // output parameters
        NAMESPACE1
    );

    $server->register(
        'PublishPreUploadedMap',
        array('request' => 'tns:PublishPreUploadedMapRequest'),        // input parameters
        array('response' => 'tns:PublishPreUploadedMapResponse'),      // output parameters
        NAMESPACE1
    );

    $server->register(
        'UploadPartialFile',
        array('request' => 'tns:UploadPartialFileRequest'),        // input parameters
        array('response' => 'tns:UploadPartialFileResponse'),      // output parameters
        NAMESPACE1
    );

    $server->register(
        'GetAllMaps',
        array('request' => 'tns:GetAllMapsRequest'),        // input parameters
        array('response' => 'tns:GetAllMapsResponse'),      // output parameters
        NAMESPACE1
    );

    $server->register(
        'GetAllCategories',
        array('request' => 'tns:GetAllCategoriesRequest'),        // input parameters
        array('response' => 'tns:GetAllCategoriesResponse'),      // output parameters
        NAMESPACE1
    );

    $server->register(
        'Connect',
        array('request' => 'tns:ConnectRequest'),        // input parameters
        array('response' => 'tns:ConnectResponse'),      // output parameters
        NAMESPACE1
    );

    return $server;
  }

  // *************************************************************************************
  // service method implementations
  // *************************************************************************************
  function PublishMap($request)
  {
    $mapImageFileNameData = Helper::SaveTemporaryFileFromFileData($request["MapInfo"]["MapImageData"], $request["MapInfo"]["MapImageFileExtension"]);
    $mapImageFileName = $mapImageFileNameData["fileName"];
    return PublishMapHelper($request["Username"], $request["Password"], $request["MapInfo"], $mapImageFileName, null);
  }

  function PublishPreUploadedMap($request)
  {
    if($request["PreUploadedMapImageFileName"]) $mapImageFileName = Helper::LocalPath(TEMP_FILE_PATH ."/". $request["PreUploadedMapImageFileName"]);
    if($request["PreUploadedBlankMapImageFileName"]) $blankMapImageFileName = Helper::LocalPath(TEMP_FILE_PATH ."/". $request["PreUploadedBlankMapImageFileName"]);
    if($request["PreUploadedThumbnailImageFileName"]) $thumbnailMapImageFileName = Helper::LocalPath(TEMP_FILE_PATH ."/". $request["PreUploadedThumbnailImageFileName"]);
    return PublishMapHelper($request["Username"], $request["Password"], $request["MapInfo"], $mapImageFileName, $blankMapImageFileName, $thumbnailMapImageFileName);
  }

  function UploadPartialFile($request)
  {
    $success = false;
    $errorMessage = null;
    // validate username and password
    $user = DataAccess::GetUserByUsernameAndPassword($request["Username"], $request["Password"]);
    if(!$user)
    {
      $errorMessage = "Invalid username and/or password.";
    }
    else
    {
      $fileName = $request["FileName"];
      if(!$fileName) $fileName = rand(0, 1000000000) .".tmp";
      $fp = fopen(Helper::LocalPath(TEMP_FILE_PATH ."/". $fileName), "a");
      fwrite($fp, $request["Data"]);
      fclose($fp);  
      $success = true;
    }

    return array(
      'Success' => $success,
      'ErrorMessage' => $errorMessage,
      'FileName' => $fileName
    );
  }
  
  function GetAllMaps($request)
  {
    $success = false;
    // validate username and password
    $user = DataAccess::GetUserByUsernameAndPassword($request["Username"], $request["Password"]);
    if(!$user)
    {
      $errorMessage = "Invalid username and/or password.";
    }
    else
    {
      $maps = array();

      $dbMaps = DataAccess::GetAllMaps($user->ID);

      foreach($dbMaps as $m)
      {
        $maps[] = array(
          "ID" => $m->ID,
          "UserID" => $m->UserID,
          "CategoryID" => $m->CategoryID,
          "Date" => date("c", Helper::StringToTime($m->Date, true)),
          "Name" => $m->Name,
          "Organiser" => $m->Organiser,
          "Country" => $m->Country,
          "Discipline" => $m->Discipline,
          "RelayLeg" => $m->RelayLeg,
          "MapName" => $m->MapName,
          "ResultListUrl" => $m->ResultListUrl,
          "Comment" => $m->Comment
        );
      }
      $errorMessage = mysql_error();
      $success = ($errorMessage == "");
    }
    return array(
      'Success' => $success,
      'ErrorMessage' => $errorMessage,
      'Maps' => $maps
    );
  }

  function GetAllCategories($request)
  {
    $success = false;
    // validate username and password
    $user = DataAccess::GetUserByUsernameAndPassword($request["Username"], $request["Password"]);
    if(!$user)
    {
      $errorMessage = "Invalid username and/or password.";
    }
    else
    {
      $categories = array();

      $dbCategories = DataAccess::GetCategoriesByUserID($user->ID);

      foreach($dbCategories as $c)
      {
        $categories[] = array(
          "ID" => $c->ID,
          "UserID" => $c->UserID,
          "Name" => $c->Name
        );
      }
      $errorMessage = mysql_error();
      $success = ($errorMessage == "");
    }
    return array(
      'Success' => $success,
      'ErrorMessage' => $errorMessage,
      'Categories' => $categories
    );
  }

  function Connect($request)
  {
    $user = DataAccess::GetUserByUsernameAndPassword($request["Username"], $request["Password"]);
    $success = ($user ? true : false);
    $errorMessage = "";
    if(!$success)
    {
      $errorMessage = "Invalid username and/or password.";
    }
    return array("Success" => $success, "ErrorMessage" => $errorMessage, "Version" => DOMA_VERSION);
  }
  
  /* helper functions */
  function PublishMapHelper($username, $password, $mapInfo, $mapImageFileName, $blankMapImageFileName, $thumbnailImageFileName)
  {
    $success = false;
    // validate username and password
    $user = DataAccess::GetUserByUsernameAndPassword($username, $password);
    if(!$user)
    {
      $errorMessage = "Invalid username and/or password.";
    }
    else
    {
      $map = new Map();
      $map->ID = $mapInfo["ID"];
      $map->UserID = $user->ID;
      $map->CategoryID = $mapInfo["CategoryID"];
      $map->Date = gmdate("Y-m-d H:i:s", Helper::StringToTime($mapInfo["Date"], true));
      $map->Name = $mapInfo["Name"];
      $map->Organiser = $mapInfo["Organiser"];
      $map->Country = $mapInfo["Country"];
      $map->Discipline = $mapInfo["Discipline"];
      $map->RelayLeg = $mapInfo["RelayLeg"];
      $map->MapName = $mapInfo["MapName"];
      $map->ResultListUrl = $mapInfo["ResultListUrl"];
      $map->Comment = $mapInfo["Comment"];
      $map->LastChangedTime = gmdate("Y-m-d H:i:s");
      if(!$mapInfo["ID"]) $map->CreatedTime = gmdate("Y-m-d H:i:s");
      
      $thumbnailCreatedSuccessfully = false;
      $error = null;
      DataAccess::SaveMapAndThumbnailImage($map, $mapImageFileName, $blankMapImageFileName, $thumbnailImageFileName, $error, $thumbnailCreatedSuccessfully);
      if($mapImageFileName) unlink($mapImageFileName);
      if($blankMapImageFileName) unlink($blankMapImageFileName);
      if($thumbnailImageFileName) unlink($thumbnailImageFileName);
      if(!$mapInfo["ID"]) Helper::LogUsage("addMapWS", "user=". urlencode($user->Username) ."&map=". $map->ID);
      $errorMessage = mysql_error();
      $success = ($errorMessage == "");
      $url = Helper::GlobalPath("show_map.php?user=". urlencode($user->Username) ."&map=". $map->ID);
    }

    return array(
      'Success' => $success,
      'ErrorMessage' => $errorMessage,
      'URL' => $url
    );
  }  
  
  

?>