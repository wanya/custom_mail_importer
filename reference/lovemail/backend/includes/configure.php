<?php
    
    // --- Configure --- ;
    define( 'HTTP_SERVER', "http://".$_SERVER[ "SERVER_NAME" ] ) ;
    define( 'HTTP_CATALOG_SERVER', HTTP_SERVER."/ryanburch/backend/" )  ;

    define( 'DIR_WS_INCLUDES', DIR_FS_DOCUMENT_ROOT . 'includes/' ) ;
    define( 'DIR_WS_FUNCTIONS', DIR_FS_DOCUMENT_ROOT . 'includes/functions/' ) ;

    define( 'SESSION_LIFETIME', 86400 ) ; // 24 hours

    define( 'DB_SERVER', 'localhost' ) ;
    define( 'DB_SERVER_USERNAME', 'lovemail_user' ) ;
    define( 'DB_SERVER_PASSWORD', 'Byanburch123' ) ;
    define( 'DB_DATABASE', 'db_lovemail' ) ;

    define( 'USE_PCONNECT', 'false' ) ;
    define( 'STORE_SESSIONS', 'mysql' ) ;
    define( 'CHARSET','utf8' ) ;

    define( 'FILENAME_DEFAULT', 'index.php' ) ;

    define( 'SESSION_WRITE_DIRECTORY', DIR_WS_INCLUDES . 'cache/' ) ;

    define( 'SITE_TITLE', 'Love ur mail' ) ;
	
	date_default_timezone_set('Asia/kolkata');
	
	define('CONSUMER_KEY', "dj0yJmk9YTVUczRxTklGQnZaJmQ9WVdrOVVVVXlORWhFTkdjbWNHbzlNQS0tJnM9Y29uc3VtZXJzZWNyZXQmeD1jMg--");  
	define('CONSUMER_SECRET', "c30cb2f76e40afb669f4b27702efd46f83d8b3af");  
	define('APPID', "QE24HD4g");  
	define('APPURL', "http://loveurmail.com/ryanburch/backend/index.php");  

	$gmail_client 		= '4230135050-fb1p95j2cbl28i07jj9f0erh0p1t4rja.apps.googleusercontent.com';
	$gmail_secret 		= 'Bt4p1hE5bPjva-CCjt6e3Vv4';
	$gmail_uri 			= 'http://loveurmail.com/ryanburch/backend/index.php?account=gmail';
