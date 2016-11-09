<?php
  // This file contains the settings for the digital map archive.
  // Each setting contains a name and a value and is presented in the following way:
  //   define('SETTING_NAME', 'setting value');
  // The settning names must not be changed, but the setting values are up to you to edit. All text setting values
  // must be enclosed in single quotes ('). If a setting value contais a single quote, use the \' character combination.
  // Numeric settings and boolean settings (true or false) should not be enclosed in single quotes.

  // *********************************************************************************************************
  //   SYSTEM SETTINGS
  // *********************************************************************************************************

  // User names and passwords.
  // Database login information, ask your database server administrator/web hotel provider if you don't know.
  define('DB_HOST', 'localhost');
  define('DB_USERNAME', 'yourDatabaseUsername');
  define('DB_PASSWORD', 'yourDatabasePassword');
  // The name of the database where the map information is stored. The database must exist prior to creation of the site.
  define('DB_DATABASE_NAME', 'yourDatabaseName');
  // The names of the database tables where user and map information is stored. Do not change unless you have a reason.
  // The database tables must _not_ exist before creation of the site.
  define('DB_MAP_TABLE', 'doma_maps');
  define('DB_SETTING_TABLE', 'doma_settings');
  define('DB_USER_TABLE', 'doma_users');
  define('DB_USER_SETTING_TABLE', 'doma_userSettings');
  define('DB_CATEGORY_TABLE', 'doma_categories');

  // The user name and password for administration (e g adding and editing users).
  define('ADMIN_USERNAME', 'yourAdminUsername');
  define('ADMIN_PASSWORD', 'yourAdminPassword');

  // Path to the map image directory, relative to this file. Don't change unless you have a good reason.
  // The directory is created during creation. Write access to the directory for the server user account under which PHP runs is required.
  define('MAP_IMAGE_PATH', 'map_images');

  // The default language code of the site according to ISO 639-1 language codes: http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes 
  // Language files are in xml format, named [language code].xml (e.g. en.xml) and located in the 'languages' directory.
  // Language flag icons are in png format, named [language code].png (e.g. en.png) and located in the 'gfx/flags' directory.
  // You may create your own language file by copying and modifying one of the existing files.
  // Current languages include
  //   cs  (Czech, credits to Michal Besta)
  //   da  (Danish, credits to Michael Leth Jess)
  //   de  (German/Austria, credits to Markus Plohn)
  //   et  (Estonian, credits to Margus Lehtme)
  //   en  (English, credits to Boris Granovskiy)
  //   es  (Spanish, credits to Iñaki Larena)
  //   fr  (French, credits to François Coulier)
  //   it  (Italian, credits to Davide Miori)
  //   hu  (Hungarian, credits to Csaba Gösswein)
  //   nb  (Norwegian Bokmål, credits to Bjørge Solli)
  //   pl  (Polish, credits to Sławomir Cygler)
  //   pt  (Portugese, credits to Rui Tavares)
  //   ru  (Russian, credits to Yuri Mikhaylovskiy)
  //   sv  (Swedish, credits to Mats Troeng)
  //   tr  (Turkish, credits to Guven Solmaz and Berkay Kılıcoglu)
  define('LANGUAGE_CODE', 'en');

  // The MySQL text sorting order, known as 'collation'.
  // Use utf8_general_ci for English, utf8_swedish_ci for Swedish, and utf8_danish_ci for Norwegian BokmÃ¥l.
  // Other collations can be found at the MySQL website, http://dev.mysql.com
  // NOTE: this setting only has effect when creating the site. Changing this setting after the site has been created will not have any effect.
  define('DB_COLLATION', 'utf8_general_ci');

  // The email address for the administrator of the page.
  // Used as reply address when sending confirmation emails to new users.
  // Please make sure that your server is properly configured for sending emails, more info can be found at http://www.php.net/mail.
  // The email address must be changed from email@yourdomain.com to a valid address, or the sending won't work.
  define('ADMIN_EMAIL', 'email@yourdomain.com');

  // Specifies the code that a person has to enter when creating a new user accounts by himself without any administrator involved.
  // Leave the code empty ('') to prevent people to create user accounts theirselves.
  define('PUBLIC_USER_CREATION_CODE', '');

  // *********************************************************************************************************
  //   APPEARANCE SETTINGS
  // *********************************************************************************************************

  // The name of the site as displayed in the browser's window title when browsing the user list page.
  define('_SITE_TITLE', 'The Digital Orienteering Map Archive');

  // The name of the site as displayed in the browser's window title when browsing the user list page.
  define('_SITE_DESCRIPTION', 'Welcome to the digital orienteering map archive!');

  // Size and scaling of thumbnail images. Don't change unless you have a good reason.
  define('THUMBNAIL_WIDTH', 400);
  define('THUMBNAIL_HEIGHT', 100);
  define('THUMBNAIL_SCALE', 0.5);

  // *********************************************************************************************************
  //   SETTINGS ADDED IN DOMA 3.0
  // *********************************************************************************************************
  // The name of the database tables where visitor comment information is stored. Do not change unless you have a reason.
  // The database table must _not_ exist before creation of the site.
  define('DB_COMMENT_TABLE', 'doma_comments');

  // Path to temporary file storage directory, relative to this file. Don't change unless you have a good reason.
  // The directory is created during creation. Write access to the directory for the server user account under which PHP runs is required.
  define('TEMP_FILE_PATH', 'temp');

  // Show languages in topbar (1 = yes, 0 = no)
  define('SHOW_LANGUAGES_IN_TOPBAR','1');

  // Available languages, as language name / language/flag file name pairs separated by semicolons. Each pair is separated by a | character.
  // The first value in each pair is the language name.
  // The second value is the language code, see the LANGUAGE_CODE setting above.
  // Example: "English;en|Svenska;sv" makes English and Swedish available, and shows English and Swedish flags in topbar.
  define('LANGUAGES_AVAILABLE', 'Česky;cs|Dansk;da|Deutsch;de|Eesti;et|English;en|Español;es|Français;fr|Italiano;it|Magyar;hu|Norsk;nb|Polski;pl|Português;pt|Русский;ru|Svenska;sv|Türkçe;tr');

  // The image resizing method to use when generating thumbnails for map images.
  // Select a suitable method depending on server configuration and available memory. If thumbnail generation fails, try to change this value.
  // Available methods:
  // 1. Use file_get_contents() to dynamically check if an image is resizable using the GD library, and if successful resize using GD.
  // 2. Always treat the image as resizable, and resize using GD. If memory problems occur, the upload will probably hang or crash.
  define('IMAGE_RESIZING_METHOD', '1');
  
  // Include Google Analytics script (1 = yes, 0 = no) / creates in index.php, users.php, show_map.php
  define('USE_GA', '0');
  define('GA_TRACKER', 'UA-1234567-8');
  
  // If you want a different time zone than the server's time zone, specify it here. An empty string means that the server's default time zone is used.
  // see http://www.php.net/manual/en/timezones.php for available time zones.
  define('TIME_ZONE', '');
  
  // Define usage of 3DRERUN (1 = yes, 0 = no)
  define('USE_3DRERUN','0');
  // Maximal number of tries to get id from 3DRerun - keep low to avoid worldofo.com high load
  define('RERUN_MAX_TRIES','5');
  // Frequency of retry to get 3DRerun id - in hours 
  // Keep 6 hours since worldofo.com grab data from DOMA archives in the same interval
  define('RERUN_FREQUENCY','6');
  // Worldofo.com api url
  define('RERUN_APIURL','http://omaps.worldofo.com/apicall.php?apikey={0}&link={1}');
  // Worldofo.com api key - please ask Jan Kocbach jan.kocbach@gmail.com to get it 
  // By this procedure you also recheck that your maps are collected
  define('RERUN_APIKEY','xxxxxx');
?>