<body>
<style>
table{ margin:0 auto;}
table td{text-align: center;}
</style>
<?php
	error_reporting(-1);
	ini_set('display_errors', 'On');
	date_default_timezone_set('UTC'); 
	
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
	        $arrUser[$row['AccountNumber']] = $row;
	    }
	} else {
	    echo "0 results";
	}
	$conn->close();
	
	echo "<form method='POST'>";
	echo "<table>";
	echo "	<tr>";
	echo "	<th></th>";
	echo "	<th>Account Number</th>";
	echo "	<th>FirstName</th>";
	echo "	<th>LastName</th>";
	echo "	<th>EmailAddress</th>";
	echo "	<th>AccountBalance</th>";
	//echo "	<th>TransactionNumber</th>";
	echo "	<th>Time</th>";
	echo "	<th>Past Due</th>";
	echo "	<th>Type</th>";
	echo "	</tr>";
	
	foreach( $arrUser as &$user ){
		if( $user['TransactionNumber'] )
			continue;

		$now = time();
		$invoiceTime = strtotime( $user['Time'] );
		
		$datediff 	= $now - $invoiceTime;
		$daysAmount = floor($datediff / (60 * 60 * 24));
		if( $daysAmount > 27 && $daysAmount < 30 )
		{
			$user['type'] = 1;
			$user['daysAmount'] = 30 - $daysAmount;
		}
		elseif( $daysAmount > 30 && $daysAmount < 65 ){
			$user['type'] = 2;
			$user['daysAmount'] = $daysAmount - 30;
		}
		elseif( $daysAmount > 65 && $daysAmount < 71)
		{
			$user['type'] = 3;
			$user['daysAmount'] = $daysAmount - 30;
		}
		elseif( $daysAmount > 71 )
		{
			$user['type'] = "Suspended";
			$user['daysAmount'] = "-";
		}
		else{
			$user['type'] = 0;
			$user['daysAmount'] = 0;
		}
		
		$select = "";
		if( isset( $_POST['select_user'] ) )
			if( array_key_exists( $user['AccountNumber'], $_POST['select_user'] ))
				$select = "checked";

		echo "<tr>";
		echo "	<td><input type='checkbox' name='select_user[".$user['AccountNumber']."]' $select/></td>";
		echo "	<td>".$user['AccountNumber']."</td>";
		echo "	<td>".$user['FirstName']."</td>";
		echo "	<td>".$user['LastName']."</td>";
		echo "	<td>".$user['EmailAddress']."</td>";
		echo "	<td>".$user['AccountBalance']."</td>";
		// echo "	<td>$user['TransactionNumber']</td>";
		echo "	<td>".$user['Time']."</td>";
		echo "	<td>".$user['daysAmount']."</td>";
		echo "	<td>".$user['type']."</td>";
		echo "</tr>";
	}
	echo "<tr><td colspan=7 align='right'><button >Send Email</button></td></tr>";
	echo "</table>
		</form>";
		
	if( isset( $_POST['select_user'] ))
	{
		foreach( $_POST['select_user'] as $key=>$a )
		{
			if( $arrUser[$key]['TransactionNumber'] )
				continue;

			$to 		= $arrUser[$key]['EmailAddress'];
			$username 	= $arrUser[$key]['FirstName']." ".$arrUser[$key]['LastName'];
			$amount 	= $arrUser[$key]['AccountBalance'];
			$time 		= $arrUser[$key]['Time'];
			$daysAmount	= $arrUser[$key]['daysAmount'];
			$type		= $arrUser[$key]['type'];
			
			$subject = "Your My Computer & Office Supplies Account is past due";
			$header = "From: accounting@mycomputer-aruba.com "."\r\n";
			$header .= "Reply-To: ". $to . "\r\n";
			$header .= "MIME-Version: 1.0\r\n";
			$header .= "Content-Type: text/html; charset=UTF-8\r\n";
			
			switch ( $type )
			{
				case 1:
					$subject = "Your My Computer & Office Supplies account is due in 3 days";
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
						
						This is a friendly  reminder that the balance on your account, Fl <b>'.$amount.'</b>,- is due in 3 days.</b><br/>						
						Please find attached a statement with copies of open invoices as per statement date.<br/><br/>
						
						Payments can be made by transfer to our bank CMB account 24033802 or by check.<br/>
						If you pay by check please send us an email so we can pick the check or call us at our main Tel line 5823999.If you have any questions pls email us at : accounting@mycomputer-aruba.com<br/><br/>
						
						We look forward to receiving the outstanding funds shortly.<br/><br/>
						
						Yours Sincerely,<br/><br/>
						
						<span style="color:rgb(109, 158, 235)">My computer & Office Supplies accounting department</span>  | <a href="www.mycomputeraruba.co" target="_new" style="color:rgb(153, 153, 153)">www.mycomputeraruba.co</a></br>
						<div style="color:rgb(109, 158, 235); clear:both;">W 297-5823999</div>
						<img src="http://ec2-52-32-1-31.us-west-2.compute.amazonaws.com/mailchump_logo.png"/>
						 </body>
						</html>';
				break;
				case 2:
					$subject = "Your My Computer & Office Supplies Account  is past due ".$daysAmount." days";
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
						
						Please remember that the balance on your account, Fl <b>'.$amount.'</b>,- remains unpaid. It was due to be paid in full <b>'.trim($daysAmount).' days ago</b>. Please find attached a statement with copies of open invoices as per statement date.<br/><br/>
						
						 We would greatly appreciate you fixing up this outstanding amount at your soonest possible convenience to avoid <b><u>late fees</u></b> being charged to your account.<br/><br/>
						
						Payments can be made by transfer to our bank CMB account 24033802 or by check.<br/>
						If you pay by check please send us an email so we can pick the check or call us at our main Tel line 5823999.If you have any questions pls email us at : accounting@mycomputer-aruba.com<br/><br/>
						We look forward to receiving the outstanding funds shortly.<br/><br/>
						Yours Sincerely,<br/><br/>
						
						<span style="color:rgb(109, 158, 235)">My computer & Office Supplies accounting department</span>  | <a href="www.mycomputeraruba.co" target="_new" style="color:rgb(153, 153, 153)">www.mycomputeraruba.co</a></br>
						<div style="color:rgb(109, 158, 235); clear:both;">W 297-5823999</div>
						<img src="http://ec2-52-32-1-31.us-west-2.compute.amazonaws.com//mailchump_logo.png"/>
						 </body>
						</html>';
					break;
				case 3:
					$past_due = strtotime( $user['Time'] ) + 71 * 24 * 60 * 60;
					$past_due = "<b>".date('jS M Y', $past_due)."</b>";
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
						
						Your  Account is about to be <b>suspended</b>. You have not resolved the outstanding problem with your account. Our previous communications were not able to produce a satisfactory payment of past due charges.</br>
 Unless we are successful in collecting the balance of Fl <b>'.$amount.'</b>,- in full by '.$past_due.', your My Computer & Office Supplies  account <b>may be suspended or terminated</b>.</br></br>

 Please find attached a statement with copies of open invoices as per statement date.</br>
We would greatly appreciate you fixing up this outstanding amount at your soonest possible convenience to avoid <b><u>account suspension</u></b>.</br></br>
						
						Payments can be made by transfer to our bank CMB account 24033802 or by check.<br/>
						If you pay by check please send us an email so we can pick the check or call us at our main Tel line 5823999.If you have any questions pls email us at : accounting@mycomputer-aruba.com<br/><br/>
						We look forward to receiving the outstanding funds shortly.<br/><br/>
						Yours Sincerely,<br/><br/>
						
						<span style="color:rgb(109, 158, 235)">My computer & Office Supplies accounting department</span>  | <a href="www.mycomputeraruba.co" target="_new" style="color:rgb(153, 153, 153)">www.mycomputeraruba.co</a></br>
						<div style="color:rgb(109, 158, 235); clear:both;">W 297-5823999</div>
						<img src="http://ec2-52-32-1-31.us-west-2.compute.amazonaws.com/mailchump_logo.png"/>
						 </body>
						</html>';
					break;
				default:
					continue;
			}
			$res = mail( $to, $subject, $message, $header );
			echo $message;
			echo "An Email sent to $to<br>";
		}
	}
?>
</body>