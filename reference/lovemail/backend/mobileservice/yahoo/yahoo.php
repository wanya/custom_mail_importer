<?php
	require("lib/Yahoo.inc");  
	
    function getYahooLink($key, $secret, $uri)
	{
		return YahooSession::createAuthorizationUrl($key, $secret, $uri.'?data=yahoo');
	}
	
    function getYahooContacts($key, $secret, $appid)
	{
		$session 	= YahooSession::requireSession($key ,$secret, $appid); 
		$user 	 	= $session->getSessionedUser();
		$contacts 	= $user->getContacts(0, 1000);
		foreach ($contacts->contacts->contact as $contact)
		{
			foreach ($contact->fields as $field)
			{
				if ($field->type == "email")
				{
					$emails[] = $field->value;
				}
			}
		}
		print_r($emails);
	}
?>