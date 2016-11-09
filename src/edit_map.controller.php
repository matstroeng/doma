<?php
  include_once(dirname(__FILE__) ."/include/main.php");

  class EditMapController
  {
    public function Execute()
    {

      $viewData = array();

      $errors = array();

      // no user specified - redirect to user list page
      if(!getCurrentUser()) Helper::Redirect("users.php");

      if(!Helper::IsLoggedInUser()) Helper::Redirect("users.php");

      if(isset($_GET["map"])) $mapID = $_GET["map"];

      foreach($_GET as $variable => $value) $$variable = stripslashes($value);
      foreach($_POST as $variable => $value) $$variable = stripslashes($value);

      if(isset($cancel))
      {
        Helper::Redirect("index.php?". Helper::CreateQuerystring(getCurrentUser()));
      }

      if(isset($save) || isset($delete) || isset($deleteConfirmed))
      {
        $map = new Map();
        if(isset($mapID))
        {
          $map->Load($mapID);
          if($map->UserID != getCurrentUser()->ID) die("Access denied");
          $isNewMap = false;
        }
        else
        {
          $isNewMap = true;
        }
        $map->UserID = getCurrentUser()->ID;
        $map->CategoryID = $categoryID;
        $map->Date = $date;
        $map->Name = $name;
        if(__("SHOW_ORGANISER")) $map->Organiser = $organiser;
        if(__("SHOW_COUNTRY")) $map->Country = $country;
        if(__("SHOW_DISCIPLINE")) $map->Discipline = $discipline;
        if(__("SHOW_RELAY_LEG")) $map->RelayLeg = $relayLeg;
        if(__("SHOW_MAP_AREA_NAME")) $map->MapName = $mapName;
        if(__("SHOW_RESULT_LIST_URL")) $map->ResultListUrl = $resultListUrl;
        if(__("SHOW_COMMENT")) $map->Comment = $comment;
        $map->ProtectedUntil = $protectedUntil;
      }
      else
      {
        // first page load
        if(isset($_GET["map"]))
        {
          $map = new Map();
          $map->Load($mapID);
          if($map->UserID != getCurrentUser()->ID) die("Access denied");
          $isNewMap = false;
        }
        else
        {
          $map = new Map();
          $map->Date = date("Y-m-d");
          $map->CategoryID = getCurrentUser()->DefaultCategoryID;
          $isNewMap = true;
        }
      }

      if(isset($save))
      {
        // validate
        // name
        if(trim($map->Name) == "") $errors[] = __("NO_MAP_NAME_ENTERED");
        // date
        if(trim($map->Date) == "") $errors[] = __("NO_DATE_ENTERED");
        if(!Helper::LocalizedStringToTime($map->Date, false))
        {
          $errors[] = __("INVALID_DATE");
        }
        else
        {
          $map->Date = gmdate("Y-m-d H:i:s", Helper::LocalizedStringToTime($map->Date, false));
        }

        // protected until
        if(trim($map->ProtectedUntil) == "")
        {
          $map->ProtectedUntil = null;
        }
        else if(!Helper::LocalizedStringToTime($map->ProtectedUntil, false))
        {
          $errors[] = __("INVALID_PROTECTED_UNTIL");
        }
        else
        {
          $map->ProtectedUntil = gmdate("Y-m-d H:i:s", Helper::LocalizedStringToTime($map->ProtectedUntil, false));
        }

        // images
        $validMimeTypes = array("image/jpeg", "image/gif", "image/png");
        // map image
        $mapImageUploaded = ($_FILES["mapImage"]["tmp_name"] != "");
        if($mapImageUploaded) $mapImageInfo = getimagesize($_FILES["mapImage"]["tmp_name"]);
        if($mapImageUploaded && !in_array($mapImageInfo["mime"], $validMimeTypes)) $errors[] = sprintf(__("INVALID_MAP_IMAGE_FORMAT"), $_FILES["mapImage"]["name"]);
        // map image
        $blankMapImageUploaded = ($_FILES["blankMapImage"]["tmp_name"] != "");
        if($blankMapImageUploaded) $blankMapImageInfo = getimagesize($_FILES["blankMapImage"]["tmp_name"]);
        if($blankMapImageUploaded && !in_array($blankMapImageInfo["mime"], $validMimeTypes)) $errors[] = sprintf(__("INVALID_BLANK_MAP_IMAGE_FORMAT"), $_FILES["mapImage"]["name"]);
        if($isNewMap && !$mapImageUploaded && !$blankMapImageUploaded) $errors[] = __("NO_MAP_FILE_ENTERED");
        // thumbnail image
        $thumbnailImageUploaded = ($_FILES["thumbnailImage"]["tmp_name"] != "");
        if($thumbnailImageUploaded) $thumbnailImageInfo = getimagesize($_FILES["thumbnailImage"]["tmp_name"]);
        if($thumbnailImageUploaded && !in_array($thumbnailImageInfo["mime"], $validMimeTypes)) $errors[] = sprintf(__("INVALID_THUMBNAIL_IMAGE_FORMAT"), $_FILES["thumbnailImage"]["name"]);

        if(count($errors) == 0)
        {
          $thumbnailCreatedSuccessfully = false;
          $mapImageData = Helper::SaveTemporaryFileFromUploadedFile($_FILES["mapImage"]);
          if($mapImageData["error"] == "couldNotCopyUploadedFile") $errors[] = sprintf(__("MAP_IMAGE_COULD_NOT_BE_UPLOADED"), $_FILES["mapImage"]["name"]);
          $blankMapImageData = Helper::SaveTemporaryFileFromUploadedFile($_FILES["blankMapImage"]);
          if($blankMapImageData["error"] == "couldNotCopyUploadedFile") $errors[] = sprintf(__("BLANK_MAP_IMAGE_COULD_NOT_BE_UPLOADED"), $_FILES["blankMapImage"]["name"]);
          $thumbnailImageData = Helper::SaveTemporaryFileFromUploadedFile($_FILES["thumbnailImage"]);
          if($thumbnailImageData["error"] ==  "couldNotCopyUploadedFile") $errors[] = sprintf(__("THUMBNAIL_IMAGE_COULD_NOT_BE_UPLOADED"), $_FILES["thumbnailImage"]["name"]);

          $error = null;
          if(count($errors) == 0) DataAccess::SaveMapAndThumbnailImage($map, $mapImageData["fileName"], $blankMapImageData["fileName"], $thumbnailImageData["fileName"], $error, $thumbnailCreatedSuccessfully);

          if($error) $errors[] = $error;

          if($mapImageData["fileName"] && file_exists($mapImageData["fileName"])) unlink($mapImageData["fileName"]);
          if($blankMapImageData["fileName"] && file_exists($blankMapImageData["fileName"])) unlink($blankMapImageData["fileName"]);
          if($thumbnailImageData["fileName"] && file_exists($thumbnailImageData["fileName"])) unlink($thumbnailImageData["fileName"]);
          if(count($errors) == 0) Helper::Redirect("index.php?". Helper::CreateQuerystring(getCurrentUser()) . (!$thumbnailCreatedSuccessfully ? "&error=thumbnailCreationFailure" : ""));
        }
      }
      elseif(isset($deleteConfirmed))
      {
        DataAccess::DeleteMap($map);
        Helper::Redirect("index.php?". Helper::CreateQuerystring(getCurrentUser()));
      }

      $viewData["Errors"] = $errors;
      $viewData["Categories"] = getCurrentUser()->GetCategories();
      $viewData["Map"] = $map;
      if(isset($mapID)) $viewData["MapID"] = $mapID;
      $viewData["ConfirmDeletionButtonVisible"] = isset($delete);
      $viewData["Title"] = (isset($mapID) ? sprintf(__("EDIT_MAP_X"), $map->Name) : __("ADD_MAP"));

      return $viewData;
    }
  }
?>
