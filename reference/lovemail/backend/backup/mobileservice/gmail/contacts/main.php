<?php
require_once '../lib/vendor/autoload.php';
$google_client_id 		= '4230135050-fb1p95j2cbl28i07jj9f0erh0p1t4rja.apps.googleusercontent.com';
$google_client_secret 	= 'Bt4p1hE5bPjva-CCjt6e3Vv4';
$google_redirect_uri 	= 'http://loveurmail.com/ryanburch/backend/gmail/contacts/redirect.php';

$client = new Google_Client();
$client -> setApplicationName('Love ur mail');
$client -> setClientid($google_client_id);
$client -> setClientSecret($google_client_secret);
$client -> setRedirectUri($google_redirect_uri);
$client -> setAccessType('online');
$client -> setScopes('https://www.google.com/m8/feeds');
$googleImportUrl = $client -> createAuthUrl();
?>
<a href="<?php echo $googleImportUrl; ?>"> Import google contacts </a>