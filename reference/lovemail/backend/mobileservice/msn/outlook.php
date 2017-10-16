<?php
  class OutlookService {
    public static function makeApiCall($access_token, $user_email, $method, $url, $payload = NULL) 
	{
		// Generate the list of headers to always send.
		$headers = array(
			"User-Agent					: php-tutorial/1.0",        // Sending a User-Agent header is a best practice.
			"Authorization				: Bearer ".$access_token, 	// Always need our auth token!
			"Accept						: application/json",        // Always accept JSON response.
			"client-request-id			: ".self::makeGuid(), 		// Stamp each new request with a new GUID.
			"return-client-request-id	: true",       				// Tell the server to include our request-id GUID in the response.
			"X-AnchorMailbox			: ".$user_email         	// Provider user's email to optimize routing of API call
		);
		$curl = curl_init($url);

		switch(strtoupper($method)) 
		{
			case "GET"	: 	error_log("Doing GET");
							break;
							
			case "POST"	: 	error_log("Doing POST");
							$headers[] = "Content-Type: application/json";
							curl_setopt($curl, CURLOPT_POST, true);
							curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
							break;
							
			case "PATCH":   error_log("Doing PATCH");
							$headers[] = "Content-Type: application/json";
							curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
							curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
							break;
							
			case "DELETE":  error_log("Doing DELETE");
							curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
							break;
							
			default		:   error_log("INVALID METHOD: ".$method);
							exit;
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$response 	= curl_exec($curl);
		error_log("curl_exec done.");

		$httpCode 	= curl_getinfo($curl, CURLINFO_HTTP_CODE);
		error_log("Request returned status ".$httpCode);
		if ($httpCode >= 400) 
		{
			return array('errorNumber' => $httpCode, 'error' => 'Request returned HTTP error '.$httpCode);
		}

		$curl_errno = curl_errno($curl);
		$curl_err = curl_error($curl);
		if ($curl_errno) 
		{
			$msg = $curl_errno.": ".$curl_err;
			error_log("CURL returned an error: ".$msg);
			curl_close($curl);
			return array('errorNumber' => $curl_errno, 'error' => $msg);
		}
		else 
		{
			error_log("Response: ".$response);
			curl_close($curl);
			return json_decode($response, true);
		}
    }

    // This function generates a random GUID.
    public static function makeGuid(){
      if (function_exists('com_create_guid')) {
        error_log("Using 'com_create_guid'.");
        return strtolower(trim(com_create_guid(), '{}'));
      }
      else {
        error_log("Using custom GUID code.");
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid, 12, 4).$hyphen
                .substr($charid, 16, 4).$hyphen
                .substr($charid, 20, 12);

        return $uuid;
      }
    }
	
	private static $outlookApiUrl = "https://outlook.office.com/api/v2.0";
		
	public static function getMessages($access_token, $user_email) {
	  $getMessagesParameters = array (
		// Only return Subject, ReceivedDateTime, and From fields
		"\$select" => "Subject,ReceivedDateTime,From",
		// Sort by ReceivedDateTime, newest first
		"\$orderby" => "ReceivedDateTime DESC",
		// Return at most 10 results
		"\$top" => "50"
	  );

	  $getMessagesUrl = self::$outlookApiUrl."/Me/Messages?".http_build_query($getMessagesParameters);
							
	  return self::makeApiCall($access_token, $user_email, "GET", $getMessagesUrl);
	}	
	
  }
  
?>