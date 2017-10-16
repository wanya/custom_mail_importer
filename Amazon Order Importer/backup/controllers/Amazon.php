<?php defined('BASEPATH') OR exit('No direct script access allowed');

date_default_timezone_set("Canada/Atlantic");

define( UPLOAD_PATH, APPPATH.'../uploads/amazon/');
define( SITE_URL, "http://ec2-35-164-238-86.us-west-2.compute.amazonaws.com");

define( CREDENTIAL_PATH, APPPATH."../client_secret.json");
define( REFRESH_TOKEN, APPPATH."../refresh.dat");


class Amazon extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		
		$this->load->model('Orders');
		$this->load->model('Items');
	}

	private function writeAccessToken( $access_token )
	{
		if( isset($access_token['refresh_token']) )
			file_put_contents( REFRESH_TOKEN, $access_token['refresh_token']);
			
		return file_put_contents(CREDENTIAL_PATH, json_encode($access_token));
	}
	
	private function readAccessToken()
	{
		$accessToken = json_decode(file_get_contents(CREDENTIAL_PATH), true);
		return $accessToken;
	}
	
	private function readRefreshToken()
	{
		return file_get_contents(REFRESH_TOKEN);
	}


	public function index()
	{
		$redirect_uri  = SITE_URL."/index.php";

	  	$client_id     = '298458107943-r15vddm5nnqgvukt29evcmjk4ehn5kvr.apps.googleusercontent.com';
	  	$client_secret = 'bGkik5a-uQb0dpzSD5ifGYt8';
	 
	  	$client = new Google_Client();
	  	$client->setApplicationName("Importing Invoices");
	  	$client->setClientId($client_id);
	 	$client->setClientSecret($client_secret);
	  	$client->setRedirectUri($redirect_uri);
	  	$client->setAccessType('offline');
	  	$client->setApprovalPrompt('force');
	  	
		// We only need permissions to compose and send emails
		$client->addScope("https://www.googleapis.com/auth/gmail.readonly");
		
		$service = new Google_Service_Gmail($client);

		// Redirect the URL after OAuth
		if (isset($_GET['code']) && $_GET['code'] != "") 
		{
			$res = $client->authenticate($_GET['code']);
			$this->writeAccessToken( $client->getAccessToken() );
			
			$redirect = $redirect_uri;
			header('Location: ' . filter_var($redirect));
			exit;
		}

		// If Access Toket is not set, show the OAuth URL
		$token = $this->readAccessToken();
		
		if ( ! empty( $token ) ) 
		{
			$client->setAccessToken( $token );

			if ($client->isAccessTokenExpired()) {
				echo "Access Token expired!!!";
				
				// refresh Access Token
				if( $refresh_token = $this->readRefreshToken() )
				{
					$client->refreshToken( $refresh_token  );
					$access_token = $client->getAccessToken();
				}
				// error_log( print_r( $client->getAccessToken(), true ), 3, APPPATH."../log.txt");
			}
		} 
		else 
		{
			$authUrl = $client->createAuthUrl();
		}

		$access_token = $client->getAccessToken();

		// error_log( print_r( $access_token, true ), 3, APPPATH."../log.txt");

		if ( !empty($access_token) ) 
		{
		    $this->writeAccessToken( $access_token );
			$gmail = $service;
		    try 
			{
				$list = $gmail->users_messages->listUsersMessages('me',['maxResults' => 5, 'q' => $search]);
				$messageList = $list->getMessages();
				$inboxMessage = [];
				
				$got_last = false;
				foreach($messageList as $mlist){
					
					if( $got_last == true )
						continue;
				
					$optParamsGet2['format'] = 'full';
					$single_message = $gmail->users_messages->get('me',$mlist->id, $optParamsGet2);
					$message_id = $mlist->id;
					$headers = $single_message->getPayload()->getHeaders();
					$snippet = $single_message->getSnippet();

					$arrAttach = array(); $index = 0;
					$parts = $single_message->getPayload()->getParts();
					foreach( $parts as $part )
					{
						$attachID = $part->getBody()->getAttachmentId();
						if( $attachID )
						{
							$file_name = $part->filename;
							$attach = $service->users_messages_attachments->get('me', $mlist->id, $attachID );
							
							$fh = fopen( UPLOAD_PATH.$file_name, "w+");
							fwrite($fh, base64_decode(strtr($attach->data, array('-' => '+', '_' => '/'))));
							fclose($fh);
							
							$arrAttach[ $index ]['filename'] 	= $file_name;
							$arrAttach[ $index++ ]['url'] 		= SITE_URL ."/uploads/".$file_name;
							
							$ext = pathinfo($file_name, PATHINFO_EXTENSION);
							if( $ext == "csv" || $ext == "CSV" ){
								$file_list[] = $file;
								$got_last = true;
							}							
						}
					}
					
					foreach($headers as $single) {
		
						if ($single->getName() == 'Subject') {
		
							$message_subject = $single->getValue();
		
						}
		
						else if ($single->getName() == 'Date') {
		
							$message_date = $single->getValue();
							$message_date = date('M jS Y h:i A', strtotime($message_date));
						}
		
						else if ($single->getName() == 'From') {
		
							$message_sender = $single->getValue();
							$message_sender = str_replace('"', '', $message_sender);
						}
					}		
					
					if( $got_last == true )
					$inboxMessage[] = [
						'messageId' 	=> $message_id,
						'messageSnippet' => $snippet,
						'messageSubject' => $message_subject,
						'messageDate' 	=> $message_date,
						'messageSender' => $message_sender,
						'attachments'	=> $arrAttach
					];		
				}
				echo '
				<table border="1">
					<tr>
						<th>messageId</th>
						<th>messageSnippet</th>
						<th>messageSubject</th>
						<th>messageDate</th>
						<th>messageSender</th>
						<th>Attachments</th>
					</tr>';
					foreach( $inboxMessage as $msg )
					{
						echo '<tr>
							<td>'.$msg['messageId'].'</td>
							<td>'.$msg['messageSnippet'].'</td> 
							<td>'.$msg['messageSubject'].'</td>
							<td>'.$msg['messageDate'].'</td>
							<td>'.$msg['messageSender'].'</td>
							<td>';
								foreach( $msg['attachments'] as $attach ) 
									echo "<a href='".$attach['url']."'>".$attach['filename']."</a><br>";
							echo '</td>				
						</tr>';
					}
				echo '</table>';
				
				$this->parse();
			} 
			catch (Exception $e) 
			{
				print($e->getMessage());
				unset($_SESSION['access_token']);
				$this->writeAccessToken("");
			}
		} 
		else
			header('Location: ' . filter_var($authUrl));
		
		// echo '<a href="'.$authUrl.'"><img src="google.png" title="Sign-in with Google" /></a>';
	}

	public function parse(){

		$file_list = array();
		$files = scandir( UPLOAD_PATH );
		foreach( $files as $file )
		{
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if( $ext == "csv" || $ext == "CSV" )
				$file_list[] = $file;
		}

		// $file_list = array( "0" => UPLOAD_PATH."../test/test.csv" );

		foreach( $file_list as $file_name )
		{
			echo "<br><br>".$file_name;

			$row = 1; 
			$arrRows = array(
				0 	=> "OrderDate", 		// A
				1 	=> "OrderID", 			// B
				3	=> "PO_number", 		// D
				4	=> "OrderQty", 			// E
				5	=> "SubTotal", 			// F
				6	=> "Shipping_Handling", // G
				7	=> "Promotion", 		// H
				8	=> "Tax", 				// I
				9	=> "NetTotal", 			// J
				10	=> "Status", 			// K
				19	=> "PaymentRef",		// T
				20	=> "PaymentDate",		// U
				21 	=> "PaymentAmount",		// V
				36	=> "ASIN", 				// AK
				37	=> "Title", 			// AL
				41	=> "PPU", 				// AP
				42	=> "QTY"				// AQ
				);

			$arrOrders = array();
			$file_path = UPLOAD_PATH.$file_name;

			$echo_str = "<style> td{}</style><table border=1>";
			if (($handle = fopen( $file_path, "r")) !== FALSE) {
			    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	
			        $num = count($data);
			    	if( $row == 1 )
			    	{
			    		$echo_str .= "<thead><th>No</th>";
				        for ($c=0; $c < $num; $c++) {
				    		if( array_key_exists( $c, $arrRows ) )
					            $echo_str .= "<th>".$data[$c] . "</th>";
				        }
				        $echo_str .= "<th>Error</th>";
				        $echo_str .= "</thead>";
			    	}
			    	else
			    	{
				        for ($c=0; $c < $num; $c++) {
				    		if( array_key_exists( $c, $arrRows ) )
					        {
					        	$arrOrders[$row-1][ $arrRows[$c] ] = $data[$c];
					        }
				        }
					}
			        $row++;
			    }
			    fclose($handle);
			}

			$arrCheckError = array();
			foreach( $arrOrders as &$order )
			{
				$order['SubTotal'] 			= floatval( preg_replace("/[^-0-9\.]/","", $order['SubTotal'] ));
				$order['Shipping_Handling'] = floatval( preg_replace("/[^-0-9\.]/","", $order['Shipping_Handling'] ));
				$order['Promotion'] 		= floatval( preg_replace("/[^-0-9\.]/","", $order['Promotion'] ));
				$order['Tax'] 				= floatval( preg_replace("/[^-0-9\.]/","", $order['Tax'] ));
				$order['NetTotal'] 			= floatval( preg_replace("/[^-0-9\.]/","", $order['NetTotal'] ));
				$order['PPU'] 				= floatval( preg_replace("/[^-0-9\.]/","", $order['PPU'] ));
				$order['AddDate']			= date("Y-m-d h:i:s");
				$order['OrderDate'] 		= $this->getDateFormat( $order['OrderDate'] );
				$order['PaymentDate']		= $this->getDateFormat( $order['PaymentDate'] );
				$order['PaymentAmount']		= floatval( preg_replace("/[^-0-9\.]/","", $order['PaymentAmount'] ));

				$arrCheckError[$order['OrderID']] += $order['QTY'];
			}

			$index = 1; $order_count = 0; $arrExport = array();
			foreach( $arrOrders as &$order )
			{
				if( $order['OrderQty'] != $arrCheckError[$order['OrderID']] && $order['Status'] == "Closed" )
					$order['Error'] = "QTY Issue";

				$echo_str .= "<tr><td>".(++$index - 1)."</td>";
				$echo_str .= "<td>".$order['OrderDate']."</td>";
				$echo_str .= "<td>".$order['OrderID']."</td>";
				$echo_str .= "<td>".$order['PO_number']."</td>";
				$echo_str .= "<td>".$order['OrderQty']."</td>";
				$echo_str .= "<td>".$order['SubTotal']."</td>";
				$echo_str .= "<td>".$order['Shipping_Handling']."</td>";
				$echo_str .= "<td>".$order['Promotion']."</td>";
				$echo_str .= "<td>".$order['Tax']."</td>";
				$echo_str .= "<td>".$order['NetTotal']."</td>";
				$echo_str .= "<td>".$order['Status']."</td>";
				$echo_str .= "<td>".$order['PaymentRef']."</td>";
				$echo_str .= "<td>".$order['PaymentDate']."</td>";
				$echo_str .= "<td>".$order['PaymentAmount']."</td>";
				$echo_str .= "<td>".$order['ASIN']."</td>";
				$echo_str .= "<td>".$order['Title']."</td>";
				$echo_str .= "<td>".$order['PPU']."</td>";
				$echo_str .= "<td>".$order['QTY']."</td>";
				$echo_str .= "<td>".$order['Error']."</td>";
				$echo_str .= "</tr>";

				unset( $order['Status']);
				unset( $order['OrderQty']);

				$item_data = array(
						"PaymentAmount"		=> $order['PaymentAmount'],
						"PaymentReference"	=> $order['PaymentRef'],
						"QTY"				=> $order['QTY'],
						"ASIN"				=> $order['ASIN'],
						"PPU"				=> $order['PPU'],
						"OrderID"			=> $order['OrderID'],
						"AddDate"			=> $order['AddDate']
					);
				$id = $this->Orders->insertOrder( $order );
				if( $id && $this->Orders->getOrderByID($order['OrderID'])->AddDate == $item_data['AddDate'] )
				{
					if( $id > 0 )
						$arrExport[ $order['OrderID']] = $order;

					$this->Items->insertItem( $item_data );
					array_push($item_data, $arrExport[$order_count]['items']);
				}

				$db_order = get_object_vars( $this->Orders->getOrderByID( $order['OrderID'] ));
				$arrExport[ $db_order['ID'] ] = $db_order;
				$arrExport[ $db_order['ID'] ]['items'] = $this->Items->getItemsByOrder( $order['OrderID']);
			}

			$echo_str .= "</table>"; 
			echo $echo_str;

			// backup files and export to html
			rename( $file_path, UPLOAD_PATH."../".$file_name );
			$save_path = UPLOAD_PATH."../html/". str_replace( "csv", "html", $file_name );
			file_put_contents($save_path, $echo_str );
			
			file_put_contents( UPLOAD_PATH."../result.html", $echo_str );

			// export to XML
			if( !empty( array_values($arrExport) )){
		 		$xml_data = new SimpleXMLElement('<?xml version="1.0"?><table></table>');
		 		$this->array_to_xml( $arrExport, $xml_data);
				$save_path = UPLOAD_PATH."../xml/". str_replace( "csv", "xml", $file_name );
				$result = $xml_data->asXML( $save_path );
				$result = $xml_data->asXML( UPLOAD_PATH."../result.xml" );
			}
		}
	}

	private function array_to_xml( $data, &$xml_data ) 
	{
	    foreach( $data as $key => $value ) {
	        
	        if( is_object( $value ) )
	        	$key = 'item_'.$key;
	        
	        if( is_numeric( $key ) ){
	            $key = 'order_'.$key; //dealing with <0/>..<n/> issues
	        }

	        if( is_array($value) || is_object( $value )) {
	            $subnode = $xml_data->addChild( $key );
	            $this->array_to_xml($value, $subnode);
	        } else {
	            $xml_data->addChild("$key",htmlspecialchars("$value"));
	        }
	    }
	}
	
	private function getDateFormat( $str )
	{
		return date("Y-m-d", strtotime($str) );
	}
}