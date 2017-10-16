<?php
    
    // --- Configure --- ;
    define( 'HTTP_SERVER', "http://".$_SERVER[ "SERVER_NAME" ] ) ;
    define( 'HTTP_CATALOG_SERVER', HTTP_SERVER."/ryanburch/backend/" )  ;

    define( 'DIR_WS_INCLUDES', DIR_FS_DOCUMENT_ROOT . 'includes/' ) ;
    define( 'DIR_WS_FUNCTIONS', DIR_FS_DOCUMENT_ROOT . 'includes/functions/' ) ;

    define( 'SESSION_LIFETIME', 86400 ) ; // 24 hours

    define( 'DB_SERVER', 'localhost' ) ;
    define( 'DB_SERVER_USERNAME', 'root' ) ;
    define( 'DB_SERVER_PASSWORD', '' ) ;
    define( 'DB_DATABASE', 'db_daily_gift' ) ;

    define( 'USE_PCONNECT', 'false' ) ;
    define( 'STORE_SESSIONS', 'mysql' ) ;
    define( 'CHARSET','utf8' ) ;

    define( 'FILENAME_DEFAULT', 'index.php' ) ;

    define( 'SESSION_WRITE_DIRECTORY', DIR_WS_INCLUDES . 'cache/' ) ;

    define( 'SITE_TITLE', 'Love ur mail' ) ;
	
	date_default_timezone_set('Asia/kolkata');
	
	
	$google_client_id 		= '4230135050-fb1p95j2cbl28i07jj9f0erh0p1t4rja.apps.googleusercontent.com';
	$google_client_secret 	= 'Bt4p1hE5bPjva-CCjt6e3Vv4';
	$google_redirect_uri 	= 'http://loveurmail.com/ryanburch/backend/gmail/contacts/redirect.php';
