# Overview

YmailPHP is a PHP client library for the [Yahoo Mail web service](http://developer.yahoo.com/mail/).
The client supports the OAuth authorization model and the JSON variant of the API. 

To get an OAuth key that works for Yahoo Mail visit the [YDN OAuth page](http://developer.yahoo.com/oauth/).

Also, you can read [this getting started tutorial]() which guides you through acquiring an 
OAuth key and using the YmailPHP library. 

# Using the library

YmailPHP is a Yahoo Mail web service client library. To use it you construct a `YMClient` 
instance and make subsequent method calls that mirror the web service APIs. The following
code snippet will construct a `YMClient` and use it to print the folder list for a mailbox.

    <?php
        require_once 'ymclient.php';

        $ymc = new YMClient(
		    OAUTH_CONSUMER_KEY,
		    OAUTH_CONSUMER_SECRET
		);
		
		$oaToken = new OAuthToken(
			OAUTH_TOKEN,
			OAUTH_TOKEN_SECRET
		);
		
		try {
		    print_r($ymc->ListFolders(new stdclass(), $oaToken));
		} 

		catch(YMClientException $e) {
		    // Uh oh...
		}
    ?> 

The `OAuthToken` must be an access token, not a request token. If the access token is stale
then the YmailPHP library will attempt to refresh it. 

The YmailPHP library provides some functions that make it easier to create and 
manage OAuth sessions with the Ymail web service. The following methods are available 
in the `YMClient` class: 

* `oauth_get_request_token()`
* `oauth_get_access_token()`
* `oauth_get_refreshed_token()` - returns an OAuthToken if it was refreshed by the previous call

In addition, it provides some methods to (de-)serialize OAuth tokens to and
from query strings. These query strings are compatible with those generated by
`OAuthToken.from_string()`, but contain additional data that allows for token refresh, etc.

* `oauth_token_to_query_string()`
* `oauth_token_from_query_string()`

# Complete example

Here is a complete working example. It will fetch the list of folders from a mailbox along 
with information about the first ten messages. Set your own consumer key, secret and callback 
url constants to try it out.

	<?php
	    require_once 'ymclient.php';

	    define(OA_CONSUMER_KEY, "...");
	    define(OA_CONSUMER_SECRET, "...");
	    define(OA_CALLBACK_URL, "...");

	    define('REQUEST_TOKEN_COOKIE_NAME', 'rt');
	    define('ACCESS_TOKEN_COOKIE_NAME', 'at');
    
	    $ymc = new YMClientRequest(OA_CONSUMER_KEY, OA_CONSUMER_SECRET, OA_CALLBACK_URL);

	    try {
	        header("Content-type: application/json\r\n\r\n");
    
	        $folders = $ymc->ListFolders(new stdclass());

	        $req = new stdclass();
	        $req->fid = "Inbox";
	        $req->startInfo = 0;
	        $req->numInfo = 10;
	        $messages = $ymc->ListMessages($req);
    
	        print json_encode(array($folders, $messages));
	    } 

	    catch(YMClientException $e) {
	        header("Content-type: text/plain\r\n\r\n");
	        print_r($e);
	    }

	    /**
	    * A wrapper class that manages OAuth sessions in cookies for 
	    * this application. It could be modified to store sessions 
	    * along with access tokens in a database.
	    */
	    class YMClientRequest {
	        function __construct($oaConsumerKey, $oaConsumerSecret, $callbackURL) {
	            $this->oaConsumerKey = $oaConsumerKey;
	            $this->oaConsumerSecret = $oaConsumerSecret;
	            $this->callbackURL = $callbackURL;
	            $this->ymc = new YMClient($oaConsumerKey, $oaConsumerSecret);
	        }
    
	        function __call($method, $arguments) {
	            $tok = $this->__get_access_token();            
	            $result = $this->ymc->$method($arguments, $tok);
	            $newtok = $this->ymc->oauth_get_refreshed_token();
	            if($newtok) {
	                setcookie(ACCESS_TOKEN_COOKIE_NAME, 
	                    YMClient::oauth_token_to_query_string($newtok));
	            }
        
	            return $result;
	        }
    
	        /**
	        * This method attempts to get an access token from the cookie. If 
	        * that fails it checks if this is a callback request from app 
	        * authentication and if so requests a new access token. Otherwise 
	        * the user hasn't granted the application access yet and it 
	        * redirects them to the Yahoo login page to do so. 
	        */
	        private function __get_access_token() {
	            // Access token exists in a cookie
	            if($_COOKIE[ACCESS_TOKEN_COOKIE_NAME]) {
	                parse_str($_COOKIE[ACCESS_TOKEN_COOKIE_NAME], $tok);                
	                return $tok;
	            }

	            // Handling a redirect back from login
	            else if($_COOKIE[REQUEST_TOKEN_COOKIE_NAME] && $_REQUEST['oauth_verifier'] && $_REQUEST['oauth_token']) {
	                $tok = YMClient::oauth_token_from_query_string($_COOKIE[REQUEST_TOKEN_COOKIE_NAME]);

	                if($tok['oauth_token'] != $_REQUEST['oauth_token']) {
	                    throw new Exception("Cookie and URL disagree about request token value");
	                }

	                $tok['oauth_verifier'] = $_REQUEST['oauth_verifier'];                            
	                $newtok = $this->ymc->oauth_get_access_token($tok);

	                setcookie(REQUEST_TOKEN_COOKIE_NAME, "", time()-3600);
	                setcookie(ACCESS_TOKEN_COOKIE_NAME, YMClient::oauth_token_to_query_string($newtok));
	                return $newtok;
	            }

	            // Sending the user to login to grant access to this app
	            else {
	                list ($tok, $url) = $this->ymc->oauth_get_request_token($this->callbackURL);
	                setcookie(REQUEST_TOKEN_COOKIE_NAME, YMClient::oauth_token_to_query_string($tok));
	                header("Location: $url");
	            }
	        }
	    }
	?>

# Notes

Authored by Mike Curtis - [http://twitter.com/mikecurtis](http://twitter.com/mikecurtis)

Mostly a port of [PyCascade](http://github.com/pgriess/PyCascade) by [Peter Griess](http://github.com/pgriess)