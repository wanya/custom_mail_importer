<?php defined('BASEPATH') OR exit('No direct script access allowed');
/*
	description: mail parser controller
	author: wanyab87
*/
date_default_timezone_set("Canada/Atlantic");

require_once APPPATH. 'vendor/autoload.php';
error_reporting(E_ALL); ini_set('display_errors', 1); 

define( UPLOAD_PATH, APPPATH.'../uploads/mailattach/');
define( CREDENTIAL_PATH, APPPATH."../client_secret.json");
define( REFRESH_TOKEN, APPPATH."../refresh.dat");

define( SITE_URL, "http://ec2-52-32-1-31.us-west-2.compute.amazonaws.com");

class Scraper extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');

		$this->load->model('invoices');
		$this->load->model('items');
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
	/**
	 * Index page
	 */
	public function index()
	{
	  	$redirect_uri  = SITE_URL."/index.php";

	  	$client_id     = '298458107943-qq1sdp4r90jo8ucddjjrrgmkgtiop2n8.apps.googleusercontent.com';
	  	$client_secret = 'XHHQqsD0bvy8dSevrVLw_fvj';
	 
	  	$client = new Google_Client();
	  	$client->setApplicationName("Importing Invoices");
	  	$client->setClientId($client_id);
	 	$client->setClientSecret($client_secret);
	  	$client->setRedirectUri($redirect_uri);
	  	$client->setAccessType('offline');
	  	$client->setApprovalPrompt('force');
	  	
		// We only need permissions to compose and send emails
		$client->addScope("https://www.googleapis.com/auth/gmail.readonly");
		$client->setScopes(array(
		    'https://mail.google.com/',
		    'https://www.googleapis.com/auth/gmail.compose'
		));

		$service = new Google_Service_Gmail($client);

		// Redirect the URL after OAuth
		if (isset($_GET['code']) && $_GET['code'] != "") 
		{
			$res = $client->authenticate($_GET['code']);
			$this->writeAccessToken( $client->getAccessToken() );
			$redirect = $redirect_uri;
			header('Location: ' . filter_var($redirect));
			exit(0);
		}

		// If Access Toket is not set, show the OAuth URL
		$token = $this->readAccessToken();
		if ( ! empty( $token ) ) 
		{
			$client->setAccessToken( $token );

			if ($client->isAccessTokenExpired()) {
				echo json_encode(array("status"=>false, "message"=>"Access Token expired!!!"));

				// refresh Access Token
				if( $refresh_token = $this->readRefreshToken() )
				{
					$client->refreshToken( $refresh_token  );
					$access_token = $client->getAccessToken();
				}
			}
		} 
		else 
		{
			echo json_encode(array("status"=>false, "message"=>"Failed to get access token"));
			$authUrl = $client->createAuthUrl();
		}

		$access_token = $client->getAccessToken();

		if ( !empty($access_token) ) 
		{
		    $this->writeAccessToken( $access_token );
			$gmail = $service;
		    try 
			{
				$list = $gmail->users_messages->listUsersMessages('me',['maxResults' => 10, 'q' => $search]);
				$messageList = $list->getMessages();
				$inboxMessage = [];
				
				$got_last = false;
				foreach($messageList as $mlist){

					$optParamsGet2['format'] = 'full';

					// Get Message Info
					$single_message = $gmail->users_messages->get('me', $mlist->id, $optParamsGet2);
					$message_id = $mlist->id;
					
					// Get header
					$headers = $single_message->getPayload()->getHeaders();
					$snippet = $single_message->getSnippet();

					// Get mail infos
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
						else if( $single->getName() == 'Return-Path'){
							$message_sender .= $single->getValue();
						}
					}

					// get label and update
					$arrLabels = $single_message->getLabelIds();
					$unread = false;
					foreach($arrLabels as $label_index=>$label) {
					    if ($label=="UNREAD") {
					        $unread = true;
					    }
					}

					if (strpos(strtolower($message_subject), 'invoice') === false || $unread == false ) {
					    continue;
					}
					
					// Mark as read ( please check setScope : require compose permission )
					$mods = new Google_Service_Gmail_ModifyMessageRequest();
					$mods->setRemoveLabelIds(array("UNREAD"));
					$message = $gmail->users_messages->modify('me', $mlist->id, $mods);

					// get mail attachment
					$arrAttach = array(); $index = 0;
					$parts = $single_message->getPayload()->getParts();
					$has_invoice = false;
					foreach( $parts as $part )
					{
						$attachID = $part->getBody()->getAttachmentId();
						if( $attachID )
						{
							$file_name = $part->filename;
							$attach = $service->users_messages_attachments->get('me', $mlist->id, $attachID );
							$file_name = str_replace("PDF", "pdf", $file_name);

							$ext = pathinfo($file_name, PATHINFO_EXTENSION);
							if( $ext == "pdf" || $ext == "PDF" )
								$has_invoice = true;
							else
								continue;

							$fh = fopen( UPLOAD_PATH.$file_name, "w+");
							fwrite($fh, base64_decode(strtr($attach->data, array('-' => '+', '_' => '/'))));
							fclose($fh);
							
							$arrAttach[ $index ]['filename'] 	= $file_name;
							$arrAttach[ $index++ ]['url'] 		= SITE_URL ."/uploads/".$file_name;
						}
					}
					
					if( !$has_invoice )
						continue;

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
					foreach($inboxMessage as $msg)
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
				
				$this->parse(1);
			} 
			catch (Exception $e) 
			{
				$message = print_r($e->getMessage(),true);
				echo json_encode(array("status"=>false, "message"=> $message));
				unset($_SESSION['access_token']);
				$this->writeAccessToken("");
			}
		} 
		else
			header('Location: ' . filter_var($authUrl));
		session_destroy();
		// echo '<a href="'.$authUrl.'"><img src="google.png" title="Sign-in with Google" /></a>';
	}

	public function parse( $isDir = 0 )
	{

		$file_list = array();
		if( $isDir == 1 )
		{
			$files = scandir( UPLOAD_PATH );
			foreach( $files as $file )
			{
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				if( $ext == "pdf" || $ext == "PDF" )
					$file_list[] = $file;
			}
		}

		foreach( $file_list as $file_name )
		{
			if( strpos( $file_name, "soin" ) !== false )
			{
				$this->intcomex( $file_name );
				continue;
			}
		
			if( $file_name != "" )
				$file_path = UPLOAD_PATH.$file_name;
			else
				$file_path = UPLOAD_PATH.'001.pdf';
				
			$invoice_data = array();
			$item_data = array();
			// change pdftohtml bin location
			\Gufy\PdfToHtml\Config::set('pdftohtml.bin', '/usr/bin/pdftohtml');

			// change pdfinfo bin location
			\Gufy\PdfToHtml\Config::set('pdfinfo.bin', '/usr/bin/pdfinfo');

			$pdf = new Gufy\PdfToHtml\Pdf( $file_path );

			$html = $pdf->html(1);
			if( strpos( $html, "WWW.TECHDATA.COM" ) === false )
			{
				continue;
			}
			echo "<br><br>".$file_name;

			$total_pages = $pdf->getPages();
			$item_count = 0;
			for( $page_num = 1; $page_num <= $total_pages ; $page_num++ )
			{
				$html = $pdf->html( $page_num );
				$dom = new DOMDocument;
				$dom->loadHTML($html);
				$text = $dom->getElementsByTagName('p');

				$arrTemp = array(); $index = 0;
				foreach( $text as $t)
					$arrTemp[] = $t;

				// get invoice ID
				if( $page_num == 1 ){
					$invoice_data['invoiceID'] 		= $this->getNodeValue( $arrTemp[2] );
					$invoice_data['orderID'] 		= $this->getNodeValue( $arrTemp[25] );
					$invoice_data['date_shipped'] 	= $this->getDateFormat( $this->getNodeValue($arrTemp[28]));
					$invoice_data['customerRef'] 	= substr($this->getNodeValue( $arrTemp[39]), 0, strpos($this->getNodeValue( $arrTemp[39]), ' ')-4);
					$invoice_data['inv_date'] 		= $this->getDateFormat( $this->getNodeValue( $arrTemp[33] ));
					$invoice_data['add_date']		= date("Y-m-d h:i:s");
					$invoice_data['fromCom']		= "techdata";

					foreach( $arrTemp as $k => $data )
					{
						if( strpos( $this->getNodeValue( $data ), "SHIP TO" ) !== false )
							$invoice_data['orderID'] = $value = $this->getNodeValue( $arrTemp[ $k+1 ] );

						if( strpos( $this->getNodeValue( $data ), "DATE SHIPPED" ) !== false )
							$invoice_data['date_shipped'] = $value = $this->getDateFormat( $this->getNodeValue($arrTemp[ $k+2 ]));

						if( strpos( $this->getNodeValue( $data ), "CUSTOMER PO REFERENCE" ) !== false )
							$invoice_data['customerRef'] = $value = substr( $this->getNodeValue( $arrTemp[ $k+2 ]), 0, 8 );

						if( strpos( $this->getNodeValue( $data ), "INV.DATE" ) !== false )
							$invoice_data['inv_date'] = $value = $this->getDateFormat( $this->getNodeValue($arrTemp[ $k+3 ]));
					}
				}

				$item_start = 0; $item_end = 0;
				foreach( $arrTemp as $k => $data )
				{
					$value = $this->getFloatValue( $this->getNodeValue( $arrTemp[ $k+1 ] ) );

					if( strpos( $this->getNodeValue( $data ), "SUBTOTAL :" ) !== false )
					{
						$invoice_data['sub_total'] = $value;
						$item_end = $k - 1;
					}

					if( strpos( $this->getNodeValue( $data ), "FREIGHT :" ) !== false )
						$invoice_data['freight'] = $value;

					if( strpos( $this->getNodeValue( $data ), "NET AMOUNT :" ) !== false ){
						$invoice_data['netAmount'] = $value;
					}

					if( strpos( $this->getNodeValue( $data ), "HANDLING :" ) !== false ){
						$invoice_data['handle'] = $value;
					}
					
					if( strpos( $this->getNodeValue( $data ), "EXTENSION" ) !== false )
						$item_start = $k + 1;

					if( strpos( $this->getNodeValue( $data ), "PROSPECTIVE PURCHASE" ) !== false )
						$item_end = $k - 1;
					
					if( $end == 0 )
						$item_end = $k;
				}

				$counter = 0;
				for( $loop = $item_start; $loop <= $item_end; $loop++ )
				{
					$counter++;
					$item_data[$item_count]['add_date'] = date("Y-m-d h:i:s");
					$item_data[$item_count]['orderID'] 	= $invoice_data['orderID'];
					$item_data[$item_count]['invoiceID'] 	= $invoice_data['invoiceID'];

					$value = $this->getNodeValue( $arrTemp[$loop] );
					if( $counter == 1 && $item_data[$item_count]['qty'] == "" )
						$item_data[$item_count]['qty'] = $value;

					if( $counter == 2 && $item_data[$item_count]['art_number'] == "" )
						$item_data[$item_count]['art_number'] = $value;

					if( $counter == 3 && $item_data[$item_count]['MFR'] == "" )
						$item_data[$item_count]['MFR'] = substr( $value , 4);

					if( $counter == 4 && $item_data[$item_count]['art_name'] == "" )
						$item_data[$item_count]['art_name'] = $value;

					if( $counter == 5 && $item_data[$item_count]['unit_price'] == "" )
						$item_data[$item_count]['unit_price'] = $this->getFloatValue( $value );

					if( $counter == 6 && $item_data[$item_count]['extension'] == "" )
						$item_data[$item_count]['extension'] = $this->getFloatValue( $value );

					if( strpos( $value , "UPC" ) !== false )
						$item_data[$item_count]['UPC'] = substr( $value , 4);

					if( strpos( $value , "MFR" ) !== false )
						$item_data[$item_count]['MFR'] = substr( $value , 4);

					if( strpos( $value , "CONTAINER" ) !== false ){
						if( $item_data[$item_count]['container'] == "" )
							$item_data[$item_count]['container'] = substr( $value , 12);
						else
							$item_data[$item_count]['container'] .=", ".substr( $value , 12);

						if( strpos( $this->getNodeValue( $arrTemp[$loop+3] ) , "CONTAINER" ) === false )
						{
							$counter = -1;
							$item_count++;
						}
					}
				}
			}

			$ret = array_pop( $item_data );
			$insert_id = $this->invoices->insertInvoice( $invoice_data );

			$echo_str = '<table width="90%" border="1"><tr><td colspan=2>InvoiceID: '.$invoice_data['invoiceID'].'</td></tr>
					<tr>
						<td>OrderID: '.$invoice_data['orderID'].'</td>
						<td>Date Shipped: '.$invoice_data['date_shipped'].'</td>
					</tr>
					<tr>
						<td>Customer Reference: '.$invoice_data['customerRef'].'</td>
						<td>Invoice Date: '.$invoice_data['inv_date'].'</td>'.
					'</tr>
				</table><br>
				<table border="1" width="90%">
					<tr>
						<th>Qty</th>
						<th>Art_Number</th>
						<th>Vendor</th>
						<th>ARTICLE</th>
						<th>UNIT PRICE</th>
						<th>EXTENSION</th>
					</tr>';

					foreach( $item_data as $item )
					{
						if( $insert_id > 0 )
							$this->items->insertItem( $item );

						$echo_str.= '<tr>
							<td>'.$item['qty'].'</td>
							<td>'.$item['art_number'].'</td> 
							<td>'.
							'MFR: '.$item['MFR'].'<br>'.
							'UPC: '.$item['UPC'].'<br>'.
							'CONTAINER: '.$item['container'].'<br>'.
							'</td>
							<td>'.$item['art_name'].'</td>
							<td>'.$item['unit_price'].'</td>
							<td>'.$item['extension'].'</td>
						</tr>';
					}
					$echo_str .= '<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td>'.
							'SUBTOTAL: '.$invoice_data['sub_total'].'<br>'.
							'HANDLE: '.$invoice_data['handle'].'<br>'.
							'FREIGHT: '.$invoice_data['freight'].'<br>'.
							'NET AMOUNT : '.$invoice_data['netAmount'].'<br>'.
							'</td>
						</tr>';
			$echo_str .= '</table>';
			echo $echo_str;
			rename( $file_path, UPLOAD_PATH."../".$file_name );
			$save_path = UPLOAD_PATH."../html/". str_replace( "pdf", "html", $file_name );
			file_put_contents($save_path, $echo_str );
			
			file_put_contents( UPLOAD_PATH."../result.html", $echo_str );

			// export to XML
			if( !empty( $invoice_data )){
		 		$xml_data = new SimpleXMLElement('<?xml version="1.0"?><table></table>');
		 		$invoice_data['items'] = $item_data;
		 		$this->array_to_xml($invoice_data, $xml_data);
				$save_path = UPLOAD_PATH."../xml/". str_replace( "pdf", "xml", $file_name );
				$result = $xml_data->asXML( $save_path );
				
				$result = $xml_data->asXML( UPLOAD_PATH."../result.xml" );
			}
		}
	}
	function array_to_xml( $data, &$xml_data ) {
	    foreach( $data as $key => $value ) {
	        if( is_numeric($key) ){
	            $key = 'item'.$key; //dealing with <0/>..<n/> issues
	        }
	        if( is_array($value) ) {
	            $subnode = $xml_data->addChild($key);
	            $this->array_to_xml($value, $subnode);
	        } else {
	            $xml_data->addChild("$key",htmlspecialchars("$value"));
	        }
	     }
	}
	
	public function html() // test 
	{
/*
		// change pdftohtml bin location
		\Gufy\PdfToHtml\Config::set('pdftohtml.bin', '/usr/bin/pdftohtml');

		// change pdfinfo bin location
		\Gufy\PdfToHtml\Config::set('pdfinfo.bin', '/usr/bin/pdfinfo');

		$pdf = new Gufy\PdfToHtml\Pdf( UPLOAD_PATH.'001.pdf');

		$total_pages = $pdf->getPages();

		for( $page_num = 1; $page_num <= $total_pages ; $page_num++ )
			echo $html = $pdf->html( $page_num );
*/
		$veryPDF_Key = "63B98A5CBD207A489278420A1E7FAE37C9D638BB";
		$file_name = "Invoice,8020226661,38021869,FA010317.pdf";
		
		$file_alias = SITE_URL ."/uploads/".$file_name;
		$url = 'http://online.verypdf.com/api/?apikey='.$veryPDF_Key.'&app=ocr&infile='.$file_alias.'&outfile=out&lang=eng&format';

		echo "<br><br>".$file_name."<br>";
		
		$ch = curl_init();
	    $timeout = 5;
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    
		$html = "";
		$arr = explode( "\n", $data );

		$page_count = explode( "=", $arr['3'] );
		$page_count = $page_count[1];
		foreach( $arr as $key => $value )
		{
			if( $key < 4 )
				continue;

			$temp = explode( " ", $value );
			$txt_url = substr( $temp[1], 0, strlen( $temp[1] ) - 4 ); // rm <br>
			
			$html .= file_get_contents( $txt_url );
			// error_log( $txt_url."\n", 3, APPPATH."../log.txt");
		}
		
		$invoice_data = array();
		$dom = new DOMDocument;
		$dom->loadHTML($html);

		$invoice_data = array();
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		$text = $dom->getElementsByTagName('span');		
		$arrTemp = array(); $index = 0;
		
		foreach( $text as $t)
			$arrTemp[] = $t;
		
		$counter = 0; $item_count = 0;
		foreach( $arrTemp as $k => $data )
		{
			print_r( $data );
		}
	}

	private function getNodeValue( $node )
	{
		if( substr( $node->nodeValue, 0, 2 ) == 'Â')
			return trim( substr( $node->nodeValue, 4 ));

		return $node->nodeValue;
	}

	private function getDateFormat( $str )
	{
		return date("Y-m-d", strtotime($str) );
	}
	public function intcomex( $file_name )
	{
		$veryPDF_Key = "63B98A5CBD207A489278420A1E7FAE37C9D638BB";

		$file_alias = SITE_URL ."/uploads/mailattach/".$file_name;
		$url = 'http://online.verypdf.com/api/?apikey='.$veryPDF_Key.'&app=pdftools&infile='.$file_alias.'&outfile=verypdf.jpg&-r=300';

		echo "<br><br>".$file_name."<br>";
		
		$ch = curl_init();
	    $timeout = 5;
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    
		$html = "";
		$arr = explode( "[Output]", $data );

		$page_count = count( $arr ) - 1;

		foreach( $arr as $key => $value )
		{
			if( $key < 1 )
				continue;
			$value = substr( $value, 0, strlen($value)-4);
			// $url = 'http://online.verypdf.com/api/?apikey='.$veryPDF_Key.'&app=ocr&infile='.$value.'&outfile=out&lang=eng&format';
			$url = 'http://online.verypdf.com/api/?apikey='.$veryPDF_Key.'&app=ocr&infile='.trim($value).'&outfile=out&lang=eng&format';
			$ch1 = curl_init();
		    curl_setopt($ch1, CURLOPT_URL, $url);
		    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, $timeout);
		    $data = curl_exec($ch1);
		    curl_close($ch1);
		    
			$arr1 = explode( "[Output]", $data );

			$html .= file_get_contents( trim( substr( $arr1[1], 0, strlen($arr1[1])-4) ) );
		}
		// echo $html; exit;

		$invoice_data = array();
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		$text = $dom->getElementsByTagName('span');		
		$arrTemp = array(); $index = 0;
		
		foreach( $text as $t)
			$arrTemp[] = $t;
		
		$counter = 0; $item_count = 0;

		$invoice_data['add_date']	= date("Y-m-d h:i:s");
		$invoice_data['inv_date']	= date("Y-m-d h:i:s");

		foreach( $arrTemp as $k => $data )
		{
			if( strcmp( $this->getNodeValue( $data ), "Invoice:" ) == false || strcmp( $this->getNodeValue( $data ), "Invoice." ) == false )
				$invoice_data['invoiceID'] = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "#:" ) == false || strcmp( $this->getNodeValue( $data ), "#2" ) == false)
				$invoice_data['orderID'] = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "Date:" ) == false || strcmp( $this->getNodeValue( $data ), "Date;" ) == false)
				$invoice_data['inv_date'] = date("Y-m-d h:i:s", strtotime( $this->getNodeValue( $arrTemp[ $k+1 ] ))); //$this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "Customer:" ) == false || strcmp( $this->getNodeValue( $data ), "Customer;" ) == false)
				$invoice_data['customerRef'] = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "Fee:" ) == false || strcmp( $this->getNodeValue( $data ), "Fee;" ) == false)
				$invoice_data['handle'] = $this->getFloatValue( $this->getNodeValue( $arrTemp[ $k+1 ] ) );

			if( strcmp( $this->getNodeValue( $data ), "Balance;" ) == false || strcmp( $this->getNodeValue( $data ), "Balance:" ) == false )
				$invoice_data['netAmount'] = $this->getFloatValue( $this->getNodeValue( $arrTemp[ $k+1 ] ) );


			// order items
			if( strcmp( $this->getNodeValue( $data ), "Amt" ) == false ){
				$counter = -1;
				$item_count++;
			}

			// echo $k."--".$counter."--". $this->getNodeValue( $data )."<br>";
			$item_data[$item_count]['add_date'] = date("Y-m-d h:i:s");
			$item_data[$item_count]['orderID'] 	= $invoice_data['orderID'];
			$item_data[$item_count]['invoiceID'] 	= $invoice_data['invoiceID'];

			if( $counter == 1 )
				$item_data[$item_count]['art_number'] = $this->getNodeValue( $data );
			if( $counter == 2 )
				$item_data[$item_count]['qty'] = $this->getFloatValue( $this->getNodeValue( $data ) );
			if( $counter == 5 )
				$item_data[$item_count]['unit_price'] = $this->getFloatValue( $this->getNodeValue( $data ) );
			if( $counter == 6 )
				$item_data[$item_count]['extension'] = $this->getFloatValue( $this->getNodeValue( $data ) );
			if( $counter == 7 )
				$item_data[$item_count]['art_name'] = $this->getNodeValue( $data );
			
			if( strcmp( $this->getNodeValue( $data ), "MPN:" ) == false || strcmp( $this->getNodeValue( $data ), "MPN;" ) == false){
				$item_data[$item_count]['UPC'] = $this->getNodeValue( $arrTemp[ $k+1 ] );
				$item_count++; 
			}

			if( strlen( $this->getNodeValue( $arrTemp[ $k+2 ] )) == 10 && strpos( $this->getNodeValue( $arrTemp[ $k+1 ]) , $this->getNodeValue( $arrTemp[ $k+2 ]) ) !== false && $item_data[$item_count-1]['UPC'] != "" ){
				$counter = -1;
			}
			$counter++;
		}
		// $invoice_data['sub_total'] 	= $invoice_data['netAmount'] - $invoice_data['handle'];
		$invoice_data['fromCom']	= "intcomex";
		$invoice_data['date_shipped']	= $invoice_data['inv_date'];

		$insert_id = $this->invoices->insertInvoice( $invoice_data );
		$echo_str = '<table width="90%" border="1">
					<tr>
						<td>InvoiceID: '.$invoice_data['invoiceID'].'</td>
						<td>OrderID: '.$invoice_data['orderID'].'</td>
					</tr>
					<tr>
						<td>Order Date: '.$invoice_data['inv_date'].'</td>.
						<td colspan=2>Customer Reference: '.$invoice_data['customerRef'].'</td>
					</tr>
				</table><br>
				<table border="1" width="90%">
					<tr>
						<th>No</th>
						<th>Qty</th>
						<th>Art_Number</th>
						<th>Vendor</th>
						<th>ARTICLE</th>
						<th>UNIT PRICE</th>
						<th>EXTENSION</th>
					</tr>';

					$index = 0;
					$invoice_data['sub_total'] = 0;
					foreach( $item_data as $k=>$item )
					{
						if( $item['qty'] == 0 || $item['UPC'] == "" )
						{
							unset( $item_data[$k]);
							continue;
						}
						
						$item['invoiceID']  = $invoice_data['invoiceID'];
						if( $insert_id > 0 ){
							$this->items->insertItem( $item );
						}

						$echo_str.= '<tr>
							<td>'.++$index.'</td>
							<td>'.$item['qty'].'</td>
							<td>'.$item['art_number'].'</td> 
							<td>'.
							'MPN: '.$item['UPC'].'<br>'.
							'</td>
							<td>'.$item['art_name'].'</td>
							<td>'.$item['unit_price'].'</td>
							<td>'.$item['extension'].'</td>
						</tr>';
						
						$invoice_data['sub_total'] += $item['extension'];
					}
					$invoice_data['netAmount'] = $invoice_data['sub_total'] + $invoice_data['handle'];

					$echo_str .= '<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td>'.
							'SUBTOTAL: '.$invoice_data['sub_total'].'<br>'.
							'FEE: '.$invoice_data['handle'].'<br>'.
							'NET AMOUNT : '.$invoice_data['netAmount'].'<br>'.
							'</td>
						</tr>';
		
		$this->invoices->updateInvoiceAmount($invoice_data['invoiceID'], $invoice_data['netAmount'], $invoice_data['sub_total'] );
		
		$echo_str .= '</table>';
		echo $echo_str;
		
		$file_path = UPLOAD_PATH.$file_name;
		rename( $file_path, UPLOAD_PATH."../".$file_name );
		$save_path = UPLOAD_PATH."../html/". str_replace( "pdf", "html", $file_name );
		file_put_contents($save_path, $echo_str );
		
		file_put_contents( UPLOAD_PATH."../result.html", $echo_str );
		// export to XML
		if( !empty( $invoice_data )){
	 		$xml_data = new SimpleXMLElement('<?xml version="1.0"?><table></table>');
	 		$invoice_data['items'] = $item_data;
	 		$this->array_to_xml($invoice_data, $xml_data);
			$save_path = UPLOAD_PATH."../xml/". str_replace( "pdf", "xml", $file_name );
			$result = $xml_data->asXML( $save_path );
			$result = $xml_data->asXML( UPLOAD_PATH."../result.xml" );
		}
	}

	public function intcomex_old( $file_name )
	{
		$veryPDF_Key = "63B98A5CBD207A489278420A1E7FAE37C9D638BB";

		$file_alias = SITE_URL ."/uploads/mailattach/".$file_name;
		$url = 'http://online.verypdf.com/api/?apikey='.$veryPDF_Key.'&app=ocr&infile='.$file_alias.'&outfile=out&lang=eng&format';

		echo "<br><br>".$file_name."<br>";
		
		$ch = curl_init();
	    $timeout = 5;
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    $data = curl_exec($ch);
	    curl_close($ch);
	    
		$html = "";
		$arr = explode( "\n", $data );

		$page_count = explode( "=", $arr['3'] );
		$page_count = $page_count[1];

		foreach( $arr as $key => $value )
		{
			if( $key < 4 )
				continue;

			$temp = explode( " ", $value );
			$txt_url = substr( $temp[1], 0, strlen( $temp[1] ) - 4 ); // rm <br>
			
			$html .= file_get_contents( $txt_url );			
			// error_log( $txt_url."\n", 3, APPPATH."../log.txt");
		}
		
		$invoice_data = array();
		$dom = new DOMDocument;
		$dom->loadHTML($html);
		$text = $dom->getElementsByTagName('span');		
		$arrTemp = array(); $index = 0;
		
		foreach( $text as $t)
			$arrTemp[] = $t;
		
		$counter = 0; $item_count = 0;
		foreach( $arrTemp as $k => $data )
		{
			if( strcmp( $this->getNodeValue( $data ), "Invoice:" ) == false )
				$invoice_data['invoiceID'] = $value = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "#:" ) == false )
				$invoice_data['orderID'] = $value = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "Date:" ) == false )
				$invoice_data['inv_date'] = $value = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "Customer:" ) == false )
				$invoice_data['customerRef'] = $value = $this->getNodeValue( $arrTemp[ $k+1 ] );

			if( strcmp( $this->getNodeValue( $data ), "Fee:" ) == false )
				$invoice_data['handle'] = $this->getFloatValue( $this->getNodeValue( $arrTemp[ $k+1 ] ) );

			if( strcmp( $this->getNodeValue( $data ), "Balance:" ) == false )
				$invoice_data['netAmount'] = $this->getFloatValue( $this->getNodeValue( $arrTemp[ $k+1 ] ) );

			$invoice_data['sub_total'] 	= $invoice_data['netAmount'] - $invoice_data['handle'];
			$invoice_data['add_date']	= date("Y-m-d h:i:s");
			$invoice_data['inv_date']	= date("Y-m-d h:i:s");
			$invoice_data['fromCom']	= "intcomex";

			// order items
			if( strcmp( $this->getNodeValue( $data ), "Amt" ) == false ){
				$counter = -1;
				$item_count++;
			}

			// echo $k."--".$counter."--". $this->getNodeValue( $data )."<br>";
			$item_data[$item_count]['add_date'] = date("Y-m-d h:i:s");
			$item_data[$item_count]['orderID'] 	= $invoice_data['orderID'];
			$item_data[$item_count]['invoiceID'] 	= $invoice_data['invoiceID'];

			if( $counter == 1 )
				$item_data[$item_count]['art_number'] = $this->getNodeValue( $data );
			if( $counter == 2 )
				$item_data[$item_count]['qty'] = $this->getFloatValue( $this->getNodeValue( $data ) );
			if( $counter == 5 )
				$item_data[$item_count]['unit_price'] = $this->getFloatValue( $this->getNodeValue( $data ) );
			if( $counter == 6 )
				$item_data[$item_count]['extension'] = $this->getFloatValue( $this->getNodeValue( $data ) );
			if( $counter == 7 )
				$item_data[$item_count]['art_name'] = $this->getNodeValue( $data );
			
			if( strcmp( $this->getNodeValue( $data ), "MPN:" ) == false ){
				$item_data[$item_count]['UPC'] = $this->getNodeValue( $arrTemp[ $k+1 ] );
				$item_count++; 
			}

			if( strlen( $this->getNodeValue( $arrTemp[ $k+2 ] )) == 10 && strpos( $this->getNodeValue( $arrTemp[ $k+1 ]) , $this->getNodeValue( $arrTemp[ $k+2 ]) ) !== false && $item_data[$item_count-1]['UPC'] != "" ){
				$counter = -1;
			}
			$counter++;
		}
		
		$insert_id = $this->invoices->insertInvoice( $invoice_data );

		$echo_str = '<table width="90%" border="1">
					<tr>
						<td>InvoiceID: '.$invoice_data['invoiceID'].'</td>
						<td>OrderID: '.$invoice_data['orderID'].'</td>
					</tr>
					<tr>
						<td>Order Date: '.$invoice_data['inv_date'].'</td>.
						<td colspan=2>Customer Reference: '.$invoice_data['customerRef'].'</td>
					</tr>
				</table><br>
				<table border="1" width="90%">
					<tr>
						<th>No</th>
						<th>Qty</th>
						<th>Art_Number</th>
						<th>Vendor</th>
						<th>ARTICLE</th>
						<th>UNIT PRICE</th>
						<th>EXTENSION</th>
					</tr>';

					$index = 0;
					foreach( $item_data as $k=>$item )
					{
						if( $item['qty'] == 0 || $item['UPC'] == "" )
						{
							unset( $item_data[$k]);
							continue;
						}
						
						$item['invoiceID']  = $invoice_data['invoiceID'];
						if( $insert_id > 0 ){
							$this->items->insertItem( $item );
						}

						$echo_str.= '<tr>
							<td>'.++$index.'</td>
							<td>'.$item['qty'].'</td>
							<td>'.$item['art_number'].'</td> 
							<td>'.
							'MPN: '.$item['UPC'].'<br>'.
							'</td>
							<td>'.$item['art_name'].'</td>
							<td>'.$item['unit_price'].'</td>
							<td>'.$item['extension'].'</td>
						</tr>';
					}
					$echo_str .= '<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td>'.
							'SUBTOTAL: '.$invoice_data['sub_total'].'<br>'.
							'FEE: '.$invoice_data['handle'].'<br>'.
							'NET AMOUNT : '.$invoice_data['netAmount'].'<br>'.
							'</td>
						</tr>';
		$echo_str .= '</table>';
		echo $echo_str;
		
		$file_path = UPLOAD_PATH.$file_name;
		rename( $file_path, UPLOAD_PATH."../".$file_name );
		$save_path = UPLOAD_PATH."../html/". str_replace( "pdf", "html", $file_name );
		file_put_contents($save_path, $echo_str );
		
		file_put_contents( UPLOAD_PATH."../result.html", $echo_str );
		// export to XML
		if( !empty( $invoice_data )){
	 		$xml_data = new SimpleXMLElement('<?xml version="1.0"?><table></table>');
	 		$invoice_data['items'] = $item_data;
	 		$this->array_to_xml($invoice_data, $xml_data);
			$save_path = UPLOAD_PATH."../xml/". str_replace( "pdf", "xml", $file_name );
			$result = $xml_data->asXML( $save_path );
			$result = $xml_data->asXML( UPLOAD_PATH."../result.xml" );
		}
	}
	
	private function getFloatValue( $val )
	{
		$point = substr( $val, strlen( $val )-3, 1 );
		if( $point == "_")
			$val = str_replace("_", ".", $val );
		else
			$val = str_replace("_", ",", $val );

		if( $point == ",")
		{
			$temp = str_replace( ",", "-", $val );
			$val = str_replace(".", ",", $temp );
			$val = str_replace("-", ".", $val );
		}
		
		return floatval(preg_replace("/[^-0-9\.]/","", $val ));
	}
}
