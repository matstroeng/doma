<?php
  include_once(dirname(__FILE__) ."/include/main.php");
  
  class EditUserController
  {
    public function Execute()
    {
      $viewData = array();  

      $isAdmin = (isset($_GET["mode"]) && $_GET["mode"] == "admin" && Helper::IsLoggedInAdmin());

      // no user specified and not admin mode - redirect to user list page
      if(!$isAdmin && !getCurrentUser() && !Session::GetPublicCreationCodeEntered()) Helper::Redirect("users.php");
      
      $errors = array();
      $user = getCurrentUser();
      $isNewUser = !isset($user) || !$user->ID;
      if($isNewUser) $user = new User();
      
      if(isset($_POST["cancel"]))
      {
        Helper::Redirect($isAdmin ? "users.php" : "index.php?". Helper::CreateQuerystring($user));
      }

      if($isAdmin && isset($_POST["deleteConfirmed"]))
      {
        DataAccess::DeleteUserByID($user->ID);
        Helper::Redirect($isAdmin ? "users.php" : "index.php?". Helper::CreateQuerystring($user));
      }
      
      // any category handling button clicked?
      $addCategory = null;
      $deleteCategory = null;
      foreach($_POST as $key=>$value)
      {
        if(substr($key, 0, 15) == "deleteCategory_")
        {
          $deleteCategory = substr($key, 15);
          break;
        }
        if($key == "addCategory")
        {
          $addCategory = true;
          break;
        }
      }
      
      if(isset($_POST["save"]) || isset($_POST["delete"]) || $deleteCategory || $addCategory)
      {
        // populate user object with data from form elements
        $user->Username = stripslashes($_POST["username"]);
        $password = stripslashes($_POST["password"]);
        if($password) $user->Password = md5($password);
        $user->FirstName = stripslashes($_POST["firstName"]);
        $user->LastName = stripslashes($_POST["lastName"]);
        $user->Email = stripslashes($_POST["email"]);
        if($isAdmin) $user->Visible = ($_POST["visible"] ? 1 : 0);
        if(!$isAdmin && $isNewUser) $user->Visible = 1;

        $defaultCategory = $_POST["defaultCategory"];
        $noOfCategoriesAdded = $_POST["noOfCategoriesAdded"];

        // the category array
        foreach($_POST as $key=>$value)
        {
          if(substr($key, 0, 17) == "categoryName_new_")
          {
            $id = "1_". sprintf("%08d", substr($key, 17));
            $categories[$id] = new Category();
            $categories[$id]->Name = stripslashes($value);
            $categories[$id]->UserID = $user->ID;
          }
          elseif(substr($key, 0, 13) == "categoryName_")
          {
            $id = "0_". sprintf("%08d", substr($key, 13));
            $categories[$id] = new Category();
            $categories[$id]->Name = stripslashes($value);
            $categories[$id]->UserID = $user->ID;
            $categories[$id]->ID = substr($key, 13);
          }
        }    
        ksort($categories);
        
        // shall we delete a category?
        if($deleteCategory)
        {
          if(count($categories) <= 1)
          {
            $errors[] = __("CAN_NOT_DELETE_ALL_CATEGORIES");
          }
          else
          {
            if(substr($deleteCategory, 0, 4) == "new_")
            {
              // unsaved categories can be deleted directly
              $id = "1_". sprintf("%08d", substr($deleteCategory, 4));
              unset($categories[$id]);
            }
            else
            {
              // for saved categories, we need to check for existing maps
              $id = "0_". sprintf("%08d", $deleteCategory);
              $noOfMapsInCategory = DataAccess::NoOfMapsInCategory($deleteCategory);
              if($noOfMapsInCategory > 0)
              {
                $errors[] = sprintf(__("CAN_NOT_DELETE_NONEMPTY_CATEGORY"), $categories[$id]->Name, $noOfMapsInCategory);
              }
              else
              {
                unset($categories[$id]);
              }
            } 
          }
        }
        
        if($addCategory)
        {
          $id = "1_". sprintf("%08d", $noOfCategoriesAdded);
          $categories[$id] = new Category();
          $categories[$id]->UserID = $user->ID;
          $noOfCategoriesAdded++;
        }
      }
      else
      {
        // first page visit
        if($isNewUser) 
        {
          $noOfCategoriesAdded = 0;
          if($isAdmin) $_POST["sendEmail"] = 1;
          $defaultCategoryNames = @explode(";", __("DEFAULT_CATEGORY_NAMES"));
          sort($defaultCategoryNames);
          $categories = array();
          foreach($defaultCategoryNames as $dcn)
          {
            $c = new Category();
            $c->Name = $dcn;
            $categories["1_". sprintf("%08d", $noOfCategoriesAdded)] = $c;
            $noOfCategoriesAdded++;
          }
          $defaultCategory = "new_0";
        }
        else
        {
          $categories = $user->GetCategories();
          $defaultCategory = $user->DefaultCategoryID;  
        }

        $customizableSettings = Helper::GetCustomizableStrings();
        foreach($customizableSettings["settings"] as $key => $value)
        {
          $_POST["CV_$key"] = __($key);
        }
      }
      
      // create category data for output and make sure that there is a default category
      $categoryData = array();
      $defaultCategoryIndex = -1;
      foreach($categories as $key=>$c)
      {
        $d = array();
        $d["category"] = $c;
        if($c->ID)
        {
          $d["nameId"] = "categoryName_". $c->ID;
          $d["defaultValue"] = $c->ID;
          $d["deleteId"] = "deleteCategory_". $c->ID;
        }
        else
        {
          $id = (int)substr($key,2);
          $d["nameId"] = "categoryName_new_$id";
          $d["defaultValue"] = "new_$id";
          $d["deleteId"] = "deleteCategory_new_$id";
        }
        $d["defaultId"] = "categoryDefault_". $d["defaultValue"];
        if($defaultCategory == $d["defaultValue"]) $defaultCategoryIndex = count($categoryData);
        $categoryData[] = $d;
      }
      $defaultCategory = $defaultCategoryIndex == -1 ? 0 : $categoryData[$defaultCategoryIndex]["defaultValue"];
      
      if(isset($_POST["save"]))
      {
        // validate
        if(DataAccess::UsernameExists($user->Username, $user->ID))
        {
          $errors[] = __("USERNAME_EXISTS");
        }
        if(trim($user->Username) == "")
        {
          $errors[] = __("NO_USERNAME_ENTERED");
        }
        if(!$user->ID && trim($password) == "")
        {
          $errors[] = __("NO_PASSWORD_ENTERED");
        }
        if(trim($user->FirstName) == "")
        {
          $errors[] = __("NO_FIRST_NAME_ENTERED");
        }
        if(trim($user->LastName) == "")
        {
          $errors[] = __("NO_LAST_NAME_ENTERED");
        }
        if($user->Email == "") 
        {
          $errors[] = __("NO_EMAIL_ENTERED");
        }
        if($user->Email != "" && !Helper::IsValidEmailAddress($user->Email)) 
        {
          $errors[] = __("INVALID_EMAIL");
        }
        foreach($categories as $c)
        {
          if(trim($c->Name) == "") $emptyCategoryNameFound = true;
        }
        if(isset($emptyCategoryNameFound)) $errors[] = __("CATEGORY_NAME_CANNOT_BE_EMPTY");
        
        if(count($errors) == 0)
        {
          $userSettings = array();
          foreach($_POST as $key => $value)
          {
            if(substr($key, 0, 3) == "CV_")
            {
              $key = substr($key, 3);
              $userSettings[$key] = stripslashes($value);
            }  
          }
          DataAccess::SaveUser($user, $categories, $defaultCategoryIndex, $userSettings);
          
          // send welcome email
          if($isNewUser && !($isAdmin && !$_POST["sendEmail"]))
          {
            $fromName = __("DOMA_ADMIN_EMAIL_NAME");
            $subject = __("NEW_USER_EMAIL_SUBJECT");
            $baseAddress = Helper::GlobalPath("");
            $userAddress = Helper::GlobalPath("index.php?user=". $user->Username);
            $body = sprintf(__("NEW_USER_EMAIL_BODY"), $user->FirstName, $baseAddress, $userAddress, $user->Username, $password);  
            $emailSent = true;
            $emailSentSuccessfully = Helper::SendEmail($fromName, $user->Email, $subject, $body);
          }
          
          // clear language cache
          Session::SetLanguageStrings(null);
          
          if($isAdmin)
          {
            Helper::Redirect("users.php". ($emailSent && !$emailSentSuccessfully ? "?error=email" : ""));
          }
          else
          {
            Helper::Redirect("index.php?". Helper::CreateQuerystring($user));
          }
        }
      }
      
      if($isAdmin) 
      {
        $viewData["Title"] = ($user->ID ? sprintf(__("EDIT_USER_X"), $user->FirstName ." ". $user->LastName) : __("ADD_USER"));
        $viewData["Info"] = ($user->ID ? __("ADMIN_EDIT_USER_INFO") : __("ADMIN_ADD_USER_INFO")) ." ". sprintf(__("REQUIRED_FIELDS_INFO"), '<span class="required">*</span>');
      }
      else
      {
        $viewData["Title"] = ($isNewUser ? __("ADD_USER_PROFILE_TITLE") : __("EDIT_USER_PROFILE_TITLE"));
        $viewData["Info"] = sprintf(__("REQUIRED_FIELDS_INFO"), '<span class="required">*</span>');
      }
      $atoms = array();
      if($isAdmin) $atoms[] = "mode=admin";
      if($user->ID) $atoms[] = Helper::CreateQuerystring($user);
      
      $viewData["FormActionURL"] = $_SERVER["PHP_SELF"] . (count($atoms) > 0 ? "?". join("&amp;", $atoms) : "");
      
      $viewData["Errors"] = $errors;
      $viewData["IsAdmin"] = $isAdmin;
      $viewData["IsNewUser"] = $isNewUser;
      $viewData["User"] = $user;
      $viewData["SendEmail"] = isset($_POST["sendEmail"]);
      $viewData["CategoryData"] = $categoryData;
      $viewData["DefaultCategory"] = $defaultCategory;
      $viewData["DeleteButtonClicked"] = isset($_POST["delete"]);
      $viewData["NoOfCategoriesAdded"] = isset($noOfCategoriesAdded) ? $noOfCategoriesAdded : 0;
      $viewData["CustomizableSettings"] = Helper::GetCustomizableStrings();

      return $viewData;
    }
  }
?>
