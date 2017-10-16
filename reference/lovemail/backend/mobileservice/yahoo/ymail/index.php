<?php
    require_once 'ymclient.php';

    define(OA_CONSUMER_KEY, "dj0yJmk9YTVUczRxTklGQnZaJmQ9WVdrOVVVVXlORWhFTkdjbWNHbzlNQS0tJnM9Y29uc3VtZXJzZWNyZXQmeD1jMg--");
    define(OA_CONSUMER_SECRET, "c30cb2f76e40afb669f4b27702efd46f83d8b3af");
    define(OA_CALLBACK_URL, "http://loveurmail.com/ryanburch/backend/mobileservice/yahoo/ymail/index.php");

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