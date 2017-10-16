<?php
	require( 'includes/application.php' ) ;
	$action		= tep_get_value_require( "action" ) ;
	session_start();
	if (isset($_REQUEST['account']) && $_REQUEST['account'] == 'gmail') 
	{
		$action = 'putGmailAuthCode';
	}
	switch($action)
	{
		case 'getGmailAuthurl' 		: 	require('mobileservice/gmail/gmailPage.php');
										break;
										
		case 'putGmailAuthCode' 	: 	$code	= $_REQUEST['code'];
										require('mobileservice/gmail/gmailPage.php');
										break;
										
		case 'getGmailMessages' 	: 	require('mobileservice/gmail/gmailPage.php');
										break;
										
										
										
										
										
		case 'getGmailContacts' 	: 	require('mobileservice/gmail/gmail.php');
										getGmailContacts($google_client_id, $google_client_secret, $google_redirect_uri, $code);
										break;
										
		
		case 'yahooLink' 			: 	require('mobileservice/yahoo/yahoo.php');
										$yahooLink = getYahooLink(CONSUMER_KEY, CONSUMER_SECRET, APPURL);
										echo '{"action" : "yahooLink", "url" : "'.$yahooLink.'"}'; 
										break;
		
		case 'getYahooContacts' 	: 	require('mobileservice/yahoo/yahoo.php');
										getYahooContacts(CONSUMER_KEY, CONSUMER_SECRET, APPID);
										break;
										
		default 					: 
	}
?>