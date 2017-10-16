<?php
	require( 'includes/application.php' ) ;
	$action		= tep_get_value_require( "action" ) ;
	
	switch($action)
	{
		case 'gmailLink' 			: 	require('mobileservice/gmail/gmail.php');
										$gmailUrl = getGmaillink($google_client_id, $google_client_secret, $google_redirect_uri);
										echo '{"action" : "gmailLink", "url" : "'.$gmailUrl.'"}'; 
										break;
		default 					: 
	}
?>