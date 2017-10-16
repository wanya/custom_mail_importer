<?php

	$servername = "localhost";
	$username 	= "root";
	$password 	= "root";
	$dbname 	= "techdata";
	
	// get users
	$arrUsers = array();
	
	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 
	
	$sql = "SELECT * FROM Customer";
	$result = $conn->query($sql);
	
	if ($result->num_rows > 0) {
	    while( $row = $result->fetch_assoc()) {
	        $arrUser[] = $row;
	    }
	} else {
	    echo "0 results";
	}
	$conn->close();
	
	$subject = "Your My Computer & Office Supplies Account is past due";
	foreach( $arrUser as $user ){
		
		// client already paid
		if( $user['TransactionNumber'] )
			continue;
		
		$to 		= $user['EmailAddress'];
		$username 	= $user['FirstName']." ".$user['LastName'];
		$amount 	= $user['AccountBalance'];
		$time 		= $user['Time'];
		
		$now = time();
		$invoiceTime = strtotime( $time );
		
		$datediff 	= $now - $invoiceTime;
		$daysAmount = floor($datediff / (60 * 60 * 24));
		if( $daysAmount - 30 > 0 )
			$daysAmount = $daysAmount - 30;
			
		$header = "From: accounting@mycomputer-aruba.com "."\r\n";
		$header .= "Reply-To: ". $to . "\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: text/html; charset=UTF-8\r\n";
		
		$message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>Demystifying Email Design</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
 </head>
 <style>
 	*{ font-family: arial, sans-serif; line-height:20px;}
 </style>
 <body>
Hi '.$username.',<br/><br/>

Please remember that the balance on your account, <b>'.$amount.'</b> remains unpaid. It was due to be paid in full <b>'.trim($daysAmount).' days ago</b>. Please find attached a statement with copies of open invoices as per statement date.<br/><br/>

 We would greatly appreciate you fixing up this outstanding amount at your soonest possible convenience to avoid <b><u>late fees</u></b> being charged to your account.<br/><br/>

Payments can be made by transfer to our bank CMB account 24033802 or by check.<br/>
If you pay by check please send us an email so we can pick the check or call us at our main Tel line 5823999.If you have any questions pls email us at : accounting@mycomputer-aruba.com<br/><br/>
We look forward to receiving the outstanding funds shortly.<br/><br/>
Yours Sincerely,<br/><br/>

<span style="color:rgb(109, 158, 235)">My computer & Office Supplies accounting department</span>  | <a href="www.mycomputeraruba.co" target="_new" style="color:rgb(153, 153, 153)">www.mycomputeraruba.co</a></br>
<div style="color:rgb(109, 158, 235); clear:both;">W 297-5823999</div>
<img src="http://ec2-52-40-200-48.us-west-2.compute.amazonaws.com/mailchump_logo.png"/>
 </body>
</html>';
		$res = mail( $to, $subject, $message, $header );
		echo $message;
		
	}
?>