<?php
	require_once 'lib/vendor/autoload.php';
	
    function getGmaillink($id, $secret, $uri)
	{
		$client = new Google_Client();
		$client -> setApplicationName('Love ur mail');
		$client -> setClientid($id);
		$client -> setClientSecret($secret);
		$client -> setRedirectUri($uri);
		$client -> setAccessType('online');
		$client -> setScopes('https://www.google.com/m8/feeds');
		$googleImportUrl = $client -> createAuthUrl();
		return $googleImportUrl;
	}

?>