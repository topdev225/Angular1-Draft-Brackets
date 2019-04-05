<?php
/*
THIS IS ONLY A SAMPLE AND IS NOT MEANT TO CONTAIN APPLICATION SETTINGS.
VIEW README.MD FOR DETAILS ON WHERE TO PLACE THIS FILE
*/

//PHPDraft Application Settings

$configuration_variables = array(
  //Provide the DBAL driver name for the database (see http://silex.sensiolabs.org/doc/providers/doctrine.html)
  'DB_DRIVER' => 'pdo_mysql',
  'DB_HOST' => 'localhost', //on some *nix systems, it may be helpful to use '127.0.0.1' instead of 'localhost'
  //Name of your database
  'DB_NAME' => 'phpdraft',
  'DB_PORT' => 3306,
  'DB_USER' => 'root',
  'DB_PASS' => '',

  //The number of seconds to cache drafts in PHP memory to save on database calls. Cache is automatically updated when
  //changes are made to the draft, so the higher the number the better.
  'CACHE_SECONDS' => 3600,
  //The absolute path to a folder PHP can write to. On Windows machines it will look like 'C:/full/path/to/tmp'
  'CACHE_PATH' => '/home/draftbrackets/public_html/tmp',

  //Provide a secret key to generate all JWT authentication tokens. Make sure it's long, and keep it a secret!
  'AUTH_KEY' => 'I4+$p9Y9#RX,94q',
  //The number of seconds for tokens to be valid (after this time, they will need to re-authenticate; default: 86,400 (1 day))
  'AUTH_SECONDS' => 86400,
  //Grab your application's Google Recaptcha 2.0 secret key. Without it registration will not work!
  'RECAPTCHA_SECRET' => '6Lf87nIUAAAAALPwwvfRtgcXSXUc51gcCoLhMTyq',

  //Mail configuration
  //If you are developing or running the app on localhost and using a helper app like Mailcatcher or Papercut, switch to true:
  'MAIL_DEVELOPMENT' => false,
  'MAIL_SERVER' => 'localhost',
  'MAIL_USER' => 'noreply@draftbrackets.com',
  'MAIL_PASS' => 'TR5IiCqFyaH?',
  'MAIL_PORT' => 25,
  'MAIL_USE_ENCRYPTION' => false, //STRONGLY suggested to use this (otherwise sensitive data can be intercepted!)
  'MAIL_ENCRYPTION' => 'tls', //May also use 'ssl', but there are security issues with SSL: https://en.wikipedia.org/wiki/POODLE

  //See /api/Domain/Services/EmailService.php for further help if debugging your mail server settings.

  //Provide the base URL for the Angular app (no trailing slash):
  'APP_BASE_URL' => 'https://draftbrackets.com',
  //Provide the base URL for the API (no trailing slash):
  'API_BASE_URL' => 'https://draftbrackets.com/api',
 //Provide the base URL for the MongoDB (no trailing slash):
  'MONGO_API_BASE_URL' => 'https://draftbrackets.com:8569/graphql?',  'MONGO_API_BASE_URL_NO_QUERY' => 'https://draftbrackets.com:8569/',
  
  //Provide the name of the header to store the authorization token in. (Default: "X-Access-Token")
  'AUTH_KEY_HEADER' => 'X-Access-Token',
  //Provide the name of the header to store the draft password in (Default: "X-PhpDraft-DraftPassword")
  'DRAFT_PASSWORD_HEADER' => 'X-PhpDraft-DraftPassword',

  //Run the app in debug mode? If so, LOGFILE_NAME (below) will be populated with log information.
  'DEBUG_MODE' => false,
  //Name of the file you have created in /api/config/logs folder to contain Silex logging information
  'LOGFILE_NAME' => 'phpdraft.log',

  'SET_CSV_TIMEOUT' => false

);

foreach($configuration_variables as $name => $value) {
  define($name, $value);
}

unset($configuration_variables);
?>