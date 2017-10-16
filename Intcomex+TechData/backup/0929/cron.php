<?php

	date_default_timezone_set("UTC");
	error_log( date("Y-m-d h:i:s"). " : cron is working\n", 3, realpath(dirname(__FILE__)."/log.txt"));

	$ch = curl_init();
	
	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, 'http://ec2-52-32-1-31.us-west-2.compute.amazonaws.com/');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// grab URL and pass it to the browser
	$result = curl_exec($ch);
	
	// close cURL resource, and free up system resources
	curl_close($ch);

	$result = json_decode($result, true);
	
	if( isset( $result['status'] ))
	{
		error_log( date("Y-m-d h:i:s")."--- Scraper is crashed...\n\r".$result['message'], 3, realpath(dirname(__FILE__)."/log.txt"));
		mail( "wanyab87@gmail.com", "Mail Scraper Issue", "Mail scraper for intcomex and techdata is crashed. Please fix issue. \n\r Error: ".$result['message'] );
		mail( "ibian12@gmail.com", "Mail Scraper Issue", "Mail scraper for intcomex and techdata is crashed. Please fix issue. \n\r Error: ".$result['message'] );
	}
?>