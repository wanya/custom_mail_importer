<?php
$client_id 			= '0000000040194C17';
$client_secret	 	= 'vCbbJYpc1otUYR90UphTMxK0A12IandY';
$redirect_uri 		= 'http://loveurmail.com/ryanburch/backend/msn/contacts/redirect.php';
$urls_	= 'https://login.live.com/oauth20_authorize.srf?client_id='.$client_id.'&scope=wl.signin%20wl.basic%20wl.emails%20wl.contacts_emails&response_type=code&redirect_uri='.$redirect_uri;
$msn_link =  '<a href="'.$urls_.'" >MSN Contacts</a>';
echo $msn_link;
?>