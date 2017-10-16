<?php
 	require_once('lib/vendor/autoload.php');
	include('gmailFunctions.php');
  	$client_id     = '4230135050-fb1p95j2cbl28i07jj9f0erh0p1t4rja.apps.googleusercontent.com';
  	$client_secret = 'Bt4p1hE5bPjva-CCjt6e3Vv4';
  	$redirect_uri  = 'http://loveurmail.com/ryanburch/backend/index.php?account=gmail';
 
  	$client = new Google_Client();
  	$client->setClientId($client_id);
 	$client->setClientSecret($client_secret);
  	$client->setRedirectUri($redirect_uri);
	$client->addScope("https://www.googleapis.com/auth/gmail.modify");
	$client->addScope("https://www.googleapis.com/auth/userinfo.profile");
	$client->addScope("https://www.googleapis.com/auth/userinfo.email");
	$client->setApprovalPrompt('force');	
	$client->setAccessType('offline');
	$oauth 		= new Google_Service_Oauth2($client);
