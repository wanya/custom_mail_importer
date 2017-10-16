<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Commercial extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');

		$this->load->model('invoices');
		$this->load->model('items');
		
		$this->load->helper(array('form', 'url'));
        $this->db = $this->load->database('default', true);
	}
	
	public function index()
    {
	    $field_per_row = 2;
        $this->load->view('upload_form', array('error' => ' ' ));
		
		$full_path = "/var/www/html/uploads/commercial.csv";
		
    }

    public function do_upload()
    {
        $config['upload_path']          = './uploads/';
        $config['allowed_types']        = 'csv';
        $config['max_size']             = 100;
        $config['max_width']            = 1024;
        $config['max_height']           = 768;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('userfile'))
        {
            $error = array('error' => $this->upload->display_errors());

            $this->load->view('upload_form', $error);
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());
            
            $full_path = $data['upload_data']['full_path'];

			$result = array();
			if(( $handle = fopen($full_path, "r"))!==FALSE ){
				while(($data = fgetcsv($handle, 1000, "\r"))!==FALSE ){
					$row++;
					$result[] = $data;
					
				}
				fclose($handle);
			}
			
			$arrFormated = array();
			foreach( $result as $key => $parag ){
				
				foreach( $parag as $row )
				{
					$len = sizeof( $arrFormated );
					if( sizeof( explode(",", $arrFormated[$len-1]) ) < $field_per_row && $len > 1 )
					{
						$arrFormated[$len-1] .= $row;
					}
					else
						$arrFormated[] = $row;
				}
			}
			
			foreach( $arrFormated as $row )
			{
				$values = explode(",", $row );
				$invoiceID = $values[0];
				$commercialNo = $values[1];
				
				echo $invoiceID. "---". $commercialNo."<br>\n";
		        $this->db->set('commercialNo', $commercialNo);
		        $this->db->where('invoiceID', $invoiceID);
		        $this->db->update('invoices');	
			}
	
            echo "Deleting upload file: $full_path";
            unlink( $full_path );
			
			$this->load->view('upload_success', array());
            // $this->load->view('upload_success', $data);
        }
    }
}

