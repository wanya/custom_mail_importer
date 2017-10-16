<?php

	date_default_timezone_set("UTC");
	error_log( date("Y-m-d h:i:s"). " : start --- ", 3, realpath(dirname(__FILE__)."/log.txt"));
	$start = date("Y-m-d h:i:s");
	echo $start;

	$ch = curl_init();
	
	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, 'http://ec2-52-32-1-31.us-west-2.compute.amazonaws.com/');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	
	// grab URL and pass it to the browser
	curl_exec($ch);
	
	// close cURL resource, and free up system resources
	curl_close($ch);

	error_log( date("Y-m-d h:i:s"). " : end\n", 3, realpath(dirname(__FILE__)."/log.txt"));

	$end = date("Y-m-d h:i:s");
	echo $end;
	
	if( $start === $end )
		error_log( "--- scraper not working...\n\r", 3, realpath(dirname(__FILE__)."/log.txt"));

?>