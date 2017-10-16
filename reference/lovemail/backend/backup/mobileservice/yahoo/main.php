<?php
	require("../lib/Yahoo.inc");  
	define('CONSUMER_KEY', "dj0yJmk9YTVUczRxTklGQnZaJmQ9WVdrOVVVVXlORWhFTkdjbWNHbzlNQS0tJnM9Y29uc3VtZXJzZWNyZXQmeD1jMg--");  
	define('CONSUMER_SECRET', "c30cb2f76e40afb669f4b27702efd46f83d8b3af");  
	define('APPID', "QE24HD4g");  
	define('APPURL', "http://loveurmail.com/ryanburch/backend/yaho/contacts/main.php");  
	
	if($_REQUEST['data'] == 'abc')
	{
		$session 	= YahooSession::requireSession(CONSUMER_KEY,CONSUMER_SECRET,APPID); 
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
<a href="<?php echo YahooSession::createAuthorizationUrl(CONSUMER_KEY, CONSUMER_SECRET, APPURL.'?data=abc'); ?>">Fetch Yahoo Contacts</a>